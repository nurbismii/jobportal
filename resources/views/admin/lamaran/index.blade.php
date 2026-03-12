@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<!-- FixedColumns CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- Quill CSS -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />

<style>
    td.editable {
        cursor: text;
        background-color: #f9f9f9;
        border: 1px solid #ccc;
        padding: 6px 8px;
        border-radius: 4px;
        font-family: inherit;
        font-size: 14px;
        transition: all 0.2s ease-in-out;
    }

    td.editable:focus,
    td.editable[contenteditable="true"]:focus {
        outline: none;
        border: 1px solid #007bff;
        background-color: #fff;
        box-shadow: 0 0 3px rgba(0, 123, 255, 0.5);
    }

    td.editable[contenteditable="true"] {
        background-color: #f9f9f9;
    }

    td.editable.editing {
        background-color: #fffbe6;
        /* kuning soft saat sedang diedit */
    }

    /* Gaya tombol DataTables */
    .dt-button {
        margin-right: 5px;
        border-radius: 0.25rem;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    /* Dropdown Show entries */
    .dataTables_length select {
        width: auto;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        border: 1px solid #ced4da;
    }

    /* Search box styling */
    .dataTables_filter input {
        border-radius: 0.25rem;
        padding: 0.25rem 0.5rem;
        border: 1px solid #ced4da;
    }

    /* Pagination */
    .dataTables_paginate .pagination .page-item .page-link {
        border-radius: 0.25rem;
        /* margin: 0 1px; */
        color: #007bff;
    }

    .dataTables_paginate .pagination .page-item.active .page-link {
        background-color: #007bff;
        border-color: #007bff;
        color: #fff;
    }

    /* Hover row effect */
    #dataTable tbody tr:hover {
        background-color: #f1f3f5;
        cursor: pointer;
    }

    div.dataTables_wrapper div.dataTables_length {
        margin-right: 1rem;
        /* atau 16px, bisa ditambah sesuai kebutuhan */
    }
</style>
@endpush
<div class="position-absolute top-0 end-0 p-3" style="z-index: 1050;">
    <div id="toast-update-success" c class="toast align-items-center text-white bg-success border-0 fs-5 px-4 py-3 rounded shadow"
        role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                Data berhasil diperbarui.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between mb-3">
    <h2 class="m-0 font-weight-bold text-primary">{{ $lowongan->nama_lowongan }}</h2>
    <div class="d-flex align-items-center">

        <button type="button" class="btn btn-warning btn-sm mr-2" data-toggle="modal" data-target="#modalRefreshStatus">
            Refresh Status Pelamar
        </button>

        <a href="{{ route('lowongan.index') }}" class="btn btn-danger btn-sm btn-icon-split">
            <span class="icon text-white-50">
                <i class="fas fa-arrow-left"></i>
            </span>
            <span class="text">Kembali</span>
        </a>


    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalRefreshStatus" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="exampleModalLabel">Peringatan!</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('refreshData') }}" method="post">
                    @csrf
                    <div class="modal-body">
                        Kamu yakin ingin melakukan refresh status pelamar ?
                    </div>
                    @foreach($lamarans as $data)
                    <input type="hidden" name="no_ktp[]" value="{{ $data->biodata->no_ktp }}">
                    @endforeach

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tidak</button>
                        <button type="submit" class="btn btn-primary">Ya</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Filter data pelamar -->
