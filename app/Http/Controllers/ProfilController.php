<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use Illuminate\Http\Request;
use App\Models\User;
use RealRashid\SweetAlert\Facades\Alert;

class ProfilController extends Controller
{
    public function index()
    {
        return view('user.profil.index');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:255',
            'no_ktp' => 'required|string|min:16|max:16',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
            'password' => 'nullable|string|min:8|confirmed',
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nama.string' => 'Nama lengkap harus berupa teks.',
            'nama.max' => 'Nama maksimal 255 karakter.',

            'no_ktp.required' => 'Nomor KTP wajib diisi.',
            'no_ktp.string' => 'Nomor KTP harus berupa teks.',
            'no_ktp.max' => 'Nomor KTP maksimal 16 karakter.',
            'no_ktp.min' => 'Nomor KTP minimal 16 karakter.',

            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email terlalu panjang.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',

            'password.min' => 'Password minimal harus 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user->name = $request->nama;
        $user->no_ktp = $request->no_ktp;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        Alert::success('Berhasil', 'Profil telah diperbarui');
        return redirect()->back();
    }
}
