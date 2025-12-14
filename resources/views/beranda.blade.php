@extends('layouts.app')

@section('content')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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

    .badge i {
        font-size: 12px;
    }

    .hover-card {
        transition: 0.3s ease;
        border-radius: 15px;
    }

    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    }
</style>
@endpush

@if(Auth::guest())
<!-- Carousel Start -->
<div class="header-carousel owl-carousel">
    <div class="header-carousel-item bg-primary">
        <div class="carousel-caption">
            <div class="container">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-7 animated fadeInLeft">
                        <div class="text-sm-center text-md-start">
                            <h1 class="text-white text-uppercase fw-bold mb-2">V-HIRE</h1>
                            <h4 class="display-2 text-white mb-4">Rekrutmen Online</h4>
                            <p class="mb-5 fs-5">Selamat Datang di Website Rekrutmen Resmi PT VDNI</p>
                            <div class="d-flex justify-content-center justify-content-md-start flex-shrink-0 mb-4">
                                <a class="btn btn-light rounded-pill py-3 px-4 px-md-5 me-2" href="{{ route('login') }}"><i class="fas fa-arrow-circle-right me-2"></i> Masuk</a>
                                <a class="btn btn-dark rounded-pill py-3 px-4 px-md-5 ms-2" href="{{ asset('pdf/MANUAL BOOK V-HIRE (1).pdf') }}" target="_blank">Panduan Melamar Pekerjaan</a>
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
</div>
@else

@php
$steps = [
1 => 'Data Pribadi',
2 => 'Pendidikan',
3 => 'Data Keluarga',
4 => 'Kontak Darurat',
5 => 'Dokumen Pribadi',
6 => 'Selesai'
];

$maxStep = count($steps);
@endphp

<div class="container py-4">
    @if(($step < $maxStep) || (Auth::user()->role == 'user'))
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
            <i class="fa fa-exclamation-triangle"></i>
            <span>Silakan lengkapi <a href="{{ route('biodata.index') }}" class="">Formulir Biodata</a> untuk <b>melihat lowongan pekerjaan tersedia.</b></span>
        </div>
        @else
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
            <i class="fa fa-check-circle"></i>
            <span>Formulir lengkap! Kamu sudah bisa melamar pekerjaan <a href="" class="btn btn-sm btn-success">Lihat lowongan pekerjaan</a></span>
        </div>
        @endif
        <div class="row g-3">
            <!-- Card Formulir Biodata -->
            <div class="col-12 col-md-6">
                <a href="{{ route('biodata.index') }}" class="text-decoration-none">
                    <div class="card bg-light shadow-sm border-0 h-100 hover-card">
                        <div class="card-body text-center py-4">
                            <div class="icon-wrapper mb-3">
                                <i class="fa-solid fa-id-card fa-3x text-primary"></i>
                            </div>
                            <h5 class="fw-bold text-dark">Upload Berkas Lamaran</h5>
                            <p class="text-muted mb-0">Lengkapi data pribadi dan berkas untuk melanjutkan proses lamaran.</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Card Panduan Melamar -->
            <div class="col-12 col-md-6">
                <a href="{{ asset('pdf/MANUAL BOOK V-HIRE (1).pdf') }}" target="_blank" class="text-decoration-none">
                    <div class="card bg-light shadow-sm border-0 h-100 hover-card">
                        <div class="card-body text-center py-4">
                            <div class="icon-wrapper mb-3">
                                <i class="fa-solid fa-book-open-reader fa-3x text-success"></i>
                            </div>
                            <h5 class="fw-bold text-dark">Panduan Melamar Pekerjaan</h5>
                            <p class="text-muted mb-0">Pelajari langkah-langkah sebelum mengajukan lamaran.</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
</div>

