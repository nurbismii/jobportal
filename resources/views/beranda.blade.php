@extends('layouts.app')

@section('content')

@push('styles')
<style>
    .hover-putih li {
        color: #787878;
        transition: color 0.3s ease;
    }

    .service-item:hover .hover-putih li {
        color: #ffffff;
    }
</style>
@endpush

<!-- Carousel Start -->
<div class="header-carousel owl-carousel">
    <div class="header-carousel-item bg-primary">
        <div class="carousel-caption">
            <div class="container">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-7 animated fadeInLeft">
                        <div class="text-sm-center text-md-start">
                            <h4 class="text-white text-uppercase fw-bold mb-4">PT Virtue Dragon Nickel Industry</h4>
                            <h1 class="display-1 text-white mb-4">Rekrutmen Online</h1>
                            <p class="mb-5 fs-5">Selamat Datang di Website Resmi PT VDNI
                            </p>
                            <div class="d-flex justify-content-center justify-content-md-start flex-shrink-0 mb-4">
                                <a class="btn btn-light rounded-pill py-3 px-4 px-md-5 me-2" href="#"></i> Daftar</a>
                                <a class="btn btn-dark rounded-pill py-3 px-4 px-md-5 ms-2" href="#">Masuk</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 animated fadeInRight">
                        <div class="calrousel-img" style="object-fit: cover;">
                            <img src="{{ asset('user/img/carousel-2.png') }}" class="img-fluid w-100" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="header-carousel-item bg-primary">
        <div class="carousel-caption">
            <div class="container">
                <div class="row gy-4 gy-lg-0 gx-0 gx-lg-5 align-items-center">
                    <div class="col-lg-5 animated fadeInLeft">
                        <div class="calrousel-img">
                            <img src="{{ asset('user/img/carousel-2.png') }}" class="img-fluid w-100" alt="">
                        </div>
                    </div>
                    <div class="col-lg-7 animated fadeInRight">
                        <div class="text-sm-center text-md-end">
                            <h4 class="text-white text-uppercase fw-bold mb-4">PT Virtue Dragon Nickel Industry</h4>
                            <h1 class="display-1 text-white mb-4">Rekrutmen Online</h1>
                            <p class="mb-5 fs-5">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy...
                            </p>
                            <div class="d-flex justify-content-center justify-content-md-end flex-shrink-0 mb-4">
                                <a class="btn btn-light rounded-pill py-3 px-4 px-md-5 me-2" href="#"></i> Daftar</a>
                                <a class="btn btn-dark rounded-pill py-3 px-4 px-md-5 ms-2" href="#">Masuk</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Carousel End -->

