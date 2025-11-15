<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendProsesLamaranEmail;
use App\Models\EmailBlastLog;
use App\Models\Lamaran;
use App\Models\PermintaanTenagaKerja;
use App\Models\RiwayatProsesLamaran;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class LamaranController extends Controller
{
    public function updateStatusMassal(Request $request)
    {
        $limitPerJam = 50;
        $counter = 0;

        // DB::beginTransaction();

        $lamaran = Lamaran::with('biodata', 'lowongan')->whereIn('id', $request->selected_ids)->get();

        foreach ($lamaran as $data) {

            $pesan = null;
            $lolos = null;
            $userId = $data->biodata->user_id;
            $lamaranId = $data->id;
            $status = $request->status_proses;

            // Hitung delay berdasarkan akumulasi kirim
            $delayJam = $this->getBatchDelayJam($counter, $limitPerJam);
            $batchKe = $delayJam + 1;

            // Simpan log email blast
            $log = EmailBlastLog::create([
                'user_id' => $userId,
                'lamaran_id' => $lamaranId,
                'status_proses' => $status,
                'batch_ke' => $batchKe,
                'delay_jam' => $delayJam,
            ]);

            $tahapanTidakLolos = [
                'Belum Sesuai Kriteria',
                'Tidak Lolos Verifikasi Berkas',
                'Tidak Lolos Tes Kesehatan',
                'Tidak Lolos Tes Lapangan',
                // 'Tidak Lolos Medical Check-Up',
                'Tidak Lolos Induksi Safety',
                'Tidak Lolos Tanda Tangan Kontrak',
            ];

            if (in_array($status, $tahapanTidakLolos)) {
                $lolos = 'Tidak Lolos';
                Lamaran::where('id', $lamaranId)->update([
                    'status_lamaran' => 0
                ]);
            } else {
                $lolos = null;

                $pesan = pesanStatusLamaran($status, $request->tanggal_proses, $request->jam, $request->tempat);
                // Kirim job dengan delay sesuai batch
                SendProsesLamaranEmail::dispatch($userId, $status, $lamaranId, $log->id, $pesan)
                    ->delay(now()->addHours($delayJam));

                $counter++;
            }

            $sudahAda = RiwayatProsesLamaran::where('user_id', $userId)
                ->where('lamaran_id', $lamaranId)
                ->where('status_proses', $status)
                ->exists();

            // Cek apakah sudah ada riwayat proses dengan status yang sama
            if (!$sudahAda) {

                RiwayatProsesLamaran::create([
                    'user_id' => $userId,
                    'lamaran_id' => $lamaranId,
                    'status_lolos' => $lolos,
                    'status_proses' => $status,
                    'tanggal_proses' => $request->tanggal_proses,
                    'jam' => $request->jam,
                    'tempat' => $request->tempat,
                    'pesan' => $pesan ?? '-'
                ]);

                if (strtolower($request->status_proses) == 'aktif bekerja') {
                    PermintaanTenagaKerja::where('id', $data->lowongan->permintaan_tenaga_kerja_id)
                        ->increment('jumlah_masuk', 1);
                }
            }
        }

        // Update semua yang dipilih
        if (strtolower($request->status_proses) == 'tanda tangan kontrak' || strtolower($request->status_proses) == 'belum sesuai kriteria' || strtolower($request->status_proses) == 'aktif bekerja') {
            Lamaran::whereIn('id', $request->selected_ids)
                ->update([
                    'status_lamaran' => 0,
                    'status_proses' => $request->status_proses,
                ]);
        } else {
            Lamaran::whereIn('id', $request->selected_ids)
                ->update([
                    'status_proses' => $request->status_proses
                ]);
        }

        // DB::commit();

        Alert::success('Berhasil', 'Status proses berhasil diperbarui menjadi [ ' . $request->status_proses . ' ]');
        return back()->with('success', 'Status berhasil diperbarui.');
        try {
        } catch (Exception $e) {
            DB::rollBack();

            Alert::error('Gagal', 'Terjadi kesalahan');
            return back();
        }
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
        ];

        $allowedFields = [
            'biodata' => ['status_ktp', 'status_skck', 'status_sim_b2', 'status_sio', 'status_sertifikat'],
            'lamaran' => ['rekomendasi'],
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
