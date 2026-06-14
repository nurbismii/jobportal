<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\Pengumuman;
use App\Models\PermintaanTenagaKerja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DasborController extends Controller
{
    private const STATUS_BELUM_DIPROSES = [
        'Lamaran Dikirim',
        'Belum Sesuai Kriteria',
    ];

    private const STATUS_TAHAPAN = [
        'Lamaran Dikirim',
        'Belum Sesuai Kriteria',
        'Verifikasi Online',
        'Tidak Lolos Verifikasi Online',
        'Verifikasi Berkas',
        'Tidak Lolos Verifikasi Berkas',
        'Tidak Lolos Verifikasi Berkas Fisik',
        'Tes Kesehatan',
        'Tidak Lolos Tes Kesehatan',
        'Tes Lapangan',
        'Tidak Lolos Tes Lapangan',
        'Medical Check-Up',
        'Tidak Lolos Medical Check-Up',
        'Tanda Tangan Kontrak',
        'Tidak Lolos Induksi Safety',
        'Aktif Bekerja',
    ];

    public function index()
    {
        $now = Carbon::now();
        $closingLimit = $now->copy()->addDays(7);
        $permintaanTenagaKerjas = $this->buildPtkFilterOptions();
        $totalKebutuhanPtk = (int) PermintaanTenagaKerja::where('status_ptk', '!=', 'Ditolak')->sum('jumlah_ptk');
        $totalTerpenuhiPtk = (int) PermintaanTenagaKerja::where('status_ptk', '!=', 'Ditolak')->sum('jumlah_masuk');
        $totalLamaran = Lamaran::count();
        $aktifBekerja = Lamaran::where('status_proses', 'Aktif Bekerja')->count();
        $lamaranBaru = Lamaran::whereIn('status_proses', self::STATUS_BELUM_DIPROSES)->count();

        $statusLamaranOverview = $this->getApplicationStatusOverview();
        $lowonganButuhPerhatian = Lowongan::withCount('lamaran')
            ->where('tanggal_berakhir', '>=', $now->toDateString())
            ->where(function ($query) use ($closingLimit) {
                $query->whereDate('tanggal_berakhir', '<=', $closingLimit->toDateString())
                    ->orDoesntHave('lamaran');
            })
            ->orderBy('tanggal_berakhir')
            ->limit(5)
            ->get(['id', 'nama_lowongan', 'tanggal_berakhir']);

        $topLowongans = Lowongan::withCount('lamaran')
            ->orderByDesc('lamaran_count')
            ->orderBy('tanggal_berakhir')
            ->limit(5)
            ->get(['id', 'nama_lowongan', 'tanggal_berakhir']);

        $recentPtk = PermintaanTenagaKerja::with(['departemen', 'divisi'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $latestLamarans = Lamaran::with([
            'lowongan:id,nama_lowongan',
            'biodata:id,user_id,no_ktp',
            'biodata.user:id,name,no_ktp',
        ])
            ->latest('created_at')
            ->limit(6)
            ->get();

        $ptkStats = [
            'total_kebutuhan' => $totalKebutuhanPtk,
            'total_terpenuhi' => $totalTerpenuhiPtk,
            'total_sisa' => max($totalKebutuhanPtk - $totalTerpenuhiPtk, 0),
            'persentase_terpenuhi' => $totalKebutuhanPtk > 0 ? round(($totalTerpenuhiPtk / $totalKebutuhanPtk) * 100, 1) : 0,
            'open_ptk' => PermintaanTenagaKerja::whereNotIn('status_ptk', ['Selesai', 'Ditolak'])->count(),
        ];

        return view('admin.dasbor.index', [
            'count_user' => User::where('status_akun', '1')->where('role', '!=', 'admin')->count(),
            'count_lowongan_aktif' => Lowongan::whereDate('tanggal_berakhir', '>=', $now->toDateString())->count(),
            'count_lowongan_tidak_aktif' => Lowongan::whereDate('tanggal_berakhir', '<', $now->toDateString())->count(),
            'count_pengumuman' => Pengumuman::count(),
            'total_lamaran' => $totalLamaran,
            'lamaran_baru' => $lamaranBaru,
            'aktif_bekerja' => $aktifBekerja,
            'konversi_aktif_bekerja' => $totalLamaran > 0 ? round(($aktifBekerja / $totalLamaran) * 100, 1) : 0,
            'lowongan_tutup_minggu_ini' => Lowongan::whereBetween('tanggal_berakhir', [$now->toDateString(), $closingLimit->toDateString()])->count(),
            'permintaanTenagaKerjas' => $permintaanTenagaKerjas,
            'ptkStats' => $ptkStats,
            'statusLamaranOverview' => $statusLamaranOverview,
            'lowonganButuhPerhatian' => $lowonganButuhPerhatian,
            'topLowongans' => $topLowongans,
            'recentPtk' => $recentPtk,
            'latestLamarans' => $latestLamarans,
            'lastUpdatedAt' => $now,
        ]);
    }

    public function lowonganData(Request $request)
    {
        $query = $this->dashboardLowonganQuery($request)->withCount([
            'lamaran as jumlah_lamaran',
            'lamaran as aktif_bekerja' => function ($q) {
                $q->where('status_proses', 'Aktif Bekerja');
            },
            'lamaran as belum_sesuai_kriteria' => function ($q) {
                $q->whereIn('status_proses', self::STATUS_BELUM_DIPROSES);
            },
            'lamaran as tidak_lolos' => function ($q) {
                $q->where('status_proses', 'like', 'Tidak Lolos%');
            },
        ])
            ->with('permintaanTenagaKerja:id,jumlah_ptk,posisi,status_ptk')
            ->with(['lamaran' => function ($q) {
                $q->select('id', 'loker_id', 'status_proses')
                    ->with(['riwayatProsesLamaran' => function ($subQuery) {
                        $subQuery->select('id', 'lamaran_id', 'status_proses', 'created_at', 'jam')
                            ->orderByDesc('created_at')
                            ->orderByDesc('jam');
                    }]);
            }]);

        return datatables()->eloquent($query)
            ->addColumn('nama_lowongan', function ($row) {
                return e($row->nama_lowongan);
            })
            ->addColumn('tanggal_mulai', function ($row) {
                return $this->formatDateForDisplay($row->tanggal_mulai);
            })
            ->addColumn('tanggal_berakhir', function ($row) {
                return $this->formatDateForDisplay($row->tanggal_berakhir);
            })
            ->addColumn('status_lowongan', function ($row) {
                $isActive = Carbon::parse($row->tanggal_berakhir)->endOfDay()->greaterThanOrEqualTo(Carbon::now());
                $badge = $isActive ? 'success' : 'secondary';
                $label = $isActive ? 'Aktif' : 'Tutup';

                return "<span class='badge badge-{$badge}'>{$label}</span><div class='small text-muted mt-1'>{$this->daysLeftLabel($row->tanggal_berakhir)}</div>";
            })
            ->addColumn('tahapan_terakhir', function ($row) {
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
                    $output .= "<span class='badge badge-primary mr-1'>" . e($tahap) . ": {$jumlah}</span><br>";
                }

                return $output ?: '-';
            })
            ->addColumn('jumlah_lamaran', function ($row) {
                return $row->jumlah_lamaran;
            })
            ->addColumn('kebutuhan_ptk', function ($row) {
                return optional($row->permintaanTenagaKerja)->jumlah_ptk ?: '-';
            })
            ->addColumn('progress_ptk', function ($row) {
                $kebutuhan = (int) optional($row->permintaanTenagaKerja)->jumlah_ptk;
                $terpenuhi = (int) $row->aktif_bekerja;
                $percentage = $kebutuhan > 0 ? min(round(($terpenuhi / $kebutuhan) * 100), 100) : 0;

                return "
                    <div class='small mb-1'>{$terpenuhi}/{$kebutuhan} aktif bekerja</div>
                    <div class='progress dashboard-progress'>
                        <div class='progress-bar bg-success' role='progressbar' style='width: {$percentage}%' aria-valuenow='{$percentage}' aria-valuemin='0' aria-valuemax='100'></div>
                    </div>
                ";
            })
            ->addColumn('aksi', function ($row) {
                $url = route('directToLamaran', $row->id);

                return "<a href='{$url}' class='btn btn-sm btn-outline-primary'><i class='fas fa-users mr-1'></i> Pelamar</a>";
            })
            ->rawColumns(['status_lowongan', 'tahapan_terakhir', 'progress_ptk', 'aksi'])
            ->removeColumn('permintaan_tenaga_kerja_id')
            ->removeColumn('kualifikasi')
            ->removeColumn('status_sim_b2')
            ->removeColumn('status_sio')
            ->removeColumn('poster')
            ->removeColumn('created_at')
            ->removeColumn('updated_at')
            ->removeColumn('permintaanTenagaKerja')
            ->removeColumn('permintaan_tenaga_kerja')
            ->removeColumn('lamaran')
            ->toJson();
    }

    public function lowonganChart(Request $request)
    {
        $lowongans = $this->dashboardLowonganQuery($request)
            ->with(['permintaanTenagaKerja:id,jumlah_ptk,status_ptk,posisi'])
            ->with(['lamaran' => function ($q) {
                $q->select('id', 'loker_id', 'status_proses', 'created_at');
            }])
            ->withCount('lamaran')
            ->get();

        $lowonganIds = $lowongans->pluck('id');
        $topLowongans = $lowongans->sortByDesc('lamaran_count')->take(12)->values();

        $chartPosisi = [
            'labels' => $topLowongans->pluck('nama_lowongan')->toArray(),
            'datasets' => [[
                'label' => 'Jumlah Lamaran',
                'backgroundColor' => '#36a2eb',
                'data' => $topLowongans->pluck('lamaran_count')->toArray(),
            ]],
        ];

        $statusCount = $this->summarizeApplicationStatuses($lowongans->flatMap(function ($lowongan) {
            return $lowongan->lamaran;
        }));

        $chartStatus = [
            'labels' => array_keys($statusCount),
            'datasets' => [[
                'label' => 'Distribusi Proses Lamaran',
                'backgroundColor' => ['#4caf50', '#f44336', '#ffc107'],
                'data' => array_values($statusCount),
            ]],
        ];

        $latestProses = collect();

        if ($lowonganIds->isNotEmpty()) {
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
                ->whereIn('lamaran.loker_id', $lowonganIds->all())
                ->get();
        }

        $tahapan = array_values(array_unique(array_merge(
            self::STATUS_TAHAPAN,
            $latestProses->pluck('status_proses')->filter()->unique()->values()->all()
        )));

        $rekap = [];
        foreach ($tahapan as $tahap) {
            $rows = $latestProses->where('status_proses', $tahap);
            $tidakLolos = $rows->filter(function ($item) {
                return $item->status_lolos === 'Tidak Lolos' || stripos((string) $item->status_proses, 'Tidak Lolos') === 0;
            })->count();
            $lanjut = max($rows->count() - $tidakLolos, 0);

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

        $chartDaily = $this->buildDailyApplicationChart($lowonganIds);
        $summary = $this->buildFilteredSummary($lowongans);

        return response()->json([
            'summary' => $summary,
            'chart_posisi' => $chartPosisi,
            'chart_daily' => $chartDaily,
            'chart_status' => $chartStatus,
            'chart_proses' => $chartProses,
        ]);
    }

    private function dashboardLowonganQuery(Request $request)
    {
        $query = Lowongan::query();

        if ($request->filled('ptk_id')) {
            $query->where('permintaan_tenaga_kerja_id', $request->ptk_id);
        }

        if ($request->filled('lowongan_id')) {
            $query->where('id', $request->lowongan_id);
        }

        if ($request->filled('tgl_mulai')) {
            $query->whereDate('tanggal_mulai', '>=', $request->tgl_mulai);
        }

        if ($request->filled('tgl_berakhir')) {
            $query->whereDate('tanggal_berakhir', '<=', $request->tgl_berakhir);
        }

        if ($request->filled('sim_b2')) {
            $query->where('status_sim_b2', $request->sim_b2);
        }

        return $query;
    }

    private function buildPtkFilterOptions(): array
    {
        $ptks = PermintaanTenagaKerja::with(['departemen', 'divisi'])
            ->select('id', 'departemen_id', 'divisi_id', 'posisi', 'status_ptk')
            ->orderByRaw("FIELD(status_ptk, 'Proses', 'Diterima', 'Menunggu', 'Selesai', 'Ditolak')")
            ->orderByDesc('created_at')
            ->get();

        $groupedPtk = $ptks->groupBy([
            function ($item) {
                return $item->departemen_id ?: 'tanpa_departemen';
            },
            function ($item) {
                return $item->divisi_id ?: 'tanpa_divisi';
            },
        ]);

        $formatted = [];

        foreach ($groupedPtk as $deptId => $divisis) {
            $ptk = $ptks->firstWhere('departemen_id', $deptId === 'tanpa_departemen' ? null : $deptId);
            $departemen = $ptk ? $ptk->departemen : null;

            $deptGroup = [
                'departemen_id' => $deptId,
                'departemen' => $departemen ? $departemen->departemen : 'Tanpa Departemen',
                'divisis' => [],
            ];

            foreach ($divisis as $divisiId => $items) {
                $divisi = $items->first()->divisi;

                $deptGroup['divisis'][] = [
                    'divisi_id' => $divisiId,
                    'nama_divisi' => $divisi ? $divisi->nama_divisi : 'Tanpa Divisi',
                    'lowongans' => $items->map(function ($i) {
                        return [
                            'id' => $i->id,
                            'posisi' => $i->posisi,
                            'status_ptk' => $i->status_ptk,
                        ];
                    })->values(),
                ];
            }

            $formatted[] = $deptGroup;
        }

        return $formatted;
    }

    private function getApplicationStatusOverview(): array
    {
        $statuses = Lamaran::select('status_proses', DB::raw('COUNT(*) as total'))
            ->groupBy('status_proses')
            ->get();

        $summary = $this->summarizeApplicationStatuses($statuses);

        return [
            [
                'label' => 'Belum Diproses',
                'value' => $summary['Belum Diproses'],
                'class' => 'warning',
                'icon' => 'inbox',
            ],
            [
                'label' => 'Dalam Proses',
                'value' => $summary['Lanjut'],
                'class' => 'primary',
                'icon' => 'route',
            ],
            [
                'label' => 'Tidak Lolos',
                'value' => $summary['Tidak Lolos'],
                'class' => 'danger',
                'icon' => 'user-times',
            ],
            [
                'label' => 'Aktif Bekerja',
                'value' => (int) $statuses->where('status_proses', 'Aktif Bekerja')->sum('total'),
                'class' => 'success',
                'icon' => 'user-check',
            ],
        ];
    }

    private function summarizeApplicationStatuses($items): array
    {
        $statusCount = [
            'Lanjut' => 0,
            'Tidak Lolos' => 0,
            'Belum Diproses' => 0,
        ];

        foreach ($items as $item) {
            $status = (string) $item->status_proses;
            $total = isset($item->total) ? (int) $item->total : 1;

            if (stripos($status, 'Tidak Lolos') === 0) {
                $statusCount['Tidak Lolos'] += $total;
                continue;
            }

            if (in_array($status, self::STATUS_BELUM_DIPROSES, true)) {
                $statusCount['Belum Diproses'] += $total;
                continue;
            }

            $statusCount['Lanjut'] += $total;
        }

        return $statusCount;
    }

    private function buildDailyApplicationChart($lowonganIds): array
    {
        $startDate = Carbon::now()->subDays(29)->startOfDay();
        $rows = collect();

        if ($lowonganIds->isNotEmpty()) {
            $rows = DB::table('lamaran')
                ->selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
                ->whereIn('loker_id', $lowonganIds->all())
                ->whereDate('created_at', '>=', $startDate->toDateString())
                ->groupBy(DB::raw('DATE(created_at)'))
                ->pluck('total', 'tanggal');
        }

        $labels = [];
        $data = [];

        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            $key = $date->toDateString();
            $labels[] = $date->format('d M');
            $data[] = (int) ($rows[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Lamaran Masuk',
                'borderColor' => '#1cc88a',
                'backgroundColor' => 'rgba(28, 200, 138, 0.12)',
                'fill' => true,
                'tension' => 0.35,
                'data' => $data,
            ]],
        ];
    }

    private function buildFilteredSummary($lowongans): array
    {
        $now = Carbon::now();
        $lamarans = $lowongans->flatMap(function ($lowongan) {
            return $lowongan->lamaran;
        });
        $statusSummary = $this->summarizeApplicationStatuses($lamarans);
        $totalLamaran = (int) $lowongans->sum('lamaran_count');
        $aktifBekerja = (int) $lamarans->where('status_proses', 'Aktif Bekerja')->count();
        $uniquePtks = $lowongans->pluck('permintaanTenagaKerja')->filter()->unique('id');
        $totalKebutuhan = (int) $uniquePtks->sum('jumlah_ptk');

        return [
            'total_lowongan' => $lowongans->count(),
            'lowongan_aktif' => $lowongans->filter(function ($lowongan) use ($now) {
                return Carbon::parse($lowongan->tanggal_berakhir)->endOfDay()->greaterThanOrEqualTo($now);
            })->count(),
            'lowongan_tutup' => $lowongans->filter(function ($lowongan) use ($now) {
                return Carbon::parse($lowongan->tanggal_berakhir)->endOfDay()->lessThan($now);
            })->count(),
            'lowongan_tanpa_pelamar' => $lowongans->where('lamaran_count', 0)->count(),
            'lowongan_tutup_7_hari' => $lowongans->filter(function ($lowongan) use ($now) {
                $endDate = Carbon::parse($lowongan->tanggal_berakhir)->endOfDay();

                return $endDate->greaterThanOrEqualTo($now) && $endDate->lessThanOrEqualTo($now->copy()->addDays(7));
            })->count(),
            'total_lamaran' => $totalLamaran,
            'rata_rata_lamaran' => $lowongans->count() > 0 ? round($totalLamaran / $lowongans->count(), 1) : 0,
            'aktif_bekerja' => $aktifBekerja,
            'belum_diproses' => $statusSummary['Belum Diproses'],
            'tidak_lolos' => $statusSummary['Tidak Lolos'],
            'total_kebutuhan' => $totalKebutuhan,
            'persentase_terpenuhi' => $totalKebutuhan > 0 ? round(($aktifBekerja / $totalKebutuhan) * 100, 1) : 0,
            'last_updated' => $now->format('d M Y H:i'),
        ];
    }

    private function formatDateForDisplay($date): string
    {
        if (blank($date)) {
            return '-';
        }

        try {
            return tanggalIndo(Carbon::parse($date)->toDateString());
        } catch (\Throwable $e) {
            return (string) $date;
        }
    }

    private function daysLeftLabel($date): string
    {
        if (blank($date)) {
            return '-';
        }

        try {
            $today = Carbon::now()->startOfDay();
            $endDate = Carbon::parse($date)->startOfDay();
            $days = $today->diffInDays($endDate, false);
        } catch (\Throwable $e) {
            return '-';
        }

        if ($days < 0) {
            return 'Lewat ' . abs($days) . ' hari';
        }

        if ($days === 0) {
            return 'Berakhir hari ini';
        }

        return $days . ' hari lagi';
    }
}