<div class="card shadow mb-3">
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-4 mb-3">
                <select name="status[]" class="form-control form-control-sm multiple-status-pelamar" multiple="multiple">
                    <option value="">-- Filter Status --</option>
                    <optgroup label="Tahapan Proses">
                        <option value="Belum Sesuai Kriteria" {{ collect(request('status'))->contains('Belum Sesuai Kriteria') ? 'selected' : '' }}>Belum Sesuai Kriteria</option>
                        <option value="Lamaran Dikirim" {{ collect(request('status'))->contains('Lamaran Dikirim') ? 'selected' : '' }}>Lamaran Dikirim</option>
                        <option value="Verifikasi Online" {{ collect(request('status'))->contains('Verifikasi Online') ? 'selected' : '' }}>Verifikasi Online</option>
                        <option value="Verifikasi Berkas" {{ collect(request('status'))->contains('Verifikasi Berkas') ? 'selected' : '' }}>Verifikasi Berkas</option>
                        <option value="Tes Kesehatan" {{ collect(request('status'))->contains('Tes Kesehatan') ? 'selected' : '' }}>Tes Kesehatan</option>
                        <option value="Tes Lapangan" {{ collect(request('status'))->contains('Tes Lapangan') ? 'selected' : '' }}>Tes Lapangan</option>
                        <option value="Medical Check-Up" {{ collect(request('status'))->contains('Medical Check-Up') ? 'selected' : '' }}>Medical Check-Up</option>
                        <option value="Induksi Safety" {{ collect(request('status'))->contains('Induksi Safety') ? 'selected' : '' }}>Induksi Safety</option>
                        <option value="Tanda Tangan Kontrak" {{ collect(request('status'))->contains('Tanda Tangan Kontrak') ? 'selected' : '' }}>Tanda Tangan Kontrak</option>
                        <option value="Aktif Bekerja" {{ collect(request('status'))->contains('Aktif Bekerja') ? 'selected' : '' }}>Aktif Bekerja</option>
                    </optgroup>
                    <optgroup label="Tahapan Tidak Lolos">
                        <option value="Tidak Sesuai Kriteria" {{ collect(request('status'))->contains('Tidak Sesuai Kriteria') ? 'selected' : '' }}>Tidak Sesuai Kriteria</option>
                        <option value="Tidak Lolos Verifikasi Berkas" {{ collect(request('status'))->contains('Tidak Lolos Verifikasi Berkas') ? 'selected' : '' }}>Tidak Lolos Verifikasi Berkas</option>
                        <option value="Tidak Lolos Tes Kesehatan" {{ collect(request('status'))->contains('Tidak Lolos Tes Kesehatan') ? 'selected' : '' }}>Tidak Lolos Tes Kesehatan</option>
                        <option value="Tidak Lolos Tes Lapangan" {{ collect(request('status'))->contains('Tidak Lolos Tes Lapangan') ? 'selected' : '' }}>Tidak Lolos Tes Lapangan</option>
                        <option value="Tidak Lolos Medical Check-Up" {{ collect(request('status'))->contains('Tidak Lolos Medical Check-Up') ? 'selected' : '' }}>Tidak Lolos Medical Check-Up</option>
                    </optgroup>

                </select>
            </div>

            <div class="col-md-4 mb-3">
                <input type="number" name="umur_min" class="form-control form-control-sm" placeholder="Umur Minimal" value="{{ request('umur_min') }}">
            </div>
            <div class="col-md-4 mb-3">
                <input type="number" name="umur_max" class="form-control form-control-sm" placeholder="Umur Maksimal" value="{{ request('umur_max') }}">
            </div>

            <div class="col-md-4 mb-3">
                <select name="pendidikan[]" class="form-control form-control-sm multiple-pendidikan" multiple="multiple">
                    <option value="">-- Filter Pendidikan --</option>
                    <option value="SD 小学" {{ collect(request('pendidikan'))->contains('SD 小学') ? 'selected' : '' }}>SD 小学</option>
                    <option value="SMP 初中" {{ collect(request('pendidikan'))->contains('SMP 初中') ? 'selected' : '' }}>SMP 初中</option>
                    <option value="SMA 高中" {{ collect(request('pendidikan'))->contains('SMA 高中') ? 'selected' : '' }}>SMA 高中</option>
                    <option value="SMK 高中" {{ collect(request('pendidikan'))->contains('SMK 高中') ? 'selected' : '' }}>SMK 高中</option>
                    <option value="D3 大专三年" {{ collect(request('pendidikan'))->contains('D3 大专三年') ? 'selected' : '' }}>D3 大专三年</option>
                    <option value="D4 大专三年" {{ collect(request('pendidikan'))->contains('D4 大专三年') ? 'selected' : '' }}>D4 大专三年</option>
                    <option value="S1 本科" {{ collect(request('pendidikan'))->contains('S1 本科') ? 'selected' : '' }}>S1 本科</option>
                    <option value="S2 研究生" {{ collect(request('pendidikan'))->contains('S2 研究生') ? 'selected' : '' }}>S2 研究生</option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <select name="status_resign[]" class="form-control form-control-sm multiple-status" multiple="multiple">
                    <option value="">-- Filter Status Pelamar --</option>
                    <option value="PENDAFTAR BERSIH" {{ collect(request('status_resign'))->contains('PENDAFTAR BERSIH') ? 'selected' : '' }}>PENDAFTAR BERSIH</option>
                    <option value="RESIGN SESUAI PROSEDUR" {{ collect(request('status_resign'))->contains('RESIGN SESUAI PROSEDUR') ? 'selected' : '' }}>RESIGN SESUAI PROSEDUR</option>
                    <option value="RESIGN TIDAK SESUAI PROSEDUR" {{ collect(request('status_resign'))->contains('RESIGN TIDAK SESUAI PROSEDUR') ? 'selected' : '' }}>RESIGN TIDAK SESUAI PROSEDUR</option>
                    <option value="PHK" {{ collect(request('status_resign'))->contains('PHK') ? 'selected' : '' }}>PHK</option>
                    <option value="PHK PIDANA" {{ collect(request('status_resign'))->contains('PHK PIDANA') ? 'selected' : '' }}>PHK PIDANA</option>
                    <option value="PB PHK" {{ collect(request('status_resign'))->contains('PB PHK') ? 'selected' : '' }}>PB PHK</option>
                    <option value="PB RESIGN" {{ collect(request('status_resign'))->contains('PB RESIGN') ? 'selected' : '' }}>PB RESIGN</option>
                    <option value="PUTUS KONTRAK" {{ collect(request('status_resign'))->contains('PUTUS KONTRAK') ? 'selected' : '' }}>PUTUS KONTRAK</option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <select name="jenis_kelamin" class="form-control form-control-sm">
                    <option value="">-- Filter Jenis Kelamin --</option>
                    <option value="M 男" {{ request('jenis_kelamin') == 'M 男' ? 'selected' : '' }}>M 男</option>
                    <option value="F 女" {{ request('jenis_kelamin') == 'F 女' ? 'selected' : '' }}>F 女</option>
                </select>
            </div>

            <div class="col-md-12">
                <a href="{{ route('directToLamaran', $lowongan->id) }}" class="btn btn-secondary">Reset</a>
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>
</div>

