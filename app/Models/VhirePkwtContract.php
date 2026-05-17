<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VhirePkwtContract extends Model
{
    protected $table = 'vhire_pkwt_contracts';

    protected $guarded = [];

    protected $casts = [
        'tanggal_mulai_kontrak' => 'date',
        'tanggal_akhir_kontrak' => 'date',
        'gaji' => 'decimal:2',
        'matched_at' => 'datetime',
        'signed_at' => 'datetime',
        'visible_in_vhire' => 'boolean',
        'hidden_at' => 'datetime',
        'activated_as_employee_at' => 'datetime',
        'manual_uploaded_at' => 'datetime',
        'source_payload' => 'array',
        'last_imported_at' => 'datetime',
    ];

    public function onboardingCandidate()
    {
        return $this->belongsTo(VhireOnboardingCandidate::class, 'onboarding_candidate_id');
    }

    public function matchedBiodata()
    {
        return $this->belongsTo(Biodata::class, 'matched_biodata_id');
    }

    public function matchedUser()
    {
        return $this->belongsTo(User::class, 'matched_user_id');
    }

    public function matchedLamaran()
    {
        return $this->belongsTo(Lamaran::class, 'matched_lamaran_id');
    }

    public function histories()
    {
        return $this->hasMany(VhirePkwtContractHistory::class, 'contract_id');
    }

    public function getMaskedNoKtpAttribute(): string
    {
        return mask_no_ktp($this->no_ktp);
    }

    public function isVisibleForCandidate(): bool
    {
        return (bool) $this->visible_in_vhire
            && blank($this->employee_nik)
            && $this->match_status === 'matched_to_candidate'
            && $this->signing_method === 'electronic'
            && in_array($this->signature_status, ['draft', 'waiting_signature'], true);
    }
}
