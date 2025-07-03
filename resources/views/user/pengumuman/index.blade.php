@extends('layouts.app')

@section('content')
<!-- Carousel Start -->
<!-- Header Start -->
<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5" style="max-width: 900px;">
        <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Pengumuman</h4>
    </div>
</div>
<!-- Header End -->

<!-- Pengumumam Start -->
@forelse($pengumumans as $pengumuman)
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
        </div>
    </div>
</div>
@empty
<div class="container py-5">
    <div class="text-center p-5 my-4 border rounded-3 shadow-sm bg-light">
        <i class="fa fa-bullhorn fa-3x text-primary mb-3"></i>
        <h4 class="fw-bold mb-2">Belum ada pengumuman terbaru</h4>
        <p class="text-muted mb-3">Kami akan segera memperbarui informasi pengumuman di sini.</p>
        <a href="{{ url('/') }}" class="btn btn-primary rounded-pill px-4">Kembali ke Beranda</a>
    </div>
</div>
@endforelse
<!-- Pengumumam End -->
@endsection