<div class="container">

    <div class="card shadow-sm mb-4">
        <div class="card-header fw-bold py-2">
            Infomasi Pribadi yang Perlu Dilengkapi!
        </div>
        <div class="card-body">
            <div class="border-start ps-3">

                @foreach ($steps as $number => $label)
                <div class="d-flex align-items-center mb-3 position-relative">

                    {{-- ICON --}}
                    <span class="me-3">
                        @if($step >= $number)
                        <span class="badge bg-success rounded-circle p-2">
                            <i class="fa fa-check"></i>
                        </span>
                        @else
                        <span class="badge bg-danger rounded-circle p-2">
                            <i class="fa fa-times"></i>
                        </span>
                        @endif
                    </span>

                    {{-- LABEL --}}
                    <div class="{{ $step == $number ? 'fw-bold text-primary' : '' }}">
                        {{ $label }}
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Progress Bar --}}
            <div class="mt-4">
                <div class="progress" style="height: 10px;">
                    <div
                        class="progress-bar bg-primary"
                        role="progressbar"
                        style="width: {{ ($step / $maxStep) * 100 }}%">
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    Langkah {{ min($step, $maxStep) }} dari {{ $maxStep }}
                </small>
            </div>
        </div>
    </div>

    @if($step != 6)
    <a href="{{ route('biodata.index') }}" class="btn btn-primary mb-3">Lengkapi Formulir Biodata</a>
    @endif

    @if(($step == 6) || (Auth::check() && Auth::user()->role == 'admin'))
    <h4 class="display-4 fw-bold mb-0">Lowongan</h4>
    <p class="text-primary mb-3">Pilih lowongan kerja yang kamu minati dan kesempatan berkarir bersama kami.</p>
    <div class="row g-4 justify-content-center">

        @php
        $shareUrl = route('lowongan-kerja.index');
        @endphp

        @forelse($lowongans as $lowongan)
        <div class="col-md-6 col-lg-4">
            <div class="service-item h-100 d-flex flex-column"> <!-- h-100: biar tinggi seragam -->
                <div class="service-img">
                    <img src="{{ asset('img/megapone-loker.jpg') }}" class="img-fluid rounded-top w-100" alt="">
                    <div class="service-icon p-3">
                        <i class="fa fa-users fa-2x"></i>
                    </div>
                </div>
                <div class="service-content p-4 d-flex flex-column flex-grow-1">
                    <div class="service-content-inner flex-grow-1 d-flex flex-column justify-content-between">
                        <a href="{{ route('lowongan-kerja.show', $lowongan->id) }}" class="d-inline-block h4 mb-0">{{ $lowongan->nama_lowongan }}</a>
                        <!-- Isian deskripsi lowongan kerja -->
                        <p class="mb-4">
                            {!! substr($lowongan->kualifikasi, 0, 409) !!}
                        </p>

                        <p class="fw-bold mb-1">Tanggal aktif</p>
                        <p class="mb-1">{{ tanggalIndo($lowongan->tanggal_mulai) }} – {{ tanggalIndo($lowongan->tanggal_berakhir) }}</p>
                        <!-- Isian deskripsi lowongan kerja end -->
                        <div>
                            @if(strtolower($lowongan->status_lowongan) == 'aktif')
                            <span class="mb-1 badge bg-success">{{ $lowongan->status_lowongan }}</span>
                            @else
                            <span class="mb-1 badge bg-danger">{{ $lowongan->status_lowongan }}</span>
                            @endif
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-auto pt-3">
                            <a class="btn btn-primary btn-sm rounded-pill py-2 px-3" href="{{ route('lowongan-kerja.show', $lowongan->id) }}">Lihat</a>
                            <a class="btn btn-primary btn-sm rounded-pill py-2 px-3" href="javascript:void(0)" onclick="copyToClipboard('{{ $shareUrl }}')">Bagikan</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center p-5 my-4 border rounded-3 shadow-sm bg-light wow fadeInRight" data-wow-delay="0.2s">
                <i class="fa fa-briefcase fa-3x text-primary mb-3"></i>
                <h4 class="fw-bold mb-2">Belum ada lowongan tersedia</h4>
                <p class="text-muted mb-3">Silakan cek kembali di lain waktu. Kami terus memperbarui informasi lowongan secara berkala.</p>
            </div>
        </div>
        @endforelse

    </div>
    <div class="d-flex justify-content-end mt-3">
        <a href="{{ route('lowongan-kerja.index') }}" class="text-primary fw-bold">
            Lihat semua lowongan &gt;&gt;
        </a>
    </div>
    @endif
