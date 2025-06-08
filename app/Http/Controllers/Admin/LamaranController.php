<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lamaran;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class LamaranController extends Controller
{
    public function updateStatusMassal(Request $request)
    {
        $request->validate([
            'selected_ids' => 'required|array',
            'status_proses' => 'required|string'
        ]);

        // Update semua yang dipilih
        Lamaran::whereIn('id', $request->selected_ids)
            ->update(['status_proses' => $request->status_proses]);

        Alert::success('Berhasil', 'Status proses berhasil diperbarui menjadi [ ' . $request->status_proses . ' ]');
        return back()->with('success', 'Status berhasil diperbarui.');
    }

    public function update(Request $request, $id)
    {
        Lamaran::where('id', $id)->update([
            'rekomendasi' => $request->rekomendasi
        ]);

        Alert::success('Berhasil', 'Nama perekomendasi berhasil ditambahkan');
        return back()->with('success', 'Status berhasil diperbarui.');
    }
}
