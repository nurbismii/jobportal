@extends('layouts.app-pic')

@push('styles')
<link href="{{ versioned_asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

<style>
    .dashboard-page {
        color: #24324b;
    }

    .dashboard-hero {
        background: #4e73df;
        border-radius: 8px;
        color: #fff;
        padding: 1.35rem;
    }

    .dashboard-hero .btn {
        border-radius: 6px;
    }

    .metric-card {
        border: 0;
        border-radius: 8px;
        box-shadow: 0 0.15rem 0.75rem rgba(20, 35, 60, 0.08);
        height: 100%;
    }

    .metric-card .metric-icon {
        align-items: center;
        border-radius: 8px;
        display: inline-flex;
        height: 42px;
        justify-content: center;
        width: 42px;
    }

    .metric-value {
        color: #1d2b44;
        font-size: 1.65rem;
        font-weight: 800;
        line-height: 1;
    }

    .metric-label {
        color: #667085;
        font-size: .72rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .dashboard-card {
        border: 0;
        border-radius: 8px;
        box-shadow: 0 0.15rem 0.75rem rgba(20, 35, 60, 0.08);
    }

    .dashboard-card .card-header {
        background: #fff;
        border-bottom: 1px solid #e9eef5;
        border-radius: 8px 8px 0 0;
    }

    .quick-action {
        border: 1px solid rgba(255, 255, 255, .45);
        color: #fff;
    }

    .quick-action:hover {
        background: rgba(255, 255, 255, .14);
        color: #fff;
    }

    .mini-stat {
        background: #f8fafc;
        border: 1px solid #e9eef5;
        border-radius: 8px;
        padding: .75rem;
    }

    .chart-box {
        height: 320px;
        position: relative;
    }

    .chart-box.chart-box-sm {
        height: 270px;
    }

    .chart-box.chart-box-tall {
        height: 460px;
    }

    .chart-box canvas {
        height: 100% !important;
        max-height: 360px;
        width: 100% !important;
    }

    .chart-box.chart-box-tall canvas {
        max-height: 460px;
    }

    .dashboard-progress {
        background: #edf2f7;
        height: .55rem;
    }

    .insight-list {
        max-height: 360px;
        overflow-y: auto;
    }

    .insight-item {
        border-bottom: 1px solid #eef2f7;
        padding: .75rem 0;
    }

    .insight-item:last-child {
        border-bottom: 0;
    }

    .filter-toolbar .form-control {
        min-height: 34px;
    }

    #tableLowongan th {
        background-color: #f8f9fa;
        white-space: nowrap;
    }

    #tableLowongan tbody tr:hover {
        background-color: #eef7ff;
    }

    #tableLowongan td {
        vertical-align: middle;
    }

    .dashboard-loading {
        align-items: center;
        background: rgba(255, 255, 255, .75);
        border-radius: 8px;
        display: flex;
        inset: 0;
        justify-content: center;
        position: absolute;
        z-index: 5;
    }

    @media (max-width: 767.98px) {
        .dashboard-hero {
            padding: 1rem;
        }

        .metric-value {
            font-size: 1.35rem;
        }

        .chart-box {
            height: 260px;
        }
    }
</style>
@endpush

