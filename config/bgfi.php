<?php

return [
    /*
    |--------------------------------------------------------------------------
    | BGFI RDC API Configuration
    |--------------------------------------------------------------------------
    | Connection settings for the Omnitech/BGFI gateway.
    */

    // Base API URL (UAT or production)
    'base_url' => env('BGFI_BASE_URL', 'https://rdc-api2-partenaire-test.omnitechafrica.com'),

    // Consumer credentials provided by the bank
    'consumer_id'     => env('BGFI_CONSUMER_ID'),
    'consumer_secret' => env('BGFI_CONSUMER_SECRET'),

    // Login credentials used for the OAuth token request
    'login'    => env('BGFI_LOGIN'),
    'password' => env('BGFI_PASSWORD'),

    // Default settings for payments
    'currency'            => env('BGFI_CURRENCY', 'CDF'),
    'default_description' => env('BGFI_DEFAULT_DESCRIPTION', 'Payment via '.env('APP_NAME', 'Laravel')),

    // Webhook route and registration
    'register_callback_route' => env('BGFI_REGISTER_CALLBACK_ROUTE', true),
    'callback_path'           => env('BGFI_CALLBACK_PATH', 'api/bgfi/callback'),

    // HTTP client options
    'verify_ssl' => env('BGFI_VERIFY_SSL', true),
    'ca_path'    => env('BGFI_CA_PATH', 'storage/cert/cacert.pem'),
    'user_agent' => env('BGFI_USER_AGENT', 'Mozilla/5.0 (Linux; Android 11; Pixel 5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.91 Mobile Safari/537.36'),
    'token_ttl'  => env('BGFI_TOKEN_TTL', 3500),

    // URL where the bank redirects the customer after payment (if applicable)
    'return_url' => env('BGFI_RETURN_URL', '/payment/success'),
];
