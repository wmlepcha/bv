<?php

return [
    'app_id' => env('CASHFREE_APP_ID'),
    'secret_key' => env('CASHFREE_SECRET_KEY'),
    'webhook_secret' => env('CASHFREE_WEBHOOK_SECRET'),
    'environment' => env('CASHFREE_ENVIRONMENT', 'production'),
    'api_version' => env('CASHFREE_API_VERSION', '2023-08-01'),
    'registration_fee_amount' => (int) env('REGISTRATION_FEE_AMOUNT', 100),
    'currency' => env('CASHFREE_CURRENCY', 'INR'),
];
