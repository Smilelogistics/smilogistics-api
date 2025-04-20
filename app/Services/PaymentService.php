<?php

namespace App\Services;

use App\Models\User;
use App\Models\Plan;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    public function initializePayment($method, User $user, Plan $plan, Transaction $transaction)
    {
        switch ($method) {
            case 'paystack':
                return $this->initializePaystack($user, $plan, $transaction);
            case 'flutterwave':
                return $this->initializeFlutterwave($user, $plan, $transaction);
            case 'stripe':
                return $this->initializeStripe($user, $plan, $transaction);
            case 'paypal':
                return $this->initializePaypal($user, $plan, $transaction);
            case 'bank_transfer':
                return $this->initializeBankTransfer($user, $plan, $transaction);
            case 'crypto':
                return $this->initializeCrypto($user, $plan, $transaction);
            case 'wallet':
                return $this->initializeWallet($user, $plan, $transaction);
            default:
                throw new \Exception('Unsupported payment method');
        }
    }

    protected function initializePaystack(User $user, Plan $plan, Transaction $transaction)
    {
        //dd(env('PAYSTACK_SECRET_KEY'));
        //dd(config('app.url'));
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/transaction/initialize', [
            'email' => $user->email,
            'amount' => $plan->price * 100, // Paystack uses kobo
            'reference' => $transaction->payment_gateway_ref,
            //'callback_url' => config('app.url') . '/api/v1/verify-paystack',
            'callback_url' => route('verify.paystack', [
                'trxref' => $transaction->reference,
                'reference' => $transaction->reference
            ]),
            'metadata' => [
                'user_id' => $user->id,
            ]
        ]);
        
       // dd($response->json());

        if (!$response->successful()) {
            throw new \Exception('Failed to initialize Paystack payment');
        }

        $data = $response->json();

        return [
            'payment_link' => $data['data']['authorization_url'],
            'reference' => $transaction->payment_gateway_ref,
        ];
    }

    protected function initializeFlutterwave(User $user, Plan $plan, Transaction $transaction)
    {
        //dd(env('FLUTTERWAVE_SECRET_KEY'));
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('FLUTTERWAVE_SECRET_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.flutterwave.com/v3/payments', [
            'tx_ref' => $transaction->payment_gateway_ref,
            'amount' => $plan->price,
            'currency' => $transaction->currency,
            'payment_options' => 'card,account,ussd',
            'redirect_url' => config('app.url') . '/api/payments/callback-flutterwave',
            'customer' => [
                'email' => $user->email,
                'name' => $user->fname. ' '. $user->lname,
            ],
            'customizations' => [
                'title' => env('APP_NAME'),
                'description' => "Payment for {$plan->name} plan",
            ],
            'meta' => [
                'plan_id' => $plan->id,
                'user_id' => $user->id,
            ],
        ]);

        //dd($response);

        if (!$response->successful() || $response->json('status') !== 'success') {
            throw new \Exception('Failed to initialize Flutterwave payment');
        }

        $data = $response->json();

        return [
            'payment_link' => $data['data']['link'],
            'reference' => $transaction->payment_gateway_ref,
        ];
    }

    protected function initializeStripe(User $user, Plan $plan, Transaction $transaction)
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret_key'));

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($transaction->currency),
                    'product_data' => [
                        'name' => $plan->name,
                    ],
                    'unit_amount' => $plan->price * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => config('app.url') . '/payment/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('app.url') . '/payment/cancel',
            'client_reference_id' => $transaction->payment_gateway_ref,
            'metadata' => [
                'plan_id' => $plan->id,
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
            ],
        ]);

        return [
            'payment_link' => $session->url,
            'reference' => $transaction->payment_gateway_ref,
        ];
    }

    protected function initializePaypal(User $user, Plan $plan, Transaction $transaction)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode(config('services.paypal.client_id') . ':' . config('services.paypal.secret')),
            'Content-Type' => 'application/json',
        ])->post(config('services.paypal.base_url') . '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $transaction->payment_gateway_ref,
                    'amount' => [
                        'currency_code' => $transaction->currency,
                        'value' => $plan->price,
                    ],
                    'description' => $plan->name,
                ]
            ],
            'application_context' => [
                'return_url' => config('app.url') . '/api/payments/callback/paypal',
                'cancel_url' => config('app.url') . '/payment/cancel',
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to initialize PayPal payment');
        }

        $data = $response->json();

        // Find approve link
        $approveLink = collect($data['links'])->firstWhere('rel', 'approve');

        return [
            'payment_link' => $approveLink['href'],
            'reference' => $transaction->payment_gateway_ref,
        ];
    }

    protected function initializeBankTransfer(User $user, Plan $plan, Transaction $transaction)
    {
        // Generate unique bank transfer reference
        $bankDetails = [
            'account_number' => config('services.bank_transfer.account_number'),
            'account_name' => config('services.bank_transfer.account_name'),
            'bank_name' => config('services.bank_transfer.bank_name'),
            'reference' => 'BANK_' . $transaction->payment_gateway_ref,
            'amount' => $plan->price,
            'currency' => $transaction->currency,
        ];

        $transaction->update([
            'bank_details' => json_encode($bankDetails),
            'status' => 'pending_verification',
        ]);

        return [
            'bank_details' => $bankDetails,
            'instructions' => 'Please transfer the exact amount to the bank account provided and use the reference code for verification.',
        ];
    }

    protected function initializeCrypto(User $user, Plan $plan, Transaction $transaction)
    {
        // This would integrate with a crypto payment processor like Coinbase Commerce
        // Simplified example:
        $cryptoDetails = [
            'wallet_address' => config('services.crypto.wallet_address'),
            'amount' => $plan->price,
            'currency' => $transaction->currency,
            'reference' => 'CRYPTO_' . $transaction->reference,
        ];

        $transaction->update([
            'crypto_details' => json_encode($cryptoDetails),
            'status' => 'pending_verification',
        ]);

        return [
            'crypto_details' => $cryptoDetails,
            'instructions' => 'Send the exact amount in crypto to the wallet address provided and include the reference code in the memo field.',
        ];
    }

    protected function initializeWallet(User $user, Plan $plan, Transaction $transaction)
    {
        // Check if user has sufficient wallet balance
        if ($user->wallet_balance < $plan->price) {
            throw new \Exception('Insufficient wallet balance');
        }

        // Deduct from wallet
        $user->wallet_balance -= $plan->price;
        $user->save();

        // Update transaction
        $transaction->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        // TODO: Implement plan activation logic here

        return [
            'message' => 'Payment successful from wallet',
            'redirect_url' => config('app.url') . '/payment/success',
        ];
    }
}