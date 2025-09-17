<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use Carbon\Carbon;
use App\Models\Plan;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Flutterwave\Flutterwave;
use Illuminate\Http\Request;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class TransactionsController extends Controller
{
    protected $paystackSecretKey;
    protected $flutterwaveSecretKey;
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paystackSecretKey = env('PAYSTACK_SECRET_KEY');
        $this->flutterwaveSecretKey = env('FLUTTERWAVE_SECRET_KEY');
        $this->paymentService = $paymentService;
    }


    public function initialize(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'plan_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
            'payment_method' => 'required|in:paystack,flutterwave,stripe,paypal,bank_transfer,crypto,wallet',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        if ($plan->price != $validated['amount']) {
            return response()->json([
                'error' => 'Plan price mismatch',
            ], 400);
        }

        // Create transaction record
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'payment_method' => $validated['payment_method'],
            'status' => 'pending',
            'payment_gateway_ref' => $this->generateReference($validated['payment_method']),
        ]);
        //dd($transaction);

        // Initialize payment based on method
        try {
            $paymentData = $this->paymentService->initializePayment(
                $validated['payment_method'],
                $user,
                $plan,
                $transaction
            );

            return response()->json($paymentData);
        } catch (\Exception $e) {
            $transaction->update(['status' => 'failed']);
            
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function generateReference($gateway)
    {
        $prefix = strtoupper(substr($gateway, 0, 3));
        return $prefix . '_' . Str::random(10) . '_' . time();
    }

    public function verifyPaysatckPayment(Request $request, $reference = null)
    {
        // Get reference from URL parameter or query parameter
        $reference = $reference ?? $request->query('reference') ?? $request->query('trxref');

        
        
        if (!$reference) {
            return response()->json(['error' => 'Payment reference is required'], 400);
        }

        try {
            // Check if transaction already processed
            if (Transaction::where('payment_gateway_ref', $reference)->where('status', 'success')->exists()) {
                // If already processed, still return success for receipt display
                $existingTransaction = Transaction::where('payment_gateway_ref', $reference)
                    ->where('status', 'success')
                    ->with(['user', 'plan'])
                    ->first();
                    
                return $this->formatSuccessResponse($existingTransaction);
            }

            // Verify payment with Paystack
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                'Content-Type' => 'application/json',
            ])->get('https://api.paystack.co/transaction/verify/' . $reference);

            $responseData = $response->json();

            if (!$response->successful() || !$responseData['status']) {
                throw new Exception("Failed to verify payment: " . ($responseData['message'] ?? 'Unknown error'));
            }

            DB::beginTransaction();

            $transaction = Transaction::where('payment_gateway_ref', $reference)
                ->where('status', 'pending')
                ->with(['user', 'plan'])
                ->first();

            if (!$transaction) {
                throw new Exception("Transaction not found or already processed");
            }

            // Update transaction
            $transaction->update([
                'status' => 'success',
                'payment_gateway_ref' => $responseData['data']['reference'],
                'currency' => $responseData['data']['currency'],
                'auth_token' => $responseData['data']['authorization']['authorization_code'] ?? null,
                'channel' => $responseData['data']['channel'] ?? null,
                'customer_email' => $responseData['data']['customer']['email'] ?? null,
                'ip_address' => $responseData['data']['ip_address'] ?? null,
                'device' => $responseData['data']['user_agent'] ?? null,
                'location' => $responseData['data']['log']['geolocation'] ?? null,
                'paid_at' => isset($responseData['data']['paid_at'])
                ? Carbon::parse($responseData['data']['paid_at'])
                : now(),
            ]);

            // Update subscription
            $user = $transaction->user;
            $branch = $user->branch;

            if (!$branch) {
                throw new \Exception('User is not associated with any branch');
            }

            $plan = $transaction->plan;
            $currentSubscription = $branch->activeSubscription();
            $startDate = now();
            $endDate = $currentSubscription && $currentSubscription->ends_at > now()
                ? $currentSubscription->ends_at
                : $startDate;

            $endDate = Carbon::parse($endDate);
            $endDate = $plan->interval === 'yearly'
                ? $endDate->copy()->addYear()
                : $endDate->copy()->addMonth();

            // Cancel existing active subscriptions
            $branch->subscriptions()->isActive()->update([
                'status' => 'canceled',
                'canceled_at' => now()
            ]);

            // Create new subscription
            $subscription = $branch->subscriptions()->create([
                'plan_id' => $plan->id,
                'starts_at' => $startDate,
                'ends_at' => $endDate,
                'status' => 'active'
            ]);

            // Update branch
            $branch->update([
                'isSubscribed' => true,
                'subscription_end_date' => $endDate,
                'subscription_start_date' => $startDate,
                'subscription_type' => $plan->slug,
                'subscription_count' => $branch->subscription_count + 1
            ]);

            DB::commit();

            // Return JSON response for AJAX calls
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return $this->formatSuccessResponse($transaction);
            }

            // Redirect to receipt page for direct browser access
            return redirect()->to(config('app.frontend_url') . '/receipt.html?reference=' . $reference);

        } catch (Exception $e) {
            DB::rollback();
            
            // Mark transaction as failed
            Transaction::where('payment_gateway_ref', $reference)->update(['status' => 'failed']);
            
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 400);
            }

            // Redirect to error page for browser access
            //dd($e);
            return redirect()->to(config('app.frontend_url') . '/payment-error.html?error=' . urlencode($e->getMessage()));
        }
    }

    // Add route for API verification
    public function verifyPaymentAPI($reference)
    {
        return $this->verifyPaysatckPayment(request(), $reference);
    }

    //flutterwave integrt=ation
    public function verifyFlutterwavePayment(Request $request)
    {
        $status = $request->query('status');
        $txid = $request->query('transaction_id');
        $txRef = $request->query('tx_ref');

         \Log::debug('Dump payload data:', [
            'status' => $status ?? null,
            'txid' => $txid ?? null,
            'txRef' => $txRef ?? null,
        ]);

        
        //dd(env('FLUTTERWAVE_SECRET_KEY'));

        // Check if we have the necessary parameters
        if (!$txid || !$txRef) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Missing transaction parameters'
            ], 400);
        }

        // Only verify if status is successful or we need to check anyway
        if ($status === 'successful' || $status === 'completed') {
            try {
                $secretKey = env('FLUTTERWAVE_SECRET_KEY');
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Content-Type' => 'application/json'
                ])->get("https://api.flutterwave.com/v3/transactions/{$txid}/verify");

                if ($response->failed()) {
                    throw new \Exception('Unable to verify transaction: HTTP error');
                }

                $responseData = $response->json();

                // Check Flutterwave API response status
                if ($responseData['status'] !== 'success') {
                    throw new \Exception('Transaction verification failed: ' . ($responseData['message'] ?? 'Unknown error'));
                }

                $transactionData = $responseData['data'];
                $reference = $transactionData['tx_ref'];

                // Verify transaction status from Flutterwave
                if ($transactionData['status'] !== 'successful') {
                    throw new \Exception('Transaction was not successful: ' . $transactionData['status']);
                }

                DB::beginTransaction();

                $transaction = Transaction::where('payment_gateway_ref', $reference)
                    ->where('status', 'pending')
                    ->with(['user', 'plan'])
                    ->first();

                if (!$transaction) {
                    throw new \Exception("Transaction not found or already processed");
                }

                // Update transaction
                $transaction->update([
                    'status' => 'success',
                    'payment_gateway_ref' => $transactionData['tx_ref'],
                    'description' => $transactionData['id'], // Store Flutterwave transaction ID
                    'amount' => $transactionData['amount'],
                    'currency' => $transactionData['currency'],
                    'auth_token' => $transactionData['card']['token'] ?? null,
                    'channel' => $transactionData['channel'] ?? null,
                    'customer_email' => $transactionData['customer']['email'] ?? null,
                    'ip_address' => $transactionData['ip'] ?? null,
                    'device' => $transactionData['device_fingerprint'] ?? null,
                    'paid_at' => isset($transactionData['created_at'])
                        ? Carbon::parse($transactionData['created_at'])
                        : now(),
                    'meta' => json_encode($transactionData) // Store complete response for debugging
                ]);

                $user = $transaction->user;
                $branch = $user->branch;
                if (!$branch) {
                    throw new \Exception('User is not associated with any branch');
                }

                $plan = $transaction->plan;
                $currentSubscription = $branch->activeSubscription();

                $startDate = now();
                $endDate = $currentSubscription && $currentSubscription->ends_at > now()
                    ? $currentSubscription->ends_at
                    : $startDate;

                $endDate = $plan->interval === 'yearly'
                    ? Carbon::parse($endDate)->addYear()
                    : Carbon::parse($endDate)->addMonth();

                // Cancel existing active subscriptions
                $branch->subscriptions()->isActive()->update([
                    'status' => 'canceled',
                    'canceled_at' => now()
                ]);

                // Create new subscription
                $subscription = $branch->subscriptions()->create([
                    'plan_id' => $plan->id,
                    'starts_at' => $startDate,
                    'ends_at' => $endDate,
                    'status' => 'active',
                ]);

                // Update branch subscription info
                $branch->update([
                    'isSubscribed' => true,
                    'subscription_end_date' => $endDate,
                    'subscription_start_date' => $startDate,
                    'subscription_type' => $plan->slug,
                    'subscription_count' => $branch->subscription_count + 1
                ]);
