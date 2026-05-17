<?php

namespace App\Jobs;

use App\Models\VhireIntegrationSyncLog;
use App\Models\VhireOnboardingCandidate;
use App\Services\Vhire\HrisIntegrationClient;
use App\Services\Vhire\OnboardingCandidateSyncService;
use App\Services\Vhire\VhireAuditLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncOnboardingCandidateToHris implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public $timeout = 120;

    protected $candidateId;

    public function __construct(int $candidateId)
    {
        $this->candidateId = $candidateId;
    }

    public function handle(HrisIntegrationClient $client, OnboardingCandidateSyncService $service): void
    {
        $candidate = VhireOnboardingCandidate::find($this->candidateId);

        if (! $candidate) {
            return;
        }

        $payload = $service->payload($candidate);
        $endpoint = '/api/hris/onboarding-candidates';
        $idempotencyKey = 'vhire-onboarding-' . $candidate->id . '-' . $candidate->updated_at->timestamp;
        $syncLog = VhireIntegrationSyncLog::create([
            'direction' => 'outbound',
            'method' => 'POST',
            'endpoint' => $endpoint,
            'status' => 'pending',
            'idempotency_key' => $idempotencyKey,
            'payload' => $payload,
            'related_type' => VhireOnboardingCandidate::class,
            'related_id' => $candidate->id,
            'attempts' => $candidate->retry_count + 1,
            'last_attempt_at' => now(),
        ]);

        $candidate->update([
            'sync_status' => 'pending_sync',
            'last_sync_attempt_at' => now(),
        ]);

        try {
            $response = $client->post($endpoint, $payload, $idempotencyKey);

            if (! $response->successful()) {
                throw new \RuntimeException('HRIS API gagal: HTTP ' . $response->status() . ' ' . $response->body());
            }

            $candidate->update([
                'sync_status' => 'synced_to_hris',
                'synced_at' => now(),
                'last_sync_error' => null,
                'last_sync_attempt_at' => now(),
            ]);

            $syncLog->update([
                'status' => 'success',
                'http_status' => $response->status(),
                'response_body' => $response->body(),
                'retry_available' => false,
            ]);

            app(VhireAuditLogger::class)->log('onboarding_candidate_synced_to_hris', $candidate, [], [
                'sync_status' => 'synced_to_hris',
            ], [], 'vhire');
        } catch (\Throwable $e) {
            $candidate->update([
                'sync_status' => 'failed_sync',
                'last_sync_error' => $e->getMessage(),
                'retry_count' => $candidate->retry_count + 1,
                'last_sync_attempt_at' => now(),
            ]);

            $syncLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'retry_available' => true,
            ]);

            app(VhireAuditLogger::class)->log('onboarding_candidate_sync_failed', $candidate, [], [
                'sync_status' => 'failed_sync',
                'error' => $e->getMessage(),
            ], [], 'vhire');
        }
    }
}
