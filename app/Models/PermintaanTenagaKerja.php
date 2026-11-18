<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanTenagaKerja extends Model
{
    use HasFactory;

    protected $table = 'permintaan_tenaga_kerja';

    protected $guarded = [];

    protected $casts = ['rincian' => 'array'];

    public function getRincianPermintaanAttribute(): array
    {
        $rincian = $this->rincian ?: [[
            'divisi' => $this->divisi_id,
            'posisi' => $this->posisi,
            'jumlah_ptk' => $this->jumlah_ptk,
            'rentang_usia' => $this->rentang_usia,
            'background_pendidikan' => $this->background_pendidikan,
        ]];

        return array_map(function (array $row) {
            $row['jenis_kelamin'] = $row['jenis_kelamin'] ?? $this->jenis_kelamin;
            $row['kualifikasi_ptk'] = $row['kualifikasi_ptk'] ?? $this->kualifikasi_ptk;
            return $row;
        }, $rincian);
    }

    public function getRingkasanPosisiAttribute(): string
    {
        return implode(', ', array_unique(array_column($this->rincian_permintaan, 'posisi')));
    }

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

    public static function lamaranMasukById($id): Builder
    {
        return Lamaran::query()
            ->whereIn('loker_id', Lowongan::query()->select('id')->where('permintaan_tenaga_kerja_id', $id))
            ->whereHas('riwayatProsesLamaran', fn ($query) => $query->aktifBekerja());
    }

    public static function syncJumlahMasukById($id): ?int
    {
        if (blank($id)) {
            return null;
        }

        $jumlahMasuk = static::lamaranMasukById($id)->count();

        static::whereKey($id)->update([
            'jumlah_masuk' => $jumlahMasuk,
        ]);

        return $jumlahMasuk;
    }
}
