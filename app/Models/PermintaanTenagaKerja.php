<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanTenagaKerja extends Model
{
    use HasFactory;

    protected $table = 'permintaan_tenaga_kerja';

    protected $guarded = [];

    public function departemen()
    {
        return $this->belongsTo('App\Models\Hris\Departemen', 'departemen_id');
    }

    public function divisi()
    {
        return $this->belongsTo('App\Models\Hris\Divisi', 'divisi_id');
    }

    // Relasi ke Lowongan
    public function lowongan()
    {
        return $this->hasMany(Lowongan::class, 'permintaan_tenaga_kerja_id', 'id');
    }
}
