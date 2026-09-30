<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DeleteOrphanBiodata extends Command
{
    protected $signature = 'biodata:delete-orphans
        {ids* : ID biodata hasil audit, dipisahkan spasi}
        {--database-name= : Nama database yang diharapkan}
        {--expected-lamaran= : Jumlah lamaran yang disetujui untuk dihapus}
        {--execute : Backup lalu hapus; tanpa opsi ini hanya pemeriksaan}';

    protected $description = 'Hapus biodata orphan terpilih beserta data lamaran terkait secara transaksional';

    public function handle(): int
    {
        try {
            $ids = $this->argument('ids');
            foreach ($ids as $id) {
                if (! ctype_digit($id) || (int) $id < 1) {
                    throw new RuntimeException('ID biodata harus bilangan bulat positif.');
                }
            }
            $ids = array_unique(array_map('intval', $ids));
            $database = DB::connection()->getDatabaseName();
            if ($this->option('database-name') !== $database) {
                throw new RuntimeException('Nama database tidak cocok. Gunakan --database-name sesuai database koneksi aplikasi.');
            }
            if (! ctype_digit((string) $this->option('expected-lamaran'))) {
                throw new RuntimeException('--expected-lamaran wajib berupa jumlah lamaran hasil audit.');
            }
            if ($this->option('execute') && ! app()->environment('testing') && ! app()->isDownForMaintenance()) {
                throw new RuntimeException('Jalankan php artisan down dan hentikan worker/penulis database selama penghapusan.');
            }

            DB::transaction(function () use ($ids, $database) {
                $biodata = DB::table('biodata')->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
                $users = DB::table('users')->whereIn('id', $biodata->pluck('user_id')->filter()->all())->lockForUpdate()->get();
                if ($biodata->count() !== count($ids) || $users->isNotEmpty()) {
                    throw new RuntimeException('Target berubah: ada biodata tidak ditemukan atau sudah memiliki user. Tidak ada data dihapus.');
                }
                $lamaran = DB::table('lamaran')->whereIn('biodata_id', $ids)->lockForUpdate()->get();
                if ($lamaran->count() !== (int) $this->option('expected-lamaran')) {
                    throw new RuntimeException('Jumlah lamaran berbeda dari --expected-lamaran. Audit ulang sebelum menghapus.');
                }
                $lamaranIds = $lamaran->pluck('id')->all();
                foreach ([
                    'assessment_link_candidates' => ['lamaran_id' => $lamaranIds],
                    'vhire_onboarding_candidates' => ['lamaran_id' => $lamaranIds, 'biodata_id' => $ids],
                    'vhire_pkwt_contracts' => ['matched_lamaran_id' => $lamaranIds, 'matched_biodata_id' => $ids],
                ] as $table => $references) {
                    if (! Schema::hasTable($table)) continue;
                    $found = DB::table($table)->where(function ($query) use ($references) {
                        foreach ($references as $column => $values) $query->orWhereIn($column, $values);
                    })->lockForUpdate()->get();
                    if ($found->isNotEmpty()) throw new RuntimeException("Ada relasi {$table}. Tangani secara terpisah; penghapusan dibatalkan.");
                }

                $targets = [
                    'email_blast_logs' => ['lamaran_id', $lamaranIds],
                    'riwayat_proses_lamaran' => ['lamaran_id', $lamaranIds],
                    'biodata_pengalaman_kerja' => ['biodata_id', $ids],
                    'biodata_prestasi' => ['biodata_id', $ids],
                    'biodata_minat_bakat' => ['biodata_id', $ids],
                    'lamaran' => ['id', $lamaranIds],
                    'biodata' => ['id', $ids],
                ];
                $backup = ['database' => $database, 'created_at' => now()->toIso8601String(), 'tables' => []];
                foreach ($targets as $table => [$column, $values]) {
                    if (! Schema::hasTable($table)) {
                        unset($targets[$table]);
                        continue;
                    }
                    $backup['tables'][$table] = DB::table($table)->whereIn($column, $values)->lockForUpdate()->get()->all();
                }
                if (DB::getDriverName() === 'mysql') {
                    $engines = DB::table('information_schema.TABLES')->where('TABLE_SCHEMA', $database)
                        ->whereIn('TABLE_NAME', array_keys($targets))->pluck('ENGINE');
                    if ($engines->contains(fn ($engine) => $engine !== 'InnoDB')) {
                        throw new RuntimeException('Semua tabel target wajib memakai InnoDB agar rollback aman.');
                    }
                }
                $this->info('Database: ' . $database);
                $this->table(['Tabel', 'Jumlah'], collect($backup['tables'])->map(fn ($rows, $table) => [$table, count($rows)])->values()->all());
                if (! $this->option('execute')) {
                    $this->info('Pemeriksaan saja. Tidak ada data dihapus.');
                    return;
                }

                $path = 'private/orphan-backups/' . now()->format('Ymd-His') . '-' . Str::uuid() . '.json';
                $json = json_encode($backup, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                $disk = Storage::disk('local');
                if (! $disk->put($path, $json, ['visibility' => 'private']) || hash('sha256', $disk->get($path)) !== hash('sha256', $json)) {
                    throw new RuntimeException('Backup gagal diverifikasi; penghapusan dibatalkan.');
                }
                $this->line('Backup: ' . $disk->path($path));
                foreach ($targets as $table => [$column, $values]) {
                    $deleted = DB::table($table)->whereIn($column, $values)->delete();
                    if ($deleted !== count($backup['tables'][$table])) {
                        throw new RuntimeException("Jumlah penghapusan {$table} berubah. Transaksi dibatalkan.");
                    }
                }
            });
            if ($this->option('execute')) $this->info('Penghapusan berhasil di-commit. File dokumen tidak dihapus.');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
