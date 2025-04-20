<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
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



    // public function initiatePaystackPayment(Request $request)
    // {
    //     $user = auth()->user();
    //     //dd($this->paystackSecretKey);
    //     $validatedData = Validator::make($request->all(), [
    //         'amount' => 'required|numeric',
    //         'plan_name' => 'required|string',
    //     ]);

    //     if ($validatedData->fails()) {
    //         return response()->json(['errors' => $validatedData->errors()], 422);
    //     }

    //     try{

    //         $plans = Plan::where('name', $request->plan_name)->first();

    //         if (!$plans) {
    //             throw new Exception("Plan not found.");
    //         }
    //         elseif($plans->price != $request->amount){
    //             throw new Exception("Plan price mismatch.");
    //         }
    //         else{

    //         $response = Http::withHeaders([
    //             'Authorization' => 'Bearer ' . $this->paystackSecretKey,
    //             'Content-Type' => 'application/json',
    //         ])->post('https://api.paystack.co/transaction/initialize', [
    //             'email' => $user->email,
    //             'amount' => $request->amount * 100,
    //             'metadata' => [
    //                 'user_id' => $user->id,
    //             ],
    //         ]);

    //         $responseData = $response->json();

    //         if (!$response->successful() || !$responseData['status']) {
    //             throw new Exception("Failed to initialize payment: " . ($responseData['message'] ?? 'Unknown error'));
    //         }
    //         //dd($responseData);
            
    //         $transaction = Transaction::create([
    //             'user_id' => $user->id,
    //             'amount' => $request->amount,
    //             'payment_gateway_ref' => $responseData['data']['reference'],
    //             'payment_method' => 'paystack',
    //             'payment_type' => $request->payment_type,
    //             'status' => 'pending',
    //         ]);
    //     }
            
    //         return response()->json([
    //             'status' => 'success',
    //             'redirect_url' => $responseData['data']['authorization_url'],
    //             'reference' => $responseData['data']['reference'],
    //         ]);


    //     } catch (\Throwable $th) {
    //         return response()->json(['message' => $th->getMessage()], 500);
    //     }
    // }

    public function verifyPaysatckPayment(Request $request)
    {
        $trxref = $request->query('trxref');
        $reference = $request->query('reference');

        $user = auth()->user();
        try{
            if(Transaction::where('payment_gateway_ref', $reference)->where('status', 'success')->exists()){
                throw new Exception("Transaction already processed.");
            }
            else{
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->paystackSecretKey,
                    'Content-Type' => 'application/json',
                ])->get('https://api.paystack.co/transaction/verify/' . $reference);
    
                $responseData = $response->json();
    
                if (!$response->successful() || !$responseData['status']) {
                    throw new Exception("Failed to verify payment: " . ($responseData['message'] ?? 'Unknown error'));
                }

                $auth_code = $responseData['data']['authorization']['authorization_code'];
                $currency = $responseData['data']['currency'];

                DB::beginTransaction();

                $transaction = Transaction::where('payment_gateway_ref', $reference)->where('status', 'pending')->first();
                $transaction->update([
                    'status' => 'success',
                    'payment_gateway_ref' => $responseData['data']['reference'],
                    'payment_type' => $transaction->payment_type,
                    'currency' => $responseData['data']['currency'],
                    'auth_token' => $responseData['data']['authorization']['authorization_code'] ?? null,
                    'channel' => $responseData['data']['channel'] ?? null,
                    'customer_email' => $responseData['data']['customer']['email'] ?? null,
                    'ip_address' => $responseData['data']['ip_address'] ?? null,
                    'device' => $responseData['data']['user_agent'] ?? null,
                    'location' => $responseData['data']['log']['geolocation'] ?? null,
                    //'paid_at' => $responseData['data']['paid_at'] ?? null
                ]);

                $updateUser = User::where('id', $transaction->user_id)->first();

                $updateUser->update([
                    'isSubscribed' => 1,
                    'subscription_end_date' => now()->addDays(30),
                    'subscription_start_date' => now(),
                    'subscription_type' => $transaction->subscription_type,
                    'subscription_count' => $updateUser->subscription_count + 1
                ]);

                DB::commit();



                return response()->json(['message' => 'Payment verified successfully', 'transaction' => $transaction], 200);
            }
            
        }catch (Exception $e) {
            Transaction::where('payment_gateway_ref', $reference)->update(['status' => 'failed']);
            return response()->json(['error' => $e->getMessage()], 400);
        }
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
                    'redirect_url' => config('app.url') . '/api/V1/payments/callback-flutterwave',
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

        if ($status === 'success' && $txid) {
            $responseData = Flutterwave::verifyTransaction($txid);

            DB::beginTransaction();

            $transaction = Transaction::where('payment_gateway_ref', $reference)->where('status', 'pending')->first();
                $transaction->update([
                    'status' => 'success',
                    'payment_gateway_ref' => $responseData['data']['reference'],
                    'payment_type' => $transaction->payment_type,
                    'currency' => $responseData['data']['currency'],
                    'auth_token' => $responseData['data']['authorization']['authorization_code'] ?? null,
                    'channel' => $responseData['data']['channel'] ?? null,
                    'customer_email' => $responseData['data']['customer']['email'] ?? null,
                    'ip_address' => $responseData['data']['ip_address'] ?? null,
                    'device' => $responseData['data']['user_agent'] ?? null,
                    'location' => $responseData['data']['log']['geolocation'] ?? null,
                    //'paid_at' => $responseData['data']['paid_at'] ?? null
                ]);

            $updateUser = User::where('id', $transaction->user_id)->first();

            $updateUser->update([
                'isSubscribed' => 1,
                'subscription_end_date' => now()->addDays(30),
                'subscription_start_date' => now(),
                'subscription_type' => $transaction->subscription_type,
                'subscription_count' => $updateUser->subscription_count + 1
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Payment verified successfully',
                'data' => $responseData,
            ]);
        }

        return response()->json([
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