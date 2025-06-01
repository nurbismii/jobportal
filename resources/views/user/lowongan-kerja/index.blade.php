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
            <div class="container py-3">
        </div>
        <div class="mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s">
            <h4 class="text-primary">Lowongan Kerja</h4>
            <h1 class="display-4 mb-4 text-danger">Hati-Hati Penipuan!</h1>
            <p class="mb-5">
                PT Virtue Dragon Nickel Industy (PT VDNI) menyampaikan bahwa perusahaan tidak pernah meminta biaya apapun dalam proses perekrutan karyawan. Untuk itu dimohon agar para pencari kerja waspada terhadap penipuan yang dilakukan oleh pihak tertentu yang telah menggunakan nama PT VDNI untuk meminta biaya dari para pencari kerja.
            </p>
            <h4 class="display-8 mt-1">Total Lowongan Tersedia: 3</h4>
        </div>
        <div class="container-fluid service py-3">
        <div class="container py-3">
            <div class="row g-4"> <!-- g-4: jarak antar kolom & baris -->
                <!-- Layout 1 -->
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
                                <a href="#" class="d-inline-block h4 mb-4">Dump Truck</a>
                                <!-- Isian deskripsi lowongan kerja -->
                                <p class="fw-bold mb-1">Departemen</p>
                                <p class="mb-2">Transportasi</p>

                                <p class="fw-bold mb-1">Status</p>
                                <p class="mb-2">PKWT</p>

                                <p class="fw-bold mb-1">Kualifikasi</p>
                                <ul class="mb-3 hover-putih">
                                    <li>Laki-laki</li>
                                    <li>Minimal SMA sederajat</li>
                                    <li>Usia 25 Tahun ke Atas</li>
                                    <li>Memiliki SIM B II Umum</li>
                                </ul>

                                <style>

                                .hover-putih li {
                                    color: #787878;
                                    transition: color 0.3s ease;
                                }

                                .service-item:hover .hover-putih li {
                                    color: #ffffff;
                                }
                                </style>

                                <p class="fw-bold mb-1">Tanggal aktif</p>
                                <p class="mb-4">15 April 2025 – 17 April 2025</p>
                                <!-- Isian deskripsi lowongan kerja end -->

                                <div class="d-flex justify-content-end gap-2">
                                    <a class="btn btn-primary rounded-pill py-2 px-3" href="#">Lamar</a>
                                    <a class="btn btn-primary rounded-pill py-2 px-3" href="#">Bagikan</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Layout 2 -->
                <div class="col-md-6 col-lg-4">
                    <div class="service-item h-100">
                        <div class="service-img">
                            <img src="{{ asset('img/megapone-loker.jpg') }}" class="img-fluid rounded-top w-100" alt="">
                            <div class="service-icon p-3">
                                <i class="fa fa-cogs fa-2x"></i>
                            </div>
                        </div>
                        <div class="service-content p-4">
                            <div class="service-content-inner">
                                <a href="#" class="d-inline-block h4 mb-4">Excavator</a>
                                <!-- Isian deskripsi lowongan kerja -->
                                <p class="fw-bold mb-1">Departemen</p>
                                <p class="mb-2">Transportasi</p>

                                <p class="fw-bold mb-1">Status</p>
                                <p class="mb-2">PKWT</p>

                                <p class="fw-bold mb-1">Kualifikasi</p>
                                <ul class="mb-3 hover-putih">
                                    <li>Laki-laki</li>
                                    <li>Minimal SMA sederajat</li>
                                    <li>Usia 25 Tahun ke Atas</li>
                                    <li>Memiliki SIM B II Umum</li>
                                </ul>

                                <style>

                                .hover-putih li {
                                    color: #787878;
                                    transition: color 0.3s ease;
                                }

                                .service-item:hover .hover-putih li {
                                    color: #ffffff;
                                }
                                </style>

                                <p class="fw-bold mb-1">Tanggal aktif</p>
                                <p class="mb-4">15 April 2025 – 17 April 2025</p>
                                <!-- Isian deskripsi lowongan kerja end -->

                                <div class="d-flex justify-content-end gap-2">
                                    <a class="btn btn-primary rounded-pill py-2 px-3" href="#">Lamar</a>
                                    <a class="btn btn-primary rounded-pill py-2 px-3" href="#">Bagikan</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Layout 3 -->
                <div class="col-md-6 col-lg-4">
                    <div class="service-item h-100">
                        <div class="service-img">
                            <img src="{{ asset('img/megapone-loker.jpg') }}" class="img-fluid rounded-top w-100" alt="">
                            <div class="service-icon p-3">
                                <i class="fa fa-industry fa-2x"></i>
                            </div>
                        </div>
                        <div class="service-content p-4">
                            <div class="service-content-inner">
                                <a href="#" class="d-inline-block h4 mb-4">Crew Smelter</a>
                                <!-- Isian deskripsi lowongan kerja -->
                                <p class="fw-bold mb-1">Departemen</p>
                                <p class="mb-2">Transportasi</p>

                                <p class="fw-bold mb-1">Status</p>
                                <p class="mb-2">PKWT</p>

                                <p class="fw-bold mb-1">Kualifikasi</p>
                                <ul class="mb-3 hover-putih">
                                    <li>Laki-laki</li>
                                    <li>Minimal SMA sederajat</li>
                                    <li>Usia 25 Tahun ke Atas</li>
                                    <li>Memiliki SIM B II Umum</li>
                                </ul>

                                <style>

                                .hover-putih li {
                                    color: #787878;
                                    transition: color 0.3s ease;
                                }

                                .service-item:hover .hover-putih li {
                                    color: #ffffff;
                                }
                                </style>

                                <p class="fw-bold mb-1">Tanggal aktif</p>
                                <p class="mb-4">15 April 2025 – 17 April 2025</p>
                                <!-- Isian deskripsi lowongan kerja end -->

                                <div class="d-flex justify-content-end gap-2">
                                    <a class="btn btn-primary rounded-pill py-2 px-3" href="#">Lamar</a>
                                    <a class="btn btn-primary rounded-pill py-2 px-3" href="#">Bagikan</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Lowongan Kerja End -->

@endsection