<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\PermintaanTenagaKerja;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class LowonganController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::now()->toDateTimeString();

        $query = Lowongan::selectRaw("*, IF(tanggal_berakhir < '$today', 'Kadaluwarsa', 'Aktif') as status_lowongan")->withCount('lamarans');

        $title = 'Hapus Lowongan!';
        $text = "Kamu yakin ingin menghapus lowongan ini?";
        confirmDelete($title, $text);

        if ($request->has('search') && $request->search != '') {
            $query->where('nama_lowongan', 'like', '%' . $request->search . '%');
        }

        $lowongans = $query->orderBy('created_at', 'desc')->paginate(6);

        return view('admin.lowongan-kerja.index', compact('lowongans'));
    }

    public function create()
    {
        $permintaanTenagaKerjas = PermintaanTenagaKerja::where('status_ptk', '!=', 'Selesai')->orderBy('created_at', 'desc')->get();

        return view('admin.lowongan-kerja.create', compact('permintaanTenagaKerjas'));
    }

    public function store(Request $request)
    {
        // Buat data lowongan kerja baru
        Lowongan::create([
            'permintaan_tenaga_kerja_id' => $request->ptk_id,
            'nama_lowongan' => $request->nama_lowongan,
            'kualifikasi' => $request->kualifikasi,
            'status_sim_b2' => $request->status_sim_b2,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_berakhir' => $request->tanggal_berakhir,
        ]);

        Alert::success('Berhasil', 'Lowongan kerja berhasil ditambahkan!');
        return redirect()->back();
    }

    public function edit($id)
    {
        $lowongan = Lowongan::findOrFail($id);

        return view('admin.lowongan-kerja.edit', compact('lowongan'));
    }

    public function update(Request $request, $id)
    {
        // Update data lowongan kerja
        $lowongan = Lowongan::findOrFail($id);
        $lowongan->update([
            'nama_lowongan' => $request->nama_lowongan,
            'kualifikasi' => $request->kualifikasi,
            'status_sim_b2' => $request->status_sim_b2,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_berakhir' => $request->tanggal_berakhir,
        ]);

        Alert::success('Berhasil', 'Lowongan kerja berhasil diperbarui!');
        return redirect()->route('lowongan.index');
    }

    public function destroy($id)
    {
        // Hapus data lowongan kerja
        $lowongan = Lowongan::findOrFail($id);

        Lamaran::where('loker_id', $lowongan->id)->delete();

        $lowongan->delete();

        Alert::success('Berhasil', 'Lowongan kerja berhasil dihapus!');
        return redirect()->back();
    }

    public function directToLamaran(Request $request, $loker_id)
    {
        $lowongan = Lowongan::select('id', 'nama_lowongan', 'status_sim_b2')->where('id', $loker_id)->first();

        $query = Lamaran::with([
            'lowongan',
            'biodata.user',
            'biodata.getRiwayatInHris',
            'biodata.getProvinsi',
            'biodata.getKabupaten',
            'biodata.getKecamatan',
            'biodata.getKelurahan',
            'biodata.user.suratPeringatan',
        ])->where('loker_id', $loker_id);

        // Filter status proses (multiple)
        if ($request->filled('status')) {
            $statusArray = is_array($request->status) ? $request->status : [$request->status];
            $query->whereIn('status_proses', $statusArray);
        }

        // Filter pendidikan (multiple)
        if ($request->filled('pendidikan')) {
            $pendidikanArray = is_array($request->pendidikan) ? $request->pendidikan : explode(',', $request->pendidikan);
            $query->whereHas('biodata', function ($q) use ($pendidikanArray) {
                $q->whereIn('pendidikan_terakhir', $pendidikanArray);
            });
        }

        // Filter umur
        if ($request->filled('umur_min') || $request->filled('umur_max')) {
            $query->whereHas('biodata', function ($q) use ($request) {
                if ($request->filled('umur_min')) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >= ?', [$request->umur_min]);
                }
                if ($request->filled('umur_max')) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) <= ?', [$request->umur_max]);
                }
            });
        }

        // Filter status resign (multiple)
        if ($request->filled('status_resign')) {
            $statusResignArray = is_array($request->status_resign) ? $request->status_resign : [$request->status_resign];
            $noKtpList = \App\Models\Hris\Employee::whereIn('status_resign', $statusResignArray)
                ->pluck('no_ktp')
                ->toArray();

            $query->whereHas('biodata', function ($q) use ($noKtpList) {
                $q->whereIn('no_ktp', $noKtpList);
            });
        }

        $lamarans = $query->get();

        return view('admin.lamaran.index', compact('lamarans', 'lowongan'))->with('no');
    }

    // Refresh status pelamar berdasarkan data dari HRIS
    public function refreshDataPelamar(Request $request)
    {
        $noKtpArray = (array) $request->input('no_ktp', []);

        foreach ($noKtpArray as $noKtp) {
            $user = \App\Models\User::whereHas('biodata', function ($q) use ($noKtp) {
                $q->where('no_ktp', $noKtp);
            })->first();

            if (!$user) {
                continue;
            }

            $biodata = $user->biodata;
            $hrisEmployee = $biodata->getRiwayatInHris()
                ->where('no_ktp', $biodata->no_ktp)
                ->orderBy('tgl_resign', 'asc')
                ->first();

            if ($hrisEmployee) {
                $user->status_pelamar = $hrisEmployee->status_resign;
                $user->area_kerja = $hrisEmployee->area_kerja;
            } else {
                $user->status_pelamar = Null;
                $user->area_kerja = Null;
            }

            $user->save();
        }

        Alert::success('Berhasil', 'Status pelamar berhasil di-refresh!');
        return redirect()->back();
    }
}
