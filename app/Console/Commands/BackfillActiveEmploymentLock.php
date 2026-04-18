<?php

namespace App\Console\Commands;

use App\Models\RiwayatProsesLamaran;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillActiveEmploymentLock extends Command
{
    protected $signature = 'users:backfill-active-employment-lock {--dry-run : Preview users that will be updated without saving changes}';

    protected $description = 'Backfill ket_resign dan employment_lock_active untuk users yang terdeteksi aktif bekerja';

    public function handle()
    {
        $isDryRun = (bool) $this->option('dry-run');
        $flaggedLegacyLocks = 0;
        $histories = RiwayatProsesLamaran::query()
            ->select('user_id', DB::raw('MAX(COALESCE(tanggal_proses, DATE(created_at))) as latest_active_date'))
            ->whereNotNull('user_id')
            ->whereRaw('LOWER(status_proses) = ?', ['aktif bekerja'])
            ->groupBy('user_id')
            ->orderBy('user_id')
            ->get();

        User::query()
            ->where('employment_lock_active', false)
            ->where('ket_resign', 'like', 'Aktif bekerja pada tanggal %')
            ->orderBy('id')
            ->chunkById(500, function ($users) use ($isDryRun, &$flaggedLegacyLocks) {
                foreach ($users as $user) {
                    if (! $user->hasLegacyActiveEmploymentKetResign()) {
                        continue;
                    }

                    if (! $isDryRun) {
                        $user->forceFill([
                            'employment_lock_active' => true,
                            'last_hris_sync_at' => null,
                        ])->save();
                    }

                    $flaggedLegacyLocks++;
                }
            });

        if ($histories->isEmpty()) {
            $summaryPrefix = $isDryRun ? '[DRY RUN] ' : '';

            $this->info($summaryPrefix . 'Backfill selesai.');
            $this->line('Legacy lock terflag: ' . $flaggedLegacyLocks);
            $this->line('Terupdate: 0');
            $this->line('Dilewati: 0');
            $this->line('User tidak ditemukan: 0');

            return Command::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;
        $missingUsers = 0;

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
        $this->line('Legacy lock terflag: ' . $flaggedLegacyLocks);
        $this->line('Terupdate: ' . $updated);
        $this->line('Dilewati: ' . $skipped);
        $this->line('User tidak ditemukan: ' . $missingUsers);

        return Command::SUCCESS;
    }
}
