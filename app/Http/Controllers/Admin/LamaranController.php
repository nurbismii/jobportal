<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\ImportStatusLamaran;
use App\Jobs\ProcessLamaranMasterJob;
use App\Models\Biodata;
use App\Models\Lamaran;
use App\Services\LamaranStatusService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use RealRashid\SweetAlert\Facades\Alert;

class LamaranController extends Controller
{
    public function updateStatusMassal(Request $request, LamaranStatusService $lamaranStatusService)
    {
        $statusInput = strtolower($request->status_proses);

        if ($statusInput === 'kandidat potensial') {
            Biodata::whereIn('id', Lamaran::whereIn('id', $request->selected_ids)->pluck('biodata_id'))
                ->update(['status_potensial' => 1]);

            Alert::success('Berhasil', 'Kandidat ditandai sebagai potensial');
            return back();
        }

        collect($request->selected_ids)
            ->chunk(300)
            ->each(function ($chunk) use ($lamaranStatusService, $request) {
                Lamaran::with('biodata.user', 'lowongan:id,permintaan_tenaga_kerja_id')
                    ->whereIn('id', $chunk->all())
                    ->get()
                    ->each(function ($lamaran) use ($lamaranStatusService, $request) {
                        $lamaranStatusService->apply(
                            $lamaran,
                            (string) $request->status_proses,
                            $request->tanggal_proses,
                            $request->jam,
                            $request->tempat,
                            $request->pesanEmail
                        );
                    });
            });

        if ($request->blast_email === 'iya') {
            ProcessLamaranMasterJob::dispatch(
                $request->selected_ids,
                $request->status_proses,
                $request->tanggal_proses,
                $request->jam,
                $request->tempat,
                $request->pesanEmail,
                $request->blast_email
            );

            Alert::success('Berhasil', 'Status lamaran berhasil diperbarui dan email sedang diproses untuk dikirim.');
            return back();
        }

        Alert::success('Berhasil', 'Status lamaran berhasil diperbarui.');
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

    public function importStatusLamaran(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xls,xlsx|max:2048',
        ]);

        try {
            $import = new ImportStatusLamaran;
            Excel::import($import, $request->file('file'));

            if ($import->failures()->isNotEmpty()) {
                return back()->with('errors_import', $import->failures());
            }

            Alert::success('Berhasil', 'Data berhasil diupdate!');
            return back();
        } catch (\Exception $e) {
            Alert::error('Gagal', $e->getMessage());
            return back();
        }
    }

    function getBatchDelayJam($counter, $limitPerJam = 50)
    {
        return intdiv($counter, $limitPerJam);
    }
}
