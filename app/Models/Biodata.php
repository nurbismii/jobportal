<?php

namespace App\Models;

use App\Models\Hris\Employee;
use App\Models\Hris\Kabupaten;
use App\Models\Hris\Kecamatan;
use App\Models\Hris\Kelurahan;
use App\Models\Hris\Provinsi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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

    public function getRiwayatLamaran()
    {
        return $this->hasMany(Lamaran::class, 'biodata_id', 'id')
            ->with('lowongan');
    }

    public function getLatestRiwayatLamaran()
    {
        return $this->hasOne(Lamaran::class, 'biodata_id', 'id')
            ->latest()
            ->with('lowongan');
    }

    public function isValidOcrKtp(): bool
    {
        if (!$this->ocr_ktp || !$this->ocr_ktp_at) {
            return false;
        }

        $ocr = json_decode($this->ocr_ktp, true);

        if (!is_array($ocr) || !isset($ocr['result'])) {
            return false;
        }

        $namaScore = $ocr['result']['nama']['score'] ?? 0;
        $nikScore  = $ocr['result']['nik']['score'] ?? 0;
        $tglScore  = $ocr['result']['tanggalLahir']['score'] ?? 0;

        $nameValue = $ocr['result']['nama']['value'] ?? '';
        $nikValue  = $ocr['result']['nik']['value'] ?? '';

        if (strtoupper($nameValue) !== strtoupper(Auth::user()->name) || $nikValue !== Auth::user()->no_ktp) {
            return false;
        }

        return $namaScore >= 85 && $nikScore >= 85 && $tglScore >= 85 && Carbon::parse($this->ocr_ktp_at)->diffInSeconds(now()) < 3600;
    }
}
