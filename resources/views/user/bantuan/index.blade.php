@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('partials.lowongan.styles')
@endpush

@section('content')
@php
    $faqs = [
        [
            'question' => 'Bagaimana cara mengatasi KTP atau SIM B2 Umum tidak terbaca?',
            'answer' => '
                <ul class="mb-0 ps-3">
                    <li class="mb-1">Posisikan KTP dan SIM dalam keadaan tegak.</li>
                    <li class="mb-1">Pastikan hasil foto jelas, tidak blur, tidak pecah, dan semua informasi dapat dibaca.</li>
                    <li class="mb-1">Gunakan pencahayaan yang cukup dan ambil gambar dari jarak yang pas.</li>
                    <li class="mb-1">Pastikan tulisan pada SIM B2 Umum tidak tertutup hologram saat diunggah.</li>
                    <li class="mb-0">Jangan menambahkan tulisan lain di atas dokumen.</li>
                </ul>',
        ],
        [
            'question' => 'Bagaimana cara melihat status pelamaran yang dilakukan?',
            'answer' => 'Silakan masuk menggunakan akun yang telah terdaftar, lalu buka menu Lamaran dan pilih lamaran yang ingin Anda lihat riwayat prosesnya.',
        ],
        [
            'question' => 'Apa saja yang perlu dipersiapkan untuk melamar pekerjaan?',
            'answer' => '
                <ul class="mb-0 ps-3">
                    <li>CV</li>
                    <li>KTP</li>
                    <li>Surat lamaran</li>
                    <li>Kartu Keluarga</li>
                    <li>Ijazah terakhir</li>
                    <li>Sertifikat vaksin</li>
                    <li>Surat Keterangan Catatan Kepolisian (SKCK)</li>
                    <li>Kartu Pencari Kerja (AK1)</li>
                    <li>Pas foto 3x4</li>
                    <li>NPWP</li>
                    <li>SIM B2 Umum jika dibutuhkan oleh posisi yang dilamar</li>
                </ul>',
        ],
        [
            'question' => 'Di manakah hasil seleksi akan diumumkan?',
            'answer' => 'Hasil seleksi diumumkan pada halaman lamaran di website rekrutmen VDNI dan akan diberitahukan secara personal melalui sarana komunikasi tercepat yang Anda daftarkan.',
        ],
        [
            'question' => 'Berapa lama waktu seleksi di setiap tahapan?',
            'answer' => 'Durasi setiap tahapan seleksi dapat berbeda. Informasi lanjutan akan disampaikan langsung kepada peserta yang dinyatakan lolos pada tahap terkait.',
        ],
        [
            'question' => 'Siapa yang dapat dihubungi jika mengalami kendala teknis saat pendaftaran?',
            'answer' => 'Jika Anda mengalami kendala teknis selama proses pendaftaran, silakan hubungi tim support melalui email <a href="mailto:vdnirekrutmen88@gmail.com">vdnirekrutmen88@gmail.com</a>.',
        ],
        [
            'question' => 'Apakah saya dapat melamar lebih dari satu posisi secara bersamaan?',
            'answer' => 'Tidak. Anda perlu menunggu proses lamaran pada posisi yang sedang diajukan selesai terlebih dahulu sebelum melamar posisi lainnya.',
        ],
    ];
@endphp

<div class="container-fluid bg-breadcrumb help-page-banner">
    <div class="container help-page-hero text-center py-5">
        <span class="help-page-hero__eyebrow wow fadeInDown" data-wow-delay="0.1s">
            <i class="fa-solid fa-headset"></i>
            Pusat Bantuan
        </span>
        <h1 class="text-white display-4 mb-3 wow fadeInDown" data-wow-delay="0.2s">Panduan cepat untuk proses rekrutmen V-HIRE</h1>
        <p class="help-page-hero__text wow fadeInUp" data-wow-delay="0.3s">
            Temukan jawaban seputar dokumen, tahapan seleksi, dan kendala teknis agar proses melamar kerja tetap lancar dari awal sampai selesai.
        </p>
    </div>
</div>

<div class="container-fluid faq-help-section py-5">
    <div class="container py-5">
        <div class="faq-help">
            <div class="row g-4 g-xl-5 align-items-start">
                <div class="col-xl-7">
                    <div class="faq-help__header wow fadeInLeft" data-wow-delay="0.2s">
                        <span class="faq-help__eyebrow">
                            <i class="fa-solid fa-life-ring"></i>
                            Bantuan
                        </span>
                        <h2 class="faq-help__title">Pertanyaan yang paling sering muncul saat pelamar memulai prosesnya</h2>
                        <p class="faq-help__lead">
                            Halaman ini merangkum jawaban penting yang paling sering dibutuhkan pelamar, mulai dari upload dokumen, pengecekan status lamaran, hingga jalur bantuan teknis resmi.
                        </p>
                    </div>

                    <div class="accordion faq-help__accordion wow fadeInUp" data-wow-delay="0.3s" id="helpCenterAccordion">
                        @foreach($faqs as $index => $faq)
                        <div class="accordion-item faq-help__item">
                            <h2 class="accordion-header" id="helpCenterHeading{{ $index }}">
                                <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#helpCenterCollapse{{ $index }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="helpCenterCollapse{{ $index }}">
                                    <span class="faq-help__number">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span>{{ $faq['question'] }}</span>
                                </button>
                            </h2>
                            <div id="helpCenterCollapse{{ $index }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" aria-labelledby="helpCenterHeading{{ $index }}" data-bs-parent="#helpCenterAccordion">
                                <div class="accordion-body">
                                    {!! $faq['answer'] !!}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="faq-help-card wow fadeInRight" data-wow-delay="0.4s">
                        <div class="faq-help-card__media">
                            <img src="{{ asset('img/faq-1.png') }}" class="img-fluid" alt="Ilustrasi pusat bantuan rekrutmen">
                        </div>
                        <div class="faq-help-card__body">
                            <span class="faq-help-card__eyebrow">Support Rekrutmen</span>
                            <h3 class="faq-help-card__title">Masih butuh arahan sebelum melamar?</h3>
                            <p class="faq-help-card__text">
                                Gunakan panduan resmi untuk memahami alur pengisian biodata dan unggah dokumen, atau buka daftar lowongan untuk melihat posisi yang sedang tersedia saat ini.
                            </p>

                            <div class="faq-help-card__contact">
                                <div class="faq-help-card__contact-item">
                                    <i class="fa-regular fa-envelope"></i>
                                    <div>
                                        <small>Email Support</small>
                                        <a href="mailto:vdnirekrutmen88@gmail.com">vdnirekrutmen88@gmail.com</a>
                                    </div>
                                </div>
                                <div class="faq-help-card__contact-item">
                                    <i class="fa-solid fa-clock"></i>
                                    <div>
                                        <small>Status Lamaran</small>
                                        <span>Cek melalui menu Lamaran setelah login</span>
                                    </div>
                                </div>
                            </div>

                            <div class="faq-help-card__actions">
                                <a href="{{ route('lowongan-kerja.index') }}" class="btn btn-primary rounded-pill py-3 px-4">
                                    Lihat Lowongan
                                </a>
                                <a href="{{ asset('pdf/MANUAL BOOK V-HIRE (1).pdf') }}" target="_blank" class="btn btn-outline-primary rounded-pill py-3 px-4">
                                    Buka Panduan Resmi
                                </a>
                                <a href="{{ route('beranda') }}" class="btn btn-light rounded-pill py-3 px-4">
                                    Kembali ke Beranda
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
