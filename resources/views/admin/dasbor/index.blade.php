@extends('layouts.app-pic')

@push('styles')
<link href="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css">

<style>
    #tableLowongan th {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        z-index: 1;
    }

    #tableLowongan tbody tr:hover {
        background-color: #eef5ff;
    }
</style>
@endpush

@section('content-admin')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Dashboard Rekrutmen</h1>

    {{-- Info Boxes --}}
    <div class="row mb-3">
        @php
        $cards = [
        ['color' => 'primary', 'icon' => 'briefcase', 'label' => 'Lowongan Aktif', 'value' => $count_lowongan_aktif],
        ['color' => 'danger', 'icon' => 'times-circle', 'label' => 'Lowongan Tidak Aktif', 'value' => $count_lowongan_tidak_aktif],
        ['color' => 'success', 'icon' => 'users', 'label' => 'Total Pengguna', 'value' => $count_user],
        ['color' => 'warning', 'icon' => 'bullhorn', 'label' => 'Total Pengumuman', 'value' => $count_pengumuman]
        ];
        @endphp
        @foreach ($cards as $card)
        <div class="col-md-3 mb-2">
            <div class="card border-left-{{ $card['color'] }} shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-{{ $card['color'] }} text-uppercase mb-1">
                                {{ $card['label'] }}
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $card['value'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-{{ $card['icon'] }} fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filter Section --}}
    <div class="card mb-4 shadow">
        <div class="card-body">
            <h5 class="mb-3 font-weight-bold">Filter Data Rekrutmen</h5>
            <div class="row">
                <div class="col-md-3">
                    <label for="filter-ptk">Permintaan Tenaga Kerja (PTK)</label>
                    <select name="ptk" id="filter-ptk" class="form-control form-control-sm">
                        <option value="">Semua</option>
                        @foreach ($permintaanTenagaKerjas as $departemen)
                        <optgroup label="{{ $departemen['departemen'] }}">
                            @foreach ($departemen['divisis'] as $divisi)
                        <optgroup label="&nbsp;&nbsp;{{ $divisi['nama_divisi'] }}">
                            @foreach ($divisi['lowongans'] as $ptk)
                            <option value="{{ $ptk['id'] }}">&nbsp;&nbsp;&nbsp;&nbsp;{{ $ptk['posisi'] }}</option>
                            @endforeach
                        </optgroup>
                        @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter-lowongan">Lowongan</label>
                    <select name="lowongan" id="filter-lowongan" class="form-control form-control-sm">
                        <option value="">Pilih PTK lebih dulu</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filter-tgl-mulai">Tanggal Mulai</label>
                    <input type="date" id="filter-tgl-mulai" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label for="filter-tgl-berakhir">Tanggal Berakhir</label>
                    <input type="date" id="filter-tgl-berakhir" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label for="filter-sim-b2">SIM B2</label>
                    <select id="filter-sim-b2" class="form-control form-control-sm">
                        <option value="">Semua</option>
                        <option value="1">Wajib SIM B2</option>
                        <option value="0">Tidak Wajib</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Detail --}}
    <div class="card shadow mb-3">
        <div class="card-body">
            <h5 class="card-title font-weight-bold">Data Detail Lowongan</h5>
            <div class="table-responsive">
                <table class="table table-striped table-bordered nowrap table-sm small" id="tableLowongan" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>Nama Lowongan</th>
                            <th>Mulai</th>
                            <th>Berakhir</th>
                            <th>Tahapan</th>
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    {{-- Visualisasi Data --}}
    <div class="row mb-4">
        <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title font-weight-bold">Rekapan Proses Lamaran</h5>
                    <canvas id="chartTahapanProses"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-8 mb-4">
            <div class="card shadow">
                <div class="card-body" style="height: 400px;">
                    <h5 class="card-title font-weight-bold">Jumlah Lamaran Per Rekrutmen</h5>
                    <canvas id="chartLowongan"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title font-weight-bold">Distribusi Status Lamaran</h5>
                    <canvas id="chartStatus"></canvas>
                </div>
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
    const filterSelectors = '#filter-ptk, #filter-lowongan, #filter-tgl-mulai, #filter-tgl-berakhir, #filter-sim-b2';

    const chartLowongan = new Chart($('#chartLowongan'), {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Jumlah Pelamar',
                data: [],
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderRadius: 5,
                barThickness: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // penting agar div wrapper mengatur tinggi
            indexAxis: 'y',
            scales: {
                x: {
                    stacked: true,
                    title: {
                        display: true,
                        text: 'Jumlah Kandidat'
                    },
                    ticks: {
                        precision: 0
                    }
                },
                y: {
                    stacked: true,
                    ticks: {
                        autoSkip: false,
                        font: {
                            size: 12
                        },
                        callback: function(value) {
                            if (value.length > 40) {
                                return value.substr(0, 40) + '...';
                            }
                            return value;
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    mode: 'index',
                    intersect: false
                },
                legend: {
                    position: 'top'
                }
            }
        }
    });

    const chartStatus = new Chart($('#chartStatus'), {
        type: 'pie',
        data: {
            labels: [],
            datasets: [{
                backgroundColor: ['#28a745', '#dc3545', '#ffc107'],
                data: []
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    const chartTahapanProses = new Chart($('#chartTahapanProses'), {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                    label: 'Lanjut',
                    backgroundColor: '#36a2eb',
                    data: []
                },
                {
                    label: 'Tidak Lolos',
                    backgroundColor: '#dc3545',
                    data: []
                }
            ]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            scales: {
                x: {
                    stacked: true,
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Jumlah Kandidat'
                    },
                    ticks: {
                        stepSize: 1, // <- Tambahkan ini agar hanya tampil angka bulat
                        precision: 0 // Pastikan angka tidak ada desimal
                    }
                },
                y: {
                    stacked: true
                }
            },
            plugins: {
                title: {
                    display: false
                },
                legend: {
                    position: 'top'
                }
            }
        }
    });

    function loadCharts() {
        $.get("{{ url('/admin/dasbor/lowongan-chart') }}", {
            ptk_id: $('#filter-ptk').val(),
            lowongan_id: $('#filter-lowongan').val(),
            tgl_mulai: $('#filter-tgl-mulai').val(),
            tgl_berakhir: $('#filter-tgl-berakhir').val(),
            sim_b2: $('#filter-sim-b2').val()
        }, function(res) {
            // Hitung tinggi wrapper chart berdasarkan jumlah label (bar)
            let jumlahBar = res.chart_posisi.labels.length;
            let tinggiPerBar = 45;
            let tinggiMinimal = 200;
            let tinggiChart = Math.max(tinggiMinimal, jumlahBar * tinggiPerBar);

            $('#chartLowongan').parent().css('height', tinggiChart + 'px');

            // chartLowongan
            chartLowongan.data.labels = res.chart_posisi.labels;
            chartLowongan.data.datasets[0].label = res.chart_posisi.datasets[0].label;
            chartLowongan.data.datasets[0].backgroundColor = res.chart_posisi.datasets[0].backgroundColor;
            chartLowongan.data.datasets[0].data = res.chart_posisi.datasets[0].data;
            chartLowongan.update();

            // chartStatus
            chartStatus.data.labels = res.chart_status.labels;
            chartStatus.data.datasets[0].label = res.chart_status.datasets[0].label;
            chartStatus.data.datasets[0].backgroundColor = res.chart_status.datasets[0].backgroundColor;
            chartStatus.data.datasets[0].data = res.chart_status.datasets[0].data;
            chartStatus.update();

            // chartTahapanProses
            chartTahapanProses.data.labels = res.chart_proses.labels;
            chartTahapanProses.data.datasets[0].label = res.chart_proses.datasets[0].label;
            chartTahapanProses.data.datasets[0].backgroundColor = res.chart_proses.datasets[0].backgroundColor;
            chartTahapanProses.data.datasets[0].data = res.chart_proses.datasets[0].data;

            chartTahapanProses.data.datasets[1].label = res.chart_proses.datasets[1].label;
            chartTahapanProses.data.datasets[1].backgroundColor = res.chart_proses.datasets[1].backgroundColor;
            chartTahapanProses.data.datasets[1].data = res.chart_proses.datasets[1].data;

            chartTahapanProses.update();
        });
    }


    function loadLowonganOptions(ptkId) {
        if (!ptkId) {
            $('#filter-lowongan').html('<option value="">Semua</option>');
            return;
        }
        $('#filter-lowongan').html('<option value="">Memuat...</option>');
        $.get(`/api/lowongan-by-ptk/${ptkId}`, function(res) {
            let options = '<option value="">Semua</option>';
            res.forEach(lowongan => {
                options += `<option value="${lowongan.id}">${lowongan.nama_lowongan}</option>`;
            });
            $('#filter-lowongan').html(options);
        }).fail(() => {
            $('#filter-lowongan').html('<option value="">Gagal memuat</option>');
        });
    }

    const table = $('#tableLowongan').DataTable({
        responsive: true,
        fixedHeader: true,
        scrollX: true,
        dom: 'Bfrtip',
        buttons: ['excel'],
        processing: true,
        serverSide: true,
        columnDefs: [{
                targets: [1, 4],
                className: 'text-wrap'
            } // Bungkus kolom panjang
        ],
        ajax: {
            url: "{{ url('/admin/dasbor/lowongan-data') }}",
            data: function(d) {
                d.ptk_id = $('#filter-ptk').val();
                d.lowongan_id = $('#filter-lowongan').val();
                d.tgl_mulai = $('#filter-tgl-mulai').val();
                d.tgl_berakhir = $('#filter-tgl-berakhir').val();
                d.sim_b2 = $('#filter-sim-b2').val();
            }
        },
        columns: [{
                data: 'nama_lowongan'
            },
            {
                data: 'tanggal_mulai'
            },
            {
                data: 'tanggal_berakhir'
            },
            {
                data: 'tahapan_terakhir',
                name: 'tahapan_terakhir',
                orderable: false,
                searchable: false
            },
            {
                data: 'jumlah_lamaran'
            }

        ],
        dom: 'Bfrtip',
        buttons: [{
            extend: 'excelHtml5',
            title: 'Data Lowongan'
        }]
    });

    $(function() {
        loadCharts();

        $(document).on('change', filterSelectors, function() {
            table.ajax.reload();
            loadCharts();
        });

        $('#filter-ptk').on('change', function() {
            loadLowonganOptions($(this).val());
        });
    });
</script>
@endpush


@endsection