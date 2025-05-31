<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\Hris\Provinsi;
use App\Models\User;

class PenggunaController extends Controller
{
    public function index()
    {
        // Logic to display the list of users
        $penggunas = User::where('role', '!=', 'admin')->get();

        return view('admin.pengguna.index', compact('penggunas'))->with('no');
    }

    public function edit($id)
    {
        // Logic to edit a user
        $pengguna = User::with('biodata')->findOrFail($id);
        $biodata = Biodata::where('user_id', $pengguna->id)->first();

        $provinsis = Provinsi::all();

        return view('admin.pengguna.edit', compact('pengguna', 'biodata', 'provinsis'));
    }

    public function update($id)
    {
        // Logic to update a user
        $pengguna = User::findOrFail($id);
        $pengguna->update(request()->all());

        // Update biodata if exists
        if ($pengguna->biodata) {
            $pengguna->biodata->update(request()->all());
        }

        return redirect()->route('pengguna.index')->with('success', 'Pengguna berhasil diperbarui.');
    }
}
