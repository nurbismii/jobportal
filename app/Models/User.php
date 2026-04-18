<?php

namespace App\Models;

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
    ];

    public function hasActiveEmploymentStatusLock(): bool
    {
        return preg_match(
            '/^Aktif bekerja pada tanggal \d{4}-\d{2}-\d{2}-$/i',
            trim((string) $this->ket_resign)
        ) === 1;
    }

    public function needsActiveEmploymentLockBackfill(): bool
    {
        $ketResign = trim((string) $this->ket_resign);

        if ($ketResign === '') {
            return true;
        }

        return stripos($ketResign, 'Aktif bekerja pada tanggal ') === 0
            && ! $this->hasActiveEmploymentStatusLock();
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

    public function markAsActiveEmployee($date = null): bool
    {
        return $this->forceFill([
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
