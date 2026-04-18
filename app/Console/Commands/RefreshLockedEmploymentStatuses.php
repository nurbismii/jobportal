<?php

namespace App\Console\Commands;

use App\Jobs\ProcessLockedEmploymentStatusRefreshChunkJob;
use App\Services\EmploymentStatusRefreshService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshLockedEmploymentStatuses extends Command
{
    protected $signature = 'users:refresh-locked-employment-statuses';

    protected $description = 'Dispatch queued refresh jobs for locked user employment statuses from HRIS';

    public function handle(EmploymentStatusRefreshService $employmentStatusRefreshService)
    {
        $chunkSize = max(1, (int) config('recruitment.locked_employment_refresh.chunk_size', 250));
        $queueName = config('recruitment.locked_employment_refresh.queue');
        $queuedUsers = 0;
        $queuedBatches = 0;

        $employmentStatusRefreshService->lockedUsersDueForRefreshQuery()
            ->select('users.id')
            ->orderBy('users.id')
            ->chunkById($chunkSize, function ($users) use (&$queuedUsers, &$queuedBatches, $queueName) {
                $userIds = $users->pluck('id')->all();

                if (empty($userIds)) {
                    return;
                }

                $dispatch = ProcessLockedEmploymentStatusRefreshChunkJob::dispatch($userIds);

                if (! blank($queueName)) {
                    $dispatch->onQueue($queueName);
                }

                $queuedUsers += count($userIds);
                $queuedBatches++;
            });

        $message = sprintf(
            'Dispatch refresh locked employment statuses selesai. User terantrikan: %d, batch: %d, chunk size: %d.',
            $queuedUsers,
            $queuedBatches,
            $chunkSize
        );

        $this->info($message);

        Log::info('Scheduled locked employment status refresh dispatched.', [
            'queued_users' => $queuedUsers,
            'queued_batches' => $queuedBatches,
            'chunk_size' => $chunkSize,
            'queue' => $queueName,
        ]);

        return Command::SUCCESS;
    }
}
