<?php

namespace App\Console\Commands;

use App\Models\RiwayatProsesLamaran;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillActiveEmploymentLock extends Command
{
    protected $signature = 'users:backfill-active-employment-lock {--dry-run : Preview users that will be updated without saving changes}';

    protected $description = 'Backfill ket_resign for users whose application history shows they are actively employed';

    public function handle()
    {
        $histories = RiwayatProsesLamaran::query()
            ->select('user_id', DB::raw('MAX(COALESCE(tanggal_proses, DATE(created_at))) as latest_active_date'))
            ->whereNotNull('user_id')
            ->whereRaw('LOWER(status_proses) = ?', ['aktif bekerja'])
            ->groupBy('user_id')
            ->orderBy('user_id')
            ->get();

        if ($histories->isEmpty()) {
            $this->info('Tidak ada riwayat "Aktif bekerja" yang perlu dibackfill.');

            return Command::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;
        $missingUsers = 0;
        $isDryRun = (bool) $this->option('dry-run');

        $histories->chunk(500)->each(function ($chunk) use (&$updated, &$skipped, &$missingUsers, $isDryRun) {
            $users = User::whereIn('id', $chunk->pluck('user_id')->filter()->all())
                ->get()
                ->keyBy('id');

            foreach ($chunk as $history) {
                $user = $users->get($history->user_id);

                if (! $user) {
                    $missingUsers++;
                    continue;
                }

                if (! $user->needsActiveEmploymentLockBackfill()) {
                    $skipped++;
                    continue;
                }

                if ($isDryRun) {
                    $updated++;

                    if ($this->output->isVerbose()) {
                        $this->line(sprintf(
                            '[DRY RUN] User #%d -> %s',
                            $user->id,
                            User::activeEmploymentKetResign($history->latest_active_date)
                        ));
                    }

                    continue;
                }

                $user->markAsActiveEmployee($history->latest_active_date);
                $updated++;

                if ($this->output->isVerbose()) {
                    $this->line(sprintf(
                        'User #%d dibackfill ke "%s".',
                        $user->id,
                        $user->ket_resign
                    ));
                }
            }
        });

        $summaryPrefix = $isDryRun ? '[DRY RUN] ' : '';

        $this->info($summaryPrefix . 'Backfill selesai.');
        $this->line('Terupdate: ' . $updated);
        $this->line('Dilewati: ' . $skipped);
        $this->line('User tidak ditemukan: ' . $missingUsers);

        return Command::SUCCESS;
    }
}
