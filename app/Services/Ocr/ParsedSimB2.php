<?php

namespace App\Services\Ocr;

use App\Models\Biodata;
use Illuminate\Support\Str;

class ParsedSimB2
{
    protected string $text = '';

    public function parse($biodata, $save = true)
    {
        if (!$biodata->ocr_sim_b2) {
            return ['message' => 'Belum ada hasil OCR untuk diparsing.'];
        }

        $text = $biodata->ocr_sim_b2;
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));
        $normalizedText = strtoupper(implode(' ', $lines));

        $isFormatBaru = Str::contains($normalizedText, 'NAMA/NAME') || Str::contains($normalizedText, 'PLACE, DATE OF BIRTH');

        // Lanjutkan dengan parsing seperti sebelumnya
        $parsed = [
            'nama' => '',
            'tempat_lahir' => '',
            'tanggal_lahir' => '',
            'jenis_kelamin' => '',
            'alamat' => '',
            'pekerjaan' => '',
            'wilayah' => '',
            'berlaku_sampai' => '',
        ];

        // [masukkan seluruh logic parsing di sini, persis seperti yang sudah kamu buat sebelumnya]
        $afterAlamat = false;

        if ($isFormatBaru) {
            foreach ($lines as $i => $line) {
                $upper = strtoupper($line);

                if (Str::contains($upper, 'NAMA') && isset($lines[$i + 1])) {
                    $parsed['nama'] = ucwords(strtolower($lines[$i + 1]));
                }

                if (preg_match('/([A-Z\s]+),\s*(\d{2}-\d{2}-\d{4})/', $line, $match)) {
                    $parsed['tempat_lahir'] = ucwords(strtolower($match[1]));
                    $parsed['tanggal_lahir'] = $match[2];
                }

                if (Str::contains($upper, 'JENIS KELAMIN') || in_array($upper, ['PRIA', 'WANITA'])) {
                    $parsed['jenis_kelamin'] = $upper;
                }

                if (Str::contains($upper, 'ALAMAT') && isset($lines[$i + 1])) {
                    $alamat = [];
                    for ($j = $i + 1; $j <= $i + 3 && isset($lines[$j]); $j++) {
                        $alamat[] = $lines[$j];
                    }
                    $parsed['alamat'] = implode(', ', $alamat);
                }

                if (Str::contains($upper, 'PEKERJAAN') && isset($lines[$i + 1])) {
                    $parsed['pekerjaan'] = ucwords(strtolower($lines[$i + 1]));
                }

                if (Str::contains($upper, 'DITERBITKAN OLEH') && isset($lines[$i + 1])) {
                    $parsed['wilayah'] = ucwords(strtolower($lines[$i + 1]));
                }

                if (empty($parsed['berlaku_sampai'])) {
                    $allDates = [];

                    foreach ($lines as $line) {
                        // Tangkap semua format tanggal: dd-mm-yyyy atau dd/mm/yyyy
                        if (preg_match_all('/\b(\d{2})[\/\-](\d{2})[\/\-](\d{4})\b/', $line, $matches, PREG_SET_ORDER)) {
                            foreach ($matches as $match) {
                                $tanggal = "{$match[1]}-{$match[2]}-{$match[3]}";
                                $timestamp = strtotime(str_replace('/', '-', $tanggal));
                                if ($timestamp !== false) {
                                    $allDates[] = [
                                        'raw' => date('d-m-Y', $timestamp),
                                        'timestamp' => $timestamp
                                    ];
                                }
                            }
                        }
                    }

                    if (!empty($allDates)) {
                        // Ambil tanggal dengan timestamp terbesar
                        usort($allDates, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
                        $parsed['berlaku_sampai'] = end($allDates)['raw']; // hasil: "25-03-2030"
                    }
                }
                // Early break jika semua data sudah terisi
                if ($this->isParsedComplete($parsed)) break;
            }
        } else {
            foreach ($lines as $i => $line) {
                $line = trim($line);
                $upper = strtoupper($line);

                if (preg_match('/1\.\s*(.+)/', $line, $match)) {
                    $parsed['nama'] = ucwords(strtolower($match[1]));
                    continue;
                }

                if (preg_match('/2\.\s*\.?([A-Z\s]+),\s*(\d{2}-\d{2}-\d{4})/i', $line, $match)) {
                    $parsed['tempat_lahir'] = ucwords(strtolower($match[1]));
                    $parsed['tanggal_lahir'] = $match[2];
                    continue;
                }

                if (preg_match('/3\.\s*(PRIA|WANITA)/i', $line, $match)) {
                    $parsed['jenis_kelamin'] = strtoupper($match[1]);
                    continue;
                } elseif (preg_match('/\b(PRIA|WANITA)\b/i', $line, $match) && empty($parsed['jenis_kelamin'])) {
                    $parsed['jenis_kelamin'] = strtoupper($match[1]);
                    continue;
                }

                if (preg_match('/4\.\s*(.+)/', $line, $match)) {
                    $parsed['alamat'] = ucwords(strtolower($match[1]));
                    $afterAlamat = true;
                    continue;
                } elseif ($afterAlamat && !preg_match('/^\d+\./', $line) && !preg_match('/\d{2}-\d{2}-\d{4}/', $line)) {
                    $parsed['alamat'] .= ', ' . ucwords(strtolower($line));
                    continue;
                } elseif (preg_match('/5\./', $line)) {
                    $afterAlamat = false;
                }

                if (preg_match('/5\.\s*(.+)/', $line, $match)) {
                    $parsed['pekerjaan'] = ucwords(strtolower($match[1]));
                    continue;
                }

                if (preg_match('/6\.\s*(.+)/', $line, $match)) {
                    $parsed['wilayah'] = ucwords(strtolower($match[1]));
                    continue;
                }

                if (preg_match('/\bPOL(RES|DA|RESTA|SEK)\b/i', $line)) {
                    $parsed['wilayah'] = ucwords(strtolower($line));
                    continue;
                }

                if (preg_match('/\d{2}-\d{2}-\d{4}/', $line, $match) && $match[0] !== $parsed['tanggal_lahir']) {
                    $parsed['berlaku_sampai'] = $match[0];
                }

                if ($this->isParsedComplete($parsed)) break;
            }

            $parsed['alamat'] = rtrim($parsed['alamat'], ', ');
        }

        // Fallback jika data penting belum terbaca
        if (empty($parsed['nama']) || empty($parsed['tanggal_lahir'])) {
            foreach ($lines as $i => $line) {
                $cleanLine = preg_replace('/[^A-Za-z0-9\s.,-]/', '', $line);
                $upper = strtoupper($cleanLine);

                if (preg_match('/^[A-Z]\.?\s+[A-Z]+$/', $cleanLine)) {
                    $parsed['nama'] = ucwords(strtolower($cleanLine));
                }

                if (preg_match('/^([A-Z\s]+),\s*(\d{2}-\d{2}-\d{4})$/', $cleanLine, $match)) {
                    $kota = ucwords(strtolower($match[1]));
                    $tanggal = $match[2];

                    // Asumsikan ini tempat dan tanggal pembuatan SIM, **jangan anggap ini berlaku_sampai**
                    if (empty($parsed['tempat_pembuatan']) && empty($parsed['tanggal_pembuatan'])) {
                        $parsed['tempat_pembuatan'] = $kota;
                        $parsed['tanggal_pembuatan'] = $tanggal;
                        continue;
                    }
                }

                // Tangkap semua tanggal lain, pastikan bukan tanggal pembuatan
                if (preg_match('/\d{2}-\d{2}-\d{4}/', $cleanLine, $match)) {
                    $tgl = $match[0];

                    // Jika belum ada tanggal lahir, dan tanggal ini bukan tanggal_pembuatan
                    if (empty($parsed['tanggal_lahir']) && (!isset($parsed['tanggal_pembuatan']) || $tgl !== $parsed['tanggal_pembuatan'])) {
                        $parsed['tanggal_lahir'] = $tgl;
                    }
                    // Jika belum ada berlaku_sampai, dan bukan tanggal lahir & tanggal_pembuatan
                    elseif (empty($parsed['berlaku_sampai']) && $tgl !== $parsed['tanggal_lahir'] && $tgl !== $parsed['tanggal_pembuatan']) {
                        $parsed['berlaku_sampai'] = $tgl;
                    }
                }

                if (in_array($upper, ['PRIA', 'WANITA'])) {
                    $parsed['jenis_kelamin'] = $upper;
                }

                if (empty($parsed['alamat']) && isset($lines[$i + 1], $lines[$i + 2])) {
                    if (preg_match('/JEMBATAN|DUSUN|KAB|DESA|RT|RW/i', $cleanLine)) {
                        $alamat = [$cleanLine];
                        $j = $i + 1;
                        while (isset($lines[$j]) && !preg_match('/POL(RES|DA|RESTA|SEK)|\d{2}-\d{2}-\d{4}/', $lines[$j])) {
                            $alamat[] = preg_replace('/[^A-Za-z0-9\s.,-]/', '', $lines[$j]);
                            $j++;
                        }
                        $parsed['alamat'] = ucwords(strtolower(implode(', ', $alamat)));
                    }
                }

                if (preg_match('/(SWASTA|WIRASWASTA|PEGAWAI|SISWA|MAHASISWA)/i', $cleanLine, $match)) {
                    $parsed['pekerjaan'] = ucfirst(strtolower($match[1]));
                }

                if (preg_match('/POL(RES|DA|RESTA|SEK)/i', $cleanLine)) {
                    $parsed['wilayah'] = ucwords(strtolower($cleanLine));
                }

                if (preg_match('/\d{2}-\d{2}-\d{4}/', $cleanLine, $match)) {
                    if ($match[0] !== $parsed['tanggal_lahir']) {
                        $parsed['berlaku_sampai'] = $match[0];
                    }
                }

                if (preg_match('/Nama\s*:\s*(.+)/i', $line, $match)) {
                    $parsed['nama'] = ucwords(strtolower($match[1]));
                }

                if (preg_match('/Alamat\s*:\s*(.+)/i', $line, $match)) {
                    $alamat = [$match[1]];
                    $j = $i + 1;
                    while (isset($lines[$j]) && !preg_match('/^\s*Tempat|Tgi\.Lahir|Peker|No\.|Berlaku/i', $lines[$j])) {
                        $alamat[] = $lines[$j];
                        $j++;
                    }
                    $parsed['alamat'] = ucwords(strtolower(implode(', ', $alamat)));
                }

                if (preg_match('/Tempat.*:\s*(.+)/i', $line, $match)) {
                    $parsed['tempat_lahir'] = ucwords(strtolower($match[1]));
                }

                if (preg_match('/Tgi\.?\.?Lahir\s*:\s*(\d{2}-\d{2}-\d{4})/i', $line, $match)) {
                    $parsed['tanggal_lahir'] = $match[1];
                }

                if (preg_match('/Peker.?aan\s*:\s*(.+)/i', $line, $match)) {
                    $parsed['pekerjaan'] = ucwords(strtolower($match[1]));
                }

                if (preg_match('/Berlaku.*:\s*(\d{2}-\d{2}-\d{4})/i', $line, $match)) {
                    $parsed['berlaku_sampai'] = $match[1];
                }

                if (preg_match('/\bP.?RIA\b/i', $line)) {
                    $parsed['jenis_kelamin'] = 'PRIA';
                } elseif (preg_match('/\bW.?ANITA\b/i', $line)) {
                    $parsed['jenis_kelamin'] = 'WANITA';
                }

                // Tangkap nama satu baris di atas baris yang memuat tempat, tanggal lahir
                // Jika nama dan tempat/tanggal lahir masih kosong, coba deteksi pola fallback format baru
                if (empty($parsed['nama']) && empty($parsed['tempat_lahir'])) {
                    foreach ($lines as $i => $line) {
                        $cleanLine = preg_replace('/[^A-Za-z0-9\s.,-]/', '', $line);
                        $upperLine = strtoupper(trim($cleanLine));

                        // Cari baris yang merupakan tempat dan tanggal lahir
                        if (preg_match('/([A-Z\s]+),\s*(\d{2}-\d{2}-\d{4})/', $upperLine, $match)) {
                            $kota = ucwords(strtolower(trim($match[1])));
                            $tanggal = $match[2];

                            // Validasi tahun masuk akal sebagai tanggal lahir
                            $year = (int)substr($tanggal, -4);
                            if ($year < 2010) {
                                $parsed['tempat_lahir'] = $kota;
                                $parsed['tanggal_lahir'] = $tanggal;

                                // Cari nama di baris sebelumnya, jika huruf kapital semua
                                if ($i > 0) {
                                    $namaLine = trim($lines[$i - 1]);
                                    $namaClean = preg_replace('/[^A-Za-z\s]/', '', $namaLine);
                                    if (preg_match('/^[A-Z\s]{3,}$/', strtoupper($namaClean))) {
                                        $parsed['nama'] = ucwords(strtolower($namaClean));
                                    }
                                }

                                break; // selesai parsing nama dan TTL
                            }
                        }

                        if (empty($parsed['nama'])) {
                            foreach ($lines as $i => $line) {
                                $cleanLine = preg_replace('/[^A-Za-z\s]/', '', $line);
                                $upper = strtoupper($cleanLine);

                                // Abaikan baris yang terlalu umum
                                $nonNamePatterns = [
                                    'INDONESIA',
                                    'SURAT IZIN MENGEMUDI',
                                    'SURAT IZIN MENOL',
                                    'DRIVING LICENSE',
                                    'SIM',
                                    'POLRI',
                                    'REPUBLIK INDONESIA',
                                    'JENIS KELAMIN',
                                    'GOL DARAH',
                                    'ALAMAT',
                                    'PEKERJAAN',
                                    'DITERBITKAN OLEH',
                                    'KENDARAAN',
                                    'BILUMUM',
                                    'IA MAINA NE'
                                ];

                                if (in_array($upper, $nonNamePatterns) || strlen($cleanLine) < 4) {
                                    continue;
                                }

                                // Coba ambil baris sebelum tempat lahir
                                if (!empty($parsed['tanggal_lahir']) && isset($lines[$i + 1])) {
                                    if (preg_match('/\d{2}-\d{2}-\d{4}/', $lines[$i + 1])) {
                                        // Baris ini kemungkinan besar adalah nama
                                        $parsed['nama'] = ucwords(strtolower($cleanLine));
                                        break;
                                    }
                                }

                                // Atau baris yang huruf besar semua dan terdiri dari 2 kata
                                if (preg_match('/^[A-Z\s]{4,}$/', $upper) && substr_count($upper, ' ') <= 2) {
                                    $parsed['nama'] = ucwords(strtolower($cleanLine));
                                    break;
                                }
                            }
                        }
                    }
                }

                if ($this->isParsedComplete($parsed)) break;
            }
        }

        if (
            !empty($parsed['tanggal_pembuatan']) &&
            !empty($parsed['tanggal_lahir']) &&
            $parsed['tanggal_lahir'] === $parsed['berlaku_sampai']
        ) {
            // Asumsikan tanggal_pembuatan adalah tanggal lahir
            $parsed['tanggal_lahir'] = $parsed['tanggal_pembuatan'];
            $parsed['tempat_lahir'] = $parsed['tempat_pembuatan'];

            // Reset tanggal_pembuatan karena ternyata salah
            $parsed['tanggal_pembuatan'] = '';
            $parsed['tempat_pembuatan'] = '';
        }

        if ($save && method_exists($biodata, 'save')) {
            $biodata->parsed_sim_b2 = $parsed;

            Biodata::where('id', $biodata->id)->update(['parsed_sim_b2' => $parsed]);
            $biodata->save();
        }

        return [
            'message' => 'Parsing berhasil.',
            'data' => $parsed,
        ];
    }

    /**
     * Mengecek apakah semua data penting sudah terisi
     */
    private function isParsedComplete(array $parsed): bool
    {
        return !empty($parsed['nama']) &&
            !empty($parsed['tanggal_lahir']) &&
            !empty($parsed['alamat']) &&
            !empty($parsed['jenis_kelamin']) &&
            !empty($parsed['tempat_lahir']) &&
            !empty($parsed['pekerjaan']) &&
            !empty($parsed['wilayah']) &&
            !empty($parsed['berlaku_sampai']);
    }
}
