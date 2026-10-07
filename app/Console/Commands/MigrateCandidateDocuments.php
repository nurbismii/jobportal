<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateCandidateDocuments extends Command
{
    protected $signature = 'documents:migrate-private {--execute : Salin dan verifikasi file} {--delete-public : Hapus file sumber setelah checksum cocok}';
    protected $description = 'Audit atau pindahkan dokumen kandidat dari public ke storage private';

    public function handle(): int
    {
        if ($this->option('delete-public') && ! $this->option('execute')) {
            $this->error('--delete-public memerlukan --execute.');
            return self::FAILURE;
        }
        if ($this->option('execute') && ! app()->environment('testing') && ! app()->isDownForMaintenance()) {
            $this->error('Aktifkan maintenance dan hentikan worker selama migrasi dokumen.');
            return self::FAILURE;
        }
        $disk = Storage::disk('candidate_private');
        $count = 0;
        $publicRoot = realpath(public_path());
        foreach (glob(public_path('[0-9]*'), GLOB_ONLYDIR) ?: [] as $folder) {
            $nik = basename($folder);
            if (! preg_match('/^[0-9]{16}$/', $nik)) continue;
            foreach (glob($folder . '/dokumen/*') ?: [] as $source) {
                if (! is_file($source)) continue;
                $resolved = realpath($source);
                if (is_link($source) || ! $resolved || ! str_starts_with($resolved, $publicRoot . DIRECTORY_SEPARATOR)) {
                    $this->error('Path sumber tidak aman. Migrasi dihentikan.');
                    return self::FAILURE;
                }
                $relative = $nik . '/dokumen/' . basename($source);
                candidate_document_path($nik, basename($source), false);
                $count++;
                if (! $this->option('execute')) continue;
                if (! $disk->exists($relative)) {
                    $stream = fopen($source, 'rb');
                    try { $disk->put($relative, $stream); }
                    finally { fclose($stream); }
                }
                if (! hash_equals(hash_file('sha256', $source), hash_file('sha256', $disk->path($relative)))) {
                    $this->error('Checksum berbeda; sumber dipertahankan. Migrasi dihentikan.');
                    return self::FAILURE;
                }
                if ($this->option('delete-public') && ! unlink($source)) {
                    $this->error('Gagal menghapus sumber yang sudah diverifikasi.');
                    return self::FAILURE;
                }
            }
        }
        $this->info($count . ' file ' . ($this->option('execute') ? 'diverifikasi.' : 'ditemukan (dry-run, tidak ada perubahan).'));
        return self::SUCCESS;
    }
}
