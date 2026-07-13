<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentLink extends Model
{
    protected $guarded = [];

    protected $casts = [
        'form_schema' => 'array',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(AssessmentLinkCandidate::class);
    }

    public function isAccessibleAt(Carbon $now): bool
    {
        return $this->is_active && $this->expires_at !== null && $this->expires_at->gt($now);
    }
}
