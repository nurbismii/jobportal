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
            <div class="col-lg-6 col-xl-4 wow fadeInUp" data-wow-delay="0.2s">
                <div class="blog-item">
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
                            <div class="small"><span class="fa fa-comment-alt text-primary"></span> 6 Comments</div>
                        </div>
                        <a href="#" class="h4 d-inline-block mb-3">Which allows you to pay down insurance bills</a>
                        <p class="mb-3">Lorem, ipsum dolor sit amet consectetur adipisicing elit. Eius libero soluta impedit eligendi? Quibusdam, laudantium.</p>
                        <a href="#" class="btn p-0">Read More <i class="fa fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Pengumumam End -->
@endsection