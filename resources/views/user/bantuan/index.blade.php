@extends('layouts.app')

@section('content')
<!-- Carousel Start -->
<!-- Header Start -->
<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5" style="max-width: 900px;">
        <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Bantuan</h4>
    </div>
</div>
<!-- Header End -->

<!-- FAQs Start -->
<div class="container-fluid faq-section bg-light py-5">
    <div class="container py-5">
        <div class="row g-5 align-items-center">
            <div class="col-xl-6 wow fadeInLeft" data-wow-delay="0.2s">
                <div class="h-100">
                    <div class="mb-5">
                        <h4 class="text-primary">Bantuan</h4>
                        <h1 class="display-4 mb-0">Pertanyaan umum yang sering diajukan</h1>
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
                                    </ul>
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
@endsection