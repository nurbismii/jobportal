<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VhirePkwtContract extends Model
{
    private const MAX_EMBEDDED_SIGNATURE_BASE64_LENGTH = 4194304;

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
        [$content, $dataImageSources] = $this->extractDataImageSources($content);

        if (class_exists(\HTMLPurifier::class) && class_exists(\HTMLPurifier_Config::class)) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'a[href|target|rel|class|style],p[class|style],br,strong,b,em,i,u,s,sub,sup,ol[class|style],ul[class|style],li[class|style],table[class|style|border|cellpadding|cellspacing|width],caption[class|style],colgroup[class|style],col[class|style|span|width],thead[class|style],tbody[class|style],tfoot[class|style],tr[class|style],td[class|style|colspan|rowspan|width|height|align|valign],th[class|style|colspan|rowspan|width|height|align|valign],h1[class|style],h2[class|style],h3[class|style],h4[class|style],h5[class|style],h6[class|style],div[class|style],span[class|style],blockquote[class|style],figure[class|style],figcaption[class|style],hr[class|style],img[src|alt|class|style|width|height]');
            $config->set('HTML.DefinitionID', 'vhire-pkwt-contract-tinymce');
            $config->set('HTML.DefinitionRev', 1);
            $config->set('CSS.AllowedProperties', [
                'background-color',
                'border',
                'border-collapse',
                'color',
                'font-size',
                'font-style',
                'font-weight',
                'height',
                'line-height',
                'margin',
                'margin-bottom',
                'margin-left',
                'margin-right',
                'margin-top',
                'float',
                'max-width',
                'padding',
                'padding-bottom',
                'padding-left',
                'padding-right',
                'padding-top',
                'text-align',
                'text-decoration',
                'text-indent',
                'vertical-align',
                'width',
            ]);
            $config->set('Attr.AllowedFrameTargets', ['_blank']);
            $config->set('URI.AllowedSchemes', [
                'data' => true,
                'http' => true,
                'https' => true,
                'mailto' => true,
            ]);
            $config->set('AutoFormat.RemoveEmpty', false);
            $config->set('Cache.DefinitionImpl', null);

            if ($definition = $config->maybeGetRawHTMLDefinition()) {
                $definition->addElement('figure', 'Block', 'Flow', 'Common');
                $definition->addElement('figcaption', 'Inline', 'Flow', 'Common');
            }

            return $this->restoreDataImageSources(
                (new \HTMLPurifier($config))->purify($content),
                $dataImageSources
            );
        }

        return $this->restoreDataImageSources(
            strip_tags($content, '<a><p><br><strong><b><em><i><u><s><sub><sup><ol><ul><li><table><caption><colgroup><col><thead><tbody><tfoot><tr><td><th><h1><h2><h3><h4><h5><h6><div><span><blockquote><figure><figcaption><hr><img>'),
            $dataImageSources
        );
    }

    private function extractDataImageSources(string $content): array
    {
        $sources = [];

        $content = preg_replace_callback(
            '/<img\b[^>]*>/i',
            function (array $matches) use (&$sources): string {
                return $this->replaceDataImageSourceInImgTag($matches[0], $sources);
            },
            $content
        ) ?? $content;

        return [$content, $sources];
    }

    private function replaceDataImageSourceInImgTag(string $tag, array &$sources): string
    {
        if (! preg_match('/\bsrc\s*=\s*(["\'])/i', $tag, $matches, PREG_OFFSET_CAPTURE)) {
            return $tag;
        }

        $quote = $matches[1][0];
        $valueStart = $matches[1][1] + 1;
        $valueEnd = strpos($tag, $quote, $valueStart);

        if ($valueEnd === false) {
            return $tag;
        }

        $source = $this->normalizeDataImageSource(substr($tag, $valueStart, $valueEnd - $valueStart));

        if ($source === null) {
            return $tag;
        }

        $placeholder = 'https://vhire.local/contract-data-image/' . sha1($source) . '.png';
        $sources[$placeholder] = $source;

        return substr($tag, 0, $valueStart) . $placeholder . substr($tag, $valueEnd);
    }

    private function normalizeDataImageSource(string $source): ?string
    {
        if (! preg_match('/^data:image\/(png|jpe?g|webp);base64,/i', $source, $matches)) {
            return null;
        }

        $base64 = preg_replace('/\s+/', '', substr($source, strlen($matches[0])));

        if (! is_string($base64) || $base64 === '' || strlen($base64) > self::MAX_EMBEDDED_SIGNATURE_BASE64_LENGTH) {
            return null;
        }

        $binary = base64_decode($base64, true);

        if ($binary === false || $binary === '') {
            return null;
        }

        $mime = strtolower(str_replace(['data:image/', ';base64,'], '', $matches[0]));
        $mime = $mime === 'jpg' ? 'jpeg' : $mime;

        return 'data:image/' . $mime . ';base64,' . $base64;
    }

    private function restoreDataImageSources(string $content, array $sources): string
    {
        if ($sources === []) {
            return $content;
        }

        return strtr($content, $sources);
    }

    public function getDisplayNumberAttribute(): string
    {
        return $this->no_pkwt ?: ($this->kode_kontrak ?: (string) $this->hris_contract_id);
    }

    public function getSignatureStatusLabelAttribute(): string
    {
        $labels = [
            'draft' => 'Draft',
            'waiting_signature' => 'Menunggu Tanda Tangan',
            'signed' => 'Sudah Ditandatangani',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan',
        ];

        return $labels[$this->signature_status] ?? ($this->signature_status ?: '-');
    }

    public function isVisibleForCandidate(): bool
    {
        return (bool) $this->visible_in_vhire
            && blank($this->employee_nik)
            && $this->match_status === 'matched_to_candidate'
            && $this->signing_method === 'electronic';
    }

    public function isSignableByCandidate(): bool
    {
        return $this->isVisibleForCandidate()
            && in_array($this->signature_status, ['draft', 'waiting_signature'], true);
    }
}
