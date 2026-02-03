<?php

namespace App\Http\Controllers;

use App\Models\Biodata;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\RiwayatProsesLamaran;
use App\Services\DocumentCheck\DocumentCheck;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class LowonganController extends Controller
{
    public function index()
    {
        $lowongans = Lowongan::select('*')
            ->selectRaw("IF(tanggal_berakhir < ?, 'Kadaluwarsa', 'Aktif') as status_lowongan", [Carbon::now()])
            ->where('tanggal_mulai', '<=', Carbon::now()) // hanya yang sudah mulai
            ->having('status_lowongan', '=', 'Aktif')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('user.lowongan-kerja.index', compact('lowongans'));
    }

    public function show($id)
    {
        $today = Carbon::now()->toDateTimeString();

        $lowongan = Lowongan::selectRaw("*, IF(tanggal_berakhir < '$today', 'Kadaluwarsa', 'Aktif') as status_lowongan")->findOrFail($id);
        $biodata = Biodata::where('user_id', auth()->id())->first();

        $documentCheck = new DocumentCheck();
        $fieldLabels = $documentCheck->getFieldLabels($lowongan->status_sim_b2, $lowongan->status_sio);

        return view('user.lowongan-kerja.show', compact('lowongan', 'biodata', 'fieldLabels'));
    }

    public function store(Request $request)
    {
        $lamaran = Lamaran::where('biodata_id', $request->biodata_id)->latest()->first();

        if ($lamaran) {

            if ($lamaran->loker_id == $request->loker_id) {
                Alert::warning('Peringatan', 'Kamu telah melamar lowongan ini sebelumnya.');
                return redirect()->route('lamaran.index');
            }

            if ($lamaran->status_lamaran == '1') {
                Alert::warning('Peringatan', 'Lamaran kamu sedang dalam proses, harap tunggu hingga proses selesai sebelum melamar lagi.');
                return redirect()->route('lamaran.index');
            }
        }

        $documentCheck = new DocumentCheck();
        $cekBerkas = $documentCheck->checkDocument($request->loker_id);

        if ($cekBerkas) {
            return $cekBerkas; // Jika ada pesan verifikasi, kembalikan ke view verifikasi
        }

        try {
            DB::beginTransaction();

            $lamaran = Lamaran::create([
                'loker_id' => $request->loker_id,
                'biodata_id' => $request->biodata_id,
                'status_lamaran' => '1',
                'status_proses' => 'Lamaran Dikirim',
            ]);

            RiwayatProsesLamaran::create([
                'user_id' => auth()->id(),
                'lamaran_id' => $lamaran->id,
                'tanggal_proses' => Carbon::now(),
                'jam' => Carbon::now()->format('H:i:s'),
                'status_proses' => $lamaran->status_proses,
                'tempat' => 'Online (Website)',
                'pesan' => 'Lamaran telah dikirim pada ' . Carbon::now()->format('d-m-Y H:i:s'),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Gagal', 'Terjadi kesalahan saat mengirim lamaran' . ': ' . $e->getMessage());
            return redirect()->back();
        }

        Alert::success('Lamaran Anda Sudah Kami Terima', 'Terima kasih telah melamar pekerjaan di perusahaan kami. Kami akan segera memproses lamaran Anda.');
        return redirect()->back();
    }
}
