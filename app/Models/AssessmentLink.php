<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentLink extends Model
{
    public const TYPE_MCU = 'mcu';

    public function isDocumentOnly(): bool
    {
        return $this->assessment_type === self::TYPE_MCU;
    }

    public function supportsMcuDocuments(): bool
    {
        return $this->isDocumentOnly() || $this->assessment_type === 'kesehatan';
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->isDocumentOnly() ? 'Hasil MCU' : ucfirst($this->assessment_type);
    }
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

    public function isAccessibleAt(CarbonInterface $now): bool
    {
        return $this->is_active && $this->expires_at !== null && $this->expires_at->gt($now);
    }
}
