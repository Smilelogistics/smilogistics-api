<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
        

        'paystack' => [
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
        ],

        'flutterwave' => [
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        ],

        'stripe' => [
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],

        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'secret' => env('PAYPAL_SECRET'),
            'base_url' => env('PAYPAL_BASE_URL'),
        ],

        'bank_transfer' => [
            'account_number' => env('BANK_TRANSFER_ACCOUNT_NUMBER'),
            'account_name' => env('BANK_TRANSFER_ACCOUNT_NAME'),
            'bank_name' => env('BANK_TRANSFER_BANK_NAME'),
        ],

        'crypto' => [
            'wallet_address' => env('CRYPTO_WALLET_ADDRESS'),
        ],

        'app' =>[
            'url' => env('APP_URL'),
        ],

        'google' => [
            'api' => env('GOOGLE_MAPS_API_KEY'),
        ],
    ],

];
