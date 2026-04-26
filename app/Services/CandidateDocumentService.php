<?php

namespace App\Services;

use App\Models\Biodata;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class CandidateDocumentService
{
    private const DOCUMENT_TYPES = [
        'cv' => 'CV',
        'pas_foto' => 'Pas Foto',
        'surat_lamaran' => 'Surat Lamaran',
        'ijazah' => 'Ijazah',
        'ktp' => 'KTP',
        'sim_b_2' => 'SIM B II',
        'sio' => 'SIO',
        'skck' => 'SKCK',
        'sertifikat_vaksin' => 'Sertifikat Vaksin',
        'kartu_keluarga' => 'Kartu Keluarga',
        'npwp' => 'NPWP',
        'ak1' => 'AK1',
        'sertifikat_pendukung' => 'Sertifikat Pendukung',
    ];

    public function findByNoKtp(string $noKtp): array
    {
        $biodata = $this->findCandidate($noKtp);

        if (! $biodata) {
            return [
                'found' => false,
                'documents' => [],
            ];
        }

        $expiresAt = now()->addMinutes($this->temporaryUrlMinutes());

        return [
            'found' => true,
            'candidate' => [
                'no_ktp' => (string) $biodata->no_ktp,
                'name' => $this->candidateName($biodata),
            ],
            'documents' => $this->availableDocuments($biodata, $expiresAt),
        ];
    }

    public function preview(string $noKtp, string $type)
    {
        $document = $this->resolveDocument($noKtp, $type);

        abort_if(! $document, 404);

        return $this->disk()->response($document['path'], null, [
            'Content-Type' => $document['mime'],
        ], 'inline');
    }

    public function download(string $noKtp, string $type)
    {
        $document = $this->resolveDocument($noKtp, $type);

        abort_if(! $document, 404);

        return $this->disk()->download($document['path'], $document['download_name'], [
            'Content-Type' => $document['mime'],
        ]);
    }

    private function availableDocuments(Biodata $biodata, $expiresAt): array
    {
        $documents = [];

        foreach (self::DOCUMENT_TYPES as $type => $label) {
            $document = $this->resolveDocumentFromBiodata($biodata, $type, $label);

            if (! $document) {
                continue;
            }

            $documents[] = [
                'type' => $type,
                'label' => $label,
                'mime' => $document['mime'],
                'preview_url' => URL::temporarySignedRoute(
                    'internal.candidate-documents.preview',
                    $expiresAt,
                    ['no_ktp' => $biodata->no_ktp, 'type' => $type]
                ),
                'download_url' => URL::temporarySignedRoute(
                    'internal.candidate-documents.download',
                    $expiresAt,
                    ['no_ktp' => $biodata->no_ktp, 'type' => $type]
                ),
                'expires_at' => $expiresAt->toIso8601String(),
            ];
        }

        return $documents;
    }

    private function resolveDocument(string $noKtp, string $type): ?array
    {
        if (! isset(self::DOCUMENT_TYPES[$type])) {
            return null;
        }

        $biodata = $this->findCandidate($noKtp);

        if (! $biodata) {
            return null;
        }

        return $this->resolveDocumentFromBiodata($biodata, $type, self::DOCUMENT_TYPES[$type]);
    }

    private function resolveDocumentFromBiodata(Biodata $biodata, string $type, string $label): ?array
    {
        $fileName = $biodata->{$type} ?? null;

        if (! $this->isSafeFileName($fileName) || ! $this->isSafeSegment($biodata->no_ktp)) {
            return null;
        }

        $path = $this->documentPath((string) $biodata->no_ktp, (string) $fileName);

        if (! $this->isSafeRelativePath($path) || ! $this->disk()->exists($path)) {
            return null;
        }

        return [
            'type' => $type,
            'label' => $label,
            'mime' => $this->mimeType($path),
            'path' => $path,
            'file_name' => (string) $fileName,
            'download_name' => $this->downloadFileName($biodata, $label, (string) $fileName),
        ];
    }

    private function findCandidate(string $noKtp): ?Biodata
    {
        $noKtp = trim($noKtp);

        if (! $this->isSafeSegment($noKtp) || strlen($noKtp) > 32) {
            return null;
        }

        return Biodata::with('user:id,name,no_ktp')
            ->where('no_ktp', $noKtp)
            ->first();
    }

    private function documentPath(string $noKtp, string $fileName): string
    {
        $basePath = (string) config('recruitment.candidate_documents.base_path', '{no_ktp}/dokumen');
        $basePath = str_replace('{no_ktp}', $noKtp, $basePath);

        return $this->normalizeRelativePath($basePath . '/' . $fileName);
    }

    private function normalizeRelativePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path);

        return trim($path, '/');
    }

    private function isSafeSegment($value): bool
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $value = trim((string) $value);

        return $value !== ''
            && strpos($value, "\0") === false
            && strpos($value, '/') === false
            && strpos($value, '\\') === false
            && strpos($value, '..') === false;
    }

    private function isSafeFileName($fileName): bool
    {
        if (! is_string($fileName) || trim($fileName) === '') {
            return false;
        }

        if (basename($fileName) !== $fileName) {
            return false;
        }

        return $this->isSafeSegment($fileName);
    }

    private function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || Str::startsWith($path, ['/']) || preg_match('/^[A-Za-z]:/', $path)) {
            return false;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return strpos($path, "\0") === false;
    }

    private function mimeType(string $path): string
    {
        try {
            return $this->disk()->mimeType($path) ?: 'application/octet-stream';
        } catch (\Throwable $e) {
            return 'application/octet-stream';
        }
    }

    private function downloadFileName(Biodata $biodata, string $label, string $fileName): string
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $name = $this->candidateName($biodata) ?: 'Tanpa Nama';
        $baseName = trim($biodata->no_ktp . ' ' . $name . ' - ' . $label);
        $baseName = preg_replace('/\s+/', ' ', $baseName);
        $baseName = preg_replace('/[\/\\\\:*?"<>|\x00-\x1F\x7F]/', '', $baseName);

        return $extension !== '' ? $baseName . '.' . $extension : $baseName;
    }

    private function candidateName(Biodata $biodata): string
    {
        return trim((string) (optional($biodata->user)->name ?? $biodata->nama ?? ''));
    }

    private function temporaryUrlMinutes(): int
    {
        return (int) config('recruitment.candidate_documents.temporary_url_minutes', 10);
    }

    private function disk()
    {
        return Storage::disk((string) config('recruitment.candidate_documents.disk', 'recruitment_public'));
    }
}
