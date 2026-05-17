<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VhireIntegrationSyncLog extends Model
{
    protected $table = 'vhire_integration_sync_logs';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'retry_available' => 'boolean',
        'last_attempt_at' => 'datetime',
    ];
}
