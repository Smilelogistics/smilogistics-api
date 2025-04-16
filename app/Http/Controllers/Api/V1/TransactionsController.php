<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class TransactionsController extends Controller
{
    protected $paystackSecretKey;

    public function __construct()
    {
        $this->paystackSecretKey = env('PAYSTACK_SECRET_KEY');
    }
    public function initiatePaystackPayment(Request $request)
    {
        $user = auth()->user();
        $validatedData = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'payment_type' => 'nullable|string',
        ]);

        if ($validatedData->fails()) {
            return response()->json(['errors' => $validatedData->errors()], 422);
        }

        try{

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->paystackSecretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/transaction/initialize', [
                'email' => $user->email,
                'amount' => $amount * 100,
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ]);

            $responseData = $response->json();

            if (!$response->successful() || !$responseData['status']) {
                throw new Exception("Failed to initialize payment: " . ($responseData['message'] ?? 'Unknown error'));
            }
            
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'email' => $request->email,
                'payment_gateway_ref' => $responseData['data']['reference'],
                'payment_method' => 'paystack',
                'currency' => $responseData['data']['currency'],
                'payment_type' => $request->payment_type,
                'auth_token' => $responseData['data']['authorization']['authorization_code'],
                'channel' => $responseData['data']['channel'],
                'customer_email' => $responseData['data']['customer']['email'],
                'ip_address' => $responseData['data']['customer']['ip_address'],
                'device' => $responseData['data']['customer']['user_agent'],
                'location' => $responseData['data']['customer']['geoip'],
                'status' => 'pending',
            ]);


        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function verifyPaysatckPayment($reference)
    {
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

                DB::beginTransaction();

                $transaction = Transaction::where('payment_gateway_ref', $reference)->where('status', 'pending')->first();
                $transaction->update(['status' => 'success']);

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
            Transaction::where('reference', $reference)->update(['status' => 'failed']);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

}