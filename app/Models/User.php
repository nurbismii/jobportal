<?php

namespace App\Models;

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
        'email_verifikasi_token'
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
}
