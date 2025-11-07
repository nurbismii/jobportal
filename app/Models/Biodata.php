<?php

namespace App\Models;

use App\Models\Hris\Employee;
use App\Models\Hris\Kabupaten;
use App\Models\Hris\Kecamatan;
use App\Models\Hris\Kelurahan;
use App\Models\Hris\Provinsi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Biodata extends Model
{
    use HasFactory;

    protected $table = 'biodata';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getProvinsi()
    {
        return $this->hasOne(Provinsi::class, 'id', 'provinsi');
    }

    public function getKabupaten()
    {
        return $this->hasOne(Kabupaten::class, 'id', 'kabupaten');
    }

    public function getKecamatan()
    {
        return $this->hasOne(Kecamatan::class, 'id', 'kecamatan');
    }

    public function getKelurahan()
    {
        return $this->hasOne(Kelurahan::class, 'id', 'kelurahan');
    }

    public function getRiwayatInHris()
    {
        return $this->hasMany(Employee::class, 'no_ktp', 'no_ktp')
            ->select(['no_ktp', 'no_ktp as no_ktp_hris', 'nama_karyawan', 'tgl_resign', 'alasan_resign', 'posisi', 'status_resign', 'area_kerja']);
    }
}
