@extends('layouts.app')

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter&display=swap');

body, label, input, select, textarea, button {
  font-family: 'Inter', sans-serif;
}
</style>

@section('content')
<!-- Carousel Start -->
<div class="header-carousel owl-carousel">
    <div class="header-carousel-item" style="background-color: #6AA7D7;">
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
    <div class="header-carousel-item" style="background-color: #6AA7D7;">
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
                            <p class="mb-5 fs-5">Selamat Datang di Website Resmi PT VDNI
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

<!-- Filter Start -->
<div class="container-fluid bg-light about pt-5 pb-5">
    <!-- Baris Atas -->
    <div class="d-flex justify-content-;eft align-items-center mb-3">
    <span class="ps-3 fw-semibold fs-5">(3) Lowongan Tersedia</span>
    <a href="#" class="text-primary fw-semibold text-decoration-none px-4 fs-5">
        Lihat Semua <i class="bi bi-arrow-right"></i>
    </a>
    </div>

    <!-- Bar Filter -->
    <div class="p-3 rounded bg-lightblue">
        <div class="row g-2">
            <div class="col-md-3">
                <select name="area_kerja" id="area_kerja" class="form-select fs-6" style="height: 60px;" required>
                    <option value="" disabled selected hidden>Pilih Area Kerja</option>
                    <option value="VDNI">VDNI</option>
                    <option value="VDNIP">VDNIP</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select fs-6" style="height: 60px;">
                    <option value="" disabled selected hidden>Pilih Departemen</option>
                    <option value="Administration">Administration</option>
                    <option value="Equipment">Equipment</option>
                    <option value="Finance">Finance</option>
                    <option value="General Affairs">General Affairs</option>
                    <option value="HRD">HRD</option>
                    <option value="IT">IT</option>
                    <option value="Pengurus Lahan">Pengurus Lahan</option>
                    <option value="PLTU">PLTU</option>
                    <option value="PMS Jetty">PMS Jetty</option>
                    <option value="Purchasing">Purchasing</option>
                    <option value="Quality Control">Quality Control</option>
                    <option value="Safety">Safety</option>
                    <option value="Smelter">Smelter</option>
                    <option value="Transportasi">Transportasi</option>
                    <option value="Workshop Manufacture">Workshop Manufacture</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select fs-6" style="height: 60px;">
                    <option value="" disabled selected hidden>Pilih Jabatan</option>
                    <option value="Staf">Staf</option>
                    <option value="Crew">Crew</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control fs-6" style="height: 60px;" placeholder="Cari Posisi...">
            </div>
            <div class="col-md-1">
                <button class="btn btn-dark w-100 fs-6" style="height: 60px;">Cari</button>
            </div>
        </div>
    </div>
</div>
<!-- Filter End -->
 
<!-- Lowongan Kerja Start -->
<div class="container-fluid service py-5">
    <div class="container py-5">
        <h4 class="text-primary">Informasi</h4>
        <h1 class="display-4 mb-4">Lowongan Tersedia</h1>
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
        <div class="d-flex justify-content-end mt-3">
        <a href="/semua-lowongan" class="text-primary fw-bold">
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
                        <div class="d-flex justify-content-end mt-auto">
                            <a href="#" class="btn p-0" style="color: #0056B3;">Baca Detail <i class="fa fa-arrow-right"></i></a>
                        </div>
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
                        <h4 class="text-primary">Bantuan</h4>
                        <h1 class="display-4 mb-0">Pertanyaan yang Sering Diajukan (FAQ)</h1>
                    </div>
                    <div class="accordion" id="accordionExample">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button border-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    Q: Bagaimana cara melamar kerja di PT VDNI?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show active" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                <div class="accordion-body rounded">
                                    A: Anda dapat melamar melalui website resmi rekrutmen PT VDNI dengan membuat akun, melengkapi data diri, lalu memilih posisi yang tersedia dan sesuai dengan kualifikasi Anda.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Q: Apa saja dokumen yang dibutuhkan untuk melamar?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    A: Dokumen umum meliputi: CV/Daftar Riwayat Hidup, KTP, Ijazah dan Transkrip Nilai, Sertifikat pendukung (jika ada), Pas foto terbaru, dan dokumen lain sesuai dengan persyaratan posisi yang dilamar.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Q: Apakah semua pelamar akan mendapatkan pemberitahuan hasil seleksi?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    A: Hanya pelamar yang lolos ke tahap selanjutnya yang akan dihubungi oleh tim rekrutmen melalui email atau WhatsApp resmi.
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

@endsection