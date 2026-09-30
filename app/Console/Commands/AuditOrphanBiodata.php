<?php

namespace App\Console\Commands;

use App\Models\Biodata;
use Illuminate\Console\Command;

class AuditOrphanBiodata extends Command
{
    protected $signature = 'biodata:audit-orphans';

    protected $description = 'Audit biodata tanpa akun user, tanpa mengubah data';

    public function handle(): int
    {
        $query = Biodata::whereDoesntHave('user');
        $this->info('Jumlah biodata orphan: ' . $query->count());
        $this->line('Menampilkan maksimal 100 ID. Tidak ada data yang diubah.');
        $this->table(['Biodata ID', 'User ID lama', 'Jumlah lamaran'], $query
            ->withCount('getRiwayatLamaran')
            ->orderBy('id')->limit(100)->get()
            ->map(fn ($biodata) => [$biodata->id, $biodata->user_id, $biodata->get_riwayat_lamaran_count])
            ->all());

        return self::SUCCESS;
    }
}
