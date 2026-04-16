<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\Hris\Provinsi;
use App\Models\Lamaran;
use App\Models\RiwayatProsesLamaran;
use App\Models\SuratPeringatan;
use App\Models\User;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
use Yajra\DataTables\Facades\DataTables;

class PenggunaController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Hapus Pengguna!';
        $text = "Kamu yakin ingin menghapus pengguna ini?";
        confirmDelete($title, $text);

        if ($request->ajax()) {
            $query = User::with([
                'biodataUser' => function ($query) {
                    $query->with('getLatestRiwayatLamaran.lowongan')
                        ->withCount('getRiwayatLamaran');
                }
            ])
                ->where('role', '!=', 'admin');

            return DataTables::of($query)

                ->addColumn('status', function ($pengguna) {
                    $checked = $pengguna->status_akun == 1 ? 'checked' : '';

                    return '
            <label class="toggle-switch">
                <input type="checkbox"
                    class="toggle-input toggle-status"
                    data-id="' . $pengguna->id . '"
                    data-old="' . $pengguna->status_akun . '"
                    ' . $checked . '>
                <span class="toggle-slider">
                    <span class="toggle-text">ON</span>
                </span>
            </label>';
                })

                ->addColumn('status_proses', function ($pengguna) {
                    return optional(optional($pengguna->biodataUser)->getLatestRiwayatLamaran)->status_proses ?? '-';
                })

                ->addColumn('lowongan', function ($pengguna) {

                    $riwayat = optional($pengguna->biodataUser)->getLatestRiwayatLamaran;

                    if (!$riwayat || !$riwayat->lowongan) {
                        return '-';
                    }

                    $lowongan = $riwayat->lowongan;
                    $nama = substr($lowongan->nama_lowongan, 0, 15);

                    $url = route('directToLamaran', [
                        'loker_id' => $lowongan->id,
                        'user_id' => $pengguna->id
                    ]);

                    return '<a href="' . $url . '" target="_blank">' . $nama . '</a>';
                })

                ->addColumn('jumlah_melamar', function ($pengguna) {
                    return optional($pengguna->biodataUser)->get_riwayat_lamaran_count ?? 0;
                })

                ->addColumn('rekomendasi', function ($pengguna) {
                    return $pengguna->rekomendasi ?? '';
                })

                ->addColumn('riwayat', function ($pengguna) {
                    return '
            <a href="' . route('pengguna.show', $pengguna->id) . '" target="_blank"
            class="btn btn-secondary btn-sm btn-icon-split mr-2">
                <span class="icon text-white-50">
                    <i class="fas fa-history"></i>
                </span>
                <span class="text">Riwayat</span>
            </a>';
                })

                ->addColumn('aksi', function ($pengguna) {
                    return '
            <div class="d-flex">
                <a href="' . route('pengguna.edit', $pengguna->id) . '"
                class="btn btn-success btn-sm btn-icon-split mr-2">
                    <span class="icon text-white-50">
                        <i class="fas fa-pen"></i>
                    </span>
                    <span class="text">Edit</span>
                </a>

                <a href="' . route('pengguna.destroy', $pengguna->id) . '"
                class="btn btn-danger btn-sm btn-icon-split"
                data-confirm-delete="true">
                    <span class="icon text-white-50">
                        <i class="fas fa-trash"></i>
                    </span>
                    <span class="text">Hapus</span>
                </a>
            </div>';
                })

                ->rawColumns(['status', 'riwayat', 'lowongan', 'aksi'])
                ->make(true);
        }

        return view('admin.pengguna.index');
    }

    public function edit($id)
    {
        // Logic to edit a user
        $pengguna = User::with('biodata')->findOrFail($id);
        $biodata = Biodata::where('user_id', $pengguna->id)->first();

        if ($biodata) {
            $provinsis = Provinsi::all();

            return view('admin.pengguna.edit', compact('pengguna', 'biodata', 'provinsis'));
        }
        Alert::warning('Peringatan', 'Biodata pengguna belum tersedia.');
        return back();
    }

    public function update(Request $request, $id)
    {
        // Logic to update a biodata
        $user = user::where('id', $id)->first();

        $user->update([
            'status_akun' => $request->status_akun
        ]);

        Alert::success('Berhasil', 'Pengguna berhasil diperbarui.');
        return redirect()->route('pengguna.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function show($id)
    {
        // Logic to show user details
        $user = User::with('biodataUser.getRiwayatLamaran')->findOrFail($id);

        return view('admin.pengguna.show', compact('user'));
    }

    public function destroy($id)
    {
        // Logic to delete a user
        $user = User::findOrFail($id);

        $biodata = Biodata::where('user_id', $user->id)->first();

        deleteImageBiodata($biodata);

        if ($biodata) {
            Lamaran::where('biodata_id', $biodata->id)->delete();
        }

        RiwayatProsesLamaran::where('user_id', $user->id)->delete();

        SuratPeringatan::where('user_id', $user->id)->delete();

        $user->delete();

        Alert::success('Berhasil', 'Pengguna berhasil dihapus.');
        return redirect()->route('pengguna.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    public function updateStatusAkun(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
            'status_akun' => 'required|in:0,1',
        ]);

        User::where('id', $request->id)->update([
            'status_akun' => $request->status_akun
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status akun berhasil diperbarui'
        ]);
    }
}
