<?php

namespace App\Models;

use App\Models\Hris\Kabupaten;
use App\Models\Hris\Kecamatan;
use App\Models\Hris\Kelurahan;
use App\Models\Hris\Provinsi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lamaran extends Model
{
    use HasFactory;

    protected $table = 'lamaran';

    protected $guarded = [];

    public function lowongan()
    {
        return $this->hasOne(Lowongan::class, 'id', 'loker_id');
    }

    public function biodata()
    {
        return $this->hasOne(Biodata::class, 'id', 'biodata_id');
    }

    public function riwayatProses()
    {
        return $this->hasOne(RiwayatProsesLamaran::class, 'id, lamaran_id');
    }

    // Relasi ke Lowongan
    public function lowonganBelongs()
    {
        return $this->belongsTo(Lowongan::class, 'loker_id', 'id');
    }

    // Relasi ke User (jika ada)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function riwayatProsesLamaran()
    {
        return $this->hasMany(RiwayatProsesLamaran::class, 'lamaran_id');
    }
}
