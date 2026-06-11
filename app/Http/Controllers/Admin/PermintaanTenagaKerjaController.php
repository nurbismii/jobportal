<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Biodata;
use App\Models\Hris\Departemen;
use App\Models\Hris\Divisi;
use App\Models\PermintaanTenagaKerja;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RealRashid\SweetAlert\Facades\Alert;

class PermintaanTenagaKerjaController extends Controller
{
    private const JENIS_KELAMIN_OPTIONS = [
        'Laki-laki',
        'Perempuan',
        'Laki-laki dan Perempuan',
    ];

    private const PENDIDIKAN_OPTIONS = ['SMA/SMK', 'D3', 'S1', 'S2', 'S3'];

    private const STATUS_PTK_OPTIONS = ['Diterima', 'Ditolak', 'Menunggu', 'Proses', 'Selesai'];

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

        return view('admin.permintaan-tenaga-kerja.create', compact('departemens', 'total_ptk', 'month'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePermintaanTenagaKerja($request);

        PermintaanTenagaKerja::create($this->buildPayload($validated) + [
            'jumlah_masuk' => 0,
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
        $validated = $this->validatePermintaanTenagaKerja($request);

        $permintaanTenagaKerja->update($this->buildPayload($validated));

        Alert::success('Berhasil', 'Permintaan tenaga kerja berhasil diperbarui.');
        return redirect()->route('permintaan-tenaga-kerja.index');
    }

    public function show($id)
    {
        $permintaanTenagaKerja = PermintaanTenagaKerja::with(['departemen', 'divisi'])->findOrFail($id);

        return view('admin.permintaan-tenaga-kerja.show', compact('permintaanTenagaKerja'));
    }

    private function validatePermintaanTenagaKerja(Request $request): array
    {
        $departemen = new Departemen();
        $divisi = new Divisi();
        $departemenTable = $departemen->getConnectionName() . '.' . $departemen->getTable();
        $divisiTable = $divisi->getConnectionName() . '.' . $divisi->getTable();

        return $request->validate([
            'no_surat_permintaan' => ['required', 'string', 'max:255'],
            'departemen' => ['required', 'integer', Rule::exists($departemenTable, 'id')],
            'divisi' => [
                'nullable',
                'integer',
                Rule::exists($divisiTable, 'id')->where(function ($query) use ($request) {
                    return $query->where('departemen_id', $request->departemen);
                }),
            ],
            'posisi' => ['required', 'string', 'max:255'],
            'tanggal_pengajuan' => ['required', 'date'],
            'tanggal_terima' => ['required', 'date'],
            'jumlah_ptk' => ['required', 'integer', 'min:1'],
            'jenis_kelamin' => ['required', Rule::in(self::JENIS_KELAMIN_OPTIONS)],
            'rentang_usia' => ['required', 'string', 'max:255'],
            'background_pendidikan' => ['required', Rule::in(self::PENDIDIKAN_OPTIONS)],
            'kualifikasi_ptk' => ['required', 'string'],
            'status_ptk' => ['nullable', Rule::in(self::STATUS_PTK_OPTIONS)],
        ]);
    }

    private function buildPayload(array $validated): array
    {
        return [
            'no_surat_ptk' => $validated['no_surat_permintaan'],
            'departemen_id' => $validated['departemen'],
            'divisi_id' => $validated['divisi'] ?? null,
            'posisi' => $validated['posisi'],
            'tanggal_pengajuan' => $validated['tanggal_pengajuan'],
            'tanggal_terima' => $validated['tanggal_terima'],
            'jumlah_ptk' => $validated['jumlah_ptk'],
            'jenis_kelamin' => $validated['jenis_kelamin'],
            'rentang_usia' => $validated['rentang_usia'],
            'background_pendidikan' => $validated['background_pendidikan'],
            'kualifikasi_ptk' => $validated['kualifikasi_ptk'],
            'status_ptk' => $validated['status_ptk'] ?? 'Menunggu',
        ];
    }
}
