<?php

namespace App\Services\Ocr;

class ParsedKtp
{
    protected string $text = '';

    public function parse(string $parsedText): array
    {
        $this->normalize($parsedText);

        [$tempatLahir, $tanggalLahir] = $this->parseTtl();
        [$rt, $rw] = $this->parseRtRw();

        return [
            'name' => 'KTP',
            'result' => [
                'provinsi' => $this->field(
                    'provinsi',
                    $this->get('/^PROVINSI\s+(.+)$/m')
                ),

                'kabupatenKota' => $this->field(
                    'kabupatenKota',
                    $this->get('/^KABUPATEN\s+(.+)$/m')
                ),

                'nik' => $this->field(
                    'nik',
                    $this->get('/NIK\s*[:\-]?\s*([0-9]{16})/m')
                ),

                'nama' => $this->field(
                    'nama',
                    $this->get('/NAMA\s*[:\-]?\s*(.+)$/m')
                ),

                'jenisKelamin' => $this->field(
                    'jenisKelamin',
                    $this->get('/JENIS KELAMIN\s*[:\-]?\s*(LAKI-LAKI|PEREMPUAN)/m')
                ),

                'tempatLahir' => $this->field('tempatLahir', $tempatLahir),
                'tanggalLahir' => $this->field('tanggalLahir', $tanggalLahir),

                'golonganDarah' => $this->field(
                    'golonganDarah',
                    $this->get('/GOL\.?\s*DARAH\s*[:\-]?\s*([ABO\-]{1,2})/m')
                ),

                'alamat' => $this->field(
                    'alamat',
                    $this->get('/ALAMAT\s*[:\-]?\s*(.+)$/m')
                ),

                'rt' => $this->field('rt', $rt),
                'rw' => $this->field('rw', $rw),

                'kelurahanDesa' => $this->field(
                    'kelurahanDesa',
                    $this->get('/KEL\/DESA\s*[:\-]?\s*(.+)$/m')
                ),

                'kecamatan' => $this->field(
                    'kecamatan',
                    $this->get('/KECAMATAN\s*[:\-]?\s*(.+)$/m')
                ),

                'agama' => $this->field(
                    'agama',
                    $this->get('/AGAMA\s*[:\-]?\s*(.+)$/m')
                ),

                'statusPerkawinan' => $this->field(
                    'statusPerkawinan',
                    $this->get('/STATUS PERKAWINAN\s*[:\-]?\s*(.+)$/m')
                ),

                'pekerjaan' => $this->field(
                    'pekerjaan',
                    $this->get('/PEKERJAAN\s*[:\-]?\s*(.+)$/m')
                ),

                'kewarganegaraan' => $this->field(
                    'kewarganegaraan',
                    $this->get('/KEWARGANEGARAAN\s*[:\-]?\s*(.+)$/m')
                ),

                'berlakuHingga' => $this->field(
                    'berlakuHingga',
                    $this->get('/BERLAKU HINGGA\s*[:\-]?\s*(SEUMUR HIDUP|[0-9\-]+)/m')
                ),

                'tempatDiterbitkan' => $this->field(
                    'tempatDiterbitkan',
                    $this->get('/\n([A-Z ]+)\n[0-9]{2}-[0-9]{2}-[0-9]{4}$/m')
                ),

                'tanggalDiterbitkan' => $this->field(
                    'tanggalDiterbitkan',
                    $this->parseTanggalTerbit()
                ),
            ]
        ];
    }

    /* ================== HELPERS ================== */

    protected function normalize(string $text): void
    {
        $text = strtoupper($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/[ \t]+/", " ", $text);
        $text = preg_replace("/\n{2,}/", "\n", $text);

        $this->text = trim($text);
    }

    protected function get(string $pattern, int $group = 1): string
    {
        return preg_match($pattern, $this->text, $m)
            ? trim($m[$group])
            : '';
    }

    protected function parseTtl(): array
    {
        if (preg_match('/TEMPAT\/TGL LAHIR\s*[:\-]?\s*(.+)$/m', $this->text, $m)) {
            if (str_contains($m[1], ',')) {
                return array_map('trim', explode(',', $m[1], 2));
            }
        }

        return ['', ''];
    }

    protected function parseRtRw(): array
    {
        if (preg_match('/RT\s*\/\s*RW\s*[:\-]?\s*([0-9]{1,3})\/([0-9]{1,3})/m', $this->text, $m)) {
            return [$m[1], $m[2]];
        }

        return ['', ''];
    }

    protected function parseTanggalTerbit(): string
    {
        if (
            preg_match(
                '/\n([A-Z ]+)\n([0-9]{2}-[0-9]{2}-[0-9]{4})\s*$/',
                $this->text,
                $m
            )
        ) {
            return trim($m[2]);
        }

        return '';
    }

    protected function field(string $name, string $value): array
    {
        return [
            'value' => $value,
            'score' => $value !== '' ? 100 : 0
        ];
    }
}
