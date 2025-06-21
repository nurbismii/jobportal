@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css">

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
        margin: 0 2px;
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

<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
    <h2 class="m-0 font-weight-bold text-primary">{{ $lowongan->nama_lowongan }}</h2>
    <a href="{{ route('lowongan.index') }}" class="btn btn-danger btn-sm btn-icon-split">
        <span class="icon text-white-50">
            <i class="fas fa-arrow-left"></i>
        </span>
        <span class="text">Kembali</span>
    </a>
</div>

<!-- Filter data pelamar -->
<div class="card shadow mb-3">
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-3">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Filter Status --</option>
                    <option value="Tidak dapat lanjut ke tahap selanjutya" {{ request('status') == 'Tidak dapat lanjut ke tahap selanjutya' ? 'selected' : '' }}>Tidak dapat lanjut ke tahap selanjutya</option>
                    <option value="Verifikasi Online" {{ request('status') == 'Verifikasi Online' ? 'selected' : '' }}>Verifikasi Online</option>
                    <option value="Verifikasi Berkas" {{ request('status') == 'Verifikasi Berkas' ? 'selected' : '' }}>Verifikasi Berkas</option>
                    <option value="Tes Kesehatan" {{ request('status') == 'Tes Kesehatan' ? 'selected' : '' }}>Tes Kesehatan</option>
                    <option value="Tes Lapangan" {{ request('status') == 'Tes Lapangan' ? 'selected' : '' }}>Tes Lapangan</option>
                    <option value="Medical Check-Up" {{ request('status') == 'Medical Check-Up' ? 'selected' : '' }}>Medical Check-Up</option>
                    <option value="Induksi Safety" {{ request('status') == 'Induksi Safety' ? 'selected' : '' }}>Induksi Safety</option>
                    <option value="Tanda Tangan Kontrak" {{ request('status') == 'Tanda Tangan Kontrak' ? 'selected' : '' }}>Tanda Tangan Kontrak</option>
                </select>
            </div>

            <div class="col-md-3">
                <input type="number" name="umur_min" class="form-control" placeholder="Umur Minimal" value="{{ request('umur_min') }}" onchange="this.form.submit()">
            </div>
            <div class="col-md-3">
                <input type="number" name="umur_max" class="form-control" placeholder="Umur Maksimal" value="{{ request('umur_max') }}" onchange="this.form.submit()">
            </div>

            <div class="col-md-3">
                <select name="pendidikan" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Filter Pendidikan --</option>
                    <option value="SMA" {{ request('pendidikan') == 'SMA' ? 'selected' : '' }}>SMA</option>
                    <option value="D3" {{ request('pendidikan') == 'D3' ? 'selected' : '' }}>D3</option>
                    <option value="S1" {{ request('pendidikan') == 'S1' ? 'selected' : '' }}>S1</option>
                </select>
            </div>
        </form>
    </div>
</div>

<form action="{{ route('lamaran.updateStatusMassal') }}" method="POST">
    <div class="card shadow mb-3">
        <div class="card-body">
            @csrf
            <div class="mb-3">
                <select name="status_proses" class="form-control" required>
                    <option value="">-- Pilih Status Baru --</option>
                    <option value="Tidak dapat lanjut ke tahap selanjutya">Tidak dapat lanjut ke tahap selanjutya</option>
                    <option value="Verifikasi Online">Verifikasi Online</option>
                    <option value="Verifikasi Berkas">Verifikasi Berkas</option>
                    <option value="Tes Kesehatan">Tes Kesehatan</option>
                    <option value="Tes Lapangan">Tes Lapangan</option>
                    <option value="Medical Check-Up">Medical Check-Up (MCU)</option>
                    <option value="Induksi Safety">Induksi Safety</option>
                    <option value="Tanda Tangan Kontrak">Tanda Tangan Kontrak</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mb-3">Perbarui proses lamaran</button>
        </div>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Data Pelamar</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover nowrap" id="dataTable" width="100%" cellspacing="0">
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
                            <th class="bg-warning">Ex Area</th>
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
                            <th>SIM B2</th>
                            <th>SIM B2 Status</th>
                            <th>KK</th>
                            <th>Ijazah</th>
                            <th>SKCK</th>
                            <th>SKCK Status</th>
                            <th>AK1</th>
                            <th>Vaksin</th>
                            <th>NPWP</th>
                            <th>Pas Foto</th>
                            <th>Pendukung</th>
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
                                @else
                                <span class="text-muted">-</span>
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
                            @if($data->biodata->user->area_kerja)
                            <td class="bg-warning">{{ $data->biodata->user->area_kerja }}</td>
                            @else
                            <td>{{ $data->biodata->user->area_kerja ?? '---' }}</td>
                            @endif
                            <td>{{ $data->biodata->jenis_kelamin == 'L' ? 'Laki-Laki' : 'Perempuan' }}</td>
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
                            <td>{{ $data->biodata->getProvinsi->provinsi }}</td>
                            <td>{{ $data->biodata->getKabupaten->kabupaten }}</td>
                            <td>{{ $data->biodata->getKecamatan->kecamatan }}</td>
                            <td>{{ $data->biodata->getKelurahan->kelurahan }}</td>
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
                            <td>{{ $data->biodata->vaksinasi_covid }}</td>
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
</form>

@foreach($lamarans as $data)
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
            <div class="modal-header bg-warning text-dark">
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
                                <th>Tanggal Resign</th>
                                <th>Alasan Resign</th>
                                <th>Posisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data->biodata->getRiwayatInHris as $riwayat)
                            <tr>
                                <td>{{ $riwayat->nama_karyawan }}</td>
                                <td class="text-center">{{ tanggalIndo($riwayat->tgl_resign) }}</td>
                                <td>{{ $riwayat->alasan_resign }}</td>
                                <td>{{ $riwayat->posisi }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Tidak ada data resign.</td>
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
@endpush

@endsection