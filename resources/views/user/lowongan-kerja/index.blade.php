@extends('layouts.app')

@section('content')

@push('styles')
<style>
    .service-item:hover {
        background-color: #007bff;
        color: #fff;
    }

    .service-item:hover *:not(.btn):not(.btn *) {
        color: #fff !important;
    }

    .service-item .btn {
        background-color: #fff;
        color: #007bff;
        border: 2px solid #007bff;
        transition: all 0.3s ease;
    }

    .service-item .btn:hover {
        background-color: #007bff;
        color: #fff;
    }
</style>
@endpush

<!-- Header Start -->
<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5" style="max-width: 900px;">
        <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Lowongan Kerja</h4>
    </div>
</div>
<!-- Header End -->

<!-- Lowongan Kerja Start -->
<div class="container-fluid service py-5">
    <div class="container py-5">
        <h1 class="display-4 mb-4">Informasi</h1>
        <h4 class="text-primary">Lowongan Tersedia</h4>

        <div class="row row-cols-1 row-cols-md-3 g-4">

            @php
            $shareUrl = route('lowongan-kerja.index');
            @endphp

            @forelse($lowongans as $lowongan)
            <div class="col">
                <div class="service-item h-100 d-flex flex-column">

                    <div class="service-content p-4 d-flex flex-column flex-grow-1">
                        <div class="service-content-inner flex-grow-1 d-flex flex-column justify-content-between">

                            <a href="{{ route('lowongan-kerja.show', $lowongan->id) }}"
                                class="d-inline-block h4 mb-0">
                                {{ $lowongan->nama_lowongan }}
                            </a>

                            <p class="mb-4">
                                {!! substr($lowongan->kualifikasi, 0, 409) !!}
                            </p>

                            <p class="fw-bold mb-1">Tanggal aktif</p>
                            <p class="mb-1">
                                {{ tanggalIndo($lowongan->tanggal_mulai) }} –
                                {{ tanggalIndo($lowongan->tanggal_berakhir) }}
                            </p>

                            <div>
                                <span class="badge {{ strtolower($lowongan->status_lowongan) == 'aktif' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $lowongan->status_lowongan }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-auto pt-3">
                                <a class="btn btn-primary btn-sm rounded-pill px-3"
                                    href="{{ route('lowongan-kerja.show', $lowongan->id) }}">
                                    Lihat
                                </a>
                                <a class="btn btn-primary btn-sm rounded-pill px-3"
                                    href="javascript:void(0)"
                                    onclick="copyToClipboard('{{ $shareUrl }}')">
                                    Bagikan
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="text-center p-5 my-4 border rounded-3 shadow-sm bg-light">
                    <i class="fa fa-briefcase fa-3x text-primary mb-3"></i>
                    <h4 class="fw-bold mb-2">Belum ada lowongan tersedia</h4>
                    <p class="text-muted mb-3">
                        Silakan cek kembali di lain waktu. Kami terus memperbarui informasi lowongan secara berkala.
                    </p>
                    <a href="{{ url('/') }}" class="btn btn-primary rounded-pill px-4">
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</div>
<!-- Lowongan Kerja End -->
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Link telah disalin',
                confirmButtonText: 'OK'
            });
        }, function(err) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Gagal menyalin link',
                confirmButtonText: 'OK'
            });
        });
    }
</script>
@endpush

@endsection