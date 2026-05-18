@extends('layouts.app')

@section('content')

@push('styles')
@include('partials.lowongan.styles')
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