</div>
@endif

@if(Auth::guest())
<div class="container py-5">
    <h4 class="display-4 fw-bold mb-0">Lowongan</h4>
    <p class="text-primary mb-3">Pilih lowongan kerja yang kamu minati dan kesempatan berkarir bersama kami.</p>
    <div class="row g-4 justify-content-center">

        @php
        $shareUrl = route('lowongan-kerja.index');
        @endphp

        @forelse($lowongans as $lowongan)
        <div class="col-md-6 col-lg-4">
            <div class="service-item h-100 d-flex flex-column"> <!-- h-100: biar tinggi seragam -->
                <div class="service-img">
                    <img src="{{ asset('img/megapone-loker.jpg') }}" class="img-fluid rounded-top w-100" alt="">
                    <div class="service-icon p-3">
                        <i class="fa fa-users fa-2x"></i>
                    </div>
                </div>
                <div class="service-content p-4 d-flex flex-column flex-grow-1">
                    <div class="service-content-inner flex-grow-1 d-flex flex-column justify-content-between">
                        <a href="{{ route('lowongan-kerja.show', $lowongan->id) }}" class="d-inline-block h4 mb-0">{{ $lowongan->nama_lowongan }}</a>
                        <!-- Isian deskripsi lowongan kerja -->
                        <p class="mb-4">
                            {!! substr($lowongan->kualifikasi, 0, 409) !!}
                        </p>

                        <p class="fw-bold mb-1">Tanggal aktif</p>
                        <p class="mb-1">{{ tanggalIndo($lowongan->tanggal_mulai) }} – {{ tanggalIndo($lowongan->tanggal_berakhir) }}</p>
                        <!-- Isian deskripsi lowongan kerja end -->
                        <div>
                            @if(strtolower($lowongan->status_lowongan) == 'aktif')
                            <span class="mb-1 badge bg-success">{{ $lowongan->status_lowongan }}</span>
                            @else
                            <span class="mb-1 badge bg-danger">{{ $lowongan->status_lowongan }}</span>
                            @endif
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-auto pt-3">
                            <a class="btn btn-primary btn-sm rounded-pill py-2 px-3" href="{{ route('lowongan-kerja.show', $lowongan->id) }}">Lihat</a>
                            <a class="btn btn-primary btn-sm rounded-pill py-2 px-3" href="javascript:void(0)" onclick="copyToClipboard('{{ $shareUrl }}')">Bagikan</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center p-5 my-4 border rounded-3 shadow-sm bg-light wow fadeInRight" data-wow-delay="0.2s">
                <i class="fa fa-briefcase fa-3x text-primary mb-3"></i>
                <h4 class="fw-bold mb-2">Belum ada lowongan tersedia</h4>
                <p class="text-muted mb-3">Silakan cek kembali di lain waktu. Kami terus memperbarui informasi lowongan secara berkala.</p>
            </div>
        </div>
        @endforelse

    </div>
    <div class="d-flex justify-content-end mt-3">
        <a href="{{ route('lowongan-kerja.index') }}" class="text-primary fw-bold">
            Lihat semua lowongan &gt;&gt;
        </a>
    </div>
</div>
@endif


