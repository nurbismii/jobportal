<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use App\Models\Lamaran;
use App\Models\Lowongan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Storage;

class LowonganController extends Controller
{
    public function index()
    {
        $lowongans = Lowongan::orderBy('id', 'desc')->get();

        return view('user.lowongan-kerja.index', compact('lowongans'));
    }

    public function show($id)
    {
        $biodata = Biodata::where('user_id', auth()->id())->first();

        if (!$biodata) {
            Alert::info('Informasi', 'Silahkan login terlebih dahulu untuk melihat lowongan kerja.');
            return redirect()->route('login');
        }

        $filePath = public_path($biodata->no_ktp . '/dokumen/' . $biodata->ktp);

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File KTP tidak ditemukan.'], 404);
        }

        $url = config('services.ocr.link') . '/' . config('services.ocr.type');

        $response = Http::withToken(config('services.ocr.token'))
            ->withHeaders([
                'Authentication' => 'bearer ' . config('services.ocr.token'),
            ])
            ->attach('file', file_get_contents($filePath), $biodata->ktp)
            ->put($url);

        if (!$response->successful()) {
            return response()->json([
                'status' => 'error',
                'http_code' => $response->status(),
                'reason' => $response->reason(),
                'headers' => $response->headers(),
                'body' => $response->body(),
            ]);
        }

        $ocrData = $response->json();

        // Validasi NIK hasil OCR dengan model
        $ocrNik = $ocrData['result']['nik']['value'] ?? null;
        $ocrNikScore = $ocrData['result']['nik']['score'] ?? null;

        $lowongan = Lowongan::findOrFail($id);

        if ($lowongan) {

            if ($lowongan->status_sim_b2 == '0') {

                $fieldLabels = [

                    // Identitas Pribadi
                    'user_id' => 'ID Pengguna',
                    'no_ktp' => 'Nomor KTP',
                    'no_telp' => 'Nomor Telepon',
                    'no_kk' => 'Nomor Kartu Keluarga',
                    'jenis_kelamin' => 'Jenis Kelamin',
                    'tempat_lahir' => 'Tempat Lahir',
                    'tanggal_lahir' => 'Tanggal Lahir',

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
                    'tahun_masuk' => 'Tahun Masuk',
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
            } else {

                $fieldLabels = [

                    // Identitas Pribadi
                    'user_id' => 'ID Pengguna',
                    'no_ktp' => 'Nomor KTP',
                    'no_telp' => 'Nomor Telepon',
                    'no_kk' => 'Nomor Kartu Keluarga',
                    'jenis_kelamin' => 'Jenis Kelamin',
                    'tempat_lahir' => 'Tempat Lahir',
                    'tanggal_lahir' => 'Tanggal Lahir',

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
                    'tahun_masuk' => 'Tahun Masuk',
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
            }

            // Cek field yang kosong
            $emptyFields = [];
            foreach ($fieldLabels as $field => $label) {
                if (empty($biodata->$field)) {
                    $emptyFields[] = $label;
                }
            }

            // Jika ada field kosong, kembalikan dengan alert
            if (count($emptyFields) || $ocrNikScore < 70 || $ocrNik !== $biodata->no_ktp) {

                $fieldList = implode(', ', $emptyFields);

                $msg_no_ktp = $ocrNik !== $biodata->no_ktp ? 'No KTP yang diambil dari hasil OCR tidak sesuai dengan data biodata Anda.' : null;
                $msg_no_ktp_score =  $ocrNikScore < 70 ? 'Skor kecocokan NIK hasil OCR terlalu rendah. silahkan perbarui KTP pada dokumnen biodata Anda.' : null;

                return view('user.lowongan-kerja.verifikasi',  [
                    'emptyFields' => $emptyFields,
                    'msg_no_ktp' => $msg_no_ktp ?? null,
                    'msg_no_ktp_score' => $msg_no_ktp_score ?? null,
                ]);
            }

            $lowongan = Lowongan::findOrFail($id);

            return view('user.lowongan-kerja.show', compact('lowongan', 'biodata'));
        }
    }

    public function store(Request $request)
    {
        $lamaran = Lamaran::where('biodata_id', $request->biodata_id)
            ->where('loker_id', $request->loker_id)
            ->first();

        if ($lamaran) {
            Alert::warning('Peringatan', 'Anda sudah melamar pekerjaan ini sebelumnya.');
            return redirect()->back();

            if ($lamaran->status_lamaran == '1') {
                Alert::warning('Peringatan', 'Saat ini Anda dalam proses lamaran, tidak dapat melamar lebih dari satu lowongan.');
                return redirect()->back();
            }
        }

        Lamaran::create([
            'loker_id' => $request->loker_id,
            'biodata_id' => $request->biodata_id,
            'status_lamaran' => '1',
            'status_proses' => 'Lamaran Dikirim',
        ]);

        Alert::success('Lamaran Anda Sudah Kami Terima', 'Terima kasih telah melamar pekerjaan di perusahaan kami. Kami akan segera memproses lamaran Anda.');
        return redirect()->back();
    }
}