//dd($transaction);
                DB::commit();

                // Return formatted response
                if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                    return $this->formatSuccessResponse($transaction, 'flutterwave');
                }

                return redirect()->to(config('app.frontend_url') . '/receipt.html?reference=' . $reference . '&type=flutterwave');

            } catch (\Exception $e) {
                DB::rollBack();

                // Update transaction status to failed
                if (isset($reference)) {
                    Transaction::where('payment_gateway_ref', $reference)->update(['status' => 'failed']);
                }

                \Log::error('Flutterwave callback error: ' . $e->getMessage(), [
                    'transaction_id' => $txid,
                    'tx_ref' => $txRef,
                    'status' => $status
                ]);

                if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                    return response()->json([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ], 400);
                }
                dd($e);
                return redirect()->to(config('app.frontend_url') . '/payment-error.html?error=' . urlencode($e->getMessage()));
            }
        }

        // Handle cancelled or failed payments
        if ($status === 'cancelled') {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'cancelled',
                    'message' => 'Payment was cancelled by user'
                ], 200);
            }
            return redirect()->to(config('app.frontend_url') . '/payment-cancelled.html');
        }

        // Handle failed payments
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Payment failed or was not successful'
            ], 400);
        }

        return redirect()->to(config('app.frontend_url') . '/payment-error.html?error=Payment failed');
    }


     // Helper method to format success response
    private function formatSuccessResponse($transaction)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Payment verified successfully',
            'payment' => [
                'reference' => $transaction->payment_gateway_ref,
                'email' => $transaction->customer_email ?? $transaction->user->email,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency ?? 'NGN',
                'gateway' => 'Paystack',
                'date' => $transaction->paid_at ?? $transaction->updated_at,
                'plan' => $transaction->plan->name ?? null,
                'status' => $transaction->status
            ]
        ], 200);
    }


    private function generateUniqueFlutterwaveReference()
    {
        do {
            $reference = 'FLW-' . strtoupper(Str::random(10));
        } while (Transaction::where('payment_gateway_ref', $reference)->exists());

        return $reference;
    }

}