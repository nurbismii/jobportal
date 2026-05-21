<?php

namespace App\Services\Vhire;

use App\Jobs\SyncContractSignatureStatusToHris;
use App\Models\Biodata;
use App\Models\Lamaran;
use App\Models\User;
use App\Models\VhireOnboardingCandidate;
use App\Models\VhirePkwtContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

            $candidateMatch = $this->matchCandidateByNoKtp($noKtp);
            $candidate = $candidateMatch ? $this->ensureOnboardingCandidate($data, $noKtp, $candidateMatch) : null;
            $contract = $this->matchContract($data, $noKtp);
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
                'match_status' => $candidateMatch ? 'matched_to_candidate' : 'pending_match',
                'matched_biodata_id' => $candidateMatch ? optional($candidateMatch['biodata'])->id : null,
                'matched_user_id' => $candidateMatch ? optional($candidateMatch['user'])->id : null,
                'matched_lamaran_id' => $candidateMatch ? optional($candidateMatch['lamaran'])->id : null,
                'matched_at' => $candidateMatch ? now() : null,
                'hris_contract_id' => $data['hris_contract_id'] ?? optional($contract)->hris_contract_id,
                'vhire_candidate_id' => $this->resolveVhireCandidateId($data, $candidateMatch, $contract),
                'candidate_code' => $this->resolveCandidateCode($data, $candidateMatch, $contract),
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

            app(VhireAuditLogger::class)->log(
                $freshContract->match_status === 'matched_to_candidate' ? 'pkwt_contract_matched_to_candidate' : 'pkwt_contract_pending_match',
                $freshContract,
                $old ? ['match_status' => $old['match_status'] ?? null] : [],
                [
                    'match_status' => $freshContract->match_status,
                    'matched_biodata_id' => $freshContract->matched_biodata_id,
                    'matched_lamaran_id' => $freshContract->matched_lamaran_id,
                ],
                ['matching_identifier' => 'no_ktp'],
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

            $query = VhirePkwtContract::query();

            $query->where(function ($subQuery) use ($data, $noKtp) {
                if (! blank($data['vhire_candidate_id'] ?? null)) {
                    $subQuery->where('vhire_candidate_id', $data['vhire_candidate_id']);
                }

                if (! blank($data['candidate_code'] ?? null)) {
                    $method = blank($data['vhire_candidate_id'] ?? null) ? 'where' : 'orWhere';
                    $subQuery->{$method}('candidate_code', $data['candidate_code']);
                }

                if (! blank($noKtp)) {
                    $method = blank($data['vhire_candidate_id'] ?? null) && blank($data['candidate_code'] ?? null) ? 'where' : 'orWhere';
                    $subQuery->{$method}('no_ktp', $noKtp);
                }
            });

            if (blank($data['vhire_candidate_id'] ?? null) && blank($data['candidate_code'] ?? null) && blank($noKtp)) {
                return 0;
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

            VhireOnboardingCandidate::where(function ($query) use ($data, $noKtp) {
                    if (! blank($data['vhire_candidate_id'] ?? null)) {
                        $query->where('vhire_candidate_id', $data['vhire_candidate_id']);
                    }

                    if (! blank($data['candidate_code'] ?? null)) {
                        $method = blank($data['vhire_candidate_id'] ?? null) ? 'where' : 'orWhere';
                        $query->{$method}('candidate_code', $data['candidate_code']);
                    }

                    if (! blank($noKtp)) {
                        $method = blank($data['vhire_candidate_id'] ?? null) && blank($data['candidate_code'] ?? null) ? 'where' : 'orWhere';
                        $query->{$method}('no_ktp', $noKtp);
                    }
                })
                ->update([
                    'onboarding_status' => 'activated_as_employee',
                    'sync_status' => 'activated_as_employee',
                ]);

            return $contracts->count();
        });
    }

    public function signElectronically(VhirePkwtContract $contract, User $user, ?string $signatureData = null): VhirePkwtContract
    {
        $candidateNoKtp = preg_replace('/\D+/', '', (string) ($user->no_ktp ?? optional($user->biodata)->no_ktp));

        if ($candidateNoKtp !== $contract->no_ktp) {
            throw new InvalidArgumentException('Kontrak tidak sesuai dengan kandidat yang login.');
        }

        if (! $contract->isSignableByCandidate()) {
            throw new InvalidArgumentException('Kontrak tidak tersedia untuk tanda tangan elektronik di V-Hire.');
        }

        $storedSignature = $this->storeCandidateSignature($contract, $signatureData);
        $signatureMetadata = [
            'signature_hash' => $storedSignature['hash'],
            'signature_size' => $storedSignature['size'],
        ];
        $old = $contract->toArray();

        $contract->forceFill([
            'signature_status' => 'signed',
            'status_tanda_tangan' => 'signed',
            'signed_at' => now(),
            'signed_by_source' => 'vhire',
            'signature_file_disk' => $storedSignature['disk'],
            'signature_file_path' => $storedSignature['path'],
            'signature_file_mime' => $storedSignature['mime'],
            'signature_file_hash' => $storedSignature['hash'],
        ])->save();

        app(VhireAuditLogger::class)->log('pkwt_contract_signed_electronically', $contract, $old, $contract->fresh()->toArray(), $signatureMetadata, 'vhire');

        SyncContractSignatureStatusToHris::dispatch($contract->id)
            ->onQueue((string) config('recruitment.hris_api.queue', 'default'));

        return $contract->fresh();
    }

    private function storeCandidateSignature(VhirePkwtContract $contract, ?string $signatureData): array
    {
        if (! is_string($signatureData) || ! preg_match('/^data:image\/png;base64,/', $signatureData)) {
            throw new InvalidArgumentException('Tanda tangan kandidat wajib diisi.');
        }

        $decoded = base64_decode(substr($signatureData, strpos($signatureData, ',') + 1), true);

        if ($decoded === false || strlen($decoded) < 100) {
            throw new InvalidArgumentException('Data tanda tangan tidak dapat dibaca.');
        }

        if (strlen($decoded) > 1024 * 1024) {
            throw new InvalidArgumentException('Ukuran tanda tangan terlalu besar.');
        }

        $hash = hash('sha256', $decoded);
        $disk = (string) config('recruitment.pkwt_contracts.disk', config('filesystems.default', 'local'));
        $directoryKey = preg_replace('/[^A-Za-z0-9\-]+/', '-', (string) ($contract->candidate_code ?: $contract->no_ktp ?: $contract->id));
        $path = sprintf(
            'pkwt-contract-signatures/%s/%s/%s.png',
            $directoryKey ?: 'candidate',
            $contract->id,
            Str::uuid()
        );

        Storage::disk($disk)->put($path, $decoded);

        return [
            'disk' => $disk,
            'path' => $path,
            'mime' => 'image/png',
            'hash' => $hash,
            'size' => strlen($decoded),
        ];
    }

    public function visibleContractsForUser(User $user)
    {
        $noKtp = preg_replace('/\D+/', '', (string) ($user->no_ktp ?? optional($user->biodata)->no_ktp));

        return VhirePkwtContract::where('no_ktp', $noKtp)
            ->where('visible_in_vhire', true)
            ->whereNull('employee_nik')
            ->where('match_status', 'matched_to_candidate')
            ->where('signing_method', 'electronic')
            ->orderByDesc('created_at')
            ->get();
    }

    public function signaturePayload(VhirePkwtContract $contract): array
    {
        $payload = array_merge([
            'hris_contract_id' => $contract->hris_contract_id,
            'kode_kontrak' => $contract->kode_kontrak,
            'no_pkwt' => $contract->no_pkwt,
            'vhire_candidate_id' => $this->publicVhireCandidateId($contract),
            'candidate_code' => $contract->candidate_code,
            'no_ktp' => $contract->no_ktp,
            'signature_status' => $contract->signature_status,
            'status_tanda_tangan' => $contract->status_tanda_tangan,
            'signed_at' => optional($contract->signed_at)->toIso8601String(),
            'signed_by_source' => $contract->signed_by_source,
        ], $this->candidateProfilePayloadForHris($contract));

        if (
            $contract->signature_status === 'signed'
            && $contract->signature_file_disk
            && $contract->signature_file_path
            && Storage::disk($contract->signature_file_disk)->exists($contract->signature_file_path)
        ) {
            $payload['employee_signature_base64'] = base64_encode(
                Storage::disk($contract->signature_file_disk)->get($contract->signature_file_path)
            );
            $payload['employee_signature_mime'] = $contract->signature_file_mime ?: 'image/png';
            $payload['employee_signature_hash'] = $contract->signature_file_hash;
        }

        return $payload;
    }

    public function rematch(VhirePkwtContract $contract, ?int $actorId = null): VhirePkwtContract
    {
        $candidateMatch = $this->matchCandidateByNoKtp($contract->no_ktp);

        if (! $candidateMatch) {
            throw new InvalidArgumentException('Kandidat dengan No KTP tersebut belum ditemukan di V-Hire.');
        }

        $old = $contract->toArray();
        $candidate = $this->ensureOnboardingCandidate($contract->toArray(), $contract->no_ktp, $candidateMatch);

        $contract->forceFill([
            'onboarding_candidate_id' => $candidate->id,
            'match_status' => 'matched_to_candidate',
            'matched_biodata_id' => optional($candidateMatch['biodata'])->id,
            'matched_user_id' => optional($candidateMatch['user'])->id,
            'matched_lamaran_id' => optional($candidateMatch['lamaran'])->id,
            'matched_at' => now(),
            'matched_by' => $actorId,
            'vhire_candidate_id' => $this->resolveVhireCandidateId($contract->toArray(), $candidateMatch, $contract),
        ])->save();

        app(VhireAuditLogger::class)->log('pkwt_contract_rematched_to_candidate', $contract, $old, $contract->fresh()->toArray(), [
            'matching_identifier' => 'no_ktp',
        ], 'admin');

        return $contract->fresh();
    }

    private function matchContract(array $data, string $noKtp): ?VhirePkwtContract
    {
        if (! blank($data['hris_contract_id'] ?? null)) {
            return VhirePkwtContract::where('hris_contract_id', $data['hris_contract_id'])->first();
        }

        if (! blank($data['no_pkwt'] ?? null)) {
            return VhirePkwtContract::where('no_ktp', $noKtp)
                ->where('no_pkwt', $data['no_pkwt'])
                ->first();
        }

        if (! blank($data['kode_kontrak'] ?? null)) {
            return VhirePkwtContract::where('no_ktp', $noKtp)
                ->where('kode_kontrak', $data['kode_kontrak'])
                ->first();
        }

        return null;
    }

    private function matchCandidateByNoKtp(string $noKtp): ?array
    {
        $biodata = Biodata::with('user')
            ->where('no_ktp', $noKtp)
            ->first();

        if (! $biodata) {
            $user = User::where('no_ktp', $noKtp)->first();

            if (! $user) {
                return null;
            }

            $biodata = $user->biodata;
        }

        if (! $biodata) {
            return null;
        }

        $lamaran = Lamaran::where('biodata_id', $biodata->id)
            ->latest('id')
            ->first();

        return [
            'biodata' => $biodata,
            'user' => $biodata->user ?: User::where('no_ktp', $noKtp)->first(),
            'lamaran' => $lamaran,
        ];
    }

    private function ensureOnboardingCandidate(array $data, string $noKtp, array $candidateMatch): VhireOnboardingCandidate
    {
        $setting = app(PkwtContractSettingService::class)->pkwt1();
        $lamaran = $candidateMatch['lamaran'] ?? null;
        $biodata = $candidateMatch['biodata'] ?? null;
        $user = $candidateMatch['user'] ?? null;
        $profilePayload = $this->candidateProfilePayloadFromMatch($biodata, $user, $lamaran);
        $candidateCode = $this->resolveCandidateCode($data, $candidateMatch, null);
        $vhireCandidateId = $this->resolveVhireCandidateId($data, $candidateMatch, null);

        $lookup = $lamaran
            ? ['lamaran_id' => $lamaran->id]
            : ['candidate_code' => $candidateCode];

        return VhireOnboardingCandidate::updateOrCreate(
            $lookup,
            [
                'lamaran_id' => $lamaran ? $lamaran->id : null,
                'biodata_id' => $biodata ? $biodata->id : null,
                'user_id' => $user ? $user->id : null,
                'vhire_candidate_id' => $vhireCandidateId,
                'candidate_code' => $candidateCode,
                'no_ktp' => $noKtp,
                'nama' => $data['nama'] ?? optional($user)->name ?? '',
                'jabatan' => $data['jabatan'] ?? null,
                'tanggal_mulai_kerja' => $data['tanggal_mulai_kontrak'] ?? null,
                'departemen' => $data['departemen'] ?? null,
                'lokasi' => $data['lokasi'] ?? null,
                'recruitment_status' => optional($lamaran)->status_proses ?: 'proses_tanda_tangan_kontrak',
                'onboarding_status' => 'contract_generated',
                'contract_duration_value' => (int) ($data['duration_value'] ?? $setting->duration_value),
                'contract_duration_unit' => (string) ($data['duration_unit'] ?? $setting->duration_unit),
                'signing_method' => $data['signing_method'] ?? 'electronic',
                'payload' => $this->compactPayload(array_merge($data, $profilePayload)),
                'sync_status' => 'contract_generated',
                'last_sync_error' => null,
            ]
        );
    }

    private function candidateProfilePayloadForHris(VhirePkwtContract $contract): array
    {
        $contract->loadMissing(
            'matchedBiodata.user',
            'matchedUser',
            'matchedLamaran.lowongan.permintaanTenagaKerja.departemen',
            'matchedLamaran.lowongan.permintaanTenagaKerja.divisi',
            'onboardingCandidate.biodata.user',
            'onboardingCandidate.user',
            'onboardingCandidate.lamaran.lowongan.permintaanTenagaKerja.departemen',
            'onboardingCandidate.lamaran.lowongan.permintaanTenagaKerja.divisi'
        );

        $biodata = $contract->matchedBiodata ?: optional($contract->onboardingCandidate)->biodata;
        $user = $contract->matchedUser ?: optional($biodata)->user ?: optional($contract->onboardingCandidate)->user;
        $lamaran = $contract->matchedLamaran ?: optional($contract->onboardingCandidate)->lamaran;

        return $this->candidateProfilePayloadFromMatch($biodata, $user, $lamaran);
    }

    private function candidateProfilePayloadFromMatch($biodata, $user, $lamaran): array
    {
        $lowongan = optional($lamaran)->lowongan;
        $ptk = optional($lowongan)->permintaanTenagaKerja;

        return $this->compactPayload([
            'departemen' => $this->stringOrNull(optional(optional($ptk)->departemen)->departemen),
            'departemen_id' => $this->integerOrNull(optional($ptk)->departemen_id),
            'divisi' => $this->stringOrNull(optional(optional($ptk)->divisi)->nama_divisi),
            'divisi_id' => $this->integerOrNull(optional($ptk)->divisi_id),
            'provinsi_id' => $this->integerOrNull(optional($biodata)->provinsi),
            'kabupaten_id' => $this->integerOrNull(optional($biodata)->kabupaten),
            'kecamatan_id' => $this->integerOrNull(optional($biodata)->kecamatan),
            'kelurahan_id' => $this->integerOrNull(optional($biodata)->kelurahan),
            'kode_area_kerja' => $this->stringOrNull(optional($user)->area_kerja),
        ]);
    }

    private function resolveVhireCandidateId(array $data, ?array $candidateMatch, ?VhirePkwtContract $contract): string
    {
        if (! blank($data['vhire_candidate_id'] ?? null)) {
            return $data['vhire_candidate_id'];
        }

        if ($candidateMatch && ! blank(optional($candidateMatch['lamaran'] ?? null)->id)) {
            return 'LAMARAN-' . $candidateMatch['lamaran']->id;
        }

        if ($candidateMatch && ! blank(optional($candidateMatch['biodata'] ?? null)->id)) {
            return 'BIODATA-' . $candidateMatch['biodata']->id;
        }

        if ($contract && ! Str::startsWith((string) $contract->vhire_candidate_id, 'UNMATCHED-')) {
            return $contract->vhire_candidate_id;
        }

        $stableKey = $data['hris_contract_id'] ?? $data['no_pkwt'] ?? $data['kode_kontrak'] ?? $data['no_ktp'] ?? Str::random(12);

        return 'UNMATCHED-' . Str::slug((string) $stableKey);
    }

    private function publicVhireCandidateId(VhirePkwtContract $contract): ?string
    {
        return Str::startsWith((string) $contract->vhire_candidate_id, ['UNMATCHED-', 'LAMARAN-', 'BIODATA-'])
            ? null
            : $contract->vhire_candidate_id;
    }

    private function resolveCandidateCode(array $data, ?array $candidateMatch, ?VhirePkwtContract $contract): string
    {
        if (! blank($data['candidate_code'] ?? null)) {
            return $data['candidate_code'];
        }

        if ($contract && ! blank($contract->candidate_code)) {
            return $contract->candidate_code;
        }

        if ($candidateMatch && ! blank(optional($candidateMatch['lamaran'] ?? null)->id)) {
            return 'VHIRE-CAND-' . $candidateMatch['lamaran']->id;
        }

        return 'HRIS-CAND-' . Str::slug((string) ($data['no_ktp'] ?? Str::random(12)));
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

    private function stringOrNull($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function integerOrNull($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && (int) $value > 0) {
            return (int) $value;
        }

        return null;
    }

    private function compactPayload(array $payload): array
    {
        return array_filter($payload, fn($value) => $value !== null && $value !== '');
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
