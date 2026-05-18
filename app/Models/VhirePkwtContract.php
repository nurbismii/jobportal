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

    public function getDisplayableContractContentAttribute(): string
    {
        $content = trim((string) $this->contract_content);

        if ($content === '') {
            return '';
        }

        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($content === strip_tags($content)) {
            return nl2br(e($content));
        }

        $content = preg_replace('#<(script|style|iframe|object|embed|form|input|button|textarea|select)\b[^>]*>.*?</\1>#is', '', $content);

        if (class_exists(\HTMLPurifier::class) && class_exists(\HTMLPurifier_Config::class)) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,ol,ul,li,table,thead,tbody,tfoot,tr,td[colspan|rowspan],th[colspan|rowspan],h1,h2,h3,h4,h5,h6,div,span,blockquote,hr');
            $config->set('AutoFormat.RemoveEmpty', false);
            $config->set('Cache.DefinitionImpl', null);

            return (new \HTMLPurifier($config))->purify($content);
        }

        $allowedTags = '<p><br><strong><b><em><i><u><ol><ul><li><table><thead><tbody><tfoot><tr><td><th><h1><h2><h3><h4><h5><h6><div><span><blockquote><hr>';
        $content = strip_tags($content, $allowedTags);

        return preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $content);
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
