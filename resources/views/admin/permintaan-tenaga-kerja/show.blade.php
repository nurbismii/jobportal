@extends('layouts.app-pic')

@section('content-admin')
<div class="container-fluid">

    <div class="card shadow-sm rounded">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Detail Permintaan Tenaga Kerja (PTK)</h5>
            <span class="badge badge-{{ $permintaanTenagaKerja->status_ptk == 'Menunggu' ? 'warning' : 'success' }}">
                {{ $permintaanTenagaKerja->status_ptk }}
            </span>
        </div>
        <div class="card-body">

            {{-- Progress Bar --}}
            <div class="mb-4">
                <p class="mb-1"><strong>Progress Rekrutmen:</strong></p>
                <div class="progress" style="height: 20px;">
                    @php
                    $total = $permintaanTenagaKerja->jumlah_ptk;
                    $masuk = $permintaanTenagaKerja->jumlah_masuk;
                    $persen = $total > 0 ? round(($masuk / $total) * 100) : 0;
                    $warna = $persen < 50 ? 'bg-warning' : 'bg-success' ;
                        @endphp
                        <div class="progress-bar {{ $warna }}" role="progressbar" style="width: {{ $persen }}%;" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100">
                        {{ $persen }}%
                </div>
            </div>
        </div>

        {{-- Collapse Sections --}}
        <div id="accordion">
            <div class="card mb-2">
                <div class="card-header bg-primary text-white p-2">
                    <h6 class="mb-0">
                        <a href="#collapsePosisi" class="text-white d-block" data-toggle="collapse" aria-expanded="true" aria-controls="collapsePosisi">
                            <i class="fa fa-briefcase"></i> Informasi Posisi
                        </a>
                    </h6>
                </div>
                <div id="collapsePosisi" class="collapse show">
                    <div class="card-body">
                        <p><i class="fa fa-user"></i> <strong>Posisi:</strong> {{ $permintaanTenagaKerja->posisi }}</p>
                        <p><i class="fa fa-mars"></i> <strong>Jenis Kelamin:</strong> {{ $permintaanTenagaKerja->jenis_kelamin }}</p>
                        <p><i class="fa fa-hourglass-half"></i> <strong>Rentang Usia:</strong> {{ $permintaanTenagaKerja->rentang_usia }}</p>
                        <p><i class="fa fa-graduation-cap"></i> <strong>Pendidikan:</strong> {{ $permintaanTenagaKerja->background_pendidikan }}</p>
                        <p><i class="fa fa-users"></i> <strong>Jumlah PTK:</strong> {{ $permintaanTenagaKerja->jumlah_ptk }}</p>
                        <p><i class="fa fa-user-check"></i> <strong>Jumlah Masuk:</strong> {{ $permintaanTenagaKerja->jumlah_masuk }}</p>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header bg-primary text-white p-2">
                    <h6 class="mb-0">
                        <a href="#collapseTanggal" class="text-white d-block" data-toggle="collapse" aria-expanded="false" aria-controls="collapseTanggal">
                            <i class="fa fa-calendar"></i> Jadwal & Tanggal
                        </a>
                    </h6>
                </div>
                <div id="collapseTanggal" class="collapse">
                    <div class="card-body">
                        <p><i class="fa fa-calendar-plus"></i> <strong>Tanggal Pengajuan:</strong> {{ tanggalIndo($permintaanTenagaKerja->tanggal_pengajuan) }}</p>
                        <p><i class="fa fa-calendar-check"></i> <strong>Tanggal Terima:</strong> {{ tanggalIndo($permintaanTenagaKerja->tanggal_terima) }}</p>
                        <p><i class="fa fa-clock"></i> <strong>Dibuat Pada:</strong> {{ tanggalIndo(substr($permintaanTenagaKerja->created_at, 0, 10)) }}</p>
                        <p><i class="fa fa-history"></i> <strong>Diupdate Pada:</strong> {{ tanggalIndo(substr($permintaanTenagaKerja->updated_at, 0, 10)) }}</p>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header bg-primary text-white p-2">
                    <h6 class="mb-0">
                        <a href="#collapseDept" class="text-white d-block" data-toggle="collapse" aria-expanded="false" aria-controls="collapseDept">
                            <i class="fa fa-building"></i> Departemen & Divisi
                        </a>
                    </h6>
                </div>
                <div id="collapseDept" class="collapse">
                    <div class="card-body">
                        <p><i class="fa fa-industry"></i> <strong>Departemen:</strong> {{ $permintaanTenagaKerja->departemen->departemen }} ({{ $permintaanTenagaKerja->departemen->status_pengeluaran }})</p>
                        <p><i class="fa fa-sitemap"></i> <strong>Divisi:</strong> {{ $permintaanTenagaKerja->divisi->nama_divisi }}</p>
                        <p><i class="fa fa-user-tie"></i> <strong>Kepala Departemen:</strong> {{ $permintaanTenagaKerja->departemen->kepala_dept }}</p>
                        <p><i class="fa fa-phone"></i> <strong>Telp:</strong> {{ $permintaanTenagaKerja->departemen->no_telp_departemen }}</p>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header bg-primary text-white p-2">
                    <h6 class="mb-0">
                        <a href="#collapseKualifikasi" class="text-white d-block" data-toggle="collapse" aria-expanded="false" aria-controls="collapseKualifikasi">
                            <i class="fa fa-file-alt"></i> Kualifikasi PTK
                        </a>
                    </h6>
                </div>
                <div id="collapseKualifikasi" class="collapse">
                    <div class="card-body">
                        {!! $permintaanTenagaKerja->kualifikasi_ptk !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('permintaan-tenaga-kerja.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
</div>
</div>
@endsection