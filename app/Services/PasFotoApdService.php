<?php

namespace App\Services;

use App\Models\Biodata;
use App\Models\Hris\Employee;
use App\Models\Lamaran;
use App\Models\User;
use App\Models\VhireOnboardingCandidate;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PasFotoApdService
{
    public function eligibleBiodata(User $user, ?Lamaran $lamaran = null): ?Biodata
    {
        if ($user->role !== 'user' || (int) $user->status_akun !== 1 || ! $user->hasVerifiedEmail()) {
            return null;
        }

        $biodata = $lamaran?->biodata ?? $user->biodata;
        $noKtp = (string) $biodata?->no_ktp;
        if (! $biodata || (int) $biodata->user_id !== (int) $user->id
            || $noKtp !== (string) $user->no_ktp || ! preg_match('/^[0-9]{16}$/', $noKtp)) {
            return null;
        }

        $applications = Lamaran::where('biodata_id', $biodata->id);
        if ($lamaran) {
            $applications->whereKey($lamaran->id);
        }

        $activated = Schema::hasTable('vhire_onboarding_candidates')
            && VhireOnboardingCandidate::where('no_ktp', $noKtp)
                ->whereIn('lamaran_id', (clone $applications)->select('id'))
                ->where('onboarding_status', VhireOnboardingCandidate::STATUS_ACTIVATED)
                ->exists();

        if (! $activated && ! $applications->where(function ($query) {
            $query->whereRaw('LOWER(TRIM(status_proses)) = ?', ['aktif bekerja'])
                ->orWhereHas('riwayatProsesLamaran', fn ($history) => $history->aktifBekerja());
        })->exists()) {
            return null;
        }

        try {
            $active = Employee::where('no_ktp', $noKtp)
                ->whereRaw('UPPER(TRIM(status_resign)) = ?', ['AKTIF'])
                ->whereNull('deleted_at')
                ->where(fn ($entry) => $entry->whereNull('entry_date')->orWhereDate('entry_date', '<=', today()))
                ->exists();
        } catch (QueryException $exception) {
            Log::warning('Pengecekan status kerja untuk pas foto APD gagal.', ['code' => $exception->getCode()]);

            return null;
        }

        return $active ? $biodata : null;
    }

    public function replace(Biodata $biodata, UploadedFile $photo): void
    {
        $disk = Storage::disk('candidate_private');
        $directory = $biodata->no_ktp . '/dokumen';
        $filename = 'pas-foto-apd-' . Str::uuid() . '.' . $photo->extension();
        $path = $directory . '/' . $filename;
        try {
            $old = DB::transaction(function () use ($biodata, $photo, $disk, $directory, $filename) {
                $record = Biodata::whereKey($biodata->id)
                    ->where('user_id', $biodata->user_id)->where('no_ktp', $biodata->no_ktp)
                    ->lockForUpdate()->firstOrFail();
                $old = $record->pas_foto_apd;
                if (! $disk->putFileAs($directory, $photo, $filename)
                    || ! $record->forceFill(['pas_foto_apd' => $filename])->save()) {
                    throw new RuntimeException('Foto APD gagal disimpan.');
                }

                return $old;
            });
        } catch (Throwable $exception) {
            $this->deleteFile($path);
            throw $exception;
        }

        if (is_string($old) && preg_match('/^pas-foto-apd-[0-9a-f-]{36}\.(jpe?g|png)$/i', $old)) {
            $this->deleteFile($directory . '/' . $old);
        }
    }

    private function deleteFile(string $path): void
    {
        try {
            Storage::disk('candidate_private')->delete($path);
        } catch (Throwable $exception) {
            Log::warning('Pembersihan pas foto APD gagal.', ['type' => get_class($exception)]);
        }
    }
}
