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
        <div class="row g-4 justify-content-center">
            <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.2s">
                <div class="service-item">
                    <div class="service-img">
                        <img src="{{ asset('user/img/blog-1.png') }}" class="img-fluid rounded-top w-100" alt="">
                        <div class="service-icon p-3">
                            <i class="fa fa-users fa-2x"></i>
                        </div>
                    </div>
                    <div class="service-content p-4">
                        <div class="service-content-inner">
                            <a href="#" class="d-inline-block h4 mb-4">Life Insurance</a>
                            <p class="mb-4">Lorem ipsum dolor sit amet consectetur adipisicing elit. Perspiciatis, eum!</p>
                            <a class="btn btn-primary rounded-pill py-2 px-4" href="#">Read More</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Lowongan Kerja End -->


@endsection