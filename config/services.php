<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'deepl' => [
        'key' => env('DEEPL_API_KEY'),
        'base_url' => env('DEEPL_API_URL', 'https://api-free.deepl.com'),
    ],

    'whatsapp' => [
        'enabled' => env('WHATSAPP_NOTIFICATIONS_ENABLED', false),
        'provider' => env('WHATSAPP_PROVIDER', 'twilio'),
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_WHATSAPP_FROM'),
            'templates' => [
                'registration' => env('TWILIO_WHATSAPP_TEMPLATE_REGISTRATION'),
                'update' => env('TWILIO_WHATSAPP_TEMPLATE_UPDATE'),
            ],
        ],
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret' => env('TURNSTILE_SECRET_KEY'),
    ],

    'payment_invoice' => [
        'signed_url_ttl_hours' => (int) env('PAYMENT_INVOICE_SIGNED_URL_TTL_HOURS', 72),
    ],

    'youtube' => [
        'api_key' => env('YOUTUBE_DATA_API_KEY'),
    ],

];
