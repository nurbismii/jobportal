<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VhireOnboardingCandidate extends Model
{
    protected $table = 'vhire_onboarding_candidates';

    protected $guarded = [];

    protected $casts = [
        'tanggal_mulai_kerja' => 'date',
        'payload' => 'array',
        'synced_at' => 'datetime',
        'last_sync_attempt_at' => 'datetime',
    ];

    public function lamaran()
    {
        return $this->belongsTo(Lamaran::class, 'lamaran_id');
    }

    public function biodata()
    {
        return $this->belongsTo(Biodata::class, 'biodata_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contracts()
    {
        return $this->hasMany(VhirePkwtContract::class, 'onboarding_candidate_id');
    }

    public function getMaskedNoKtpAttribute(): string
    {
        return mask_no_ktp($this->no_ktp);
    }
}
