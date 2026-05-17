<?php

namespace App\Services\Vhire;

use App\Models\VhireIntegrationAuditLog;
use App\Models\VhirePkwtContract;
use App\Models\VhirePkwtContractHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class VhireAuditLogger
{
    public function log(string $event, ?Model $model = null, array $oldValues = [], array $newValues = [], array $metadata = [], string $source = 'system'): void
    {
        $request = request();
        $user = Auth::user();

        $candidateId = $model ? ($model->vhire_candidate_id ?? null) : ($metadata['vhire_candidate_id'] ?? null);
        $candidateCode = $model ? ($model->candidate_code ?? null) : ($metadata['candidate_code'] ?? null);
        $noKtp = $model ? ($model->no_ktp ?? null) : ($metadata['no_ktp'] ?? null);

        VhireIntegrationAuditLog::create([
            'event' => $event,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id' => $model ? $model->getKey() : null,
            'vhire_candidate_id' => $candidateId,
            'candidate_code' => $candidateCode,
            'no_ktp_masked' => $noKtp ? mask_no_ktp($noKtp) : null,
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'metadata' => $this->sanitize($metadata),
            'source' => $source,
            'actor_id' => $user ? $user->id : null,
            'actor_name' => $user ? $user->name : null,
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 255) : null,
        ]);

        if ($model instanceof VhirePkwtContract) {
            VhirePkwtContractHistory::create([
                'contract_id' => $model->id,
                'event' => $event,
                'old_values' => $this->sanitize($oldValues),
                'new_values' => $this->sanitize($newValues),
                'source' => $source,
                'actor_id' => $user ? $user->id : null,
                'actor_name' => $user ? $user->name : null,
                'ip_address' => $request ? $request->ip() : null,
                'user_agent' => $request ? substr((string) $request->userAgent(), 0, 255) : null,
                'occurred_at' => now(),
            ]);
        }
    }

    private function sanitize(array $values): array
    {
        if (array_key_exists('no_ktp', $values)) {
            $values['no_ktp'] = mask_no_ktp($values['no_ktp']);
        }

        foreach (['contract_file_base64', 'manual_signed_file_base64'] as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = '[base64-file]';
            }
        }

        return $values;
    }
}
