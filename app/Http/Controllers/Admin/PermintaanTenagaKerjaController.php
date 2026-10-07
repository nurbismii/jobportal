<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\HrBlastEmail;
use App\Models\Hris\Departemen;
use App\Models\Hris\Divisi;
use App\Models\PermintaanTenagaKerja;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

        $divisis = Divisi::whereIn('id', array_column($permintaanTenagaKerja->rincian_permintaan, 'divisi'))->get()->keyBy('id');

        return view('admin.permintaan-tenaga-kerja.show', compact('permintaanTenagaKerja', 'divisis'));
    }

    private function validatePermintaanTenagaKerja(Request $request): array
    {
        $departemen = new Departemen();
        $divisi = new Divisi();
        $departemenTable = $departemen->getConnectionName() . '.' . $departemen->getTable();
        $divisiTable = $divisi->getConnectionName() . '.' . $divisi->getTable();

        $validated = $request->validate([
            'no_surat_permintaan' => ['required', 'string', 'max:255'],
            'departemen' => ['required', 'integer', Rule::exists($departemenTable, 'id')],
            'rincian' => ['required', 'array', 'min:1', 'max:100'],
            'rincian.*' => ['required', 'array:divisi,posisi,jumlah_ptk,jenis_kelamin,rentang_usia,background_pendidikan,kualifikasi_ptk'],
            'rincian.*.divisi' => [
                'nullable',
                'integer',
                Rule::exists($divisiTable, 'id')->where(function ($query) use ($request) {
                    return $query->where('departemen_id', $request->departemen);
                }),
            ],
            'rincian.*.posisi' => ['required', 'string', 'max:255'],
            'tanggal_pengajuan' => ['required', 'date'],
            'tanggal_terima' => ['required', 'date'],
            'rincian.*.jumlah_ptk' => ['required', 'integer', 'min:1', 'max:1000000'],
            'rincian.*.jenis_kelamin' => ['required', Rule::in(self::JENIS_KELAMIN_OPTIONS)],
            'rincian.*.rentang_usia' => ['required', 'string', 'max:255'],
            'rincian.*.background_pendidikan' => ['required', Rule::in(self::PENDIDIKAN_OPTIONS)],
            'rincian.*.kualifikasi_ptk' => ['bail', 'required', 'string', 'max:50000', function ($attribute, $value, $fail) {
                $text = html_entity_decode(strip_tags(HrBlastEmail::sanitizeMessage($value)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (! preg_match('/[^\s\x{00A0}\x{200B}]/u', $text)) {
                    $fail('Kualifikasi pada setiap rincian wajib diisi.');
                }
            }],
            'status_ptk' => ['nullable', Rule::in(self::STATUS_PTK_OPTIONS)],
        ]);

        $seen = [];
        foreach ($validated['rincian'] as $index => $row) {
            $posisi = mb_strtolower(preg_replace('/\s+/u', ' ', trim($row['posisi'])), 'UTF-8');
            $key = json_encode([isset($row['divisi']) ? (int) $row['divisi'] : null, $posisi]);
            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    "rincian.$index.posisi" => 'Posisi yang sama pada divisi yang sama tidak boleh diulang. Pilih posisi yang berbeda.',
                ]);
            }
            $seen[$key] = true;
        }

        return $validated;
    }

    private function buildPayload(array $validated): array
    {
        $rincian = array_values($validated['rincian']);
        foreach ($rincian as &$row) {
            $row['kualifikasi_ptk'] = HrBlastEmail::sanitizeMessage($row['kualifikasi_ptk']);
        }
        unset($row);
        $pertama = $rincian[0];

        return [
            'no_surat_ptk' => $validated['no_surat_permintaan'],
            'departemen_id' => $validated['departemen'],
            'rincian' => $rincian,
            'divisi_id' => $pertama['divisi'] ?? null,
            'posisi' => $pertama['posisi'],
            'tanggal_pengajuan' => $validated['tanggal_pengajuan'],
            'tanggal_terima' => $validated['tanggal_terima'],
            'jumlah_ptk' => array_sum(array_column($rincian, 'jumlah_ptk')),
            'jenis_kelamin' => count(array_unique(array_column($rincian, 'jenis_kelamin'))) === 1
                ? $pertama['jenis_kelamin'] : 'Laki-laki dan Perempuan',
            'rentang_usia' => $pertama['rentang_usia'],
            'background_pendidikan' => $pertama['background_pendidikan'],
            'kualifikasi_ptk' => $pertama['kualifikasi_ptk'],
            'status_ptk' => $validated['status_ptk'] ?? 'Menunggu',
        ];
    }
}