<form action="{{ route('lamaran.updateStatusMassal') }}" method="POST">

    <div class="card shadow mb-2">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Data Pelamar</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered nowrap table-sm small" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Riwayat</th> <!-- tombol expand -->
                            <th><input type="checkbox" id="checkAll"></th>
                            <th>Status</th>
                            <th>Nama</th>
                            <th>No KTP</th>
                            <th>No KK</th>
                            <th>Email</th>
                            <th class="bg-warning">Status Pelamar</th>
                            <th class="bg-warning">Tgl Resign</th>
                            <th class="bg-warning">Rentang</th>
                            <th class="bg-warning">Ex Area</th>
                            <th class="bg-warning">Alasan</th>
                            <th>Jenis Kelamin</th>
                            <th>Tempat Lahir</th>
                            <th>Tanggal Lahir</th>
                            <th>Umur</th>
                            <th>Status Pernikahan</th>
                            <th>Jumlah Anak</th>
                            <th>RT/RW</th>
                            <th>Kode Pos</th>
                            <th>Provinsi</th>
                            <th>kabupaten</th>
                            <th>kecamatan</th>
                            <th>Kelurahan</th>
                            <th>Alamat</th>
                            <th>No HP</th>
                            <th>NPWP</th>
                            <th>Gol. Darah</th>
                            <th>Nama Instansi</th>
                            <th>Pend. Terakhir</th>
                            <th>Jurusan</th>
                            <th>Tanggal Kelulusan</th>
                            <th>Agama</th>
                            <th>Nama Ibu</th>
                            <th>Nama Ayah</th>
                            <th>Suami/Istri</th>
                            <th>Tanggal Menikah</th>
                            <th>Anak ke-1</th>
                            <th>Anak ke-2</th>
                            <th>Anak ke-3</th>
                            <th>Vaksin</th>
                            <th>Hobi</th>
                            <th>Nomor HP darurat</th>
                            <th>Pemilik kontak darurat</th>
                            <th>Hubungan kontak darurat</th>
                            <th>Tinggi Badan</th>
                            <th>Berat Badan</th>
                            <th>Surat Lamaran</th>
                            <th>CV</th>
                            <th>KTP</th>
                            <th>KTP Status</th>
                            @if($lowongan->status_sim_b2 == 1)
                            <th>SIM B2</th>
                            <th>SIM B2 Status</th>
                            @endif
                            @if($lowongan->status_sio == 1)
                            <th>SIO</th>
                            <th>Status SIO</th>
                            @endif
                            <th>KK</th>
                            <th>Ijazah</th>
                            <th>SKCK</th>
                            <th>SKCK Status</th>
                            <th>AK1</th>
                            <th>Vaksin</th>
                            <th>NPWP</th>
                            <th>Pas Foto</th>
                            <th>Sertipikat</th>
                            <th>Status Sertipikat</th>
                            <th>Rekomendasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lamarans as $data)
                        <tr>
                            <td>{{ ++$no }}</td>
                            <td>
                                @if($data->biodata->user->suratPeringatan->isNotEmpty())
                                <a data-toggle="modal" data-target="#modalSP{{ $data->id }}" class="text-link">
                                    Lihat
                                </a>
                                @elseif($data->biodata->getRiwayatInHris->isNotEmpty())
                                <a data-toggle="modal" data-target="#modalResign{{ $data->biodata->no_ktp }}" class="text-link">
                                    Lihat
                                </a>
                                @endif
                            </td>
                            <td><input type="checkbox" name="selected_ids[]" value="{{ $data->id }}"></td>
                            <td>{{ $data->status_proses }}</td>
                            <td>{{ $data->biodata->user->name }}</td>
                            <td>{{ $data->biodata->no_ktp }}</td>
                            <td>{{ $data->biodata->no_kk }}</td>
                            <td>{{ $data->biodata->user->email }}</td>

                            @if($data->biodata->user->status_pelamar)
                            <td class="bg-warning">{{ strtoupper($data->biodata->user->status_pelamar) }}</td>
                            @else
                            <td>{{ strtoupper($data->biodata->user->status_pelamar ?? '---') }}</td>
                            @endif

                            @php
                            $tglResignRaw = $data->biodata->user->tanggal_resign ?? null;
                            $threshold = \Carbon\Carbon::create(2015, 4, 1);
                            $showValue = false;
                            if ($tglResignRaw) {
                            try {
                            $tglResignCarbon = \Carbon\Carbon::parse($tglResignRaw);
                            $showValue = $tglResignCarbon->gte($threshold);
                            } catch (\Exception $e) {
                            $showValue = false;
                            }
                            }
                            @endphp

                            @if($showValue)
                            <td class="bg-warning">{{ $tglResignCarbon->format('Y-m-d') }}</td>
                            @else
                            <td>---</td>
                            @endif

                            <td class="bg-warning">
                                @if($showValue)
                                @php
                                $sekarang = \Carbon\Carbon::now();
                                $diff = $tglResignCarbon->diff($sekarang);
                                @endphp
                                {{ $diff->y }} Tahun {{ $diff->m }} Bulan
                                @else
                                ---
                                @endif
                            </td>

                            @if($data->biodata->user->area_kerja)
                            <td class="bg-warning">{{ $data->biodata->user->area_kerja }}</td>
                            @else
                            <td>{{ $data->biodata->user->area_kerja ?? '---' }}</td>
                            @endif

                            @if($data->biodata->user->ket_resign)
                            <td class="bg-warning">{{ $data->biodata->user->ket_resign }}</td>
                            @else
                            <td>{{ $data->biodata->user->ket_resign ?? '---' }}</td>
                            @endif

                            <td>{{ $data->biodata->jenis_kelamin == 'M 男' ? 'Laki-Laki' : 'Perempuan' }}</td>
                            <td>{{ $data->biodata->tempat_lahir }}</td>
                            <td>{{ $data->biodata->tanggal_lahir }}</td>
                            <td>{{ hitungUmur($data->biodata->tanggal_lahir) }}</td>
                            <td>{{ $data->biodata->status_pernikahan }}</td>
                            <td>
                                {{ is_null($data->biodata->jumlah_anak) || $data->biodata->jumlah_anak == 0 
                                    ? 'TK' 
                                    : 'K' . $data->biodata->jumlah_anak }}
                            </td>
                            <td>{{ $data->biodata->rt }}/{{ $data->biodata->rt }}</td>
                            <td>{{ $data->biodata->kode_pos }}</td>
                            <td>{{ $data->biodata->getProvinsi->provinsi ?? '-' }}</td>
                            <td>{{ $data->biodata->getKabupaten->kabupaten ?? '-'}}</td>
                            <td>{{ $data->biodata->getKecamatan->kecamatan ?? '-' }}</td>
                            <td>{{ $data->biodata->getKelurahan->kelurahan ?? '-'}}</td>
                            <td>{{ $data->biodata->alamat }}</td>
                            <td>{{ $data->biodata->no_telp }}</td>
                            <td>{{ $data->biodata->no_npwp }}</td>
                            <td>{{ $data->biodata->golongan_darah }}</td>
                            <td>{{ $data->biodata->nama_instansi }}</td>
                            <td>{{ $data->biodata->pendidikan_terakhir }}</td>
                            <td>{{ $data->biodata->jurusan }}</td>
                            <td>{{ $data->biodata->tahun_lulus }}</td>
                            <td>{{ $data->biodata->agama }}</td>
                            <td>{{ $data->biodata->nama_ibu }}</td>
                            <td>{{ $data->biodata->nama_ayah }}</td>
                            <td>{{ $data->biodata->nama_pasangan }}</td>
                            <td>{{ $data->biodata->tanggal_nikah }}</td>
                            <td>{{ $data->biodata->nama_anak_1 }}</td>
                            <td>{{ $data->biodata->nama_anak_2 }}</td>
                            <td>{{ $data->biodata->nama_anak_3 }}</td>
                            <td>{{ $data->biodata->vaksin }}</td>
                            <td>{{ $data->biodata->hobi }}</td>
                            <td>{{ $data->biodata->no_telepon_darurat }}</td>
                            <td>{{ $data->biodata->nama_kontak_darurat }}</td>
                            <td>{{ $data->biodata->status_hubungan }}</td>
                            <td>{{ $data->biodata->tinggi_badan  }}</td>
                            <td>{{ $data->biodata->berat_badan  }}</td>

                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->surat_lamaran) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->surat_lamaran) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->cv) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->cv) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->ktp) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->ktp) }}
                                </a>
                            </td>
                            <td class="editable"
                                data-id="{{ $data->biodata_id }}"
                                data-model="biodata"
                                data-field="status_ktp">
                                {{ $data->biodata->status_ktp }}
                            </td>
                            @if($lowongan->status_sim_b2 == 1)
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->sim_b_2) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->sim_b_2) }}
                                </a>
                            </td>
                            <td class="editable"
                                data-id="{{ $data->biodata_id }}"
                                data-model="biodata"
                                data-field="status_sim_b2">
                                {{ $data->biodata->status_sim_b2 }}
                            </td>
                            @endif
                            @if($lowongan->status_sio == 1)
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->sio) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->sio) }}
                                </a>
                            </td>
                            <td class="editable"
                                data-id="{{ $data->biodata_id }}"
                                data-model="biodata"
                                data-field="status_sio">
                                {{ $data->biodata->status_sio }}
                            </td>
                            @endif
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->kartu_keluarga) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->kartu_keluarga) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->ijazah) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->ijazah) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->skck) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->skck) }}
                                </a>
                            </td>
                            <td class="editable"
                                data-id="{{ $data->biodata_id }}"
                                data-model="biodata"
                                data-field="status_skck">
                                {{ $data->biodata->status_skck }}
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->ak1) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->ak1) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->sertifikat_vaksin) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->sertifikat_vaksin) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->npwp) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->npwp) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->pas_foto) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->pas_foto) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->sertifikat_pendukung) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->sertifikat_pendukung) }}
                                </a>
                            </td>
                            <td class="editable"
                                data-id="{{ $data->biodata_id }}"
                                data-model="biodata"
                                data-field="status_sertifikat">
                                {{ $data->biodata->status_sertifikat }}
                            </td>
                            <td class="editable"
                                data-id="{{ $data->id }}"
                                data-model="lamaran"
                                data-field="rekomendasi">
                                {{ $data->rekomendasi }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="accordion" id="accordionStatusProses">
        <div class="card shadow border-primary mb-3">
            <div class="card-header" id="headingStatus" style="cursor: pointer;" data-toggle="collapse" data-target="#collapseStatus" aria-expanded="true" aria-controls="collapseStatus">
                <h6 class="mb-0 d-flex justify-content-between align-items-center">
                    <span>Form Update Status Lamaran</span>
                </h6>
            </div>
            <div id="collapseStatus" class="collapse show" aria-labelledby="headingStatus" data-parent="#accordionStatusProses">
                <div class="card-body">
                    @csrf
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-2">
                                <label for="pilih-status">Pilih Status Tahapan <span class="text-danger">*</span></label>
                                <select name="status_proses" class="form-control form-control-sm" id="pilih-status" required>
                                    <option value="">-- Pilih --</option>
                                    <optgroup label="Tahapan Proses">
                                        <option value="Verifikasi Online">Verifikasi Online</option>
                                        <option value="Verifikasi Berkas">Verifikasi Berkas</option>
                                        <option value="Tes Kesehatan">Tes Kesehatan</option>
                                        <option value="Tes Lapangan">Tes Lapangan</option>
                                        <option value="Medical Check-Up">Medical Check-Up (MCU)</option>
                                        <option value="Induksi Safety">Induksi Safety</option>
                                        <option value="Tanda Tangan Kontrak">Tanda Tangan Kontrak</option>
                                        <option value="Aktif Bekerja">Aktif Bekerja</option>
                                    </optgroup>
                                    <optgroup label="Tidak Lolos">
                                        <option value="Belum Sesuai Kriteria">Belum Sesuai Kriteria</option>
                                        <option value="Tidak Lolos Verifikasi Online">Tidak Lolos Verifikasi Online</option>
                                        <option value="Tidak Lolos Verifikasi Berkas">Tidak Lolos Verifikasi Berkas</option>
                                        <option value="Tidak Lolos Tes Kesehatan">Tidak Lolos Tes Kesehatan</option>
                                        <option value="Tidak Lolos Tes Lapangan">Tidak Lolos Tes Lapangan</option>
                                        <option value="Tidak Lolos Medical Check-Up">Tidak Lolos Medical Check-Up (MCU)</option>
                                        <option value="Tidak Lolos Induksi Safety">Tidak Lolos Induksi Safety</option>
                                        <option value="Tidak Tanda Tangan Kontrak">Tidak Tanda Tangan Kontrak</option>
                                    </optgroup>
                                    <optgroup label="Kandidat Potensial">
                                        <option value="Kandidat Potensial">Kandidat Potensial</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="mb-2">
                                <label for="tanggal-proses">Tanggal Proses <span class="text-danger">*</span></label>
                                <input class="form-control form-control-sm" type="date" name="tanggal_proses" id="tanggal-proses" required>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="mb-2">
                                <label for="jam">Jam <span class="text-danger">*</span></label>
                                <input class="form-control form-control-sm" type="time" name="jam" id="jam" required>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="mb-2">
                                <label for="tempat">Tempat <span class="text-danger">*</span></label>
                                <input class="form-control form-control-sm" type="text" name="tempat" id="tempat" required>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="mb-3">
                                <label>Kirim Email? <span class="text-danger">*</span></label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="blast_email" id="blast-email-yes" value="iya" required>
                                        <label class="form-check-label" for="blast-email-yes">Iya</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="blast_email" id="blast-email-no" value="tidak" required>
                                        <label class="form-check-label" for="blast-email-no">Tidak</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="inputEmail">Pesan <span class="text-danger">*</span></label>
                            <div id="quill-editor" class="mb-3" style="height: 300px;"></div>
                            <textarea rows="3" class="mb-3 d-none" name="pesanEmail" id="quill-editor-area"></textarea>
                        </div>

                    </div>

                    <button type="submit" class="btn btn-primary mt-1">Perbarui proses lamaran</button>

                </div>
            </div>
        </div>
    </div>
</form>

@foreach($lamarans as $data)

<div class="modal fade" id="modalResign{{$data->biodata->no_ktp}}" tabindex="-1" role="dialog" aria-labelledby="modalSPLabel{{$data->biodata->no_ktp}}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold" id="modalSPLabel{{ $data->id }}">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Riwayat Bekerja - {{ $data->biodata->user->name }}
                </h5>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body bg-light">
                {{-- Section: Riwayat Resign --}}
                <h6 class="text-primary font-weight-bold mb-3">📄 Riwayat Resign</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered table-hover">
                        <thead class="thead-dark text-center">
                            <tr>
                                <th>Nama</th>
                                <th>Tanggal</th>
                                <th>Rentang</th>
                                <th>Kategori</th>
                                <th>Alasan</th>
                                <th>Posisi</th>
                                <th>Area</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data->biodata->getRiwayatInHris as $riwayat)
                            <tr>
                                <td>{{ $riwayat->nama_karyawan }}</td>
                                @php
                                $tglResignRaw = $riwayat->tgl_resign ?? null;
                                $threshold = \Carbon\Carbon::create(2015, 4, 1);
                                $showValue = false;
                                if ($tglResignRaw) {
                                try {
                                $tglResignCarbon = \Carbon\Carbon::parse($tglResignRaw);
                                $showValue = $tglResignCarbon->gte($threshold);
                                } catch (\Exception $e) {
                                $showValue = false;
                                }
                                }
                                @endphp

                                <td class="text-center">
                                    @if($showValue)
                                    {{ tanggalIndo($tglResignCarbon) }}
                                    @else
                                    -
                                    @endif
                                </td>
                                <td>
                                    @if($showValue)
                                    @php
                                    $sekarang = \Carbon\Carbon::now();
                                    $diff = $tglResignCarbon->diff($sekarang);
                                    @endphp
                                    {{ $diff->y }} Tahun {{ $diff->m }} Bulan
                                    @else
                                    -
                                    @endif
                                </td>
                                <td>{{ $riwayat->status_resign }}</td>
                                <td>{{ $riwayat->alasan_resign }}</td>
                                <td>{{ $riwayat->posisi }}</td>
                                <td>{{ $riwayat->area_kerja }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Tidak ada data resign.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

@if($data->biodata->user->suratPeringatan->isNotEmpty())
@php
$orderedSP = $data->biodata->user->suratPeringatan->sortBy(function ($item) {
$order = ['SP1' => 1, 'SP2' => 2, 'SP3' => 3];
return $order[$item->level_sp] ?? 99;
});
@endphp

<div class="modal fade" id="modalSP{{ $data->id }}" tabindex="-1" role="dialog" aria-labelledby="modalSPLabel{{ $data->id }}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold" id="modalSPLabel{{ $data->id }}">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Riwayat Bekerja - {{ $data->biodata->user->name }}
                </h5>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body bg-light">
                {{-- Section: Riwayat Resign --}}
                <h6 class="text-primary font-weight-bold mb-3">📄 Riwayat Resign</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered table-hover">
                        <thead class="thead-dark text-center">
                            <tr>
                                <th>Nama</th>
                                <th>Tanggal</th>
                                <th>Rentang</th>
                                <th>Kategori</th>
                                <th>Alasan</th>
                                <th>Posisi</th>
                                <th>Area</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data->biodata->getRiwayatInHris as $riwayat)
                            <tr>
                                <td>{{ $riwayat->nama_karyawan }}</td>
                                @php
                                $tglResignRaw = $riwayat->tgl_resign ?? null;
                                $threshold = \Carbon\Carbon::create(2015, 4, 1);
                                $showValue = false;
                                if ($tglResignRaw) {
                                try {
                                $tglResignCarbon = \Carbon\Carbon::parse($tglResignRaw);
                                $showValue = $tglResignCarbon->gte($threshold);
                                } catch (\Exception $e) {
                                $showValue = false;
                                }
                                }
                                @endphp
                                <td class="text-center">
                                    @if($showValue)
                                    {{ tanggalIndo($tglResignCarbon) }}
                                    @else
                                    ---
                                    @endif
                                </td>
                                <td>
                                    @if($showValue)
                                    @php
                                    $sekarang = \Carbon\Carbon::now();
                                    $diff = $tglResignCarbon->diff($sekarang);
                                    @endphp
                                    {{ $diff->y }} Tahun {{ $diff->m }} Bulan
                                    @else
                                    ---
                                    @endif
                                </td>
                                <td>{{ $riwayat->status_resign }}</td>
                                <td>{{ $riwayat->alasan_resign }}</td>
                                <td>{{ $riwayat->posisi }}</td>
                                <td>{{ $riwayat->area_kerja }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Tidak ada data resign.</td>
                            </tr>
                            @endforelse

                        </tbody>
                    </table>
                </div>

                {{-- Section: Surat Peringatan --}}
                <h6 class="text-danger font-weight-bold mb-3">⚠️ Surat Peringatan</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover">
                        <thead class="thead-dark text-center">
                            <tr>
                                <th style="width: 15%;">Level</th>
                                <th style="width: 60%;">Keterangan</th>
                                <th style="width: 25%;">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orderedSP as $sp)
                            <tr>
                                <td class="text-center">
                                    <span class="badge badge-pill 
                                        {{ $sp->level_sp == 'SP1' ? 'badge-dark' : 
                                           ($sp->level_sp == 'SP2' ? 'badge-warning text-dark' : 
                                           'badge-danger') }}">
                                        {{ $sp->level_sp }}
                                    </span>
                                </td>
                                <td>{{ $sp->ket_sp }}</td>
                                <td class="text-center">{{ tanggalIndo($sp->tanggal_mulai_sp) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endif
@endforeach

@push('scripts')
<!-- Page level plugins -->
<script src="{{ asset('admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

<!-- Buttons + Export + ColVis -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Select 2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Script non-aktifkan -->
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const statusSelect = document.getElementById('pilih-status');
        const tanggal = document.getElementById('tanggal-proses');
        const jam = document.getElementById('jam');
        const tempat = document.getElementById('tempat');
        const pesanArea = document.getElementById('quill-editor-area');
        const blastEmailYes = document.getElementById('blast-email-yes');
        const blastEmailNo = document.getElementById('blast-email-no');

        const fields = [tanggal, jam, tempat, pesanArea, blastEmailYes, blastEmailNo];

        statusSelect.addEventListener('change', function() {
            if (this.value === "Kandidat Potensial") {

                // Hilangkan required
                fields.forEach(field => field.removeAttribute('required'));

                // Optional: kosongkan isinya
                fields.forEach(field => field.value = "");

            } else {

                // Kembalikan menjadi required
                tanggal.setAttribute('required', true);
                jam.setAttribute('required', true);
                tempat.setAttribute('required', true);
                blastEmailYes.setAttribute('required', true);
                blastEmailNo.setAttribute('required', true);

            }
        });

    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('quill-editor-area')) {
            var editor = new Quill('#quill-editor', {
                theme: 'snow'
            });
            var quillEditor = document.getElementById('quill-editor-area');
            editor.on('text-change', function() {
                quillEditor.value = editor.root.innerHTML;
            });

            quillEditor.addEventListener('input', function() {
                editor.root.innerHTML = quillEditor.value;
            });
        }
    });
