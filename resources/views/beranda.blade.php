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
                            <h1 class="text-white text-uppercase fw-bold mb-2">V-HIRE</h1>
                            <h2 class="display-1 text-white mb-4">Rekrutmen Online</h2>
                            <p class="mb-5 fs-5">Selamat Datang di Website Rekrutmen Resmi PT VDNI</p>
                            <div class="d-flex justify-content-center justify-content-md-start flex-shrink-0 mb-4">
                                <a class="btn btn-light rounded-pill py-3 px-4 px-md-5 me-2" href="#"><i class="fas fa-play-circle me-2"></i> Cara penggunaan</a>
                                <a class="btn btn-dark rounded-pill py-3 px-4 px-md-5 ms-2" href="#">Panduan Pengguna</a>
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
<!-- Carousel End -->

<!-- Lowongan Kerja Start -->
<div class="container-fluid service py-5">
    <div class="container py-5">
        <h1 class="display-4 mb-4">Informasi</h1>
        <h4 class="text-primary">Lowongan Tersedia</h4>
        <div class="row g-4 justify-content-center">

            @php
            $shareUrl = route('lowongan-kerja.index');
            @endphp

            @foreach ($lowongans as $lowongan)
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
                                <a class="btn btn-primary rounded-pill py-2 px-3" href="{{ route('lowongan-kerja.show', $lowongan->id) }}">Lamar</a>
                                <a class="btn btn-primary rounded-pill py-2 px-3" href="javascript:void(0)" onclick="copyToClipboard('{{ $shareUrl }}')">Bagikan</a>
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
    @foreach($pengumumans as $pengumuman)
    <div class="container-fluid blog py-5">
        <div class="container py-5">
            <div class="mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s">
                <h4 class="text-primary">Pengumuman</h4>
                <h1 class="display-4 mb-4">Berita terbaru</h1>
            </div>
            <div class="row g-4 justify-content-center">
                <!-- Kolom 1 -->
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="blog-item">
                        <div class="blog-img">
                            <img src="{{ asset('img/megapone-loker.jpg') }}" class="img-fluid rounded-top w-100" alt="">
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
            </div>
        </div>
    </div>
    @endforeach
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
                                        Silakan login terlebih dahulu menggunakan akun yang telah terdaftar pilih menu lamaran -> pilih lamaran yang kamu ingin lihat riwayatnya
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