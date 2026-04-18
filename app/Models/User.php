<?php

namespace App\Models;

use App\Models\Hris\Employee;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\VerifikasiEmailNotification;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'no_ktp',
        'name',
        'email',
        'role',
        'status_akun',
        'employment_lock_active',
        'last_hris_sync_at',
        'status_pelamar',
        'tanggal_resign',
        'ket_resign',
        'password',
        'area_kerja',
        'email_verifikasi_token',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'employment_lock_active' => 'boolean',
        'last_hris_sync_at' => 'datetime',
    ];

    public function hasActiveEmploymentStatusLock(): bool
    {
        return (bool) $this->employment_lock_active || $this->hasLegacyActiveEmploymentKetResign();
    }

    public function hasLegacyActiveEmploymentKetResign(): bool
    {
        return preg_match(
            '/^Aktif bekerja pada tanggal \d{4}-\d{2}-\d{2}-$/i',
            trim((string) $this->ket_resign)
        ) === 1;
    }

    public function needsActiveEmploymentLockBackfill(): bool
    {
        $ketResign = trim((string) $this->ket_resign);

        if (! $this->employment_lock_active && $this->hasLegacyActiveEmploymentKetResign()) {
            return true;
        }

        if ($ketResign === '') {
            return true;
        }

        return stripos($ketResign, 'Aktif bekerja pada tanggal ') === 0
            && ! $this->hasLegacyActiveEmploymentKetResign();
    }

    public function activeEmploymentEntryDate(): ?CarbonInterface
    {
        if (! preg_match(
            '/^Aktif bekerja pada tanggal (\d{4}-\d{2}-\d{2})-$/i',
            trim((string) $this->ket_resign),
            $matches
        )) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $matches[1])->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function activeEmploymentKetResign($date = null): string
    {
        if ($date instanceof CarbonInterface) {
            $resolvedDate = $date;
        } elseif (!blank($date)) {
            try {
                $resolvedDate = Carbon::parse($date);
            } catch (\Throwable $e) {
                $resolvedDate = now();
            }
        } else {
            $resolvedDate = now();
        }

        return 'Aktif bekerja pada tanggal ' . $resolvedDate->format('Y-m-d') . '-';
    }

    public static function latestHrisEmployeeByNoKtp(?string $noKtp): ?Employee
    {
        if (blank($noKtp)) {
            return null;
        }

        return Employee::where('no_ktp', $noKtp)
            ->orderByRaw('LEFT(nik, 4) DESC')
            ->first();
    }

    public static function hrisEmployeeByNoKtpAndEntryDate(?string $noKtp, $entryDate): ?Employee
    {
        if (blank($noKtp) || blank($entryDate)) {
            return null;
        }

        try {
            $resolvedDate = $entryDate instanceof CarbonInterface
                ? $entryDate
                : Carbon::parse($entryDate);
        } catch (\Throwable $e) {
            return null;
        }

        return Employee::where('no_ktp', $noKtp)
            ->whereDate('entry_date', $resolvedDate->format('Y-m-d'))
            ->orderByRaw('LEFT(nik, 4) DESC')
            ->first();
    }

    public function matchingHrisEmployeeForActiveEmployment(): ?Employee
    {
        return static::hrisEmployeeByNoKtpAndEntryDate($this->no_ktp, $this->activeEmploymentEntryDate());
    }

    public static function employmentAttributesFromHrisEmployee(?Employee $employee): array
    {
        if (! $employee) {
            return [
                'employment_lock_active' => false,
                'status_pelamar' => null,
                'tanggal_resign' => null,
                'ket_resign' => null,
                'area_kerja' => null,
            ];
        }

        $statusResign = trim((string) $employee->status_resign);

        if (strcasecmp($statusResign, 'aktif') === 0) {
            return [
                'employment_lock_active' => true,
                'status_pelamar' => 'Aktif',
                'tanggal_resign' => null,
                'ket_resign' => static::activeEmploymentKetResign($employee->entry_date),
                'area_kerja' => $employee->area_kerja,
            ];
        }

        return [
            'employment_lock_active' => false,
            'status_pelamar' => $employee->status_resign,
            'tanggal_resign' => $employee->tgl_resign,
            'ket_resign' => $employee->alasan_resign,
            'area_kerja' => $employee->area_kerja,
        ];
    }

    public function markAsActiveEmployee($date = null): bool
    {
        return $this->forceFill([
            'employment_lock_active' => true,
            'last_hris_sync_at' => null,
            'status_pelamar' => 'Aktif',
            'tanggal_resign' => null,
            'ket_resign' => static::activeEmploymentKetResign($date),
        ])->save();
    }

    public function biodataUser()
    {
        return $this->hasOne(Biodata::class, 'user_id', 'id')->select('id', 'user_id', 'no_ktp');
    }

    public function biodata()
    {
        return $this->hasOne(Biodata::class, 'user_id', 'id');
    }

    public function suratPeringatan()
    {
        return $this->hasMany(SuratPeringatan::class);
    }

    public function lamaran()
    {
        // ambil 1 data lamaran terbaru untuk user ini
        return $this->hasOne(RiwayatProsesLamaran::class, 'user_id', 'id')->latestOfMany();
    }

    public function accountRecoveryRequests()
    {
        return $this->hasMany(AccountRecoveryRequest::class);
    }
}
