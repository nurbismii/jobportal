@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<style>
    .card:hover {
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        transform: scale(1.01);
        transition: 0.3s;
    }

    nav .pagination {
        gap: 6px;
    }

    .input-group input {
        font-size: 14px;
    }
</style>
@endpush

<div class="container-fluid">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">

        <!-- Title -->
        <h1 class="h3 text-gray-800 mb-2 mb-md-0">Lowongan Pekerjaan</h1>

        <div class="d-flex flex-column flex-md-row">

            <a data-toggle="modal" data-target="#import-status"
                class="btn btn-success btn-sm btn-icon-split mb-2 mb-md-0 mr-md-2">
                <span class="icon text-white-50">
                    <i class="fas fa-file-excel"></i>
                </span>
                <span class="text">Import Status</span>
            </a>

            <a href="{{ route('lowongan.create') }}"
                class="btn btn-primary btn-sm btn-icon-split">
                <span class="icon text-white-50">
                    <i class="fas fa-plus"></i>
                </span>
                <span class="text">Lowongan</span>
            </a>

        </div>

    </div>

    <div class="row mb-3">
        <div class="col-12">

            <!-- Search Form -->
            <form method="GET" action="{{ route('lowongan.index') }}">
                <div class="input-group mb-3">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari lowongan...">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>

            <!-- Lowongan Cards -->
            <div class="row">
                @forelse($lowongans as $data)
                <div class="col-12 col-sm-6 col-lg-4 mb-3">
                    <div class="card h-100 shadow border-left-primary">
                        <div class="card-body d-flex flex-column">

                            <h5 class="card-title font-weight-bold text-primary mb-2">
                                {{ $data->nama_lowongan }}
                            </h5>

                            <p class="mb-2 small text-muted">
                                Pelamar: <strong>{{$data->lamarans_count}}</strong>
                            </p>

                            <p class="mb-1 small">
                                <strong>SIM B2 :</strong>
                                {{ $data->status_sim_b2 == 1 ? 'Dibutuhkan' : 'Tidak' }}
                            </p>

                            <p class="mb-1 small">
                                <strong>Mulai :</strong> {{ tanggalIndo($data->tanggal_mulai) }}
                            </p>

                            <p class="mb-2 small">
                                <strong>Berakhir :</strong> {{ tanggalIndo($data->tanggal_berakhir) }}
                            </p>

                            <div class="mb-3">
                                @if($data->status_lowongan == 'Aktif')
                                <span class="badge badge-success">
                                    <i class="fa fa-check-circle"></i> Aktif
                                </span>
                                @else
                                <span class="badge badge-danger">
                                    <i class="fa fa-times-circle"></i> Nonaktif
                                </span>
                                @endif
                            </div>
                            <div class="mt-auto">
                                <a href="{{ route('directToLamaran', $data->id) }}" class="btn btn-secondary btn-sm btn-block mb-2">
                                    <i class="fas fa-list mr-1"></i> Pelamar
                                </a>
                                <div class="btn-group btn-group-sm btn-block" role="group">
                                    <a href="{{ route('lowongan.edit', $data->id) }}" class="btn btn-success">
                                        <i class="fas fa-pen"></i> Edit
                                    </a>
                                    <a href="{{ route('lowongan.destroy', $data->id) }}" class="btn btn-danger" data-confirm-delete="true">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="alert alert-info">Tidak ada lowongan ditemukan.</div>
                </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $lowongans->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="import-status" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">

            <!-- HEADER -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-file-excel me-2"></i> Import Data Excel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <form action="{{ route('import.status-lamaran') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="modal-body">

                    <div class="alert alert-info d-flex align-items-center">
                        <i class="fas fa-info-circle me-2 mr-2"></i>
                        Pastikan format excel sesuai template sistem.
                    </div>

                    <label class="fw-semibold mb-1">Pilih File Excel <span class="text-danger">*</span></label>
                    <div class="input-group mb-3">
                        <input type="file" name="file" id="fileExcel" class="form-control" accept=".xlsx, .xls" required>
                        <span class="input-group-text bg-success text-white">
                            <i class="fas fa-upload"></i>
                        </span>
                    </div>

                    <!-- Preview nama file -->
                    <small id="fileName" class="text-muted fst-italic">Belum ada file dipilih...</small>

                    <hr>
                </div>

                <!-- FOOTER -->
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Tutup
                    </button>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection