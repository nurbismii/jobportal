<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\Hris\Departemen;
use App\Models\PermintaanTenagaKerja;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class PermintaanTenagaKerjaController extends Controller
{
    public function index()
    {
        $title = 'Hapus Permintaan Tenaga Kerja!';
        $text = "Kamu yakin ingin menghapus PTK ini?";
        confirmDelete($title, $text);

        $permintaanTenagaKerjas = PermintaanTenagaKerja::with(['departemen', 'divisi'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.permintaan-tenaga-kerja.index', compact('permintaanTenagaKerjas'));
    }

    public function create()
    {
        $departemens = Departemen::orderBy('perusahaan_id', 'asc')->orderBy('departemen', 'asc')->whereIn('perusahaan_id', ['1', '2'])->get();

        $total_ptk = PermintaanTenagaKerja::count();
        $total_ptk = $total_ptk > 0 ? $total_ptk + 1 : 1;

        $month = date('m');

        $romanMonths = [
            '01' => 'I',
            '02' => 'II',
            '03' => 'III',
            '04' => 'IV',
            '05' => 'V',
            '06' => 'VI',
            '07' => 'VII',
            '08' => 'VIII',
            '09' => 'IX',
            '10' => 'X',
            '11' => 'XI',
            '12' => 'XII',
        ];

        $month = $romanMonths[$month];

        return view('admin.permintaan-tenaga-kerja.create', compact('departemens', 'total_ptk', 'month', 'ptk'));
    }

    public function store(Request $request)
    {
        PermintaanTenagaKerja::create([
            'no_surat_ptk' => $request->no_surat_permintaan,
            'departemen_id' => $request->departemen,
            'divisi_id' => $request->divisi ?? null,
            'posisi' => $request->posisi,
            'tanggal_pengajuan' => $request->tanggal_pengajuan,
            'tanggal_terima' => $request->tanggal_terima,
            'jumlah_ptk' => $request->jumlah_ptk,
            'jenis_kelamin' => $request->jenis_kelamin,
            'rentang_usia' => $request->rentang_usia,
            'background_pendidikan' => $request->background_pendidikan,
            'kualifikasi_ptk' => $request->kualifikasi_ptk,
            'jumlah_masuk' => 0,
            'status_ptk' => $request->status_ptk ?? 'Menunggu',
        ]);

        Alert::success('Berhasil', 'Permintaan tenaga kerja berhasil dibuat.');
        return redirect()->route('permintaan-tenaga-kerja.index');
    }

    public function edit($id)
    {
        $permintaanTenagaKerja = PermintaanTenagaKerja::with(['departemen', 'divisi'])->findOrFail($id);
        $departemens = Departemen::orderBy('perusahaan_id', 'asc')->orderBy('departemen', 'asc')->whereIn('perusahaan_id', ['1', '2'])->get();

        return view('admin.permintaan-tenaga-kerja.edit', compact('permintaanTenagaKerja', 'departemens'));
    }

    public function update(Request $request, $id)
    {
        $permintaanTenagaKerja = PermintaanTenagaKerja::findOrFail($id);

        $permintaanTenagaKerja->update([
            'no_surat_ptk' => $request->no_surat_permintaan,
            'departemen_id' => $request->departemen,
            'divisi_id' => $request->divisi ?? null,
            'posisi' => $request->posisi,
            'tanggal_pengajuan' => $request->tanggal_pengajuan,
            'tanggal_terima' => $request->tanggal_terima,
            'jumlah_ptk' => $request->jumlah_ptk,
            'jenis_kelamin' => $request->jenis_kelamin,
            'rentang_usia' => $request->rentang_usia,
            'background_pendidikan' => $request->background_pendidikan,
            'kualifikasi_ptk' => $request->kualifikasi_ptk,
            'status_ptk' => $request->status_ptk ?? 'Menunggu',
        ]);

        Alert::success('Berhasil', 'Permintaan tenaga kerja berhasil diperbarui.');
        return redirect()->route('permintaan-tenaga-kerja.index');
    }

    public function show($id)
    {
        $permintaanTenagaKerja = PermintaanTenagaKerja::with(['departemen', 'divisi'])->findOrFail($id);

        return view('admin.permintaan-tenaga-kerja.show', compact('permintaanTenagaKerja'));
    }
}
