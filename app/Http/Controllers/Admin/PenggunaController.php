<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\Hris\Provinsi;
use App\Models\Lamaran;
use App\Models\SuratPeringatan;
use App\Models\User;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class PenggunaController extends Controller
{
    public function index()
    {
        // Logic to display the list of users
        $penggunas = User::with('biodataUser.getLatestRiwayatLamaran.lowongan')->where('role', '!=', 'admin')->get();
        $title = 'Hapus Pengguna!';
        $text = "Kamu yakin ingin menghapus pengguna ini?";
        confirmDelete($title, $text);

        return view('admin.pengguna.index', compact('penggunas'))->with('no');
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

        if ($biodata) {
            Lamaran::where('biodata_id', $biodata->id)->delete();
        }

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
