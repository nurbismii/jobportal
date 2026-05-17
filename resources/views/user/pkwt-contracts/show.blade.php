@extends('layouts.app')

@section('content')
<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5" style="max-width: 900px;">
        <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Tanda Tangan Kontrak</h4>
    </div>
</div>

<div class="container-fluid py-5 bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h1 class="h3 fw-bold mb-1">Kontrak PKWT 1</h1>
                                <div class="text-muted">{{ $contract->kode_kontrak ?: $contract->no_pkwt }}</div>
                            </div>
                            <span class="badge bg-warning text-dark">{{ ucwords(str_replace('_', ' ', $contract->signature_status)) }}</span>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-2"><strong>Nama:</strong> {{ $contract->nama }}</div>
                            <div class="col-md-6 mb-2"><strong>No KTP:</strong> {{ $contract->masked_no_ktp }}</div>
                            <div class="col-md-6 mb-2"><strong>Jabatan:</strong> {{ $contract->jabatan ?: '-' }}</div>
                            <div class="col-md-6 mb-2"><strong>Durasi:</strong> {{ $contract->durasi_kontrak ?: '-' }}</div>
                            <div class="col-md-6 mb-2"><strong>Mulai:</strong> {{ optional($contract->tanggal_mulai_kontrak)->format('d-m-Y') ?: '-' }}</div>
                            <div class="col-md-6 mb-2"><strong>Akhir:</strong> {{ optional($contract->tanggal_akhir_kontrak)->format('d-m-Y') ?: '-' }}</div>
                        </div>

                        @if($contract->contract_file_path)
                        <div class="mb-4">
                            <a href="{{ route('kontrak-pkwt.download', $contract->id) }}" target="_blank" class="btn btn-outline-primary rounded-pill px-4">
                                <i class="fa fa-file-pdf me-1"></i> Buka Dokumen Kontrak
                            </a>
                        </div>
                        @endif

                        @if($contract->contract_content)
                        <div class="border rounded p-4 bg-white mb-4" style="white-space: pre-wrap;">{{ $contract->contract_content }}</div>
                        @endif

                        <form method="POST" action="{{ route('kontrak-pkwt.sign', $contract->id) }}">
                            @csrf
                            <div class="form-check mb-3">
                                <input class="form-check-input @error('agreement') is-invalid @enderror" type="checkbox" name="agreement" value="1" id="agreement" required>
                                <label class="form-check-label" for="agreement">
                                    Saya telah membaca dan menyetujui isi kontrak PKWT 1 ini.
                                </label>
                                @error('agreement')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                Tanda Tangani Kontrak
                            </button>
                            <a href="{{ route('kontrak-pkwt.index') }}" class="btn btn-link">Kembali</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
