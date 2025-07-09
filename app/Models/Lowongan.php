<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lowongan extends Model
{
    use HasFactory;

    protected $table = 'lowongan';

    protected $guarded = [];

    public function lamarans()
    {
        return $this->hasMany(Lamaran::class, 'loker_id');
    }

    // Relasi ke PermintaanTenagaKerja
    public function permintaanTenagaKerja()
    {
        return $this->belongsTo(PermintaanTenagaKerja::class, 'permintaan_tenaga_kerja_id', 'id');
    }

    // Relasi ke Lamaran
    public function lamaran()
    {
        return $this->hasMany(Lamaran::class, 'loker_id', 'id');
    }
}
