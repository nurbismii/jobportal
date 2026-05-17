<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VhireIntegrationAuditLog extends Model
{
    const UPDATED_AT = null;

    protected $table = 'vhire_integration_audit_logs';

    protected $guarded = [];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];
}