</script>

<script>
    $(document).ready(function() {
        $('.multiple-pendidikan').select2({
            placeholder: "Pilih Pendidikan Terakhir"
        });

        $('.multiple-status').select2({
            placeholder: "Pilih Status Ex Karyawan"
        });

        $('.multiple-status-pelamar').select2({
            placeholder: "Pilih Status Tahapan Pelamar"
        });
    });
</script>

<script>
    $(document).ready(function() {
        // Inisialisasi DataTable
        const table = $('#dataTable').DataTable({
            scrollX: true,
            responsive: false,
            autoWidth: false,
            fixedHeader: true,
            dom: 'lBfrtip',
            lengthMenu: [
                [10, 50, 100, -1], // Nilai jumlah baris
                [10, 50, 100, 'Semua'] // Label yang tampil di dropdown
            ],
            buttons: [{
                    extend: 'colvis',
                    text: '<i class="fas fa-eye"></i> Visibility',
                    className: 'btn btn-outline-primary btn-sm'
                },
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i> Export Excel',
                    className: 'btn btn-outline-success btn-sm',
                    title: 'Data Pelamar',
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                let $node = $(node);
                                if ($node.find('input').length > 0) {
                                    return $node.find('input').val();
                                }
                                if ($node.find('select').length > 0) {
                                    return $node.find('select option:selected').text();
                                }
                                if ($node.find('a').length > 0) {
                                    return $node.find('a').attr('href');
                                }
                                return $node.text().trim();
                            }
                        }
                    },
                    customize: function(xlsx) {
                        let sheet = xlsx.xl.worksheets['sheet1.xml'];

                        const textColumns = ['F', 'G'];

                        $(textColumns).each(function(i, col) {
                            $('c[r^="' + col + '"]', sheet).each(function() {
                                let cell = $(this);
                                let val = cell.find('v').text();

                                // Paksa jadi string
                                cell.attr('t', 'str');

                                // Jika Excel menghapus leading zero, kembali tambahkan val sebagai string
                                cell.find('v').text(val);
                            });
                        });

                        const columnsToLink = ['P', 'Q', 'R', 'T', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC'];

                        $(columnsToLink).each(function(index, colRef) {
                            $('c[r^="' + colRef + '"]', sheet).each(function() {
                                let cell = $(this);
                                let value = cell.text();
                                if (value.startsWith('http')) {
                                    cell.attr('t', 'str');
                                    cell.empty().append(`<f>HYPERLINK("${value}", "${value}")</f>`);
                                }
                            });
                        });
                    }
                }
            ],
            fixedColumns: {
                leftColumns: 6
            }
        });

        // Editable logic
        const debounceTimers = {};
        let originalContent = '';

        // Simpan nilai awal saat mulai edit
        $('#dataTable').on('focus', 'td.editable', function() {
            originalContent = $(this).text().trim();
        });

        // Aktifkan editable saat klik
        $('#dataTable').on('click', 'td.editable', function() {
            let $td = $(this);
            if (!$td.is('[contenteditable="true"]')) {
                $td.attr('contenteditable', 'true').focus();
            }
        });

        // Saat selesai edit
        $('#dataTable').on('blur', 'td.editable', function() {
            let $td = $(this);
            $td.attr('contenteditable', 'false');

            const newValue = $td.text().trim();

            // ⛔ Jika tidak ada perubahan, tidak perlu update
            if (newValue === originalContent) {
                return;
            }

            const id = $td.data('id');
            const field = $td.data('field');
            const model = $td.data('model');
            const key = `${model}-${id}-${field}`;

            if (debounceTimers[key]) {
                clearTimeout(debounceTimers[key]);
            }

            debounceTimers[key] = setTimeout(() => {
                $.ajax({
                    url: '{{ route("data.autoUpdate") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id,
                        model: model,
                        field: field,
                        value: newValue
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message || 'Data berhasil diperbarui.',
                            timer: 2500,
                            toast: true,
                            showConfirmButton: false,
                            position: 'bottom-end'
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message || 'Terjadi kesalahan.',
                            timer: 3000,
                            toast: true,
                            showConfirmButton: false,
                            position: 'bottom-end'
                        });
                    }
                });
            }, 1000);
        });

        // Tekan Enter untuk simpan cepat
        $('#dataTable').on('keydown', 'td.editable', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $(this).blur();
            }
        });


    });
</script>

<script>
    document.getElementById('checkAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });

    document.querySelectorAll('td.editable').forEach(td => {
        td.addEventListener('focus', () => td.classList.add('editing'));
        td.addEventListener('blur', () => td.classList.remove('editing'));
    });
</script>

<script>
    $('#formStatusProses').on('shown.bs.collapse', function() {
        $('[data-target="#formStatusProses"]').text('Tutup Form Update Status Lamaran');
    });

    $('#formStatusProses').on('hidden.bs.collapse', function() {
        $('[data-target="#formStatusProses"]').text('Buka Form Update Status Lamaran');
    });
</script>
@endpush

@endsection