<div class="container-fluid service py-2">
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
                        <a class="btn btn-primary rounded-pill py-3 px-5" href="https://vdni.co.id/" target="_blank">Informasi Lebih Lanjut</a>
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
                                        <span class="text-primary fs-2 fw-bold" data-toggle="counter-up">{{ $count_karyawan }}</span>
                                    </div>
                                    <h4 class="mb-0 text-dark">Karyawan</h4>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="counter-item bg-light rounded p-3 h-100">
                                    <div class="counter-counting">
                                        <span class="text-primary fs-2 fw-bold" data-toggle="counter-up">49</span>
                                    </div>
                                    <h4 class="mb-0 text-dark">Penghargaan</h4>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="counter-item bg-light rounded p-3 h-100">
                                    <div class="counter-counting">
                                        <span class="text-primary fs-2 fw-bold" data-toggle="counter-up">{{ $count_departemen }}</span>
                                    </div>
                                    <h4 class="mb-0 text-dark">Departemen</h4>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="counter-item bg-light rounded p-3 h-100">
                                    <div class="counter-counting">
                                        <span class="text-primary fs-2 fw-bold" data-toggle="counter-up">{{ $count_user }}</span>
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
            <h4 class="display-4 fw-bold mb-0">Pengumuman</h4>
            <p class="text-primary mb-3">Temukan informasi terbaru dan penting dari kami di bawah ini.</p>
            <div class="row g-4 justify-content-center">
                @forelse($pengumumans as $pengumuman)
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="blog-item">
                        <div class="blog-img">
                            @if($pengumuman->thumbnail)
                            <img src="{{ asset('thumbnail/' . $pengumuman->thumbnail) }}" class="img-fluid rounded-top w-100" alt="">
                            @else
                            <img src="{{ asset('img/megapone-loker.jpg') }}" class="img-fluid rounded-top w-100" alt="">
                            @endif
                            <div class="blog-categiry py-2 px-4">
                                <span>Pengumuman</span>
                            </div>
                        </div>
                        <div class="blog-content p-4">
                            <div class="blog-comment d-flex justify-content-between mb-3">
                                <div class="small"><span class="fa fa-user text-primary me-2"></span>PT VDNI</div>
                                <div class="small"><span class="fa fa-calendar text-primary me-2"></span>{{ tanggalIndo($pengumuman->created_at) }}</div>
                            </div>
                            <a href="{{ route('pengumuman.show', $pengumuman->id) }}" class="h4 d-inline-block mb-3">{{ $pengumuman->pengumuman }}</a>
                            <p class="mb-3">{!! substr($pengumuman->keterangan, 0, 140) !!}...</p>
                            <a href="{{ route('pengumuman.show', $pengumuman->id) }}" class="btn p-0 mt-3">Baca Detail <i class="fa fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="text-center p-5 my-4 border rounded-3 shadow-sm bg-light wow fadeInLeft" data-wow-delay="0.2s">
                        <i class="fa fa-bullhorn fa-3x text-primary mb-3"></i>
                        <h4 class="fw-bold mb-2">Belum ada pengumuman terbaru</h4>
                        <p class="text-muted mb-3">Kami akan segera memperbarui informasi pengumuman di sini.</p>
                    </div>
                </div>
                @endforelse
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
                            <h4 class="text-primary">Beberapa Pertanyaan yang Sering Diajukan</h4>
                            <h3 class="display-4 mb-0">Pertanyaan Umum yang Sering Diajukan</h3>
                        </div>
                        <div class="accordion" id="accordionExample">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button border-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        Bagaimana cara mengatasi KTP atau SIM B2 Umum tidak terbaca?
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show active" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                    <div class="accordion-body rounded">
                                        <ul class="mb-0 ps-3">
                                            <li class="mb-1">Posisikan KTP dan SIM gambar tegak</li>
                                            <li class="mb-1">Hasil foto harus jelas, tidak blur, tidak pecah, dan dapat dibaca</li>
                                            <li class="mb-1">Gambar diambil dengan pencahayaan yang bagus dan tidak terlalu jauh</li>
                                            <li class="mb-1">Pastikan teks pada SIM B2 tidak tertutup oleh hologram saat diunggah</li>
                                            <li class="mb-1">Tidak mengandung tulisan lain selain dari dokumen</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        Bagaimana cara melihat status pelamaran yang dilakukan?
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        Silakan masuk terlebih dahulu menggunakan akun yang telah terdaftar. Kemudian pilih menu Lamaran, dan pilih lamaran yang ingin Anda lihat riwayatnya.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        Apa saja yang perlu dipersiapkan untuk melamar pekerjaan?
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <ul class="mb-0 ps-3">
                                            <li>CV</li>
                                            <li>KTP</li>
                                            <li>Surat Lamaran</li>
                                            <li>Kartu Keluarga</li>
                                            <li>Ijazah Terakhir</li>
                                            <li>Sertifikat Vaksin</li>
                                            <li>Surat Keterangan Catatan Kepolisian (SKCK)</li>
                                            <li>Kartu Pencari Kerja (AK1)</li>
                                            <li>Pas Foto (3x4)</li>
                                            <li>NPWP</li>
                                            <li>SIM B2 Umum (Jika dibutuhkan)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFour">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                        Di manakah hasil seleksi akan diumumkan?
                                    </button>
                                </h2>
                                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        Hasil seleksi akan diumumkan di halaman lamaran pada website rekrutmen VDNI dan akan diberitahukan secara personal melalui sarana komunikasi tercepat yang didaftarkan.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFive">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                        Berapa lama waktu seleksi di setiap tahapan?
                                    </button>
                                </h2>
                                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        Waktu dan pengumuman seleksi setiap tahapan berbeda. Pemberitahuan lebih lanjut terkait hal ini akan disampaikan langsung kepada Peserta yang dinyatakan lolos di setiap tahapannya melalui sarana komunikasi tercepat.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 wow fadeInRight" data-wow-delay="0.4s">
                    <img src="{{ asset('img/faq-1.png') }}" class="img-fluid w-100" alt="">
                </div>
            </div>
        </div>
    </div>
    <!-- FAQs End -->
