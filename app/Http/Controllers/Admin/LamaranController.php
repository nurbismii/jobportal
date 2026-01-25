<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLamaranEmail;
use App\Models\Biodata;
use App\Models\Lamaran;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class LamaranController extends Controller
{
    public function updateStatusMassal(Request $request)
    {
        $statusInput = strtolower($request->status_proses);

        $statusTidakLolos = [
            'belum sesuai kriteria',
            'tidak lolos verifikasi online',
            'tidak lolos verifikasi berkas',
            'tidak lolos tes kesehatan',
            'tidak lolos tes lapangan',
            'tidak lolos medical check-up',
            'tidak lolos induksi safety',
            'tidak lolos tanda tangan kontrak',
            'tidak tanda tangan kontrak'
        ];

        $pesan = $request->pesanEmail;

        // === KHUSUS KANDIDAT POTENSIAL ===
        if ($statusInput === 'kandidat potensial') {
            Biodata::whereIn('id', Lamaran::whereIn('id', $request->selected_ids)->pluck('biodata_id'))
                ->update(['status_potensial' => 1]);

            Alert::success('Berhasil', 'Kandidat ditandai sebagai potensial');
            return back();
        }

        // === UPDATE STATUS LAMARAN (FAST) ===
        $updateData = ['status_proses' => $request->status_proses];

        if (in_array($statusInput, $statusTidakLolos)) {
            $updateData['status_lamaran'] = 0;
        }

        if ($statusInput === 'aktif bekerja') {
            $updateData['status_lamaran'] = 0;
        }

        Lamaran::whereIn('id', $request->selected_ids)->update($updateData);

        // === PROSES RIWAYAT + EMAIL VIA QUEUE JOB ===
        ProcessLamaranEmail::dispatch(
            $request->selected_ids,
            $request->status_proses,
            $request->tanggal_proses,
            $request->jam,
            $request->tempat,
            $pesan,
            $request->blast_email
        );

        Alert::success('Berhasil', 'Update diproses di background. Email dikirim bertahap.');
        return back();
    }

    public function update(Request $request, $id)
    {
        Lamaran::where('id', $id)->update([
            'rekomendasi' => $request->rekomendasi
        ]);

        Alert::success('Berhasil', 'Nama perekomendasi berhasil ditambahkan');
        return back()->with('success', 'Status berhasil diperbarui.');
    }


    public function autoUpdate(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'model' => 'required|string',
            'field' => 'required|string',
            'value' => 'nullable|string',
        ]);

        $modelMap = [
            'biodata' => \App\Models\Biodata::class,
            'lamaran' => \App\Models\Lamaran::class,
            'user' => \App\Models\User::class,
        ];

        $allowedFields = [
            'biodata' => ['status_ktp', 'status_skck', 'status_sim_b2', 'status_sio', 'status_sertifikat'],
            'lamaran' => ['rekomendasi'],
            'user' => ['rekomendasi'],
        ];

        $fieldLabels = [
            'status_ktp' => 'Status KTP',
            'status_skck' => 'Status SKCK',
            'status_sim_b2' => 'Status SIM B2 Umum',
            'status_sio' => 'Status SIO',
            'status_sertifikat' => 'Status Sertifikat',
            'rekomendasi' => 'Rekomendasi',
        ];

        $modelKey = $request->model;

        if (!isset($modelMap[$modelKey])) {
            return response()->json(['message' => 'Model tidak dikenali'], 400);
        }

        if (!in_array($request->field, $allowedFields[$modelKey])) {
            return response()->json(['message' => 'Field tidak diizinkan'], 403);
        }

        $modelClass = $modelMap[$modelKey];
        $record = $modelClass::findOrFail($request->id);

        $record->{$request->field} = $request->value;
        $record->save();

        $fieldName = $request->field;
        $label = $fieldLabels[$fieldName] ?? ucfirst(str_replace('_', ' ', $fieldName));

        return response()->json([
            'message' => "Berhasil memperbarui {$label}.",
        ]);
    }

    function getBatchDelayJam($counter, $limitPerJam = 50)
    {
        return intdiv($counter, $limitPerJam);
    }
}
