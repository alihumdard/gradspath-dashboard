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

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'connect_webhook_secret' => env('STRIPE_CONNECT_WEBHOOK_SECRET'),
        'api_base' => env('STRIPE_API_BASE', 'https://api.stripe.com/v1'),
        'connect_country' => env('STRIPE_CONNECT_COUNTRY', 'US'),
        'booking_success_url' => env('STRIPE_BOOKING_SUCCESS_URL'),
        'booking_cancel_url' => env('STRIPE_BOOKING_CANCEL_URL'),
    ],

    'zoom' => [
        'enabled' => env('ZOOM_ENABLED', false),
        'client_id' => env('ZOOM_CLIENT_ID'),
        'client_secret' => env('ZOOM_CLIENT_SECRET'),
        'redirect_uri' => env('ZOOM_REDIRECT_URI'),
        'authorize_url' => env('ZOOM_AUTHORIZE_URL', 'https://zoom.us/oauth/authorize'),
        'token_url' => env('ZOOM_TOKEN_URL', 'https://zoom.us/oauth/token'),
        'api_base' => env('ZOOM_API_BASE', 'https://api.zoom.us/v2'),
        'webhook_secret_token' => env('ZOOM_WEBHOOK_SECRET_TOKEN'),
        'attendance' => [
            'no_show_grace_minutes' => (int) env('ZOOM_ATTENDANCE_NO_SHOW_GRACE_MINUTES', 15),
            'feedback_fallback_grace_minutes' => (int) env('ZOOM_ATTENDANCE_FEEDBACK_FALLBACK_GRACE_MINUTES', 60),
            'minimum_overlap_minutes' => (int) env('ZOOM_ATTENDANCE_MINIMUM_OVERLAP_MINUTES', 5),
        ],
    ],

    'google_calendar' => [
        'enabled' => env('GOOGLE_CALENDAR_ENABLED', false),
        'calendar_id' => env('GOOGLE_CALENDAR_ID'),
        'service_account_email' => env('GOOGLE_SERVICE_ACCOUNT_EMAIL'),
        'private_key' => env('GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY'),
        'token_uri' => env('GOOGLE_SERVICE_ACCOUNT_TOKEN_URI', 'https://oauth2.googleapis.com/token'),
        'api_base' => env('GOOGLE_CALENDAR_API_BASE', 'https://www.googleapis.com/calendar/v3'),
    ],

];
