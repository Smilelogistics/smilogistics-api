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

    // Add route for API verification
    public function verifyPaymentAPI($reference)
    {
        return $this->verifyPaysatckPayment(request(), $reference);
    }

    //flutterwave integrt=ation

    public function initializePaymentFlutterwave(Request $request)
    {
        $user = auth()->user();
        //dd($user);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'plan_name' => 'required|string|max:255',
        ]);

        //$reference = Flutterwave::generateReference();
        
        $reference = $this->generateUniqueFlutterwaveReference();

        //dd($reference);

        $plans = Plan::where('name', $request->plan_name)->first();

            if (!$plans) {
                throw new Exception("Plan not found.");
            }
            elseif($plans->price != $request->amount){
                throw new Exception("Plan price mismatch.");
            }
            else{

            if(Transaction::where('payment_gateway_ref', $reference)->where('status', 'success')->exists()){
                throw new Exception("Transaction already processed.");
            }
            else{
              
                $data = [
                    'amount' => $validated['amount'],
                    'tx_ref' => $reference,
                    'currency' => 'NGN',
                    'redirect_url' => config('app.url') . '/api/v1/callback-flutterwave',
                    'customer' => [
                        'email' => $user->email,
                        'name' => $user->fname . ' ' . $user->lname,
                    ],
                    'customizations' => [
                        'title' => 'Smile Logistics Subscription',
                        'description' => 'Payment via API',
                    ],
                ];

                //dd(config('services.flutterwave.secret_key'));

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . 'FLWSECK_TEST-2e33c4606c3455d03d077daea372872d-X',
                    'Content-Type' => 'application/json',
                ])->post('https://api.flutterwave.com/v3/payments', $data);
                
            //dd($response->status(), $response->body(), config('services.flutterwave.secret_key'));

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'amount' => $validated['amount'],
                'customer_email' => $user->email,
                'payment_gateway_ref' => $reference,
                'payment_method' => 'flutterwave',
                'currency' => 'NGN',
                'status' => 'pending',
            ]);
    

                if ($response['status'] != 'success') {
                    return response()->json([
                        'message' => 'Failed to initialize payment',
                        'error' => $response,
                    ], 500);
                }

                return response()->json([
                    'message' => 'Payment initialized',
                    'payment_link' => $response['data']['link'],
                    'reference' => $response,
                ]);
            }
        }

    }


    public function callbackFlutterwave(Request $request)
{
    $status = $request->query('status');
    $txid = $request->query('transaction_id');

    if ($status === 'successful' && $txid) {
        $secretKey = env('FLUTTERWAVE_SECRET_KEY');
        $response = Http::withToken($secretKey)
            ->get("https://api.flutterwave.com/v3/transactions/{$txid}/verify");

        if ($response->failed()) {
            return response()->json(['status' => 'error', 'message' => 'Unable to verify transaction.'], 500);
        }

        $responseData = $response->json();

        if ($responseData['status'] !== 'success') {
            return response()->json(['status' => 'error', 'message' => 'Transaction verification failed.'], 400);
        }

        $reference = $responseData['data']['tx_ref'];

        DB::beginTransaction();

        try {
            $transaction = Transaction::where('payment_gateway_ref', $reference)
                ->where('status', 'pending')
                ->with(['user', 'plan'])
                ->first();

            if (!$transaction) {
                throw new \Exception("Transaction not found or already processed");
            }

            $transaction->update([
                'status' => 'success',
                'payment_gateway_ref' => $responseData['data']['tx_ref'],
                'currency' => $responseData['data']['currency'],
                'auth_token' => $responseData['data']['card']['token'] ?? null,
                'channel' => $responseData['data']['channel'] ?? null,
                'customer_email' => $responseData['data']['customer']['email'] ?? null,
                'ip_address' => $responseData['data']['ip'] ?? null,
                'device' => $responseData['data']['device_fingerprint'] ?? null,
                'location' => $responseData['data']['log']['geolocation'] ?? null,
                'paid_at' => isset($responseData['data']['created_at'])
                    ? Carbon::parse($responseData['data']['created_at'])
                    : now(),
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

            $branch->subscriptions()->isActive()->update([
                'status' => 'canceled',
                'canceled_at' => now()
            ]);

            $subscription = $branch->subscriptions()->create([
                'plan_id' => $plan->id,
                'starts_at' => $startDate,
                'ends_at' => $endDate,
                'status' => 'active',
            ]);

            $branch->update([
                'isSubscribed' => true,
                'subscription_end_date' => $endDate,
                'subscription_start_date' => $startDate,
                'subscription_type' => $plan->slug,
                'subscription_count' => $branch->subscription_count + 1
            ]);

            DB::commit();

            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return $this->formatSuccessResponse($transaction);
            }
            return redirect()->to(config('app.frontend_url') . '/receipt.html?reference=' . $reference);

        } catch (\Exception $e) {
            DB::rollBack();

            Transaction::where('payment_gateway_ref', $reference)->update(['status' => 'failed']);

            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 400);
            }

            return redirect()->to(config('app.frontend_url') . '/payment-error.html?error=' . urlencode($e->getMessage()));
        }
    }

    return response()->json([
        'status' => 'error',
        'message' => 'Payment failed or cancelled',
    ], 400);
}


    private function generateUniqueFlutterwaveReference()
    {
        do {
            $reference = 'FLW-' . strtoupper(Str::random(10));
        } while (Transaction::where('payment_gateway_ref', $reference)->exists());

        return $reference;
    }

}