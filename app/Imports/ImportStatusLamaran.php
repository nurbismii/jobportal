<?php

namespace App\Imports;

use App\Models\Biodata;
use App\Models\Lamaran;
use App\Services\LamaranStatusService;
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
    protected LamaranStatusService $lamaranStatusService;

    public function __construct()
    {
        $this->lamaranStatusService = app(LamaranStatusService::class);
    }

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

        $lamaran = Lamaran::with('lowongan:id,permintaan_tenaga_kerja_id')
            ->where('biodata_id', $biodata->id)
            ->latest('id')
            ->first();

        if (!$lamaran) {
            throw new \Exception("Lamaran untuk KTP '$noKtp' tidak ditemukan.");
        }

        $tanggalProses = $this->parseDate($row['tanggal_proses'] ?? null);

        $this->lamaranStatusService->apply(
            $lamaran,
            (string) ($row['status_tahapan'] ?? ''),
            $tanggalProses ?: ($row['tanggal_proses'] ?? null),
            now()->format('H:i:s'),
            $row['tempat'] ?? '-',
            '-'
        );

        return null;
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
