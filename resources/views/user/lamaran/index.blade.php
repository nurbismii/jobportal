@extends('layouts.app')

@section('content')
<!-- Carousel Start -->
<!-- Header Start -->
<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5" style="max-width: 900px;">
        <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Lamaran</h4>
    </div>
</div>
<!-- Header End -->

<div class="container-fluid blog py-5">
    <div class="container py-5">
        <div class="mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s">
            <h4 class="text-primary">Lamaran</h4>
            <h1 class="display-4 mb-4">Riwayat Pelamaran</h1>
            <p class="mb-0">
                Lorem ipsum dolor, sit amet consectetur adipisicing elit. Tenetur adipisci facilis cupiditate recusandae aperiam temporibus corporis itaque quis facere, numquam, ad culpa deserunt sint dolorem autem obcaecati, ipsam mollitia hic.
            </p>
        </div>
        <div class="row g-4 justify-content-center">
            <!-- Kolom 1 -->
            <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                <div class="blog-item">
                    <div class="blog-content p-4">
                        <div class="blog-comment d-flex justify-content-between mb-3">
                            <div class="small"><span class="fa fa-user text-primary"></span>PT VDNI</div>
                            <div class="small"><span class="fa fa-calendar text-primary"></span>{{ $lamaran->created_at }}</div>
                        </div>
                        <a href="#" class="h4 d-inline-block mb-3">{{ $lamaran->lowongan->nama_lowongan }}</a>
                        <p class="mb-3">{!! $lamaran->lowongan->kualifikas  !!}</p>
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

@endsection