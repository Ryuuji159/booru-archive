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

    'konachan' => [
        'host' => env('KONACHAN_HOST', 'https://konachan.com'),
        'download_batch_size' => env('KONACHAN_DOWNLOAD_BATCH_SIZE', 100),
        'download_request_cooldown_seconds' => env('KONACHAN_DOWNLOAD_REQUEST_COOLDOWN_SECONDS', 2),
        'scrape_pages_per_run' => env('KONACHAN_SCRAPE_PAGES_PER_RUN', 10),
        'storage_directories' => [
            'full' => env('KONACHAN_FULL_STORAGE_DIRECTORY', 'full'),
            'preview' => env('KONACHAN_PREVIEW_STORAGE_DIRECTORY', 'preview'),
        ],
    ],
];
