<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\Lamaran;
use App\Models\Lowongan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
use Yajra\DataTables\Facades\DataTables;

class PeralihanPelamarController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Biodata::with([
                'user:id,name,email',
                'getLatestRiwayatLamaran' => function ($lamaranQuery) {
                    $lamaranQuery
                        ->select(['id', 'biodata_id', 'loker_id', 'loker_id_lama', 'status_proses', 'created_at'])
                        ->with([
                            'lowongan:id,nama_lowongan',
                            'lowonganLama:id,nama_lowongan',
                        ]);
                },
            ])
                ->whereHas('getLatestRiwayatLamaran')
                ->select(['biodata.id', 'biodata.user_id', 'biodata.no_ktp']);

            return DataTables::of($query)
                ->addColumn('nama', function ($biodata) {
                    return optional($biodata->user)->name ?? '-';
                })
                ->editColumn('no_ktp', function ($biodata) {
                    return $biodata->no_ktp ?? '-';
                })
                ->addColumn('email', function ($biodata) {
                    return optional($biodata->user)->email ?? '-';
                })
                ->addColumn('lamaran', function ($biodata) {
                    return optional(optional($biodata->getLatestRiwayatLamaran)->lowongan)->nama_lowongan ?? '-';
                })
                ->addColumn('lamaran_lama', function ($biodata) {
                    return optional(optional($biodata->getLatestRiwayatLamaran)->lowonganLama)->nama_lowongan ?? '-';
                })
                ->addColumn('proses', function ($biodata) {
                    return optional($biodata->getLatestRiwayatLamaran)->status_proses ?? '-';
                })
                ->addColumn('aksi', function ($biodata) {
                    return '
                        <div class="d-flex justify-content-center">
                            <a href="' . route('peralihan.edit', $biodata->id) . '" class="btn btn-success btn-sm btn-icon-split">
                                <span class="icon text-white-50">
                                    <i class="fas fa-pen"></i>
                                </span>
                                <span class="text">Alihkan</span>
                            </a>
                        </div>';
                })
                ->filterColumn('nama', function ($query, $keyword) {
                    $query->whereHas('user', function ($userQuery) use ($keyword) {
                        $userQuery->where('name', 'like', '%' . $keyword . '%');
                    });
                })
                ->filterColumn('email', function ($query, $keyword) {
                    $query->whereHas('user', function ($userQuery) use ($keyword) {
                        $userQuery->where('email', 'like', '%' . $keyword . '%');
                    });
                })
                ->filterColumn('lamaran', function ($query, $keyword) {
                    $query->whereHas('getLatestRiwayatLamaran.lowongan', function ($lowonganQuery) use ($keyword) {
                        $lowonganQuery->where('nama_lowongan', 'like', '%' . $keyword . '%');
                    });
                })
                ->filterColumn('lamaran_lama', function ($query, $keyword) {
                    $query->whereHas('getLatestRiwayatLamaran.lowonganLama', function ($lowonganQuery) use ($keyword) {
                        $lowonganQuery->where('nama_lowongan', 'like', '%' . $keyword . '%');
                    });
                })
                ->filterColumn('proses', function ($query, $keyword) {
                    $query->whereHas('getLatestRiwayatLamaran', function ($lamaranQuery) use ($keyword) {
                        $lamaranQuery->where('status_proses', 'like', '%' . $keyword . '%');
                    });
                })
                ->only(['nama', 'no_ktp', 'email', 'lamaran', 'lamaran_lama', 'proses', 'aksi'])
                ->rawColumns(['aksi'])
                ->make(true);
        }

        return view('admin.peralihan-pelamar.index')->with('no');
    }

    public function edit($id)
    {
        $biodata = Biodata::with([
            'user',
            'getLatestRiwayatLamaran.lowongan',
        ])->where('id', $id)->firstOrFail(['id', 'user_id', 'no_ktp']);

        $latestLamaran = $biodata->getLatestRiwayatLamaran;

        if (! $latestLamaran) {
            Alert::warning('Peringatan', 'Pelamar ini belum memiliki lamaran yang bisa dialihkan.');
            return redirect()->route('peralihan.index');
        }

        $lowongans = Lowongan::select('*')
            ->selectRaw("IF(tanggal_berakhir < ?, 'Kadaluwarsa', 'Aktif') as status_lowongan", [Carbon::now()])
            ->where('tanggal_mulai', '<=', Carbon::now()) // hanya yang sudah mulai
            ->having('status_lowongan', '=', 'Aktif')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.peralihan-pelamar.edit', compact('biodata', 'latestLamaran', 'lowongans'))->with('no');
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'loker_id' => ['required', 'exists:lowongan,id'],
        ]);

        $lamaran = Lamaran::find($id);

        if ($lamaran) {
            $lamaran->where('loker_id', $lamaran->loker_id)->where('biodata_id', $lamaran->biodata_id)->update([
                'loker_id' => $validatedData['loker_id'],
                'loker_id_lama' => $lamaran->loker_id
            ]);

            Alert::success('Berhasil', 'Data pelamar berhasil dialihkan');
            return back();
        }

        Alert::error('Gagal', 'Data tidak berhasil diperbarui');
        return back();
    }
}
