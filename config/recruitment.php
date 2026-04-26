<?php

return [
    'internal_api' => [
        'token' => env('INTERNAL_API_TOKEN'),
    ],

    'candidate_documents' => [
        'disk' => 'recruitment_public',
        'base_path' => '{no_ktp}/dokumen',
        'temporary_url_minutes' => 10,
    ],

    'locked_employment_refresh' => [
        'enabled' => env('LOCKED_EMPLOYMENT_REFRESH_ENABLED', true),
        'cron' => env('LOCKED_EMPLOYMENT_REFRESH_CRON', '*/30 * * * *'),
        'resync_after_minutes' => env('LOCKED_EMPLOYMENT_REFRESH_RESYNC_AFTER_MINUTES', 1440),
        'chunk_size' => env('LOCKED_EMPLOYMENT_REFRESH_CHUNK_SIZE', 250),
        'queue' => env('LOCKED_EMPLOYMENT_REFRESH_QUEUE', 'default'),
    ],
];