</div>

<!-- Modal Scrollable + TOC -->
<div class="modal fade" id="pdfScrollModal" tabindex="-1" aria-labelledby="pdfScrollModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title me-2">PDF Viewer Lengkap</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="btnPrint">🖨️ Print</button>
                <a id="btnDownload" class="btn btn-sm btn-outline-secondary me-auto" href="#" download target="_blank">⬇️ Download</a>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body d-flex">
                <!-- Sidebar TOC -->
                <div id="pdf-toc" style="width: 250px; max-height: 80vh; overflow-y: auto;" class="pe-3 border-end"></div>

                <!-- Kontainer halaman PDF -->
                <div id="pdf-scroll-container" style="flex: 1; overflow-y: auto; max-height: 80vh;" class="ps-3">
                    <p class="text-muted">Memuat PDF...</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

<!-- PDF.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

<script>
    const pdfjsLib = window['pdfjs-dist/build/pdf'];
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    function openScrollablePdfViewer(pdfUrl) {
        const container = document.getElementById('pdf-scroll-container');
        const tocContainer = document.getElementById('pdf-toc');
        container.innerHTML = '<p class="text-muted">Memuat dokumen...</p>';
        tocContainer.innerHTML = '<p class="text-muted">Memuat TOC...</p>';

        // Set tombol print & download
        document.getElementById('btnPrint').onclick = () => window.open(pdfUrl, '_blank').print();
        document.getElementById('btnDownload').href = pdfUrl;

        // Load dokumen
        pdfjsLib.getDocument({
            url: pdfUrl
        }).promise.then(pdfDoc => {
            container.innerHTML = '';
            tocContainer.innerHTML = '';

            // Render semua halaman
            for (let pageNum = 1; pageNum <= pdfDoc.numPages; pageNum++) {
                pdfDoc.getPage(pageNum).then(page => {
                    const viewport = page.getViewport({
                        scale: 1.2
                    });

                    const canvas = document.createElement('canvas');
                    canvas.style.marginBottom = '20px';
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;

                    const ctx = canvas.getContext('2d');
                    page.render({
                        canvasContext: ctx,
                        viewport: viewport
                    });

                    // Tandai halaman untuk TOC scroll
                    canvas.setAttribute('id', `page-${page.pageNumber}`);
                    container.appendChild(canvas);
                });
            }

            // Tampilkan TOC (outline)
            pdfDoc.getOutline().then(outline => {
                if (!outline) {
                    tocContainer.innerHTML = '<p class="text-muted">Tidak ada daftar isi.</p>';
                    return;
                }

                tocContainer.innerHTML = '<ul class="list-group list-group-flush w-100"></ul>';
                const list = tocContainer.querySelector('ul');

                outline.forEach(item => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item py-1 px-2';
                    li.innerText = item.title;

                    li.style.cursor = 'pointer';
                    li.onclick = () => {
                        if (item.dest) {
                            pdfDoc.getDestination(item.dest).then(dest => {
                                if (!dest) return;
                                pdfDoc.getPageIndex(dest[0]).then(pageIndex => {
                                    const targetCanvas = document.getElementById(`page-${pageIndex + 1}`);
                                    if (targetCanvas) {
                                        targetCanvas.scrollIntoView({
                                            behavior: 'smooth'
                                        });
                                    }
                                });
                            });
                        }
                    };

                    list.appendChild(li);
                });
            });

        }).catch(error => {
            container.innerHTML = `<p class="text-danger">Gagal memuat PDF: ${error.message}</p>`;
            tocContainer.innerHTML = '';
        });

        new bootstrap.Modal(document.getElementById('pdfScrollModal')).show();
    }
