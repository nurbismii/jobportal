<?php

use App\Models\RiwayatProsesLamaran;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

if (!function_exists('hitungUmur')) {
    function hitungUmur($tanggalLahir)
    {
        $tanggalLahir = new DateTime($tanggalLahir);
        $sekarang = new DateTime();
        $umur = $sekarang->diff($tanggalLahir);
        return $umur->y;
    }
}

if (!function_exists('tanggalIndo')) {
    function tanggalIndo($tanggal)
    {
        // Konversi jika format adalah timestamp (angka saja)
        if (is_numeric($tanggal)) {
            $tanggal = date('Y-m-d', $tanggal);
        }

        // Jika format datetime, ambil hanya bagian tanggalnya
        if (strpos($tanggal, ' ') !== false) {
            $tanggal = explode(' ', $tanggal)[0];
        }

        $bulan = array(
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        );

        $split = explode('-', $tanggal);
        return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
    }
}

function interventionImg($dokumenFields, $biodata, $request)
{
    $folderPath = public_path(Auth::user()->no_ktp . '/dokumen');

    if (!file_exists($folderPath)) {
        mkdir($folderPath, 0777, true);
    }

    $fileNames = []; // 💡 pastikan ini diinisialisasi

    foreach ($dokumenFields as $field => $label) {
        if ($request->hasFile($field)) {
            if ($biodata && $biodata->{$field}) {
                $oldFilePath = $folderPath . '/' . $biodata->{$field};
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }

            $file = $request->file($field);
            $slugName = Str::slug(Auth::user()->name, '_');
            $timestamp = now()->format('mY');
            $extension = strtolower($file->getClientOriginalExtension());
            $fileName = "{$slugName}-{$label}-{$timestamp}.{$extension}";
            $savePath = $folderPath . '/' . $fileName;

            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $image = Image::make($file);

                if ($image->width() > 1500) {
                    $image->resize(1500, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }

                $quality = 85;
                do {
                    $image->encode($extension, $quality);
                    $sizeKB = strlen((string)$image) / 1024;
                    $quality -= 5;
                } while ($sizeKB > 1024 && $quality > 10);

                $image->save($savePath);
                $fileNames[$field] = $fileName;
            } elseif ($extension === 'pdf') {
                $sizeKB = $file->getSize() / 1024;
                if ($sizeKB <= 2048) {
                    $file->move($folderPath, $fileName);
                    $fileNames[$field] = $fileName;
                } else {
                    $fileNames[$field] = null;
                }
            } else {
                $fileNames[$field] = null;
            }
        } else {
            $fileNames[$field] = $biodata ? $biodata->{$field} : null;
        }
    }

    return $fileNames;
}

if (!function_exists('tanggalIndoHari')) {
    function tanggalIndoHari($tanggal)
    {
        // Konversi jika format adalah timestamp (angka saja)
        if (is_numeric($tanggal)) {
            $tanggal = date('Y-m-d', $tanggal);
        }

        // Jika format datetime, ambil hanya bagian tanggalnya
        if (strpos($tanggal, ' ') !== false) {
            $tanggal = explode(' ', $tanggal)[0];
        }

        $hari = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu'
        ];

        $bulan = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $split = explode('-', $tanggal);
        $dateObj = DateTime::createFromFormat('Y-m-d', $tanggal);
        $namaHari = $hari[$dateObj->format('l')];

        return $namaHari . ', ' . $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
    }
}

if (!function_exists('extractSimB2OnlyOCR')) {
    function extractSimB2OnlyOCR($biodata)
    {
        if ($biodata && $biodata->sim_b_2) {
            $fullPath = public_path($biodata->no_ktp . '/dokumen/' . $biodata->sim_b_2);

            $response = Http::attach(
                'file',
                file_get_contents($fullPath),
                basename($fullPath)
            )->post('https://api.ocr.space/parse/image', [
                'apikey' => 'K82052672988957',
                'language' => 'eng',
                'OCREngine' => '2',
                'scale' => 'true',
                'detectOrientation' => 'true',
                'isOverlayRequired' => 'false',
            ]);

            $text = $response->json()['ParsedResults'][0]['ParsedText'] ?? null;

            $biodata->ocr_sim_b2 = $text;
            $biodata->save();

            return ['message' => 'OCR berhasil, silakan lanjutkan parsing.'];
        }

        return ['message' => 'Gagal menemukan file SIM B2'];
    }
}

if (!function_exists('pesanStatusLamaran')) {
    function pesanStatusLamaran($status, $tanggal_proses, $jam, $tempat)
    {
        $status = strtolower($status);
        $tanggal = tanggalIndoHari($tanggal_proses);
        $jam = $jam;
        $tempat = $tempat;

        $templates = [

            'verifikasi online' => <<<EOT
Yth. Bapak/Ibu,

Terima kasih telah meluangkan waktu untuk melamar dan mengirimkan berkas persyaratan kepada PT VDNI.

Saat ini, Anda berada pada tahap Verifikasi Online, di mana seluruh dokumen yang telah Anda kirimkan akan kami periksa dan validasi terlebih dahulu untuk memastikan kesesuaian dengan persyaratan yang dibutuhkan.

Mohon pastikan seluruh dokumen yang dikirim telah lengkap, jelas terbaca, dan sesuai dengan ketentuan.
Apabila terdapat kekurangan atau ketidaksesuaian, tim Rekrutmen kami akan segera menghubungi Anda untuk melakukan perbaikan atau melengkapi dokumen tersebut.

Terima kasih atas perhatian dan kerja sama Anda.
Kami menghargai minat Anda untuk bergabung bersama PT VDNI dan berharap proses ini berjalan lancar.

Salam hormat,
Rekrutmen PT VDNI"
EOT,

            'verifikasi berkas' => <<<EOT
Kepada yth,

Peserta Tes Pelamar PT VIRTUE DRAGON NICKEL INDUSTRY

Salam Hormat,

Sehubungan dengan rekrutmen calon karyawan pada perusahaan PT VDNI, berdasarkan hasil seleksi evaluasi tahap awal Tim Rekrutmen terhadap lamaran pekerjaan saudara, dengan ini kami menyatakan telah memenuhi persyaratan administrasi.

Melalui surat ini kami mengundang saudara untuk mengikuti tes evaluasi tahap lanjutan yang akan diadakan di perusahaan kami. Oleh karenanya, Saudara diharapkan hadir pada:

Hari/Tanggal  : {$tanggal}
Waktu         : {$jam}
Tempat        : {$tempat}

Dengan ini saudara diminta membawa berkas sebagai berikut:
1. KTP (Asli dan Fotokopi 1 lembar)
2. Fotokopi KK 1 lembar
3. SKCK asli dan terbaru (berlogo timbul emas POLRI) - WAJIB
4. Fotokopi SKCK terbaru 1 lembar
5. Fotokopi Ijazah terakhir 1 lembar
6. Fotokopi Sertifikat Vaksin 1 lembar
7. Fotokopi NPWP yang dipadankan dengan NIK KTP 1 lembar
8. SIM BII UMUM Asli & Fotokopi 1 lembar

Berpakaian hitam putih dan tetap menggunakan masker.

Demikian untuk diketahui, mohon balas pesan ini untuk konfirmasi penerimaan. Terima kasih.

PERHATIAN!
Pemanggilan resmi hanya dari email HR VDNI: vdnirekrutmen88@gmail.com
EOT,



            'tes kesehatan' => <<<EOT
Selamat sore,

Bagi pelamar PT VDNI, diminta kehadirannya untuk mengikuti proses lanjutan yaitu Tes Kesehatan yang dilaksanakan pada:

Hari/Tanggal  : {$tanggal}
Waktu         : {$jam}
Tempat        : {$tempat}

Hal-hal yang harus diperhatikan:
- Membawa identitas masing-masing (KTP)
- Berpakaian rapi, bersepatu, dan menggunakan masker
EOT,

            'tes lapangan' => <<<EOT
Selamat sore,

Bagi peserta yang sudah lulus tes kesehatan, selanjutnya adalah Tes Lapangan yang akan dilaksanakan pada:

Hari/Tanggal  : {$tanggal}
Pukul         : {$jam}
Tempat        : {$tempat}

Membawa berkas berikut:
1. KTP ASLI
2. SIM BII UMUM ASLI

Wajib menggunakan sepatu
EOT,

            'medical check-up' => <<<EOT
Selamat siang,

Bagi peserta yang telah lulus interview di PT VDNI, untuk proses selanjutnya diminta mengikuti Medical Check-Up (MCU) yang dilaksanakan pada:

Hari/Tanggal  : {$tanggal}
Pukul         : {$jam}
Tempat        : {$tempat}
Alamat        : Jl. Malaka No.25, Anduonohu, Kec. Poasia, Kota Kendari, Sulawesi Tenggara 93231
Telepon       : (0401) 3081484
Lokasi        : https://maps.app.goo.gl/sLt8ZDfrtNPgG9HD8?g_st=iw

Membawa KTP, berpakaian rapi dan tetap menggunakan masker.

PERHATIAN!
1. Sampaikan bahwa Anda peserta MCU dari PT VDNI saat tiba di Klinik Rapha
2. Jadwal hanya 1 hari, mohon ikuti jadwal yang ditentukan

Terima kasih
EOT,

            'tanda tangan kontrak' => <<<EOT
PEMANGGILAN INDUKSI SAFETY & TANDA TANGAN KONTRAK
Selamat sore,
Bagi pelamar PT VDNI, diminta kehadirannya dalam mengikuti proses lanjutan yaitu INDUKSI SAFETY & TANDA TANGAN KONTRAK.

Hari/Tanggal : {$tanggal}
Pukul        : {$jam}
Tempat       : {$tempat}

Dengan melampirkan berkas sebagai berikut:
1. Fotocopy KTP 2 lembar
2. Fotocopy Kartu Keluarga 1 lembar
3. Fotocopy NPWP yang sudah dipadankan dengan NIK KTP ( WAJIB )
4. Form biodata diri yang terlampir di email, diprint dan diisi
5. SKCK Asli dan masih berlaku, berlogo emas timbul ( WAJIB )
6. Fotocopy buku rekening BNI (jika ada)

Catatan:
- Diharapkan sudah memiliki NPWP sebelum proses tanda tangan kontrak. Pendaftaran NPWP secara online di ereg.pajak.go.id/daftar, bagi yang sudah memiliki NPWP wajib dipadankan dengan NIK KTP di web CORETAX dan cukup untuk memfotocopy 1 lembar
- Berpakaian bebas, sopan, rapi, dan bersepatu
- Membawa alat tulis"
EOT,

            'aktif bekerja' => <<<EOT
Yth. Bapak/Ibu,

Terima kasih telah meluangkan waktu dan berkomitmen untuk bergabung bersama PT VDNI.
Kami mengucapkan selamat atas telah selesainya proses administrasi, termasuk penandatanganan kontrak kerja.

Kami sangat senang menyambut Anda sebagai bagian dari keluarga besar PT VDNI.
Silakan bersiap untuk memulai pekerjaan Anda sesuai jadwal yang telah disampaikan. Tim kami akan terus berkoordinasi untuk mendukung kelancaran proses awal Anda di perusahaan.

Apabila ada hal yang ingin ditanyakan atau dipersiapkan lebih lanjut, jangan ragu untuk menghubungi tim Rekrutmen kami.

Terima kasih atas kepercayaan dan kerja sama Anda.
Kami berharap Anda sukses dan berkembang bersama kami.

Salam hormat,
Rekrutmen PT VDNI"
EOT,

            'belum sesuai kriteria' => <<<EOT
Yth. Pelamar,

Terima kasih telah meluangkan waktu untuk melamar.

Setelah mempertimbangkan lamaran anda dengan seksama, kami menghargai minat dan usaha anda. Namun, untuk saat ini, kami memutuskan untuk melanjutkan proses dengan kandidat lain yang lebih sesuai dengan kualifikasi dan kebutuhan posisi tersebut.

Kami tetap menyimpan data anda dalam database kami, dan akan menghubungi anda jika ada kesempatan yang lebih sesuai di masa mendatang.

Terima kasih atas perhatian dan minat anda pada PT VDNI. Kami berharap anda sukses dalam perjalanan karier anda ke depan.

Salam hormat,
Rekrutmen PT VDNI"
EOT,


            'tidak lolos verifikasi online' => <<<EOT
Yth. Bapak/Ibu, 

Terima kasih telah meluangkan waktu untuk melamar dan mengirimkan berkas persyaratan kepada PT VDNI.

Setelah melakukan verifikasi berkas, kami mohon maaf karena berkas yang Anda kirimkan tidak memenuhi kriteria yang kami butuhkan saat ini.

Kami menghargai minat Anda untuk bergabung dengan perusahaan kami, namun saat ini kami tidak dapat melanjutkan proses lamaran Anda.

Kami menyarankan Anda untuk terus memantau lowongan pekerjaan yang kami buka di masa mendatang, dan kami berharap Anda dapat menemukan kesempatan yang lebih sesuai dengan kualifikasi dan pengalaman Anda.

Terima kasih atas perhatian dan kerja sama Anda.

Salam hormat,
Rekrutmen PT VDNI"
EOT,

            'tidak lolos verifikasi berkas' => <<<EOT
Yth. Bapak/Ibu,

Terima kasih telah meluangkan waktu untuk melamar dan mengirimkan berkas persyaratan kepada PT VDNI.

Setelah melakukan verifikasi berkas, kami mohon maaf karena berkas yang ditampilkan tidak memenuhi kriteria yang kami butuhkan saat ini.

Kami menghargai usaha dan komitmen Anda dalam mengikuti proses seleksi ini. Meskipun demikian, kami tidak dapat melanjutkan proses lamaran Anda pada tahap ini.

Kami menyarankan Anda untuk terus memantau lowongan pekerjaan yang kami buka di masa mendatang, dan kami berharap Anda dapat menemukan kesempatan yang lebih sesuai dengan kualifikasi dan pengalaman Anda.

Terima kasih atas perhatian dan kerja sama Anda.

Salam hormat,
Rekrutmen PT VDNI"
EOT,

            'tidak lolos tes kesehatan' => <<<EOT
Yth. Bapak/Ibu,

Terima kasih telah meluangkan waktu untuk mengikuti proses seleksi di PT VDNI.

Setelah melakukan evaluasi terhadap hasil Tes Kesehatan yang telah Anda jalani, kami mohon maaf karena Anda tidak memenuhi kriteria kesehatan yang kami butuhkan saat ini.

Kami menghargai usaha dan komitmen Anda dalam mengikuti proses seleksi ini. Meskipun demikian, kami tidak dapat melanjutkan proses lamaran Anda pada tahap ini.

Kami menyarankan Anda untuk terus memantau lowongan pekerjaan yang kami buka di masa mendatang, dan kami berharap Anda dapat menemukan kesempatan yang lebih sesuai dengan kualifikasi dan pengalaman Anda.

Terima kasih atas perhatian dan kerja sama Anda.

Salam hormat,
Rekrutmen PT VDNI"
EOT,

            'tidak lolos tes lapangan' => <<<EOT
Yth. Bapak/Ibu,

Terima kasih telah meluangkan waktu untuk mengikuti proses seleksi di PT VDNI.

Setelah melakukan evaluasi terhadap hasil Tes Lapangan yang telah Anda jalani, kami mohon maaf karena Anda tidak memenuhi kriteria yang kami butuhkan saat ini.

Kami menghargai usaha dan komitmen Anda dalam mengikuti proses seleksi ini. Meskipun demikian, kami tidak dapat melanjutkan proses lamaran Anda pada tahap ini.

Kami menyarankan Anda untuk terus memantau lowongan pekerjaan yang kami buka di masa mendatang, dan kami berharap Anda dapat menemukan kesempatan yang lebih sesuai dengan kualifikasi dan pengalaman Anda.

Terima kasih atas perhatian dan kerja sama Anda.

Salam hormat,
Rekrutmen PT VDNI"
EOT,

            'tidak lolos medical check-up' => <<<EOT

Yth. Bapak/Ibu,

Terima kasih telah meluangkan waktu untuk mengikuti proses seleksi di PT VDNI.

Setelah melakukan evaluasi terhadap hasil Medical Check-Up (MCU) yang telah Anda jalani, kami mohon maaf karena Anda tidak memenuhi kriteria kesehatan yang kami butuhkan saat ini.

Kami menghargai usaha dan komitmen Anda dalam mengikuti proses seleksi ini. Meskipun demikian, kami tidak dapat melanjutkan proses lamaran Anda pada tahap ini.

Kami menyarankan Anda untuk terus memantau lowongan pekerjaan yang kami buka di masa mendatang, dan kami berharap Anda dapat menemukan kesempatan yang lebih sesuai dengan kualifikasi dan pengalaman Anda.

Terima kasih atas perhatian dan kerja sama Anda.

Salam hormat,
Rekrutmen PT VDNI"
EOT,

            'tidak lolos induksi safety' => <<<EOT
Yth. Bapak/Ibu,

Terima kasih telah meluangkan waktu untuk mengikuti proses seleksi di PT VDNI.

Anda dinyatakan tidak lolos pada tahap induksi safety karena tidak mengikuti proses induksi safety sesuai jadwal yang telah ditentukan.

Kami menghargai usaha dan komitmen Anda dalam mengikuti proses seleksi ini. Meskipun demikian, kami tidak dapat melanjutkan proses lamaran Anda pada tahap ini.

Kami menyarankan Anda untuk terus memantau lowongan pekerjaan yang kami buka di masa mendatang, dan kami berharap Anda dapat menemukan kesempatan yang lebih sesuai dengan kualifikasi dan pengalaman Anda.

Terima kasih atas perhatian dan kerja sama Anda.

Salam hormat,
Rekrutmen PT VDNI"
EOT,

            'lolos verifikasi online' => <<<EOT
Yth. Bapak/Ibu,

Terima kasih telah meluangkan waktu untuk melamar dan mengirimkan berkas persyaratan kepada PT VDNI.

Setelah melakukan verifikasi berkas, kami dengan senang hati menginformasikan bahwa Anda telah lolos pada tahap Verifikasi Online.

Kami mengapresiasi usaha dan komitmen Anda dalam mengikuti proses seleksi ini. Selanjutnya, Anda akan diundang untuk mengikuti tahap berikutnya sesuai dengan jadwal yang akan kami sampaikan.

Kami berharap Anda dapat mempersiapkan diri dengan baik untuk tahap selanjutnya.

Terima kasih atas perhatian dan kerja sama Anda.

Salam hormat,
Rekrutmen PT VDNI"
EOT,

            'lolos verifikasi berkas' => <<<EOT
Yth. Bapak/Ibu,

Terima kasih telah meluangkan waktu untuk melamar dan mengirimkan berkas persyaratan kepada PT VDNI.

Setelah melakukan verifikasi berkas, kami dengan senang hati menginformasikan bahwa Anda telah lolos pada tahap Verifikasi Berkas.

Kami mengapresiasi usaha dan komitmen Anda dalam mengikuti proses seleksi ini. Selanjutnya, Anda akan diundang untuk mengikuti tahap berikutnya sesuai dengan jadwal yang akan kami sampaikan.

Kami berharap Anda dapat mempersiapkan diri dengan baik untuk tahap selanjutnya.

Terima kasih atas perhatian dan kerja sama Anda.

Salam hormat,
Rekrutmen PT VDNI"
EOT,

            'lolos tes kesehatan' => <<<EOT
Yth. Bapak/Ibu,

Terima kasih telah meluangkan waktu untuk mengikuti proses seleksi di PT VDNI.

Setelah melakukan evaluasi terhadap hasil Tes Kesehatan yang telah Anda jalani, kami dengan senang hati menginformasikan bahwa Anda telah lolos pada tahap Tes Kesehatan.

Kami mengapresiasi usaha dan komitmen Anda dalam mengikuti proses seleksi ini. Selanjutnya, Anda akan diundang untuk mengikuti tahap berikutnya sesuai dengan jadwal yang akan kami sampaikan.

Kami berharap Anda dapat mempersiapkan diri dengan baik untuk tahap selanjutnya.

Terima kasih atas perhatian dan kerja sama Anda.

Salam hormat,
Rekrutmen PT VDNI"
EOT,

            'lolos tes lapangan' => <<<EOT
Yth. Bapak/Ibu,

Terima kasih telah meluangkan waktu untuk mengikuti proses seleksi di PT VDNI.

Setelah melakukan evaluasi terhadap hasil Tes Lapangan yang telah Anda jalani, kami dengan senang hati menginformasikan bahwa Anda telah lolos pada tahap Tes Lapangan.

Kami mengapresiasi usaha dan komitmen Anda dalam mengikuti proses seleksi ini. Selanjutnya, Anda akan diundang untuk mengikuti tahap berikutnya sesuai dengan jadwal yang akan kami sampaikan.

Kami berharap Anda dapat mempersiapkan diri dengan baik untuk tahap selanjutnya.

Terima kasih atas perhatian dan kerja sama Anda.

Salam hormat,
Rekrutmen PT VDNI"
EOT,

            'lolos medical check-up' => <<<EOT
Yth. Bapak/Ibu,

Terima kasih telah meluangkan waktu untuk mengikuti proses seleksi di PT VDNI.

Setelah melakukan evaluasi terhadap hasil Medical Check-Up (MCU) yang telah Anda jalani, kami dengan senang hati menginformasikan bahwa Anda telah lolos pada tahap Medical Check-Up.

Kami mengapresiasi usaha dan komitmen Anda dalam mengikuti proses seleksi ini. Selanjutnya, Anda akan diundang untuk mengikuti tahap berikutnya sesuai dengan jadwal yang akan kami sampaikan.

Kami berharap Anda dapat mempersiapkan diri dengan baik untuk tahap selanjutnya.

Terima kasih atas perhatian dan kerja sama Anda.

Salam hormat,
Rekrutmen PT VDNI"
EOT,

        ];

        return $templates[$status] ?? null;
    }
}
