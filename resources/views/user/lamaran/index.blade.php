@extends('layouts.app')

@section('content')

@push('styles')
<style>
    .card-text {
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

@endpush

<!-- Header Start -->
<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5" style="max-width: 900px;">
        <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Lamaran</h4>
    </div>
</div>
<!-- Header End -->

<div class="container-fluid py-5 bg-light">
    <div class="container py-5">
        <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s">
            <h4 class="text-primary fw-semibold">Lamaran</h4>
            <h1 class="display-5 fw-bold mb-3">Riwayat Pelamaran</h1>
            <p class="text-muted mb-0">
                Berikut adalah daftar riwayat lamaran kerja kamu di perusahaan kami.
            </p>
        </div>
        <div class="row g-4 justify-content-center">
            @forelse($lamarans as $lamaran)
            <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between mb-2 text-muted small">
                            <div><i class="fa fa-building text-primary me-1"></i>PT VDNI</div>
                            <div><i class="fa fa-calendar-alt text-primary me-1"></i>{{ tanggalIndo(date('Y-m-d', strtotime($lamaran->created_at))) }}</div>
                        </div>
                        <h3 class="card-title text-dark fw-bold mb-0">{{ $lamaran->lowongan->nama_lowongan }}</h3>
                        <p class="card-text text-muted flex-grow-1" style="max-height: 90px; overflow: hidden;">
                            {!! $lamaran->lowongan->kualifikasi !!}
                        </p>
                        <div class="mb-1">
                            <p class="mb-1 small">
                                <strong>Status Lamaran : </strong>
                                @if ($lamaran->status_lamaran == '1')
                                <span class="badge bg-success"><i class="fa fa-check-circle me-1"></i>Dalam Proses Rekrutmen</span>
                                @else
                                <span class="badge bg-dark"><i class="fa fa-times-circle me-1"></i>Rekrutmen Selesai</span>
                                @endif
                            </p>
                            <!-- <p class="mb-0 small">
                                <strong>Status Proses : </strong>
                                <span class="fw-bold">{{ $lamaran->status_proses }}</span>
                            </p> -->
                        </div>
                        <div>
                            <a href="{{ route('lamaran.show', $lamaran->id) }}" class="btn p-0 mt-3 float-left">Baca Detail <i class="fa fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="text-center p-5 my-4 border rounded-3 shadow-sm bg-light">
                    <i class="fa fa-file-alt fa-3x text-primary mb-3"></i>
                    <h4 class="fw-bold mb-2">Belum ada lamaran yang diajukan</h4>
                    <p class="text-muted mb-3">Kamu belum mengajukan lamaran kerja. Silakan pilih lowongan yang tersedia.</p>
                    <a href="{{ route('lowongan-kerja.index') }}" class="btn btn-primary rounded-pill px-4">Lihat Lowongan</a>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection