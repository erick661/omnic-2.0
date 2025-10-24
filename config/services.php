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
        // OAuth Configuration (fallback)
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/gmail/callback'),
        
        // Service Account Configuration (primary)
        'service_account_path' => env('GOOGLE_SERVICE_ACCOUNT_PATH'),
        'admin_email' => env('GOOGLE_WORKSPACE_ADMIN_EMAIL'),
        'workspace_domain' => env('GOOGLE_WORKSPACE_DOMAIN'),
        'client_id_service_account' => env('GOOGLE_SERVICE_ACCOUNT_CLIENT_ID'),
        
        // Drive Configuration
        'drive_root_folder' => env('GOOGLE_DRIVE_ROOT_FOLDER_NAME', 'Omnic Email Attachments'),
        
        // Legacy configurations
        'service_account_key' => env('GOOGLE_SERVICE_ACCOUNT_KEY_PATH'),
        'workload_identity' => env('GOOGLE_WORKLOAD_IDENTITY_CONFIG') ? json_decode(env('GOOGLE_WORKLOAD_IDENTITY_CONFIG'), true) : null,
        'subject_email' => env('GOOGLE_SUBJECT_EMAIL', 'orpro@orproverificaciones.cl'),
    ],

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'api_url' => env('BREVO_API_URL', 'https://api.brevo.com/v3'),
    ],

];
