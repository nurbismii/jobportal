@extends('layouts.app')

@section('content')

@push('styles')
@include('partials.lowongan.styles')
@endpush

<!-- Header Start -->
<div class="container-fluid bg-breadcrumb page-hero-banner">
    <div class="container page-hero text-center py-5">
        <span class="page-hero__eyebrow wow fadeInDown" data-wow-delay="0.1s">
            <i class="fas fa-briefcase"></i>
            Lowongan Kerja
        </span>
        <h1 class="text-white display-4 mb-3 wow fadeInDown" data-wow-delay="0.2s">Temukan peluang karier resmi bersama PT VDNI</h1>
        <p class="page-hero__text wow fadeInUp" data-wow-delay="0.3s">
            Lihat posisi yang tersedia, perhatikan periode pendaftaran, dan ajukan lamaran hanya melalui sistem rekrutmen V-HIRE.
        </p>
    </div>
</div>
<!-- Header End -->

<!-- Lowongan Kerja Start -->
<div class="container-fluid service py-5">
    <div class="container py-5">
        <h4 class="display-4 mb-4">Lowongan</h4>
        <p class="text-primary mb-3">Pilih lowongan kerja yang kamu minati dan kesempatan berkarir bersama kami.</p>
        @include('partials.lowongan.list', ['lowongans' => $lowongans, 'showBackHomeButton' => true])
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
