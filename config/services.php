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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/gmail/callback'),
        
        // Para Application Default Credentials
        'service_account_key' => env('GOOGLE_SERVICE_ACCOUNT_KEY_PATH'),
        
        // Para Workload Identity Federation
        'workload_identity' => env('GOOGLE_WORKLOAD_IDENTITY_CONFIG') ? json_decode(env('GOOGLE_WORKLOAD_IDENTITY_CONFIG'), true) : null,
        
        // Email de impersonación para Domain-wide Delegation
        'subject_email' => env('GOOGLE_SUBJECT_EMAIL', 'orpro@orproverificaciones.cl'),
    ],

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'api_url' => env('BREVO_API_URL', 'https://api.brevo.com/v3'),
    ],

];