@section('content-admin')
<div class="dashboard-page">
    <div class="dashboard-hero mb-4">
        <div class="row align-items-center">
            <div class="col-lg-7 mb-3 mb-lg-0">
                <div class="small text-white-50 font-weight-bold text-uppercase mb-2">Dashboard Admin</div>
                <h1 class="h3 font-weight-bold mb-2">Dashboard Rekrutmen</h1>
                <p class="mb-0 text-white-50">
                    Pantau lowongan, progres PTK, funnel lamaran, dan aktivitas kandidat dari satu halaman.
                </p>
                <div class="small mt-3">
                    <i class="fas fa-sync-alt mr-1"></i>
                    Update awal: {{ $lastUpdatedAt->format('d M Y H:i') }}
                </div>
            </div>
            <div class="col-lg-5">
                <div class="d-flex flex-wrap justify-content-lg-end">
                    <a href="{{ route('lowongan.create') }}" class="btn quick-action btn-sm mr-2 mb-2">
                        <i class="fas fa-plus mr-1"></i> Lowongan
                    </a>
                    <a href="{{ route('permintaan-tenaga-kerja.create') }}" class="btn quick-action btn-sm mr-2 mb-2">
                        <i class="fas fa-user-plus mr-1"></i> PTK
                    </a>
                    <a href="{{ route('lowongan.index') }}" class="btn quick-action btn-sm mr-2 mb-2">
                        <i class="fas fa-briefcase mr-1"></i> Kelola Lowongan
                    </a>
                    <a href="{{ route('pengguna.index') }}" class="btn quick-action btn-sm mb-2">
                        <i class="fas fa-users mr-1"></i> Pelamar
                    </a>
                </div>
            </div>
        </div>
    </div>

    @php
        $mainCards = [
            [
                'color' => 'primary',
                'bg' => 'rgba(78, 115, 223, .12)',
                'icon' => 'briefcase',
                'label' => 'Lowongan Aktif',
                'value' => $count_lowongan_aktif,
                'note' => $lowongan_tutup_minggu_ini . ' tutup dalam 7 hari',
                'href' => route('lowongan.index'),
            ],
            [
                'color' => 'info',
                'bg' => 'rgba(54, 185, 204, .12)',
                'icon' => 'file-alt',
                'label' => 'Total Lamaran',
                'value' => $total_lamaran,
                'note' => number_format($lamaran_baru, 0, ',', '.') . ' belum diproses',
                'href' => route('lamarans.index'),
            ],
            [
                'color' => 'success',
                'bg' => 'rgba(28, 200, 138, .12)',
                'icon' => 'user-check',
                'label' => 'Aktif Bekerja',
                'value' => $aktif_bekerja,
                'note' => $konversi_aktif_bekerja . '% dari total lamaran',
                'href' => route('kandidat-potensial.index'),
            ],
            [
                'color' => 'warning',
                'bg' => 'rgba(246, 194, 62, .16)',
                'icon' => 'clipboard-list',
                'label' => 'Open PTK',
                'value' => $ptkStats['open_ptk'],
                'note' => number_format($ptkStats['total_sisa'], 0, ',', '.') . ' kebutuhan tersisa',
                'href' => route('permintaan-tenaga-kerja.index'),
            ],
        ];
    @endphp

    <div class="row mb-4">
        @foreach ($mainCards as $card)
        <div class="col-sm-6 col-xl-3 mb-3">
            <a href="{{ $card['href'] }}" class="text-decoration-none">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="metric-label">{{ $card['label'] }}</div>
                                <div class="metric-value mt-2">{{ number_format($card['value'], 0, ',', '.') }}</div>
                                <div class="small text-muted mt-2">{{ $card['note'] }}</div>
                            </div>
                            <div class="metric-icon text-{{ $card['color'] }}" style="background: {{ $card['bg'] }}">
                                <i class="fas fa-{{ $card['icon'] }}"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    <div class="row mb-4">
        <div class="col-xl-7 mb-3">
            <div class="card dashboard-card h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Pemenuhan Kebutuhan PTK</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between mb-3">
                        <div>
                            <div class="small text-muted text-uppercase font-weight-bold">Progress Terpenuhi</div>
                            <div class="h2 font-weight-bold mb-0">{{ $ptkStats['persentase_terpenuhi'] }}%</div>
                        </div>
                        <div class="text-md-right mt-2 mt-md-0">
                            <div class="small text-muted">Terpenuhi / Kebutuhan</div>
                            <div class="font-weight-bold">
                                {{ number_format($ptkStats['total_terpenuhi'], 0, ',', '.') }}
                                /
                                {{ number_format($ptkStats['total_kebutuhan'], 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                    <div class="progress dashboard-progress mb-3">
                        <div class="progress-bar bg-success" role="progressbar"
                            style="width: {{ min($ptkStats['persentase_terpenuhi'], 100) }}%"
                            aria-valuenow="{{ min($ptkStats['persentase_terpenuhi'], 100) }}"
                            aria-valuemin="0"
                            aria-valuemax="100"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <div class="mini-stat">
                                <div class="small text-muted">Kebutuhan</div>
                                <div class="h5 mb-0 font-weight-bold">{{ number_format($ptkStats['total_kebutuhan'], 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <div class="mini-stat">
                                <div class="small text-muted">Terpenuhi</div>
                                <div class="h5 mb-0 font-weight-bold text-success">{{ number_format($ptkStats['total_terpenuhi'], 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mini-stat">
                                <div class="small text-muted">Sisa</div>
                                <div class="h5 mb-0 font-weight-bold text-warning">{{ number_format($ptkStats['total_sisa'], 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="small text-muted mt-3">
                        Angka terpenuhi mengikuti field <strong>jumlah_masuk</strong> PTK yang sudah dipakai modul PTK.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5 mb-3">
            <div class="card dashboard-card h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Funnel Lamaran Saat Ini</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach ($statusLamaranOverview as $status)
                        <div class="col-6 mb-3">
                            <div class="mini-stat h-100">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="small text-muted">{{ $status['label'] }}</span>
                                    <i class="fas fa-{{ $status['icon'] }} text-{{ $status['class'] }}"></i>
                                </div>
                                <div class="h4 mb-0 mt-2 font-weight-bold text-{{ $status['class'] }}">
                                    {{ number_format($status['value'], 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="small text-muted">Akun Pelamar Aktif</div>
                            <div class="font-weight-bold">{{ number_format($count_user, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Pengumuman</div>
                            <div class="font-weight-bold">{{ number_format($count_pengumuman, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card dashboard-card mb-4">
        <div class="card-header py-3 d-flex flex-column flex-lg-row justify-content-between">
            <div>
                <h6 class="m-0 font-weight-bold text-primary">Filter Interaktif Data Rekrutmen</h6>
                <div class="small text-muted mt-1" id="filter-state">Filter otomatis memperbarui kartu, chart, dan tabel.</div>
            </div>
            <div class="mt-2 mt-lg-0">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-reset-filter">
                    <i class="fas fa-undo mr-1"></i> Reset
                </button>
                <button type="button" class="btn btn-sm btn-primary" id="btn-refresh-dashboard">
                    <i class="fas fa-sync-alt mr-1"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body filter-toolbar">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <label for="filter-ptk" class="small font-weight-bold text-muted">Permintaan Tenaga Kerja</label>
                    <select name="ptk" id="filter-ptk" class="form-control form-control-sm">
                        <option value="">Semua PTK</option>
                        @foreach ($permintaanTenagaKerjas as $departemen)
                            <optgroup label="{{ $departemen['departemen'] }}">
                                @foreach ($departemen['divisis'] as $divisi)
                                    <option value="" disabled>-- {{ $divisi['nama_divisi'] }} --</option>
                                    @foreach ($divisi['lowongans'] as $ptk)
                                        <option value="{{ $ptk['id'] }}">
                                            {{ $ptk['posisi'] }} - {{ $ptk['status_ptk'] }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <label for="filter-lowongan" class="small font-weight-bold text-muted">Lowongan</label>
                    <select name="lowongan" id="filter-lowongan" class="form-control form-control-sm">
                        <option value="">Semua lowongan</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 mb-3">
                    <label for="filter-tgl-mulai" class="small font-weight-bold text-muted">Tanggal Mulai</label>
                    <input type="date" id="filter-tgl-mulai" class="form-control form-control-sm">
                </div>
                <div class="col-lg-2 col-md-4 mb-3">
                    <label for="filter-tgl-berakhir" class="small font-weight-bold text-muted">Tanggal Berakhir</label>
                    <input type="date" id="filter-tgl-berakhir" class="form-control form-control-sm">
                </div>
                <div class="col-lg-2 col-md-4 mb-3">
                    <label for="filter-sim-b2" class="small font-weight-bold text-muted">SIM B2</label>
                    <select id="filter-sim-b2" class="form-control form-control-sm">
                        <option value="">Semua</option>
                        <option value="1">Wajib SIM B2</option>
                        <option value="0">Tidak Wajib</option>
                    </select>
                </div>
            </div>

            <div class="row" id="filtered-summary">
                @php
                    $summaryCards = [
                        ['label' => 'Lowongan', 'key' => 'total_lowongan', 'icon' => 'briefcase', 'class' => 'primary'],
                        ['label' => 'Lamaran', 'key' => 'total_lamaran', 'icon' => 'file-alt', 'class' => 'info'],
                        ['label' => 'Aktif Bekerja', 'key' => 'aktif_bekerja', 'icon' => 'user-check', 'class' => 'success'],
                        ['label' => 'Belum Diproses', 'key' => 'belum_diproses', 'icon' => 'inbox', 'class' => 'warning'],
                        ['label' => 'Tanpa Pelamar', 'key' => 'lowongan_tanpa_pelamar', 'icon' => 'exclamation-circle', 'class' => 'danger'],
                        ['label' => 'Terpenuhi', 'key' => 'persentase_terpenuhi', 'icon' => 'chart-line', 'class' => 'success', 'suffix' => '%'],
                    ];
                @endphp
                @foreach ($summaryCards as $card)
                <div class="col-6 col-lg-2 mb-2">
                    <div class="mini-stat h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="small text-muted">{{ $card['label'] }}</span>
                            <i class="fas fa-{{ $card['icon'] }} text-{{ $card['class'] }}"></i>
                        </div>
                        <div class="h5 mb-0 mt-2 font-weight-bold" data-summary="{{ $card['key'] }}" data-suffix="{{ $card['suffix'] ?? '' }}">-</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-xl-8 mb-3">
            <div class="card dashboard-card h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Trend Lamaran Masuk 30 Hari</h6>
                </div>
                <div class="card-body position-relative">
                    <div class="dashboard-loading d-none" id="dashboard-loading">
                        <div class="text-primary font-weight-bold">
                            <i class="fas fa-spinner fa-spin mr-2"></i> Memuat data
                        </div>
                    </div>
                    <div class="chart-box">
                        <canvas id="chartDaily"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 mb-3">
            <div class="card dashboard-card h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Distribusi Status Lamaran</h6>
                </div>
                <div class="card-body">
                    <div class="chart-box chart-box-sm">
                        <canvas id="chartStatus"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-xl-5 mb-3">
            <div class="card dashboard-card h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Lowongan Teramai</h6>
                </div>
                <div class="card-body">
                    <div class="chart-box">
                        <canvas id="chartPosisi"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-7 mb-3">
            <div class="card dashboard-card h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Rekap Tahapan Terakhir Kandidat</h6>
                </div>
                <div class="card-body">
                    <div class="chart-box chart-box-tall">
                        <canvas id="chartTahapanProses"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-xl-4 mb-3">
            <div class="card dashboard-card h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Butuh Perhatian</h6>
                </div>
                <div class="card-body insight-list">
                    @forelse($lowonganButuhPerhatian as $lowongan)
                    <div class="insight-item">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('directToLamaran', $lowongan->id) }}" class="font-weight-bold text-gray-800">
                                {{ $lowongan->nama_lowongan }}
                            </a>
                            <span class="badge badge-{{ $lowongan->lamaran_count == 0 ? 'danger' : 'warning' }}">
                                {{ $lowongan->lamaran_count }} pelamar
                            </span>
                        </div>
                        <div class="small text-muted mt-1">
                            Berakhir {{ $lowongan->tanggal_berakhir ? tanggalIndo($lowongan->tanggal_berakhir) : '-' }}
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                        <div>Tidak ada lowongan mendesak.</div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-4 mb-3">
            <div class="card dashboard-card h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Lamaran Terbaru</h6>
                </div>
                <div class="card-body insight-list">
                    @forelse($latestLamarans as $lamaran)
                        @php
                            $candidateName = optional(optional($lamaran->biodata)->user)->name ?: optional($lamaran->biodata)->no_ktp ?: 'Pelamar';
                            $lowonganName = optional($lamaran->lowongan)->nama_lowongan ?: 'Lowongan tidak tersedia';
                            $candidateUrl = optional($lamaran->lowongan)->id ? route('directToLamaran', $lamaran->lowongan->id) : '#';
                            if (optional(optional($lamaran->biodata)->user)->id && optional($lamaran->lowongan)->id) {
                                $candidateUrl .= '?user_id=' . optional($lamaran->biodata->user)->id;
                            }
                        @endphp
                    <div class="insight-item">
                        <div class="d-flex justify-content-between">
                            <a href="{{ $candidateUrl }}" class="font-weight-bold text-gray-800">{{ $candidateName }}</a>
                            <span class="small text-muted">{{ optional($lamaran->created_at)->format('d M') }}</span>
                        </div>
                        <div class="small text-muted mt-1">{{ $lowonganName }}</div>
                        <span class="badge badge-light mt-2">{{ $lamaran->status_proses ?: 'Belum ada status' }}</span>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <div>Belum ada lamaran.</div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-4 mb-3">
            <div class="card dashboard-card h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">PTK Terbaru</h6>
                </div>
                <div class="card-body insight-list">
                    @php
                        $ptkBadge = [
                            'diterima' => 'success',
                            'ditolak' => 'danger',
                            'menunggu' => 'secondary',
                            'proses' => 'warning',
                            'selesai' => 'primary',
                        ];
                    @endphp
                    @forelse($recentPtk as $ptk)
                    <div class="insight-item">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('permintaan-tenaga-kerja.show', $ptk->id) }}" class="font-weight-bold text-gray-800">
                                {{ $ptk->posisi }}
                            </a>
                            @php $statusKey = strtolower((string) $ptk->status_ptk); @endphp
                            <span class="badge badge-{{ $ptkBadge[$statusKey] ?? 'light' }}">{{ $ptk->status_ptk ?: '-' }}</span>
                        </div>
                        <div class="small text-muted mt-1">
                            {{ optional($ptk->departemen)->departemen ?: 'Tanpa Departemen' }}
                            @if(optional($ptk->divisi)->nama_divisi)
                                - {{ $ptk->divisi->nama_divisi }}
                            @endif
                        </div>
                        <div class="small mt-1">
                            Masuk {{ number_format((int) $ptk->jumlah_masuk, 0, ',', '.') }}
                            dari {{ number_format((int) $ptk->jumlah_ptk, 0, ',', '.') }}
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                        <div>Belum ada PTK.</div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card dashboard-card mb-4">
        <div class="card-header py-3 d-flex flex-column flex-md-row justify-content-between">
            <div>
                <h6 class="m-0 font-weight-bold text-primary">Detail Lowongan dan Funnel Kandidat</h6>
                <div class="small text-muted mt-1">Gunakan export Excel untuk follow up operasional.</div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered nowrap table-sm small" id="tableLowongan" style="width:100%">
                    <thead>
                        <tr>
                            <th>Nama Lowongan</th>
                            <th>Status</th>
                            <th>Mulai</th>
                            <th>Berakhir</th>
                            <th>Lamaran</th>
                            <th>Kebutuhan</th>
                            <th>Progress</th>
                            <th>Tahapan Terakhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
    const filterInputs = '#filter-lowongan, #filter-tgl-mulai, #filter-tgl-berakhir, #filter-sim-b2';
    let reloadTimer = null;

    function numberId(value) {
        const number = Number(value || 0);
        return new Intl.NumberFormat('id-ID').format(number);
    }

    function setLoading(isLoading) {
        $('#dashboard-loading').toggleClass('d-none', !isLoading);
        $('#btn-refresh-dashboard i').toggleClass('fa-spin', isLoading);
    }

    function collectFilters() {
        return {
            ptk_id: $('#filter-ptk').val(),
            lowongan_id: $('#filter-lowongan').val(),
            tgl_mulai: $('#filter-tgl-mulai').val(),
            tgl_berakhir: $('#filter-tgl-berakhir').val(),
            sim_b2: $('#filter-sim-b2').val()
        };
    }

    function updateChart(chart, payload) {
        chart.data.labels = payload.labels || [];
        chart.data.datasets = payload.datasets || [];
        chart.update();
    }

    function updateFilteredSummary(summary) {
        $('[data-summary]').each(function() {
            const key = $(this).data('summary');
            const suffix = $(this).data('suffix') || '';
            const value = summary && summary[key] !== undefined ? summary[key] : 0;
            $(this).text(numberId(value) + suffix);
        });

        if (summary && summary.last_updated) {
            $('#filter-state').text('Data filter diperbarui: ' + summary.last_updated);
        }
    }

    const chartDaily = new Chart(document.getElementById('chartDaily'), {
        type: 'line',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    const chartStatus = new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '62%'
        }
    });

    const chartPosisi = new Chart(document.getElementById('chartPosisi'), {
        type: 'bar',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    const chartTahapanProses = new Chart(document.getElementById('chartTahapanProses'), {
        type: 'bar',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                },
                y: {
                    stacked: true
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });

    function loadCharts() {
        setLoading(true);

        $.get("{{ url('/admin/dasbor/lowongan-chart') }}", collectFilters())
            .done(function(res) {
                updateFilteredSummary(res.summary);
                updateChart(chartDaily, res.chart_daily);
                updateChart(chartStatus, res.chart_status);
                updateChart(chartPosisi, res.chart_posisi);
                updateChart(chartTahapanProses, res.chart_proses);
            })
            .fail(function() {
                $('#filter-state').text('Gagal memuat data dashboard. Cek koneksi atau log aplikasi.');
            })
            .always(function() {
                setLoading(false);
            });
    }

    function scheduleReload() {
        clearTimeout(reloadTimer);
        reloadTimer = setTimeout(function() {
            table.ajax.reload();
            loadCharts();
        }, 250);
    }

    function loadLowonganOptions(ptkId) {
        if (!ptkId) {
            $('#filter-lowongan').html('<option value="">Semua lowongan</option>');
            return;
        }

        $('#filter-lowongan').html('<option value="">Memuat...</option>');

        $.get(`/api/lowongan-by-ptk/${ptkId}`, function(res) {
            let options = '<option value="">Semua lowongan</option>';
            res.forEach(function(lowongan) {
                options += `<option value="${lowongan.id}">${lowongan.nama_lowongan}</option>`;
            });
            $('#filter-lowongan').html(options);
        }).fail(function() {
            $('#filter-lowongan').html('<option value="">Gagal memuat lowongan</option>');
        });
    }

    const table = $('#tableLowongan').DataTable({
        scrollX: true,
        autoWidth: false,
        processing: true,
        serverSide: true,
        pageLength: 10,
        order: [
            [3, 'desc']
        ],
        ajax: {
            url: "{{ url('/admin/dasbor/lowongan-data') }}",
            data: function(d) {
                Object.assign(d, collectFilters());
            },
            error: function() {
                $('#filter-state').text('Gagal memuat tabel lowongan. Cek log aplikasi.');
            }
        },
        columns: [
            {
                data: 'nama_lowongan',
                name: 'nama_lowongan'
            },
            {
                data: 'status_lowongan',
                name: 'tanggal_berakhir',
                searchable: false
            },
            {
                data: 'tanggal_mulai',
                name: 'tanggal_mulai'
            },
            {
                data: 'tanggal_berakhir',
                name: 'tanggal_berakhir'
            },
            {
                data: 'jumlah_lamaran',
                name: 'jumlah_lamaran',
                searchable: false
            },
            {
                data: 'kebutuhan_ptk',
                name: 'kebutuhan_ptk',
                searchable: false,
                orderable: false
            },
            {
                data: 'progress_ptk',
                name: 'progress_ptk',
                searchable: false,
                orderable: false
            },
            {
                data: 'tahapan_terakhir',
                name: 'tahapan_terakhir',
                searchable: false,
                orderable: false
            },
            {
                data: 'aksi',
                name: 'aksi',
                searchable: false,
                orderable: false
            }
        ],
        dom: "<'row align-items-center mb-2'<'col-md-6'B><'col-md-6'f>>" +
            "<'row'<'col-12'tr>>" +
            "<'row align-items-center mt-2'<'col-md-5'i><'col-md-7'p>>",
        buttons: [{
            extend: 'excelHtml5',
            title: 'Dashboard Lowongan Rekrutmen',
            text: '<i class="fas fa-file-excel mr-1"></i> Export Excel',
            className: 'btn btn-sm btn-success'
        }],
        language: {
            processing: 'Memuat data...',
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            infoEmpty: 'Tidak ada data',
            zeroRecords: 'Data tidak ditemukan',
            paginate: {
                first: 'Awal',
                last: 'Akhir',
                next: 'Berikutnya',
                previous: 'Sebelumnya'
            }
        }
    });

    $(function() {
        loadCharts();

        $('#filter-ptk').on('change', function() {
            $('#filter-lowongan').val('');
            loadLowonganOptions($(this).val());
            scheduleReload();
        });

        $(document).on('change', filterInputs, scheduleReload);

        $('#btn-refresh-dashboard').on('click', function() {
            table.ajax.reload();
            loadCharts();
        });

        $('#btn-reset-filter').on('click', function() {
            $('#filter-ptk').val('');
            $('#filter-lowongan').html('<option value="">Semua lowongan</option>');
            $('#filter-tgl-mulai').val('');
            $('#filter-tgl-berakhir').val('');
            $('#filter-sim-b2').val('');
            table.ajax.reload();
            loadCharts();
        });
    });
</script>
@endpush

@endsection
