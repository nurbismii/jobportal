<?php

namespace App\Services\Vhire;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PkwtContractFileService
{
    public function storeBase64(string $base64, string $candidateCode, string $prefix, ?string $originalName = null, ?string $declaredMime = null): array
    {
        [$bytes, $mime] = $this->decodeBase64($base64, $declaredMime);
        $this->validateFile($bytes, $mime);

        $extension = $this->extensionForMime($mime);
        $safeCandidate = Str::slug($candidateCode ?: 'candidate');
        $safeOriginal = $originalName ? pathinfo($originalName, PATHINFO_FILENAME) : $prefix;
        $safeOriginal = Str::slug($safeOriginal) ?: $prefix;
        $fileName = $safeOriginal . '-' . now()->format('YmdHis') . '-' . Str::random(8) . '.' . $extension;
        $path = trim((string) config('recruitment.pkwt_contracts.base_path', 'pkwt-contracts'), '/')
            . '/' . $safeCandidate . '/' . $fileName;
        $disk = (string) config('recruitment.pkwt_contracts.disk', 'local');

        Storage::disk($disk)->put($path, $bytes);

        return [
            'disk' => $disk,
            'path' => $path,
            'name' => $originalName ?: $fileName,
            'mime' => $mime,
        ];
    }

    public function response(string $disk, string $path, ?string $name = null, ?string $mime = null)
    {
        abort_if(! $this->isSafeRelativePath($path), 404);
        abort_if(! Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path, $name, array_filter([
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $name ?: basename($path)) . '"',
        ]));
    }

    private function decodeBase64(string $base64, ?string $declaredMime): array
    {
        $mime = $declaredMime;

        if (preg_match('/^data:([^;]+);base64,(.*)$/', $base64, $matches)) {
            $mime = $matches[1];
            $base64 = $matches[2];
        }

        $bytes = base64_decode($base64, true);

        if ($bytes === false || $bytes === '') {
            throw new InvalidArgumentException('File kontrak tidak valid.');
        }

        if (! $mime) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($bytes) ?: 'application/octet-stream';
        }

        return [$bytes, $mime];
    }

    private function validateFile(string $bytes, string $mime): void
    {
        $allowed = (array) config('recruitment.pkwt_contracts.allowed_manual_mimes', [
            'application/pdf',
            'image/jpeg',
            'image/png',
        ]);

        if (! in_array($mime, $allowed, true)) {
            throw new InvalidArgumentException('Tipe file kontrak tidak diizinkan.');
        }

        $maxBytes = ((int) config('recruitment.pkwt_contracts.max_upload_kb', 10240)) * 1024;

        if (strlen($bytes) > $maxBytes) {
            throw new InvalidArgumentException('Ukuran file kontrak melebihi batas.');
        }
    }

    private function extensionForMime(string $mime): string
    {
        return [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ][$mime] ?? 'bin';
    }

    private function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || Str::startsWith($path, ['/']) || preg_match('/^[A-Za-z]:/', $path)) {
            return false;
        }

        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return strpos($path, "\0") === false;
    }
}
