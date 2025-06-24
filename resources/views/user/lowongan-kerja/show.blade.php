@extends('layouts.app')

@section('content')

<!-- Lowongan Kerja Start -->
<div class="container-fluid service py-5">
    <div class="container">
        <div class="alert border-2 border-primary shadow-sm rounded-3">
            <div class="mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s">
                <h2 class="fw-bold text-primary mb-3">{{ $lowongan->nama_lowongan}}</h2>
                <p class="mb-4">{!! $lowongan->kualifikasi !!}</p>
            </div>
            <div class="d-flex justify-content-end mt-3 mx-auto">
                <p class="mb-1 text-primary">Aktif lamaran : {{ tanggalIndo($lowongan->tanggal_mulai) }} – {{ tanggalIndo($lowongan->tanggal_berakhir) }}</p>
            </div>
            <div class="d-flex justify-content-end mx-auto">
                @if(strtolower($lowongan->status_lowongan) == 'aktif')
                <span class="mb-4 badge bg-success">{{ $lowongan->status_lowongan }}</span>
                @else
                <span class="mb-4 badge bg-danger">{{ $lowongan->status_lowongan }}</span>
                @endif
            </div>
            <div class="d-flex justify-content-end mt-3 mx-auto">
                <a class="btn btn-light rounded-pill py-2 px-3 me-2" href="{{ route('lowongan-kerja.index') }}">Kembali</a>
                @if(strtolower($lowongan->status_lowongan) == 'aktif')
                <a class="btn btn-primary rounded-pill py-2 px-3 me-2" data-bs-toggle="modal" data-bs-target="#konfirmasi-lamaran">Lamar</a>
                @endif
                <!-- Lowongan lihat semua end -->
            </div>
        </div>
    </div>
</div>
<!-- Lowongan Kerja End -->

<!-- Modal -->
<div class="modal fade" id="konfirmasi-lamaran" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Konfirmasi Lamaran</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('lowongan-kerja.store') }}" method="post">
                @csrf
                <div class="modal-body">
                    <p class="text-primary fw-bold">Kamu yakin ingin melamar posisi {{$lowongan->nama_lowongan}}?</p>
                    <input type="hidden" name="loker_id" value="{{ $lowongan->id }}" readonly>
                    <input type="hidden" name="biodata_id" value="{{ $biodata->id }}" readonly>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Ya, Lamar Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection