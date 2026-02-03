<?php

namespace App\Services\DocumentCheck;

use App\Models\Biodata;
use App\Models\Lowongan;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class DocumentCheck
{
    public function checkDocument($id)
    {
        $userId = auth()->id();

        $nama_sim = null;
        $tanggl_lahir_sim = null;
        $berlaku_sim = null;

        $aktif = 1;
        $today = Carbon::today()->toDateString();

        $lowongan = Lowongan::selectRaw("*, IF(tanggal_berakhir < '$today', 'Kadaluwarsa', 'Aktif') as status_lowongan")->findOrFail($id);

        if (!$lowongan) {
            Alert::warning('Opps!', 'Lowongan tidak ditemukan, coba beberapa saat lagi');
            return back();
        }

        if (!Auth::user()) {
            Alert::info('Opps!', 'Silahkan login untuk melihat lowongan kerja.');
            return redirect()->route('login');
        }

        $biodata = Biodata::where('user_id', $userId)->first();

        if (!$biodata) {
            Alert::info('Opss!', 'Lengkapi formulir biodata anda terlebih dahulu sebelum melamar lowongan kerja.');
            return redirect()->route('biodata.index');
        }

        if ($biodata->vaksin == null) {
            Alert::info('Opss!', 'Harap mengisi vaksinasi yang pernah dilakukan');
            return redirect()->route('biodata.index');
        }

        // Jika lowongan memerlukan SIM B2
        if ($lowongan->status_sim_b2 == $aktif) {

            if (empty($biodata->sim_b_2)) {
                Alert::info('Opss!', 'Untuk melamar lowongan ini, silakan upload foto SIM B II Umum terlebih dahulu.');
                return redirect()->to(route('biodata.index') . '#step5');
            }

            if (empty($biodata->parsed_sim_b2)) {
                Alert::info('Opss!', 'Silakan kirim ulang foto SIM B II Umum kamu.');
                return redirect()->to(route('biodata.index') . '#step5');
            }

            $res_ocr_simb2 = $biodata->parsed_sim_b2 ?? [];

            $nama_sim = strtoupper(data_get($res_ocr_simb2, 'nama') ?? '');
            $tanggl_lahir_sim = data_get($res_ocr_simb2, 'tanggal_lahir') ?? '';
            $berlaku_sim = data_get($res_ocr_simb2, 'berlaku_sampai') ?? null;
        }

        if ($lowongan->status_sio == $aktif) {
            if (empty($biodata->sio)) {
                Alert::info('Opss!', 'Untuk melamar lowongan ini, silakan upload foto SIO terlebih dahulu.');
                return redirect()->to(route('biodata.index') . '#step5');
            }
        }

        // === Bagian Optimasi OCR (pakai cache file) ===
        $filePath = public_path($biodata->no_ktp . '/dokumen/' . $biodata->ktp);

        if (!$biodata->ktp || !file_exists($filePath)) {
            Alert::warning('Gagal', 'File KTP tidak ditemukan, silakan upload KTP terlebih dahulu.');
            return redirect()->to(route('biodata.index') . '#step5');
        }

        if (!$biodata->ocr_ktp) {
            Alert::info('Opss!', 'Silakan kirim ulang foto KTP kamu.');
            return redirect()->to(route('biodata.index') . '#step5');
        }

        $ocrData = $biodata->ocr_ktp;

        // === Gunakan hasil OCR ===
        if (!$ocrData) {
            Alert::info('Opss!', 'Silakan lengkapi dokumen pribadi yang dibutuhkan terlebih dahulu.');
            return redirect()->to(route('biodata.index') . '#step5');
        }

        $ocrResult = [
            'nama_ktp'        => strtoupper(data_get($ocrData, 'result.nama.value', '')),
            'nik_ktp'         => data_get($ocrData, 'result.nik.value', ''),
            'tgl_lahir_ktp'   => data_get($ocrData, 'result.tanggalLahir.value', ''),
            'nama_sim'        => $nama_sim,
            'tgl_lahir_sim'   => $tanggl_lahir_sim,
            'expired_sim'     => $berlaku_sim,
        ];

        // Cek expired SIM
        if ($berlaku_sim) {
            $expiredSim = DateTime::createFromFormat('d-m-Y', $berlaku_sim);
            $todayDate = new DateTime();
            $msg_expired_sim = $expiredSim < $todayDate ? 'EXPIRED' : null;
            $biodata->update(['status_sim_b2' => $msg_expired_sim]);
        }

        // Bandingkan data KTP vs SIM
        $msg_name_ktp_vs_sim_b2 = null;
        $msg_date_ktp_vs_sim_b2 = null;

        if ($lowongan->status_sim_b2) {

            // ===== VALIDASI NAMA =====
            if (empty($nama_sim)) {
                $msg_name_ktp_vs_sim_b2 = 'Nama pada SIM B II Umum tidak terbaca.';
            } else {

                $normalizeName = function ($name) {
                    $name = strtoupper(trim($name));
                    $name = preg_replace('/[^A-Z\s]/', '', $name); // hapus titik & simbol
                    return array_values(array_filter(explode(' ', $name)));
                };

                $ktpParts = $normalizeName($ocrResult['nama_ktp'] ?? '');
                $simParts = $normalizeName($ocrResult['nama_sim'] ?? '');

                $valid = true;

                // Nama depan wajib sama
                if (empty($ktpParts[0]) || empty($simParts[0]) || $ktpParts[0] !== $simParts[0]) {
                    $valid = false;
                } else {
                    // Setiap kata SIM harus cocok (full / inisial) dengan KTP
                    foreach ($simParts as $i => $simWord) {
                        $ktpWord = $ktpParts[$i] ?? null;

                        if (!$ktpWord || strpos($ktpWord, $simWord) !== 0) {
                            $valid = false;
                            break;
                        }
                    }
                }

                if (!$valid) {
                    $msg_name_ktp_vs_sim_b2 = 'Nama pada SIM B2 tidak sesuai dengan KTP.';
                }
            }

            // ===== VALIDASI TANGGAL LAHIR =====
            if (empty($tanggl_lahir_sim)) {
                $msg_date_ktp_vs_sim_b2 = 'Tanggal lahir pada SIM B II Umum tidak terbaca.';
            } elseif ($ocrResult['tgl_lahir_ktp'] != $ocrResult['tgl_lahir_sim']) {
                $msg_date_ktp_vs_sim_b2 = 'Tanggal lahir pada SIM B2 tidak sesuai dengan KTP.';
            }
        }

        // Cek field kosong
        $fieldLabels = $this->getFieldLabels($lowongan->status_sim_b2, $lowongan->status_sio);
        $emptyFields = collect($fieldLabels)->filter(fn($label, $field) => empty($biodata->$field))->values()->all();

        $msg_no_ktp = $ocrResult['nik_ktp'] !== $biodata->no_ktp ? 'No KTP tidak sesuai dengan biodata anda.' : null;

        if (count($emptyFields) || $msg_no_ktp || $msg_name_ktp_vs_sim_b2 || $msg_date_ktp_vs_sim_b2) {
            return view('user.lowongan-kerja.verifikasi', [
                'emptyFields' => $emptyFields,
                'msg_no_ktp' => $msg_no_ktp,
                'msg_name_ktp_vs_sim_b2' => $msg_name_ktp_vs_sim_b2,
                'msg_date_ktp_vs_sim_b2' => $msg_date_ktp_vs_sim_b2,
                'biodata' => $biodata,
            ]);
        }

        return; // lanjut ke store()
    }

    public function getFieldLabels($status_sim_b2 = 0, $status_sio = 0)
    {
        $fieldLabels = [
            // Identitas Pribadi
            'no_ktp' => 'Nomor KTP',
            'no_telp' => 'Nomor Telepon',
            'no_kk' => 'Nomor Kartu Keluarga',
            'jenis_kelamin' => 'Jenis Kelamin',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'vaksin' => 'Vaksin',

            // Alamat Domisili
            'provinsi' => 'Provinsi',
            'kabupaten' => 'Kabupaten/Kota',
            'kecamatan' => 'Kecamatan',
            'kelurahan' => 'Kelurahan/Desa',
            'alamat' => 'Alamat Lengkap',
            'kode_pos' => 'Kode Pos',
            'rt' => 'RT',
            'rw' => 'RW',

            // Informasi Pribadi Tambahan
            'hobi' => 'Hobi',
            'golongan_darah' => 'Golongan Darah',
            'tinggi_badan' => 'Tinggi Badan (cm)',
            'berat_badan' => 'Berat Badan (kg)',

            // Riwayat Pendidikan
            'pendidikan_terakhir' => 'Pendidikan Terakhir',
            'nama_instansi' => 'Nama Instansi Pendidikan',
            'jurusan' => 'Jurusan',
            'nilai_ipk' => 'Nilai IPK/NEM',
            'tahun_lulus' => 'Tahun Lulus',

            // Data Keluarga
            'nama_ayah' => 'Nama Ayah',
            'nama_ibu' => 'Nama Ibu',
            'status_pernikahan' => 'Status Pernikahan',

            // Kontak Darurat
            'nama_kontak_darurat' => 'Nama Kontak Darurat',
            'no_telepon_darurat' => 'Nomor Telepon Darurat',
            'status_hubungan' => 'Hubungan dengan Kontak Darurat',

            // Dokumen Wajib
            'cv' => 'Curriculum Vitae (CV)',
            'pas_foto' => 'Pas Foto',
            'surat_lamaran' => 'Surat Lamaran',
            'ijazah' => 'Ijazah',
            'ktp' => 'KTP (Kartu Tanda Penduduk)',
            'skck' => 'SKCK (Surat Keterangan Catatan Kepolisian)',
            'sertifikat_vaksin' => 'Sertifikat Vaksin',
            'kartu_keluarga' => 'Kartu Keluarga (KK)',
            'npwp' => 'NPWP (Nomor Pokok Wajib Pajak)',
            'ak1' => 'Kartu AK1 (Kartu Pencari Kerja)',
        ];

        // Tambahkan SIM B II jika status_sim_b2 aktif (= 1)
        if ($status_sim_b2 == 1) {
            $fieldLabels['sim_b_2'] = 'SIM B II Umum';
        }

        // Tambahkan SIO jika status_sio aktif (= 1)
        if ($status_sio == 1) {
            $fieldLabels['sio'] = 'SIO (Surat Izin Operator)';
        }

        return $fieldLabels;
    }
}
