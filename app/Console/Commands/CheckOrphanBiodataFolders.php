<?php

namespace App\Console\Commands;

use App\Models\Biodata;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckOrphanBiodataFolders extends Command
{
    protected $signature = 'biodata:check-orphan-folders {--delete : Hapus folder yang tidak punya biodata}';

    protected $description = 'Mengecek folder public/{no_ktp} yang masih ada tetapi biodata sudah tidak ada';

    public function handle()
    {
        $publicPath = public_path();

        if (!File::exists($publicPath)) {
            $this->error('Folder public tidak ditemukan.');
            return Command::FAILURE;
        }

        $folders = File::directories($publicPath);

        $validNoKtp = Biodata::whereNotNull('no_ktp')
            ->pluck('no_ktp')
            ->map(function ($noKtp) {
                return trim((string) $noKtp);
            })
            ->filter()
            ->toArray();

        $validNoKtp = array_flip($validNoKtp);

        $orphanFolders = [];

        foreach ($folders as $folderPath) {
            $folderName = basename($folderPath);

            /**
             * Filter hanya folder yang kemungkinan folder biodata.
             * Kalau no_ktp selalu angka, pakai regex ini.
             * Biar folder seperti css, js, images, vendor tidak ikut dianggap yatim.
             */
            if (!preg_match('/^[0-9]{8,20}$/', $folderName)) {
                continue;
            }

            if (!isset($validNoKtp[$folderName])) {
                $orphanFolders[] = [
                    'folder_name' => $folderName,
                    'folder_path' => $folderPath,
                ];
            }
        }

        if (empty($orphanFolders)) {
            $this->info('Tidak ada folder yatim. Dunia digital masih sedikit tertib.');
            return Command::SUCCESS;
        }

        $this->warn('Ditemukan folder yang tidak memiliki biodata:');
        $this->newLine();

        foreach ($orphanFolders as $folder) {
            $this->line('- ' . $folder['folder_name'] . ' => ' . $folder['folder_path']);
        }

        $this->newLine();
        $this->info('Total folder yatim: ' . count($orphanFolders));

        if ($this->option('delete')) {
            if (!$this->confirm('Yakin ingin menghapus semua folder yatim ini?')) {
                $this->warn('Penghapusan dibatalkan.');
                return Command::SUCCESS;
            }

            foreach ($orphanFolders as $folder) {
                File::deleteDirectory($folder['folder_path']);
                $this->info('Dihapus: ' . $folder['folder_name']);
            }

            $this->newLine();
            $this->info('Semua folder yatim berhasil dihapus.');
        }

        return Command::SUCCESS;
    }
}
