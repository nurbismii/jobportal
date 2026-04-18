<?php

namespace App\Jobs;

use App\Services\EmploymentStatusRefreshService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessLockedEmploymentStatusRefreshChunkJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public $timeout = 300;

    protected $userIds;

    public function __construct(array $userIds)
    {
        $this->userIds = $userIds;
    }

    public function handle(EmploymentStatusRefreshService $employmentStatusRefreshService)
    {
        $summary = $employmentStatusRefreshService->refreshUsersByIds($this->userIds);

        Log::info('Locked employment status refresh chunk finished.', [
            'user_ids' => $this->userIds,
            'summary' => $summary,
        ]);
    }
}
