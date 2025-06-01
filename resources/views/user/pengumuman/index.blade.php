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

<!-- CSS untuk membatasi deskripsi 2 baris -->
<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<!-- Pengumuman Start -->
<div class="container-fluid blog py-5">
    <div class="container py-5">
        <div class="mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s">
            <h4 class="text-primary">Pengumuman</h4>
            <h1 class="display-4 mb-4">Berita terbaru</h1>
            <p class="mb-0">
                Lorem ipsum dolor sit amet consectetur adipisicing elit. Tenetur adipisci facilis cupiditate recusandae aperiam temporibus corporis itaque quis facere.
            </p>
        </div>
        <div class="row g-4 justify-content-center">
            <!-- Blog Item 1 -->
            <div class="col-md-6 col-lg-4 wow fadeInUp" data-wow-delay="0.1s">
                <div class="blog-item h-100">
                    <div class="blog-img">
                        <img src="{{ asset('user/img/blog-1.png') }}" class="img-fluid rounded-top w-100" alt="">
                        <div class="blog-categiry py-2 px-4">
                            <span>Business</span>
                        </div>
                    </div>
                    <div class="blog-content p-4">
                        <div class="blog-comment d-flex justify-content-between mb-3">
                            <div class="small"><span class="fa fa-user text-primary"></span> Martin.C</div>
                            <div class="small"><span class="fa fa-calendar text-primary"></span> 30 Dec 2025</div>
                        </div>
                        <a href="#" class="h4 d-inline-block mb-3">Which allows you to pay down insurance bills</a>
                        <p class="mb-3 line-clamp-2">Lorem, ipsum dolor sit amet consectetur adipisicing elit. Eius libero soluta impedit eligendi? Quibusdam, laudantium.</p>
                        <div class="d-flex justify-content-end mt-auto">
                            <a href="#" class="btn p-0" style="color: #0056B3;">Baca Detail <i class="fa fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Blog Item 2 -->
            <div class="col-md-6 col-lg-4 wow fadeInUp" data-wow-delay="0.2s">
                <div class="blog-item h-100">
                    <div class="blog-img">
                        <img src="{{ asset('user/img/blog-2.png') }}" class="img-fluid rounded-top w-100" alt="">
                        <div class="blog-categiry py-2 px-4">
                            <span>HR</span>
                        </div>
                    </div>
                    <div class="blog-content p-4">
                        <div class="blog-comment d-flex justify-content-between mb-3">
                            <div class="small"><span class="fa fa-user text-primary"></span> Yumna.A</div>
                            <div class="small"><span class="fa fa-calendar text-primary"></span> 21 Jan 2025</div>
                        </div>
                        <a href="#" class="h4 d-inline-block mb-3">Tips mengelola karyawan baru di area kerja besar</a>
                        <p class="mb-3 line-clamp-2">Penerimaan karyawan tidak hanya soal teknis, tapi juga tentang adaptasi dan komunikasi lintas tim.</p>
                        <div class="d-flex justify-content-end mt-auto">
                            <a href="#" class="btn p-0" style="color: #0056B3;">Baca Detail <i class="fa fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Blog Item 3 -->
            <div class="col-md-6 col-lg-4 wow fadeInUp" data-wow-delay="0.3s">
                <div class="blog-item h-100">
                    <div class="blog-img">
                        <img src="{{ asset('user/img/blog-3.png') }}" class="img-fluid rounded-top w-100" alt="">
                        <div class="blog-categiry py-2 px-4">
                            <span>Info</span>
                        </div>
                    </div>
                    <div class="blog-content p-4">
                        <div class="blog-comment d-flex justify-content-between mb-3">
                            <div class="small"><span class="fa fa-user text-primary"></span> Admin</div>
                            <div class="small"><span class="fa fa-calendar text-primary"></span> 10 Feb 2025</div>
                        </div>
                        <a href="#" class="h4 d-inline-block mb-3">Sistem HRIS baru akan segera diluncurkan</a>
                        <p class="mb-3 line-clamp-2">HRIS internal dibuat untuk mengelola pengajuan izin, cuti, SKS dan konseling.</p>
                        <div class="d-flex justify-content-end mt-auto">
                            <a href="#" class="btn p-0" style="color: #0056B3;">Baca Detail <i class="fa fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Pengumuman End -->

@endsection