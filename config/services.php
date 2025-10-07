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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sms' => [
        'api_key' => env('SMS_API_KEY'),
        'from' => env('SMS_FROM', 'Gathro'),
        'base_url' => env('SMS_BASE_URL', 'https://api.sms.com'),
        'gateway' => env('SMS_GATEWAY', 'testing'), // testing, twilio, vonage, etc.
    ],

    'google' => [
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'national_id' => [
        'api_key' => env('NATIONAL_ID_API_KEY'),
        'base_url' => env('NATIONAL_ID_BASE_URL', 'https://api.absher.sa'),
        'gateway' => env('NATIONAL_ID_GATEWAY', 'absher'), // absher, nitaqat, testing
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'phone' => env('TWILIO_PHONE'),
    ],

    'socialite' => [
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/api/v1/public/auth/google/callback'),
        ],
        'apple' => [
            'client_id' => env('APPLE_CLIENT_ID'),
            'client_secret' => env('APPLE_CLIENT_SECRET'),
            'redirect' => env('APPLE_REDIRECT_URI', env('APP_URL').'/api/v1/public/auth/apple/callback'),
            'key_id' => env('APPLE_KEY_ID'),
            'team_id' => env('APPLE_TEAM_ID'),
            'private_key' => env('APPLE_PRIVATE_KEY'),
        ],
    ],

    'otp' => [
        'delivery' => env('OTP_DELIVERY', 'log'),
    ],

    'reverb' => [
        'host' => env('REVERB_HOST', '127.0.0.1'),
        'port' => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http'),
        'dashboard' => env('REVERB_DASHBOARD_ENABLED', false),
    ],

    'fcm' => [
        'enabled' => env('FCM_ENABLED', false),
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    'apns' => [
        'enabled' => env('APNS_ENABLED', false),
        'key_id' => env('APNS_KEY_ID'),
        'team_id' => env('APNS_TEAM_ID'),
        'app_bundle_id' => env('APNS_BUNDLE_ID'),
        'private_key' => env('APNS_PRIVATE_KEY'),
        'production' => env('APNS_PRODUCTION', false),
    ],

    'tap' => [
        'base_url' => env('TAP_BASE_URL', 'https://api.tap.company/v2'),
        'public_key' => env('TAP_PUBLIC_KEY'), // For SDKs on web/mobile
        'secret_key' => env('TAP_SECRET_KEY'), // For server API
        'apple_pay_domain' => env('TAP_APPLE_PAY_DOMAIN'),
        'apple_pay_merchant_ids' => [
            'test' => env('TAP_APPLE_PAY_MERCHANT_ID_TEST'),
            'live' => env('TAP_APPLE_PAY_MERCHANT_ID_LIVE'),
        ],
        // إعدادات إعادة المحاولة
        'max_retries' => env('TAP_MAX_RETRIES', 3),
        'retry_delay' => env('TAP_RETRY_DELAY', 1000), // milliseconds
        // إعدادات الأمان
        'webhook_secret' => env('TAP_WEBHOOK_SECRET'),
        'enable_idempotency' => env('TAP_ENABLE_IDEMPOTENCY', true),
    ],

];
