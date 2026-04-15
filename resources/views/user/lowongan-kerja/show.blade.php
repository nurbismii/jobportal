@extends('layouts.app')

@section('content')

@push('styles')
@include('partials.lowongan.styles')
@endpush

@php
    $isActive = strtolower($lowongan->status_lowongan) === 'aktif';
@endphp

<!-- Lowongan Kerja Start -->
<div class="container-fluid service py-4">
    <div class="container">
        @if(Auth::user() && $fieldLabels)
        <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
            <strong>Perhatian!</strong> Harap perbarui dokumen jika ada perubahan
            <a href="{{ route('biodata.index') }}#step5" class="btn btn-sm btn-warning mr-2"> Perbarui Dokumen </a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
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

        <article class="job-detail-card">
            <div class="job-detail-card__body">
                <div class="job-detail-card__header">
                    <div class="job-detail-card__heading">
                        <div class="job-detail-card__icon">
                            <i class="fa fa-briefcase"></i>
                        </div>
                        <div>
                            <span class="job-detail-card__eyebrow">Detail Lowongan</span>
                            <h1 class="job-detail-card__title">{{ $lowongan->nama_lowongan }}</h1>
                            <p class="job-detail-card__subtitle">
                                Tinjau kualifikasi posisi ini dengan saksama. Jika sudah sesuai dengan profil Anda, lanjutkan untuk mengajukan lamaran.
                            </p>
                        </div>
                    </div>

                    <span class="job-card__badge {{ $isActive ? 'job-card__badge--active' : 'job-card__badge--inactive' }}">
                        {{ $lowongan->status_lowongan }}
                    </span>
                </div>

                <div class="job-detail-card__meta-grid">
                    <div class="job-detail-card__meta-item">
                        <span class="job-detail-card__meta-icon">
                            <i class="fa fa-calendar-alt"></i>
                        </span>
                        <div>
                            <span class="job-detail-card__meta-label">Periode Lowongan</span>
                            <span class="job-detail-card__meta-value">
                                {{ tanggalIndo($lowongan->tanggal_mulai) }} &ndash; {{ tanggalIndo($lowongan->tanggal_berakhir) }}
                            </span>
                        </div>
                    </div>

                    <div class="job-detail-card__meta-item">
                        <span class="job-detail-card__meta-icon">
                            <i class="fa fa-info-circle"></i>
                        </span>
                        <div>
                            <span class="job-detail-card__meta-label">Status Pendaftaran</span>
                            <span class="job-detail-card__meta-value">
                                {{ $isActive ? 'Pendaftaran sedang dibuka untuk posisi ini.' : 'Pendaftaran untuk posisi ini sudah ditutup.' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="job-detail-card__section">
                    <span class="job-detail-card__section-label">Kualifikasi dan Persyaratan</span>
                    <div class="job-detail-card__content">
                        {!! $lowongan->kualifikasi !!}
                    </div>
                </div>

                <div class="job-detail-card__actions">
                    <a class="btn btn-light" href="{{ route('lowongan-kerja.index') }}">
                        <i class="fa fa-arrow-left me-2"></i>Kembali
                    </a>

                    @if($isActive)
                        @if(!Auth::user())
                        <a class="btn btn-primary" href="{{ route('login') }}">
                            <i class="fa fa-sign-in-alt me-2"></i>Masuk / Buat Akun
                        </a>
                        @else
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#konfirmasi-lamaran">
                            <i class="fa fa-paper-plane me-2"></i>Lamar Posisi Ini
                        </button>
                        @endif
                    @endif
                </div>
            </div>
        </article>
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
                    <p class="text-primary fw-bold">Kamu yakin ingin melamar posisi {{ $lowongan->nama_lowongan }}?</p>
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

@push('scripts')
<script>
    document.getElementById('form-lamaran').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('btn-submit-lamaran');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengirim...';
    });
</script>
@endpush

@endsection
