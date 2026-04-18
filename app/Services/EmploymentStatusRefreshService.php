<?php

namespace App\Services;

use App\Models\Hris\Employee;
use App\Models\Hris\Peringatan;
use App\Models\SuratPeringatan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EmploymentStatusRefreshService
{
    public function refreshUsersByNoKtp(array $noKtpArray): array
    {
        $users = User::with('biodata')
            ->whereHas('biodata', function ($q) use ($noKtpArray) {
                $q->whereIn('no_ktp', $noKtpArray);
            })
            ->get();

        return $this->refreshUsers($users);
    }

    public function refreshUsersByIds(array $userIds): array
    {
        $users = User::with('biodata')
            ->whereIn('id', $userIds)
            ->get();

        return $this->refreshUsers($users);
    }

    public function refreshLockedUsers(): array
    {
        return $this->refreshUsers($this->lockedUsersDueForRefreshQuery()->get());
    }

    public function lockedUsersQuery(): Builder
    {
        return $this->lockedUsersBaseQuery()
            ->with('biodata');
    }

    public function lockedUsersDueForRefreshQuery(): Builder
    {
        $query = $this->lockedUsersQuery();
        $resyncAfterMinutes = max(0, (int) config('recruitment.locked_employment_refresh.resync_after_minutes', 1440));

        if ($resyncAfterMinutes === 0) {
            return $query;
        }

        $refreshThreshold = now()->subMinutes($resyncAfterMinutes);

        return $query->where(function ($syncQuery) use ($refreshThreshold) {
            $syncQuery->whereNull('last_hris_sync_at')
                ->orWhere('last_hris_sync_at', '<=', $refreshThreshold);
        });
    }

    public function lockedUsersBaseQuery(): Builder
    {
        return User::query()
            ->whereHas('biodata')
            ->where(function ($query) {
                $query->where('employment_lock_active', true)
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery->where('employment_lock_active', false)
                            ->where('ket_resign', 'like', 'Aktif bekerja pada tanggal %');
                    });
            });
    }

    public function refreshUsers(Collection $users): array
    {
        $summary = [
            'processed' => 0,
            'updated' => 0,
            'reset' => 0,
            'skipped' => 0,
            'warnings_synced' => 0,
        ];

        foreach ($users as $user) {
            $result = $this->refreshUser($user);

            $summary['processed']++;
            $summary[$result['action']]++;
            $summary['warnings_synced'] += $result['warnings_synced'];
        }

        return $summary;
    }

    public function refreshUser(User $user): array
    {
        $noKtp = optional($user->biodata)->no_ktp ?: $user->no_ktp;
        $isLocked = $user->hasActiveEmploymentStatusLock();
        $syncTimestamp = now();

        $hrisEmployee = $isLocked
            ? $user->matchingHrisEmployeeForActiveEmployment()
            : User::latestHrisEmployeeByNoKtp($noKtp);

        if ($hrisEmployee) {
            $user->fill(User::employmentAttributesFromHrisEmployee($hrisEmployee));
            $user->last_hris_sync_at = $syncTimestamp;

            if ($user->isDirty()) {
                $user->save();
            }

            return [
                'action' => 'updated',
                'warnings_synced' => $this->syncWarnings($user, $hrisEmployee),
            ];
        }

        if ($isLocked) {
            $user->last_hris_sync_at = $syncTimestamp;

            if ($user->isDirty()) {
                $user->save();
            }

            return [
                'action' => 'skipped',
                'warnings_synced' => 0,
            ];
        }

        $user->fill([
            'employment_lock_active' => false,
            'last_hris_sync_at' => $syncTimestamp,
            'status_pelamar' => null,
            'area_kerja' => null,
            'tanggal_resign' => null,
            'ket_resign' => null,
        ]);

        if ($user->isDirty()) {
            $user->save();
        }

        return [
            'action' => 'reset',
            'warnings_synced' => 0,
        ];
    }

    private function syncWarnings(User $user, Employee $hrisEmployee): int
    {
        $inserted = 0;

        $peringatanList = Peringatan::where('nik_karyawan', $hrisEmployee->nik)->get();

        foreach ($peringatanList as $data) {
            $exists = SuratPeringatan::where([
                'user_id' => $user->id,
                'level_sp' => $data->level_sp,
                'tanggal_mulai_sp' => $data->tgl_mulai,
            ])->exists();

            if ($exists) {
                continue;
            }

            SuratPeringatan::create([
                'user_id' => $user->id,
                'level_sp' => $data->level_sp,
                'ket_sp' => $data->keterangan,
                'tanggal_mulai_sp' => $data->tgl_mulai,
                'tanggal_berakhir_sp' => $data->tgl_berakhir,
            ]);

            $inserted++;
        }

        return $inserted;
    }
}
