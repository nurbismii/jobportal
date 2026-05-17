<?php

namespace App\Jobs;

use App\Models\VhireIntegrationSyncLog;
use App\Models\VhirePkwtContract;
use App\Services\Vhire\HrisIntegrationClient;
use App\Services\Vhire\PkwtContractService;
use App\Services\Vhire\VhireAuditLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncContractSignatureStatusToHris implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public $timeout = 120;

    protected $contractId;

    public function __construct(int $contractId)
    {
        $this->contractId = $contractId;
    }

    public function handle(HrisIntegrationClient $client, PkwtContractService $service): void
    {
        $contract = VhirePkwtContract::find($this->contractId);

        if (! $contract) {
            return;
        }

        $payload = $service->signaturePayload($contract);
        $contractIdentifier = $contract->hris_contract_id ?: ($contract->kode_kontrak ?: $contract->id);
        $endpoint = '/api/hris/contracts/' . rawurlencode((string) $contractIdentifier) . '/signature-status';
        $idempotencyKey = 'vhire-signature-' . $contract->id . '-' . optional($contract->signed_at)->timestamp;
        $syncLog = VhireIntegrationSyncLog::create([
            'direction' => 'outbound',
            'method' => 'POST',
            'endpoint' => $endpoint,
            'status' => 'pending',
            'idempotency_key' => $idempotencyKey,
            'payload' => $payload,
            'related_type' => VhirePkwtContract::class,
            'related_id' => $contract->id,
            'attempts' => 1,
            'last_attempt_at' => now(),
        ]);

        try {
            $response = $client->post($endpoint, $payload, $idempotencyKey);

            if (! $response->successful()) {
                throw new \RuntimeException('HRIS API gagal: HTTP ' . $response->status() . ' ' . $response->body());
            }

            $contract->update(['last_hris_sync_error' => null]);
            $syncLog->update([
                'status' => 'success',
                'http_status' => $response->status(),
                'response_body' => $response->body(),
                'retry_available' => false,
            ]);

            app(VhireAuditLogger::class)->log('pkwt_signature_status_synced_to_hris', $contract, [], [
                'signature_status' => $contract->signature_status,
            ], [], 'vhire');
        } catch (\Throwable $e) {
            $contract->update(['last_hris_sync_error' => $e->getMessage()]);
            $syncLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'retry_available' => true,
            ]);

            app(VhireAuditLogger::class)->log('pkwt_signature_status_sync_failed', $contract, [], [
                'error' => $e->getMessage(),
            ], [], 'vhire');
        }
    }
}
