<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lowongan;
use App\Models\Pengumuman;
use App\Models\PermintaanTenagaKerja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DasborController extends Controller
{
    public function index()
    {
        $ptks = PermintaanTenagaKerja::with(['departemen', 'divisi'])->limit(50)->get();

        $groupedPtk = $ptks->groupBy([
            fn($item) => $item->departemen_id,
            fn($item) => $item->divisi_id,
        ]);

        $formatted = [];

        foreach ($groupedPtk as $deptId => $divisis) {
            $departemen = $ptks->firstWhere('departemen_id', $deptId)->departemen;

            $deptGroup = [
                'departemen_id' => $deptId,
                'departemen' => $departemen->departemen,
                'divisis' => []
            ];

            foreach ($divisis as $divisiId => $items) {
                $divisi = $items->first()->divisi;

                $deptGroup['divisis'][] = [
                    'divisi_id' => $divisiId,
                    'nama_divisi' => $divisi->nama_divisi,
                    'lowongans' => $items->map(fn($i) => [
                        'id' => $i->id,
                        'posisi' => $i->posisi
                    ])->values()
                ];
            }

            $formatted[] = $deptGroup;
        }

        return view('admin.dasbor.index', [
            'count_user' => User::where('status_akun', '1')->count(),
            'count_lowongan_aktif' => Lowongan::where('tanggal_berakhir', '>', Carbon::now())->count(),
            'count_lowongan_tidak_aktif' => Lowongan::where('tanggal_berakhir', '<=', Carbon::now())->count(),
            'count_pengumuman' => Pengumuman::count(),
            'permintaanTenagaKerjas' => $formatted
        ]);
    }

    public function lowonganData(Request $request)
    {
        $query = Lowongan::withCount([
            'lamaran as jumlah_lamaran',
            'lamaran as aktif_bekerja' => function ($q) {
                $q->where('status_proses', 'Aktif Bekerja');
            },
            'lamaran as belum_sesuai_kriteria' => function ($q) {
                $q->where('status_proses', 'Belum Sesuai Kriteria');
            },
            'lamaran as lainnya' => function ($q) {
                $q->whereNotIn('status_proses', ['Aktif Bekerja', 'Belum Sesuai Kriteria']);
            },
        ])->with('permintaanTenagaKerja')->with(['lamaran.riwayatProsesLamaran' => function ($q) {
            $q->orderByDesc(DB::raw("CONCAT(created_at, ' ', jam)"));
        }]);

        // Filter
        if ($request->ptk_id) {
            $query->whereHas('permintaanTenagaKerja', function ($q) use ($request) {
                $q->where('id', $request->ptk_id);
            });
        }
        if ($request->lowongan_id) {
            $query->where('id', $request->lowongan_id);
        }
        if ($request->tgl_mulai) {
            $query->whereDate('tanggal_mulai', '>=', $request->tgl_mulai);
        }
        if ($request->tgl_berakhir) {
            $query->whereDate('tanggal_berakhir', '<=', $request->tgl_berakhir);
        }
        if ($request->sim_b2 !== null && $request->sim_b2 !== '') {
            $query->where('status_sim_b2', $request->sim_b2);
        }

        // Return datatables (gunakan Yajra DataTables)
        return datatables()->eloquent($query)
            ->addColumn('nama_lowongan', function ($row) {
                return $row->nama_lowongan;
            })
            ->addColumn('tanggal_mulai', function ($row) {
                return $row->tanggal_mulai;
            })
            ->addColumn('tanggal_berakhir', function ($row) {
                return $row->tanggal_berakhir;
            })
            ->addColumn('tahapan_terakhir', function ($row) {
                // Ambil semua lamaran dan mapping status_proses terbaru
                $tahapanCount = [];

                foreach ($row->lamaran as $lamaran) {
                    $lastProses = $lamaran->riwayatProsesLamaran->sortByDesc(function ($item) {
                        return $item->created_at . ' ' . $item->jam;
                    })->first();

                    if ($lastProses) {
                        $tahapan = $lastProses->status_proses;
                        $tahapanCount[$tahapan] = ($tahapanCount[$tahapan] ?? 0) + 1;
                    }
                }

                // Kembalikan sebagai string
                $output = '';
                foreach ($tahapanCount as $tahap => $jumlah) {
                    $output .= "<span class='badge badge-primary mr-1'>$tahap: $jumlah</span><br>";
                }

                return $output ?: '-';
            })
            ->rawColumns(['tahapan_terakhir']) // penting agar HTML badge bisa tampil
            ->addColumn('jumlah_lamaran', function ($row) {
                return $row->jumlah_lamaran;
            })


            ->toJson();
    }

    public function lowonganChart(Request $request)
    {
        $query = Lowongan::with(['lamaran' => function ($q) {
            $q->select('id', 'loker_id', 'status_proses');
        }])->withCount('lamaran');

        // Filter
        if ($request->ptk_id) {
            $query->whereHas('permintaanTenagaKerja', function ($q) use ($request) {
                $q->where('id', $request->ptk_id);
            });
        }
        if ($request->lowongan_id) {
            $query->where('id', $request->lowongan_id);
        }
        if ($request->tgl_mulai) {
            $query->whereDate('tanggal_mulai', '>=', $request->tgl_mulai);
        }
        if ($request->tgl_berakhir) {
            $query->whereDate('tanggal_berakhir', '<=', $request->tgl_berakhir);
        }
        if ($request->sim_b2 !== null && $request->sim_b2 !== '') {
            $query->where('status_sim_b2', $request->sim_b2);
        }

        $lowongans = $query->get();

        // Bar chart: Jumlah Lamaran per Posisi
        $chartPosisi = [
            'labels' => $lowongans->pluck('nama_lowongan')->toArray(),
            'datasets' => [[
                'label' => 'Jumlah Lamaran',
                'backgroundColor' => '#36a2eb',
                'data' => $lowongans->pluck('lamaran_count')->toArray(),
            ]],
        ];

        // Pie Chart: Status Lamaran (Aktif Bekerja, Belum Sesuai Kriteria, Lainnya)
        $kelompokStatus = [
            'Lanjut' => [
                'Verifikasi Online',
                'Verifikasi Berkas',
                'Tes Kesehatan',
                'Tes Lapangan',
                'Medical Check-Up',
                'Tanda Tangan Kontrak',
                'Aktif Bekerja'
            ],
            'Tidak Lolos' => [
                'Tidak Lolos Verifikasi Online',
                'Tidak Lolos Verifikasi Berkas',
                'Tidak Lolos Tes Kesehatan',
                'Tidak Lolos Tes Lapangan',
                'Tidak Lolos Medical Check-Up',
                'Tidak Lolos Induksi Safety'
            ],
            'Belum Diproses' => [
                'Lamaran Dikirim',
                'Belum Sesuai Kriteria'
            ],
        ];

        $statusCount = [
            'Lanjut' => 0,
            'Tidak Lolos' => 0,
            'Belum Diproses' => 0,
        ];

        foreach ($lowongans as $lowongan) {
            foreach ($lowongan->lamaran as $lamaran) {
                $status = $lamaran->status_proses;

                foreach ($kelompokStatus as $group => $statuses) {
                    if (in_array($status, $statuses)) {
                        $statusCount[$group]++;
                        break;
                    }
                }
            }
        }

        $chartStatus = [
            'labels' => array_keys($statusCount),
            'datasets' => [[
                'label' => 'Distribusi Proses Lamaran',
                'backgroundColor' => ['#4caf50', '#f44336', '#ffc107'],
                'data' => array_values($statusCount),
            ]],
        ];

        // Tahapan Proses Rekap
        $tahapan = [
            'Lamaran Dikirim',
            'Belum Sesuai Kriteria',
            'Verifikasi Online',
            'Tidak Lolos Verifikasi Online',
            'Verifikasi Berkas',
            'Tidak Lolos Verifikasi Berkas',
            'Tes Kesehatan',
            'Tidak Lolos Tes Kesehatan',
            'Tes Lapangan',
            'Tidak Lolos Tes Lapangan',
            'Medical Check-Up',
            'Tidak Lolos Medical Check-Up',
            'Tanda Tangan Kontrak',
            'Tidak Lolos Induksi Safety',
            'Aktif Bekerja'
        ];

        $latestProses = DB::table('riwayat_proses_lamaran as r1')
            ->select('r1.status_proses', 'r1.status_lolos')
            ->join(DB::raw('(
                    SELECT user_id, lamaran_id, MAX(created_at) as max_created
                    FROM riwayat_proses_lamaran
                    GROUP BY user_id, lamaran_id
                ) as r2'), function ($join) {
                $join->on('r1.user_id', '=', 'r2.user_id')
                    ->on('r1.lamaran_id', '=', 'r2.lamaran_id')
                    ->on('r1.created_at', '=', 'r2.max_created');
            })
            ->join('lamaran', 'r1.lamaran_id', '=', 'lamaran.id')
            ->when($request->lowongan_id, function ($q) use ($request) {
                $q->where('lamaran.loker_id', $request->lowongan_id);
            })
            ->get();

        $rekap = [];
        foreach ($tahapan as $tahap) {
            $lanjut = $latestProses->where('status_proses', $tahap)->whereNull('status_lolos')->count();
            $tidakLolos = $latestProses->where('status_proses', $tahap)->where('status_lolos', 'Tidak Lolos')->count();

            $rekap[] = [
                'tahap' => $tahap,
                'lanjut' => $lanjut,
                'tidak_lolos' => $tidakLolos,
            ];
        }

        $chartProses = [
            'labels' => array_column($rekap, 'tahap'),
            'datasets' => [
                [
                    'label' => 'Lanjut',
                    'backgroundColor' => '#36a2eb',
                    'data' => array_column($rekap, 'lanjut'),
                ],
                [
                    'label' => 'Tidak Lolos',
                    'backgroundColor' => '#f44336',
                    'data' => array_column($rekap, 'tidak_lolos'),
                ],
            ],
        ];

        return response()->json([
            'chart_posisi' => $chartPosisi,
            'chart_status' => $chartStatus,
            'chart_proses' => $chartProses,
        ]);
    }
}
