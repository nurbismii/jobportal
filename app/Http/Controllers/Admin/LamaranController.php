<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendProsesLamaranEmail;
use App\Models\Biodata;
use App\Models\EmailBlastLog;
use App\Models\Lamaran;
use App\Models\PermintaanTenagaKerja;
use App\Models\RiwayatProsesLamaran;
use App\Models\User;
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
        $statusInput = strtolower($request->status_proses);
        $YES_BLAST = 'iya';

        $lamaran = Lamaran::with('biodata', 'lowongan')
            ->whereIn('id', $request->selected_ids)
            ->get();

        // === Khusus kandidat potensial ===
        if ($statusInput === 'kandidat potensial') {
            Biodata::whereIn('id', $lamaran->pluck('biodata.id'))->update([
                'status_potensial' => 1
            ]);

            Alert::success('Berhasil', 'Kandidat berhasil ditambahkan ke kandidat potensial');
            return back();
        }

        // === Definisikan status tidak lolos ===
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

        foreach ($lamaran as $data) {

            $userId = $data->biodata->user_id;
            $lamaranId = $data->id;
            $pesan = $request->pesanEmail;
            $lolos = in_array($statusInput, $statusTidakLolos) ? 'Tidak Lolos' : null;

            // === Simpan log blast email ===
            $log = null;
            if ($request->blast_email === $YES_BLAST) {

                $delayJam = floor($counter / $limitPerJam);
                $batchKe = $delayJam + 1;

                $log = EmailBlastLog::create([
                    'user_id' => $userId,
                    'lamaran_id' => $lamaranId,
                    'status_proses' => $request->status_proses,
                    'batch_ke' => $batchKe,
                    'delay_jam' => $delayJam,
                ]);

                // Queue kirim email
                SendProsesLamaranEmail::dispatch($userId, $request->status_proses, $lamaranId, $log->id, $pesan)
                    ->delay(now()->addHours($delayJam));

                $counter++;
            }

            // === Cek & Simpan Riwayat ===
            $sudahAda = RiwayatProsesLamaran::where([
                'user_id' => $userId,
                'lamaran_id' => $lamaranId,
                'status_proses' => $request->status_proses,
            ])->exists();

            if (!$sudahAda) {
                RiwayatProsesLamaran::create([
                    'user_id' => $userId,
                    'lamaran_id' => $lamaranId,
                    'status_lolos' => $lolos,
                    'status_proses' => $request->status_proses,
                    'tanggal_proses' => $request->tanggal_proses,
                    'jam' => $request->jam,
                    'tempat' => $request->tempat ?? '-',
                    'pesan' => $pesan ?? '-'
                ]);

                // Tambah jumlah masuk pada status "Aktif Bekerja"
                if ($statusInput === 'aktif bekerja') {
                    PermintaanTenagaKerja::where('id', $data->lowongan->permintaan_tenaga_kerja_id)
                        ->increment('jumlah_masuk', 1);
                }
            }
        }

        // === Update Final Status Lamaran ===
        $updateData = [
            'status_proses' => $request->status_proses
        ];

        // Status tidak lolos → tutup lamaran
        if (in_array($statusInput, $statusTidakLolos)) {
            $updateData['status_lamaran'] = 0;
        }

        // Aktif bekerja → tutup lamaran + update user
        if ($statusInput === 'aktif bekerja') {

            $updateData['status_lamaran'] = 0;

            $userIds = $lamaran->pluck('biodata.user_id')->filter()->unique();
            User::whereIn('id', $userIds)->update([
                'status_pelamar' => 'AKTIF',
                'ket_resign' => 'Aktif bekerja pada tanggal ' . $request->tanggal_proses,
                'area_kerja' => 'VDNI'
            ]);
        }

        Lamaran::whereIn('id', $request->selected_ids)->update($updateData);

        Alert::success('Berhasil', 'Status proses berhasil diperbarui menjadi ' . $request->status_proses);
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