<!-- Lowongan Kerja Start -->
<div class="container-fluid service py-5">
    <div class="container py-5">
        <h1 class="display-4 mb-4">Informasi</h1>
        <h4 class="text-primary">Lowongan Tersedia</h4>
        <div class="row g-4"> <!-- g-4: jarak antar kolom & baris -->

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
        <div class="d-flex justify-content-end mt-3">
            <a href="{{ route('lowongan-kerja.index') }}" class="text-primary fw-bold">
                Lihat semua lowongan &gt;&gt;
            </a>
            <!-- Lowongan lihat semua end -->
        </div>
        <!-- Tombol lihat semua -->
    </div>
    <!-- Lowongan Kerja End -->

    <!-- Tentang Start -->
    <div class="container-fluid bg-light about pt-5 pb-5">
        <div class="container pb-5">
            <div class="row g-5">
                <div class="col-xl-6 wow fadeInLeft" data-wow-delay="0.2s">
                    <div class="about-item-content bg-white rounded p-5 h-100">
                        <h4 class="text-primary">Tentang Kami</h4>
                        <h1 class="display-4 mb-4">PT VDNI</h1>
                        <p>
                            PT Virtue Dragon Nickel Industry adalah perusahaan swasta yang bergerak di bidang peleburan bijih nikel di Sulawesi Tenggara, Indonesia.
                        </p>
                        <p>
                            VDNIP Group akan menjadi organisasi yang kreatif, peduli terhadap karyawan dan lingkungan sekitar. Perusahaan kami sempurna untuk semua.
                        </p>
                        <p>
                            Kami selalu memastikan semua tenaga kerja kami memiliki pengalaman dan juga keterampilan profesional. Sehat adalah perhatian utama kami.
                        </p>
                        <p class="text-dark"><i class="fa fa-check text-primary me-3"></i>Perfect For All</p>
                        <p class="text-dark"><i class="fa fa-check text-primary me-3"></i>Who We Are</p>
                        <p class="text-dark mb-4"><i class="fa fa-check text-primary me-3"></i>Powerful Skill</p>
                        <a class="btn btn-primary rounded-pill py-3 px-5" href="#">Informasi Lebih Lanjut</a>
                    </div>
                </div>
                <div class="col-xl-6 wow fadeInRight" data-wow-delay="0.2s">
                    <div class="bg-white rounded p-5 h-100">
                        <div class="row g-4 justify-content-center">
                            <div class="col-12">
                                <div class="rounded bg-light">
                                    <img src="{{ asset('user/img/about-2.jpg') }}" class="img-fluid rounded w-100" alt="">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="counter-item bg-light rounded p-3 h-100">
                                    <div class="counter-counting">
                                        <span class="text-primary fs-2 fw-bold" data-toggle="counter-up">129</span>
                                        <span class="h1 fw-bold text-primary">+</span>
                                    </div>
                                    <h4 class="mb-0 text-dark">Karyawan</h4>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="counter-item bg-light rounded p-3 h-100">
                                    <div class="counter-counting">
                                        <span class="text-primary fs-2 fw-bold" data-toggle="counter-up">99</span>
                                        <span class="h1 fw-bold text-primary">+</span>
                                    </div>
                                    <h4 class="mb-0 text-dark">Penghargaan</h4>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="counter-item bg-light rounded p-3 h-100">
                                    <div class="counter-counting">
                                        <span class="text-primary fs-2 fw-bold" data-toggle="counter-up">556</span>
                                        <span class="h1 fw-bold text-primary">+</span>
                                    </div>
                                    <h4 class="mb-0 text-dark">Departemen</h4>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="counter-item bg-light rounded p-3 h-100">
                                    <div class="counter-counting">
                                        <span class="text-primary fs-2 fw-bold" data-toggle="counter-up">967</span>
                                        <span class="h1 fw-bold text-primary">+</span>
                                    </div>
                                    <h4 class="mb-0 text-dark">Pengguna</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Tentang End -->

    <!-- Pengumumam Start -->
    <div class="container-fluid blog py-5">
        <div class="container py-5">
            <div class="mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s">
                <h4 class="text-primary">Pengumuman</h4>
                <h1 class="display-4 mb-4">Berita terbaru</h1>
                <p class="mb-0">
                    Lorem ipsum dolor, sit amet consectetur adipisicing elit. Tenetur adipisci facilis cupiditate recusandae aperiam temporibus corporis itaque quis facere, numquam, ad culpa deserunt sint dolorem autem obcaecati, ipsam mollitia hic.
                </p>
            </div>
            <div class="row g-4 justify-content-center">
                <!-- Kolom 1 -->
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="blog-item">
                        <div class="blog-img">
                            <img src="{{ asset('img/megapone-loker.jpg') }}" class="img-fluid rounded-top w-100" alt="">
                            <div class="blog-categiry py-2 px-4">
                                <span>Business</span>
                            </div>
                        </div>
                        <div class="blog-content p-4">
                            <div class="blog-comment d-flex justify-content-between mb-3">
                                <div class="small"><span class="fa fa-user text-primary"></span> Martin.C</div>
                                <div class="small"><span class="fa fa-calendar text-primary"></span> 30 Dec 2025</div>
                            </div>
                            <a href="#" class="h4 d-inline-block mb-3">Klaim JKP</a>
                            <p class="mb-3">Lorem ipsum dolor sit amet consectetur adipisicing elit.</p>
                            <a href="#" class="btn p-0">Baca Detail <i class="fa fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Kolom 2 -->
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.2s">
                    <div class="blog-item">
                        <!-- Isi seperti kolom 1, ganti gambar & konten -->
                    </div>
                </div>

                <!-- Kolom 3 -->
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="blog-item">
                        <!-- Isi seperti kolom 1, ganti gambar & konten -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Pengumumam End -->

    <!-- FAQs Start -->
    <div class="container-fluid faq-section bg-light py-5">
        <div class="container py-5">
            <div class="row g-5 align-items-center">
                <div class="col-xl-6 wow fadeInLeft" data-wow-delay="0.2s">
                    <div class="h-100">
                        <div class="mb-5">
                            <h4 class="text-primary">Some Important FAQ's</h4>
                            <h1 class="display-4 mb-0">Common Frequently Asked Questions</h1>
                        </div>
                        <div class="accordion" id="accordionExample">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button border-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        Q: What happens during Freshers' Week?
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show active" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                    <div class="accordion-body rounded">
                                        A: Leverage agile frameworks to provide a robust synopsis for high level overviews. Iterative approaches to corporate strategy foster collaborative thinking to further the overall value proposition. Organically grow the holistic world view of disruptive innovation via workplace diversity and empowerment.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        Q: What is the transfer application process?
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        A: Leverage agile frameworks to provide a robust synopsis for high level overviews. Iterative approaches to corporate strategy foster collaborative thinking to further the overall value proposition. Organically grow the holistic world view of disruptive innovation via workplace diversity and empowerment.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        Q: Why should I attend community college?
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        A: Leverage agile frameworks to provide a robust synopsis for high level overviews. Iterative approaches to corporate strategy foster collaborative thinking to further the overall value proposition. Organically grow the holistic world view of disruptive innovation via workplace diversity and empowerment.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 wow fadeInRight" data-wow-delay="0.4s">
                    <img src="{{ asset('user/img/carousel-2.png') }}" class="img-fluid w-100" alt="">
                </div>
            </div>
        </div>
    </div>
    <!-- FAQs End -->
</div>
@endsection