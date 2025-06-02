@extends('layouts.app')

@section('content')
<!-- Carousel Start -->
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
        <div class="mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s">
            <h4 class="text-primary">Lowongan Kerja</h4>
            <h1 class="display-4 mb-4 text-danger">Hati-Hati Penipuan!</h1>
            <p class="mb-0">
                PT Virtue Dragon Nickel Industy (PT VDNI) menyampaikan bahwa perusahaan tidak pernah meminta biaya apapun dalam proses perekrutan karyawan. Untuk itu dimohon agar para pencari kerja waspada terhadap penipuan yang dilakukan oleh pihak tertentu yang telah menggunakan nama PT VDNI untuk meminta biaya dari para pencari kerja.
            </p>
        </div>
        @foreach ($lowongans as $lowongan)
        <div class="col-md-6 col-lg-4">
            <div class="service-item h-100"> <!-- h-100: biar tinggi seragam -->
                <div class="service-img">
                    <img src="{{ asset('img/megapone-loker.jpg') }}" class="img-fluid rounded-top w-100" alt="">
                    <div class="service-icon p-3">
                        <i class="fa fa-users fa-2x"></i>
                    </div>
                </div>
                <div class="service-content p-4">
                    <div class="service-content-inner">
                        <a href="{{ route('lowongan-kerja.show', $lowongan->id) }}" class="d-inline-block h4 mb-4">{{ $lowongan->nama_lowongan }}</a>
                        <!-- Isian deskripsi lowongan kerja -->
                        <p class="mb-4">
                            {!! $lowongan->kualifikasi !!}
                        </p>

                        <p class="fw-bold mb-1">Tanggal aktif</p>
                        <p class="mb-4">{{ date('d F Y H:i', strtotime($lowongan->tanggal_mulai)) }} – {{ date('d F Y H:i', strtotime($lowongan->tanggal_berakhir)) }}</p>
                        <!-- Isian deskripsi lowongan kerja end -->

                        <div class="d-flex justify-content-end gap-2">
                            <a class="btn btn-primary rounded-pill py-2 px-3" href="{{ route('lowongan-kerja.show', $lowongan->id) }}">Lamar</a>
                            <a class="btn btn-primary rounded-pill py-2 px-3" href="#">Bagikan</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
<!-- Lowongan Kerja End -->


@endsection