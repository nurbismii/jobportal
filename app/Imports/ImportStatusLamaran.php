<?php

namespace App\Imports;

use App\Models\Biodata;
use App\Models\Lamaran;
use App\Models\RiwayatProsesLamaran;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class ImportStatusLamaran implements ToModel, WithHeadingRow, WithChunkReading, SkipsOnFailure
{
    use SkipsFailures;

    protected $requiredHeaders = ['no_ktp', 'status_tahapan', 'tanggal_proses', 'tempat'];

    protected $biodataCache = [];

    public function model(array $row)
    {
        $this->validateHeaders($row);

        $noKtp = trim($row['no_ktp'] ?? '');

        if ($noKtp === '') {
            throw new \Exception("No KTP tidak boleh kosong.");
        }

        // cache biodata supaya query tidak berulang
        if (!isset($this->biodataCache[$noKtp])) {

            $this->biodataCache[$noKtp] = Biodata::select('id', 'user_id')
                ->where('no_ktp', $noKtp)
                ->first();
        }

        $biodata = $this->biodataCache[$noKtp];

        if (!$biodata) {
            throw new \Exception("No KTP '$noKtp' tidak ditemukan dalam database.");
        }

        $lamaran = Lamaran::where('biodata_id', $biodata->id)
            ->latest('id')
            ->first();

        if (!$lamaran) {
            throw new \Exception("Lamaran untuk KTP '$noKtp' tidak ditemukan.");
        }

        $statusTahapan = strtolower(trim($row['status_tahapan'] ?? ''));
        $statusLolos = $this->isTidakLolos($statusTahapan) ? 'Tidak Lolos' : null;
        $tanggalProses = $this->parseDate($row['tanggal_proses'] ?? null);

        // update lamaran
        $lamaran->update([
            'status_lamaran' => strtolower($statusLolos) == 'tidak lolos' ? 0 : 1,
            'status_proses' => ucwords($row['status_tahapan']),
        ]);

        // tentukan status lolos
        RiwayatProsesLamaran::create([
            'user_id' => $biodata->user_id,
            'lamaran_id' => $lamaran->id,
            'status_proses' => ucwords($row['status_tahapan']),
            'status_lolos' => $statusLolos,
            'tanggal_proses' => $tanggalProses,
            'jam' => now()->format('H:i:s'),
            'tempat' => $row['tempat'] ?? '-',
            'pesan' => '-'
        ]);

        if ($statusTahapan === 'aktif bekerja') {
            $user = User::find($biodata->user_id);

            if ($user) {
                $user->markAsActiveEmployee($tanggalProses ?: ($row['tanggal_proses'] ?? null));
            }
        }

        return null;
    }

    private function isTidakLolos($status)
    {
        $status = strtolower($status);

        $keywords = [
            'tidak lolos',
            'belum sesuai'
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($status, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function validateHeaders(array $row)
    {
        static $checked = false;

        if ($checked) return;

        foreach ($this->requiredHeaders as $header) {

            if (!array_key_exists($header, $row)) {
                throw new \Exception("Header '$header' tidak ditemukan. Gunakan template yang benar.");
            }
        }

        $checked = true;
    }

    private function parseDate($value)
    {
        if (blank($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            }

            return Carbon::parse($value);
        } catch (\Throwable $th) {
            return null;
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