</script>


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

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const STORAGE_KEY = "vhire_tutorial_first_beranda";
        const lastShown = localStorage.getItem(STORAGE_KEY);
        const twelveHours = 12 * 60 * 60 * 1000; // 12 jam dalam milidetik
        const now = Date.now();

        // Cek apakah sudah lewat 12 jam sejak alert terakhir
        if (!lastShown || (now - lastShown) > twelveHours) {

            const steps = [{
                title: "⚠ Penting Dipahami!",
                html: `
                        <b>Berhasil membuat akun pengguna tidak otomatis berarti kamu sudah melamar pekerjaan.</b><br><br>
                        Agar masuk proses seleksi, kamu harus :<br><br>
                        ✔ Melengkapi semua biodata<br>
                        ✔ Mengunggah dokumen<br>
                        ✔ Memilih lowongan dan klik <b>"Lamar"</b><br><br>
                        Jika belum menekan tombol <b>"Ya, Lamar Sekarang"</b>, sistem tidak menganggap kamu mengikuti rekrutmen.<br><br>
                        <span id="countdown" style="font-size:14px;color:#d33;font-weight:bold;">Silakan baca dulu (15 detik)...</span>
                    `,
                icon: "warning",
                lockTime: 15
            }];

            let index = 0;

            function showStep() {
                let lockDuration = steps[index].lockTime ?? 0;

                Swal.fire({
                    title: steps[index].title,
                    html: steps[index].html,
                    icon: steps[index].icon,
                    showCancelButton: false,
                    confirmButtonText: "Saya Mengerti",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    width: 600,
                    didOpen: () => {

                        const confirmBtn = Swal.getConfirmButton();
                        const countdownEl = document.getElementById("countdown");

                        confirmBtn.disabled = true;

                        if (lockDuration > 0 && countdownEl) {
                            let remaining = lockDuration;

                            const timer = setInterval(() => {
                                remaining--;
                                countdownEl.textContent = `Silakan baca dulu (${remaining} detik)...`;

                                if (remaining <= 0) {
                                    clearInterval(timer);
                                    countdownEl.textContent = "Kamu bisa lanjut sekarang.";
                                    confirmBtn.disabled = false;
                                }
                            }, 1000);
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        index++;
                        if (index < steps.length) {
                            showStep();
                        } else {
                            // simpan waktu terakhir popup muncul
                            localStorage.setItem(STORAGE_KEY, Date.now());
                        }
                    }
                });

            }

            showStep();
        }
    });
</script>

@endpush
@endsection