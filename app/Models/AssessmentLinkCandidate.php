<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentLinkCandidate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'result_values' => 'array',
        'last_submitted_at' => 'datetime',
    ];

    public function assessmentLink(): BelongsTo
    {
        return $this->belongsTo(AssessmentLink::class);
    }

    public function lamaran(): BelongsTo
    {
        return $this->belongsTo(Lamaran::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(AssessmentResultAudit::class);
    }
}
