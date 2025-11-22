<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hris\Employee;
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
            'status_sio' => $request->status_sio,
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
            'status_sio' => $request->status_sio,
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
        $lowongan = Lowongan::select('id', 'nama_lowongan', 'status_sim_b2', 'status_sio')->where('id', $loker_id)->first();

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

        if ($request->filled('jenis_kelamin')) {
            $query->whereHas('biodata', function ($q) use ($request) {
                $q->where('jenis_kelamin', $request->jenis_kelamin);
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

            $containsPendaftar = in_array('PENDAFTAR BERSIH', $statusResignArray);
            $otherStatuses = array_filter($statusResignArray, function ($s) {
                return $s !== 'PENDAFTAR BERSIH';
            });

            $noKtpList = [];
            if (count($otherStatuses) > 0) {
                $noKtpList = \App\Models\Hris\Employee::whereIn('status_resign', $otherStatuses)
                    ->pluck('no_ktp')
                    ->toArray();
            }

            if ($containsPendaftar && count($noKtpList) > 0) {
                // Gabungkan pendaftar bersih (status_pelamar NULL) dan karyawan dengan status_resign tertentu
                $query->where(function ($q) use ($noKtpList) {
                    $q->whereHas('biodata.user', function ($q2) {
                        $q2->whereNull('status_pelamar');
                    })->orWhereHas('biodata', function ($q3) use ($noKtpList) {
                        $q3->whereIn('no_ktp', $noKtpList);
                    });
                });
            } elseif ($containsPendaftar) {
                // Hanya pendaftar bersih
                $query->whereHas('biodata.user', function ($q) {
                    $q->whereNull('status_pelamar');
                });
            } elseif (count($noKtpList) > 0) {
                // Hanya status_resign lainnya
                $query->whereHas('biodata', function ($q) use ($noKtpList) {
                    $q->whereIn('no_ktp', $noKtpList);
                });
            } else {
                // Tidak ada status yang valid — tidak mengembalikan hasil
                $query->whereRaw('0 = 1');
            }
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

            $hrisEmployee = Employee::where('no_ktp', $noKtp)
                ->orderByRaw('LEFT(nik, 4) DESC')
                ->first();

            if ($hrisEmployee) {
                $user->status_pelamar = $hrisEmployee->status_resign;
                $user->area_kerja = $hrisEmployee->area_kerja;
                $user->tanggal_resign = $hrisEmployee->tgl_resign;
                $user->ket_resign = $hrisEmployee->alasan_resign;
            } else {
                $user->status_pelamar = Null;
                $user->area_kerja = Null;
                $user->tanggal_resign = Null;
                $user->ket_resign = Null;
            }

            $user->save();
        }

        Alert::success('Berhasil', 'Status pelamar berhasil di-refresh!');
        return redirect()->back();
    }
}
