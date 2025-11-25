<?php

namespace App\Imports;

use App\Models\Biodata;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class KemampuanPengalamanImport implements ToModel, WithHeadingRow, SkipsOnFailure
{
    use SkipsFailures;

    // Header yang wajib ada
    protected $requiredHeaders = ['no_ktp', 'kemampuan_pengalaman'];

    public function model(array $row)
    {
        // Pastikan semua header wajib ada
        foreach ($this->requiredHeaders as $header) {
            if (!array_key_exists($header, $row)) {
                throw new \Exception("Header '$header' tidak ditemukan. Pastikan format sesuai template.");
            }
        }

        // Validasi wajib isi no_ktp
        if (!isset($row['no_ktp']) || trim($row['no_ktp']) === '') {
            throw new \Exception("No KTP tidak boleh kosong. Periksa baris dengan data kosong.");
        }

        $noKtp = trim($row['no_ktp']);

        // Cek apakah biodata ada
        $biodata = Biodata::where('no_ktp', $noKtp)->first();

        if (!$biodata) {
            throw new \Exception("No KTP '$noKtp' tidak ditemukan dalam database.");
        }

        // Update data
        $biodata->update([
            'kemampuan_pengalaman' => $row['kemampuan_pengalaman'] ?? null,
        ]);

        return null;
    }
}
