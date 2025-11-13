@extends('layouts.app')

@section('content')

<!-- Lowongan Kerja Start -->
<div class="container-fluid service py-4">
    <div class="container">
        @if(Auth::user() && $fieldLabels)
        @php
        $dataKosong = [];
        foreach ($fieldLabels as $field => $label) {
        if (empty($biodata->$field)) {
        $dataKosong[] = $label;
        }
        }
        @endphp

        @if(count($dataKosong) > 0)
        <div class="alert alert-warning mt-3">
            <strong>Perhatian!</strong> Mohon lengkapi data berikut sebelum melamar:
            <ul class="mb-0">
                @foreach($dataKosong as $label)
                <li>{{ $label }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        @endif
        <div class="alert border-2 border-primary shadow-sm rounded-3">
            <div class="mx-auto pb-2 wow fadeInUp" data-wow-delay="0.2s">
                <h1 class="fw-bold text-primary mb-4">{{ $lowongan->nama_lowongan}}</h1>
                <p class="mb-0">{!! $lowongan->kualifikasi !!}</p>
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
                <!-- Lowongan lihat semua start -->
                @if(!Auth::user())
                <a class="btn btn-primary rounded-pill py-2 px-3 me-2" href="{{ route('login') }}">Masuk/Buat Akun?</a>
                @else
                <a class="btn btn-primary rounded-pill py-2 px-3 me-2" data-bs-toggle="modal" data-bs-target="#konfirmasi-lamaran">Lamar</a>
                @endif
                @endif
                <!-- Lowongan lihat semua end -->
            </div>
        </div>
    </div>
</div>
<!-- Lowongan Kerja End -->

<!-- Modal lamar-->
<div class="modal fade" id="konfirmasi-lamaran" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Konfirmasi Lamaran</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-lamaran" action="{{ route('lowongan-kerja.store') }}" method="post">
                @csrf
                <div class="modal-body">
                    <p class="text-primary fw-bold">Kamu yakin ingin melamar posisi {{$lowongan->nama_lowongan}}?</p>
                    <input type="hidden" name="loker_id" value="{{ $lowongan->id }}" readonly>
                    <input type="hidden" name="biodata_id" value="{{ $biodata->id ?? '' }}" readonly>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btn-submit-lamaran" class="btn btn-primary">Ya, Lamar Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('form-lamaran').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('btn-submit-lamaran');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengirim...';
    });
</script>

@endsection