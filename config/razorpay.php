<?php

return [
    'key' => env('RAZORPAY_KEY'),
    'secret' => env('RAZORPAY_SECRET'),
    'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    'registration_fee_amount' => (int) env('REGISTRATION_FEE_AMOUNT', 500000),
    'currency' => env('RAZORPAY_CURRENCY', 'INR'),
];
