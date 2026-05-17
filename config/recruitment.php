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

    'hris_api' => [
        'base_url' => env('HRIS_API_BASE_URL'),
        'token' => env('HRIS_API_TOKEN'),
        'timeout' => env('HRIS_API_TIMEOUT', 20),
        'queue' => env('HRIS_API_QUEUE', 'default'),
    ],

    'pkwt_contracts' => [
        'disk' => env('PKWT_CONTRACT_DISK', 'local'),
        'base_path' => env('PKWT_CONTRACT_BASE_PATH', 'pkwt-contracts'),
        'max_upload_kb' => env('PKWT_CONTRACT_MAX_UPLOAD_KB', 10240),
        'allowed_manual_mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
    ],

    'locked_employment_refresh' => [
        'enabled' => env('LOCKED_EMPLOYMENT_REFRESH_ENABLED', true),
        'cron' => env('LOCKED_EMPLOYMENT_REFRESH_CRON', '*/30 * * * *'),
        'resync_after_minutes' => env('LOCKED_EMPLOYMENT_REFRESH_RESYNC_AFTER_MINUTES', 1440),
        'chunk_size' => env('LOCKED_EMPLOYMENT_REFRESH_CHUNK_SIZE', 250),
        'queue' => env('LOCKED_EMPLOYMENT_REFRESH_QUEUE', 'default'),
    ],
];
