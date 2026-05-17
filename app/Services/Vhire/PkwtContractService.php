<?php

namespace App\Services\Vhire;

use App\Jobs\SyncContractSignatureStatusToHris;
use App\Models\User;
use App\Models\VhireOnboardingCandidate;
use App\Models\VhirePkwtContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PkwtContractService
{
    public function importFromHris(array $data): VhirePkwtContract
    {
        return DB::transaction(function () use ($data) {
            $noKtp = preg_replace('/\D+/', '', (string) $data['no_ktp']);

            if (! preg_match('/^\d{16}$/', $noKtp)) {
                throw new InvalidArgumentException('No KTP harus 16 digit numerik.');
            }

            $candidate = $this->matchOnboardingCandidate($data, $noKtp);
            $contract = $this->matchContract($data);
            $old = $contract ? $contract->toArray() : [];
            $settingService = app(PkwtContractSettingService::class);
            $durationValue = (int) ($data['duration_value'] ?? $data['contract_duration_value'] ?? optional($candidate)->contract_duration_value ?? $settingService->pkwt1()->duration_value);
            $durationUnit = (string) ($data['duration_unit'] ?? $data['contract_duration_unit'] ?? optional($candidate)->contract_duration_unit ?? $settingService->pkwt1()->duration_unit);
            $startDate = $data['tanggal_mulai_kontrak'] ?? null;
            $endDate = $data['tanggal_akhir_kontrak'] ?? null;

            if (! $endDate && $startDate) {
                $endDate = optional($settingService->calculateEndDate($startDate, $durationValue, $durationUnit))->format('Y-m-d');
            }

            $signingMethod = $data['signing_method'] ?? optional($candidate)->signing_method ?? 'electronic';

            if (! in_array($signingMethod, ['electronic', 'manual'], true)) {
                $signingMethod = 'electronic';
            }

            $employeeNik = $data['employee_nik'] ?? optional($contract)->employee_nik;
            $visible = array_key_exists('visible_in_vhire', $data)
                ? (bool) $data['visible_in_vhire']
                : $signingMethod === 'electronic';

            if ($signingMethod === 'manual' && ! array_key_exists('visible_in_vhire', $data)) {
                $visible = false;
            }

            if (! blank($employeeNik)) {
                $visible = false;
            }

            $attributes = [
                'onboarding_candidate_id' => $candidate ? $candidate->id : null,
                'hris_contract_id' => $data['hris_contract_id'] ?? optional($contract)->hris_contract_id,
                'vhire_candidate_id' => $data['vhire_candidate_id'],
                'candidate_code' => $data['candidate_code'],
                'no_ktp' => $noKtp,
                'nama' => $data['nama'],
                'kode_kontrak' => $data['kode_kontrak'] ?? optional($contract)->kode_kontrak,
                'no_pkwt' => $data['no_pkwt'] ?? optional($contract)->no_pkwt,
                'jabatan' => $data['jabatan'] ?? null,
                'departemen' => $data['departemen'] ?? null,
                'lokasi' => $data['lokasi'] ?? null,
                'tanggal_mulai_kontrak' => $startDate,
                'tanggal_akhir_kontrak' => $endDate,
                'duration_value' => $durationValue,
                'duration_unit' => $durationUnit,
                'durasi_kontrak' => $data['durasi_kontrak'] ?? $settingService->durationLabel($durationValue, $durationUnit),
                'gaji' => $data['gaji'] ?? null,
                'status_tanda_tangan' => $data['status_tanda_tangan'] ?? $this->signatureStatusToStatusTandaTangan($data['signature_status'] ?? 'waiting_signature'),
                'signature_status' => $data['signature_status'] ?? ($signingMethod === 'manual' ? 'waiting_signature' : 'waiting_signature'),
                'signing_method' => $signingMethod,
                'signed_at' => $data['signed_at'] ?? optional($contract)->signed_at,
                'signed_by_source' => $data['signed_by_source'] ?? optional($contract)->signed_by_source,
                'visible_in_vhire' => $visible,
                'hidden_reason' => $visible ? null : ($data['hidden_reason'] ?? (! blank($employeeNik) ? 'Kandidat sudah aktif sebagai karyawan HRIS' : ($signingMethod === 'manual' ? 'Kontrak diproses manual di HRIS' : optional($contract)->hidden_reason))),
                'hidden_at' => $visible ? null : ($data['hidden_at'] ?? optional($contract)->hidden_at ?? now()),
                'employee_nik' => $employeeNik,
                'activated_as_employee_at' => $data['activated_as_employee_at'] ?? optional($contract)->activated_as_employee_at,
                'manual_uploaded_by' => $data['manual_uploaded_by'] ?? optional($contract)->manual_uploaded_by,
                'manual_uploaded_at' => $data['manual_uploaded_at'] ?? optional($contract)->manual_uploaded_at,
                'manual_verification_status' => $data['manual_verification_status'] ?? optional($contract)->manual_verification_status,
                'manual_note' => $data['manual_note'] ?? optional($contract)->manual_note,
                'contract_content' => $data['contract_content'] ?? optional($contract)->contract_content,
                'source_payload' => $this->sanitizePayload($data),
                'last_imported_at' => now(),
                'last_hris_sync_error' => null,
            ];

            $attributes = array_merge($attributes, $this->storeImportedFiles($data, $attributes['candidate_code'], $contract));

            if ($contract) {
                $contract->fill($attributes)->save();
            } else {
                $contract = VhirePkwtContract::create($attributes);
            }

            if ($candidate) {
                $candidate->update([
                    'onboarding_status' => 'contract_generated',
                    'sync_status' => 'contract_generated',
                ]);
            }

            $freshContract = $contract->fresh();

            app(VhireAuditLogger::class)->log(
                $old ? 'pkwt_contract_imported_updated' : 'pkwt_contract_imported_created',
                $freshContract,
                $old,
                $freshContract->toArray(),
                ['source' => 'hris'],
                'hris'
            );

            $this->logSpecificImportEvents($freshContract, $old, $data);

            return $freshContract;
        });
    }

    public function updateVisibility(VhirePkwtContract $contract, bool $visible, ?string $hiddenReason, string $source = 'admin'): VhirePkwtContract
    {
        if ($visible && ! blank($contract->employee_nik)) {
            throw new InvalidArgumentException('Kontrak kandidat yang sudah memiliki NIK HRIS tidak dapat dibuat visible di V-Hire.');
        }

        $old = $contract->toArray();
        $contract->forceFill([
            'visible_in_vhire' => $visible,
            'hidden_reason' => $visible ? null : ($hiddenReason ?: 'Disembunyikan dari V-Hire'),
            'hidden_at' => $visible ? null : now(),
        ])->save();

        app(VhireAuditLogger::class)->log('pkwt_contract_visibility_changed', $contract, $old, $contract->fresh()->toArray(), [], $source);

        return $contract->fresh();
    }

    public function markActivated(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $noKtp = isset($data['no_ktp']) ? preg_replace('/\D+/', '', (string) $data['no_ktp']) : null;
            $employeeNik = $data['employee_nik'];
            $activatedAt = $data['activated_as_employee_at'] ?? now();

            $query = VhirePkwtContract::query()
                ->where('vhire_candidate_id', $data['vhire_candidate_id']);

            if (! blank($data['candidate_code'] ?? null) || ! blank($noKtp)) {
                $query->orWhere(function ($subQuery) use ($data, $noKtp) {
                    if (! blank($data['candidate_code'] ?? null)) {
                        $subQuery->where('candidate_code', $data['candidate_code']);
                    }

                    if (! blank($noKtp)) {
                        $subQuery->orWhere('no_ktp', $noKtp);
                    }
                });
            }

            $contracts = $query->get();

            foreach ($contracts as $contract) {
                $old = $contract->toArray();

                $contract->forceFill([
                    'employee_nik' => $employeeNik,
                    'visible_in_vhire' => false,
                    'hidden_reason' => 'Kandidat sudah aktif sebagai karyawan HRIS',
                    'hidden_at' => now(),
                    'activated_as_employee_at' => $activatedAt,
                ])->save();

                app(VhireAuditLogger::class)->log('candidate_activated_as_employee', $contract, $old, $contract->fresh()->toArray(), [], 'hris');
            }

            VhireOnboardingCandidate::where('vhire_candidate_id', $data['vhire_candidate_id'])
                ->when(! blank($data['candidate_code'] ?? null), function ($query) use ($data) {
                    $query->orWhere('candidate_code', $data['candidate_code']);
                })
                ->when(! blank($noKtp), function ($query) use ($noKtp) {
                    $query->orWhere('no_ktp', $noKtp);
                })
                ->update([
                    'onboarding_status' => 'activated_as_employee',
                    'sync_status' => 'activated_as_employee',
                ]);

            return $contracts->count();
        });
    }

    public function signElectronically(VhirePkwtContract $contract, User $user): VhirePkwtContract
    {
        $candidateNoKtp = preg_replace('/\D+/', '', (string) ($user->no_ktp ?? optional($user->biodata)->no_ktp));

        if ($candidateNoKtp !== $contract->no_ktp) {
            throw new InvalidArgumentException('Kontrak tidak sesuai dengan kandidat yang login.');
        }

        if (! $contract->isVisibleForCandidate()) {
            throw new InvalidArgumentException('Kontrak tidak tersedia untuk tanda tangan elektronik di V-Hire.');
        }

        $old = $contract->toArray();

        $contract->forceFill([
            'signature_status' => 'signed',
            'status_tanda_tangan' => 'signed',
            'signed_at' => now(),
            'signed_by_source' => 'vhire',
        ])->save();

        app(VhireAuditLogger::class)->log('pkwt_contract_signed_electronically', $contract, $old, $contract->fresh()->toArray(), [], 'vhire');

        SyncContractSignatureStatusToHris::dispatch($contract->id)
            ->onQueue((string) config('recruitment.hris_api.queue', 'default'));

        return $contract->fresh();
    }

    public function visibleContractsForUser(User $user)
    {
        $noKtp = preg_replace('/\D+/', '', (string) ($user->no_ktp ?? optional($user->biodata)->no_ktp));

        return VhirePkwtContract::where('no_ktp', $noKtp)
            ->where('visible_in_vhire', true)
            ->whereNull('employee_nik')
            ->where('signing_method', 'electronic')
            ->whereIn('signature_status', ['draft', 'waiting_signature'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function signaturePayload(VhirePkwtContract $contract): array
    {
        return [
            'hris_contract_id' => $contract->hris_contract_id,
            'kode_kontrak' => $contract->kode_kontrak,
            'no_pkwt' => $contract->no_pkwt,
            'vhire_candidate_id' => $contract->vhire_candidate_id,
            'candidate_code' => $contract->candidate_code,
            'no_ktp' => $contract->no_ktp,
            'signature_status' => $contract->signature_status,
            'status_tanda_tangan' => $contract->status_tanda_tangan,
            'signed_at' => optional($contract->signed_at)->toIso8601String(),
            'signed_by_source' => $contract->signed_by_source,
        ];
    }

    private function matchOnboardingCandidate(array $data, string $noKtp): ?VhireOnboardingCandidate
    {
        return VhireOnboardingCandidate::where('vhire_candidate_id', $data['vhire_candidate_id'])
            ->orWhere('candidate_code', $data['candidate_code'])
            ->orWhere('no_ktp', $noKtp)
            ->first();
    }

    private function matchContract(array $data): ?VhirePkwtContract
    {
        if (
            blank($data['hris_contract_id'] ?? null)
            && blank($data['kode_kontrak'] ?? null)
            && blank($data['no_pkwt'] ?? null)
        ) {
            return null;
        }

        return VhirePkwtContract::query()
            ->when(! blank($data['hris_contract_id'] ?? null), function ($query) use ($data) {
                $query->orWhere('hris_contract_id', $data['hris_contract_id']);
            })
            ->when(! blank($data['kode_kontrak'] ?? null), function ($query) use ($data) {
                $query->orWhere('kode_kontrak', $data['kode_kontrak']);
            })
            ->when(! blank($data['no_pkwt'] ?? null), function ($query) use ($data) {
                $query->orWhere('no_pkwt', $data['no_pkwt']);
            })
            ->first();
    }

    private function storeImportedFiles(array $data, string $candidateCode, ?VhirePkwtContract $contract): array
    {
        $files = [];
        $fileService = app(PkwtContractFileService::class);

        if (! blank($data['contract_file_base64'] ?? null)) {
            $stored = $fileService->storeBase64(
                $data['contract_file_base64'],
                $candidateCode,
                'kontrak-pkwt',
                $data['contract_file_name'] ?? null,
                $data['contract_file_mime'] ?? null
            );

            $files['contract_file_disk'] = $stored['disk'];
            $files['contract_file_path'] = $stored['path'];
            $files['contract_file_name'] = $stored['name'];
            $files['contract_file_mime'] = $stored['mime'];
        } elseif ($contract) {
            $files['contract_file_disk'] = $contract->contract_file_disk;
            $files['contract_file_path'] = $contract->contract_file_path;
            $files['contract_file_name'] = $contract->contract_file_name;
            $files['contract_file_mime'] = $contract->contract_file_mime;
        }

        if (! blank($data['manual_signed_file_base64'] ?? null)) {
            $stored = $fileService->storeBase64(
                $data['manual_signed_file_base64'],
                $candidateCode,
                'manual-signed-pkwt',
                $data['manual_signed_file_name'] ?? null,
                $data['manual_signed_file_mime'] ?? null
            );

            $files['manual_signed_file_disk'] = $stored['disk'];
            $files['manual_signed_file_path'] = $stored['path'];
            $files['manual_signed_file_name'] = $stored['name'];
            $files['manual_signed_file_mime'] = $stored['mime'];
            $files['manual_uploaded_at'] = $data['manual_uploaded_at'] ?? now();
            $files['signed_by_source'] = $data['signed_by_source'] ?? 'manual_upload';
        } elseif ($contract) {
            $files['manual_signed_file_disk'] = $contract->manual_signed_file_disk;
            $files['manual_signed_file_path'] = $contract->manual_signed_file_path;
            $files['manual_signed_file_name'] = $contract->manual_signed_file_name;
            $files['manual_signed_file_mime'] = $contract->manual_signed_file_mime;
        }

        return $files;
    }

    private function logSpecificImportEvents(VhirePkwtContract $contract, array $old, array $data): void
    {
        $logger = app(VhireAuditLogger::class);

        if (($old['signing_method'] ?? null) && ($old['signing_method'] ?? null) !== $contract->signing_method) {
            $logger->log('pkwt_contract_signing_method_changed', $contract, [
                'signing_method' => $old['signing_method'],
            ], [
                'signing_method' => $contract->signing_method,
            ], [], 'hris');
        }

        if (! blank($data['manual_signed_file_base64'] ?? null)) {
            $logger->log('pkwt_contract_manual_file_uploaded', $contract, [], [
                'manual_signed_file_path' => '[private-storage]',
                'manual_signed_file_name' => $contract->manual_signed_file_name,
                'manual_uploaded_at' => optional($contract->manual_uploaded_at)->toIso8601String(),
            ], [], 'hris');
        }

        if (
            array_key_exists('manual_verification_status', $data)
            && ($old['manual_verification_status'] ?? null) !== $contract->manual_verification_status
        ) {
            $logger->log('pkwt_contract_manual_verification_changed', $contract, [
                'manual_verification_status' => $old['manual_verification_status'] ?? null,
            ], [
                'manual_verification_status' => $contract->manual_verification_status,
                'manual_note' => $contract->manual_note,
            ], [], 'hris');
        }

        if ($contract->signing_method === 'electronic' && $contract->visible_in_vhire) {
            $logger->log('pkwt_contract_sent_to_vhire', $contract, [], [
                'visible_in_vhire' => true,
                'signature_status' => $contract->signature_status,
            ], [], 'hris');
        }
    }

    private function signatureStatusToStatusTandaTangan(string $signatureStatus): string
    {
        return $signatureStatus === 'signed' ? 'signed' : $signatureStatus;
    }

    private function sanitizePayload(array $data): array
    {
        if (array_key_exists('no_ktp', $data)) {
            $data['no_ktp'] = mask_no_ktp($data['no_ktp']);
        }

        unset($data['contract_file_base64'], $data['manual_signed_file_base64']);

        return $data;
    }
}
