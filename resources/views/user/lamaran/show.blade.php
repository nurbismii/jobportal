@extends('layouts.app')

@section('content')

@push('styles')
<style>
    .timeline {
        position: relative;
        padding-left: 60px;
        counter-reset: step;
    }

    .timeline::before {
        content: '';
        position: absolute;
        top: 0;
        left: 28px;
        width: 4px;
        height: 100%;
        background-color: #0d6efd;
    }

    .timeline-item {
        position: relative;
        display: flex;
        align-items: flex-start;
        margin-bottom: 40px;
    }

    .timeline-item::before {
        content: counter(step);
        counter-increment: step;
        position: absolute;
        left: 10px;
        top: 0;
        width: 36px;
        height: 36px;
        background-color: #fff;
        border: 3px solid #0d6efd;
        border-radius: 50%;
        text-align: center;
        line-height: 30px;
        font-weight: 600;
        color: #0d6efd;
        font-size: 1rem;
        z-index: 1;
    }

    .timeline-content {
        margin-left: 50px;
    }

    .timeline-title {
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 4px;
        color: #212529;
    }

    .timeline-date {
        font-size: 0.875rem;
        color: #6c757d;
    }
</style>
@endpush

<!-- Header -->
<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5">
        <h4 class="text-white display-4 mb-4">Detail Lamaran</h4>
    </div>
</div>

<!-- Content -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- Card Lowongan -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <h4 class="fw-bold mb-0">Detail Lowongan</h4>
                        <a href="{{ route('lamaran.index') }}" class="ms-auto btn btn-link text-decoration-none">Tutup</a>

                    </div>
                    <p class="mb-1"><strong>Posisi:</strong> {{ $lamaran->lowongan->nama_lowongan }}</p>
                    <p class="mb-1"><strong>Status Lamaran:</strong>
                        @if ($lamaran->status_lamaran == '1')
                        <span class="badge bg-success">Aktif</span>
                        @else
                        <span class="badge bg-secondary">Tidak Aktif</span>
                        @endif
                    </p>
                </div>
            </div>

            <h4 class="fw-bold py-2">Status Lamaran</h4>

            <!-- timeline item 1 -->
            @forelse($riwayat_proses as $proses)
            <div class="row">
                <!-- timeline item 1 left dot -->
                <div class="col-auto text-center flex-column d-none d-sm-flex">
                    <div class="row h-50">
                        <div class="col">&nbsp;</div>
                        <div class="col">&nbsp;</div>
                    </div>
                    <h5 class="m-2">
                        <span class="badge bg-primary border shadow-sm">&nbsp;</span>
                    </h5>
                    <div class="row h-50">
                        <div class="col border-end border-primary">&nbsp;</div>
                        <div class="col">&nbsp;</div>
                    </div>
                </div>
                <!-- timeline item 1 event content -->
                <div class="col py-2">
                    <div class="card shadow">
                        <div class="card-body">
                            <div class="float-right text-muted">{{ tanggalIndo($proses->tanggal_proses) }}</div>
                            <h4 class="card-title">{{ $proses->status_proses }}</h4>
                            <p class="card-text">Welcome to the campus, introduction and get started with the tour.</p>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <p class="text-muted">Belum ada riwayat proses.</p>
            @endforelse
            <!--/row-->
        </div>
    </div>
</div>

@endsection