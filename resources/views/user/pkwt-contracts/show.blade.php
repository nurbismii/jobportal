@extends('layouts.app')

@push('styles')
<style>
    .pkwt-contract-content {
        color: #2f3542;
        font-size: .98rem;
        line-height: 1.75;
        overflow-x: auto;
    }

    .pkwt-contract-content p,
    .pkwt-contract-content ul,
    .pkwt-contract-content ol,
    .pkwt-contract-content table,
    .pkwt-contract-content blockquote {
        margin-bottom: 1rem;
    }

    .pkwt-contract-content table {
        width: 100%;
        border-collapse: collapse;
    }

    .pkwt-contract-content th,
    .pkwt-contract-content td {
        border: 1px solid #dfe4ea;
        padding: .65rem .75rem;
        vertical-align: top;
    }

    .pkwt-signature-panel {
        border: 1px solid #dfe4ea;
        border-radius: 8px;
        background: #fff;
        padding: 1rem;
    }

    .pkwt-signature-preview {
        min-height: 88px;
        border: 1px dashed #a4b0be;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        color: #1e3799;
        font-family: "Segoe Script", "Brush Script MT", cursive;
        font-size: 1.7rem;
        line-height: 1.25;
        text-align: center;
        word-break: break-word;
    }

    .pkwt-company-signature-preview {
        color: #6c757d;
        font-family: inherit;
        font-size: 1rem;
    }
</style>
@endpush

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

                        @php($displayableContractContent = $contract->displayable_contract_content)
                        @if($displayableContractContent)
                        <div class="border rounded p-4 bg-white mb-4 pkwt-contract-content">{!! $displayableContractContent !!}</div>
                        @endif

                        <form method="POST" action="{{ route('kontrak-pkwt.sign', $contract->id) }}">
                            @csrf
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="pkwt-signature-panel h-100">
                                        <div class="small text-muted mb-3">Pihak Perusahaan</div>
                                        <div class="pkwt-signature-preview pkwt-company-signature-preview">
                                            Perwakilan Perusahaan
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="pkwt-signature-panel h-100">
                                        <label for="candidate_signature" class="form-label fw-bold">Tanda Tangan Kandidat</label>
                                        <div id="candidate-signature-preview" class="pkwt-signature-preview mb-3">
                                            {{ old('candidate_signature', $contract->nama) ?: 'Nama kandidat' }}
                                        </div>
                                        <input
                                            class="form-control @error('candidate_signature') is-invalid @enderror"
                                            type="text"
                                            name="candidate_signature"
                                            id="candidate_signature"
                                            value="{{ old('candidate_signature', $contract->nama) }}"
                                            placeholder="Ketik nama lengkap sebagai tanda tangan"
                                            maxlength="255"
                                            required
                                        >
                                        @error('candidate_signature')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var signatureInput = document.getElementById('candidate_signature');
        var signaturePreview = document.getElementById('candidate-signature-preview');

        if (!signatureInput || !signaturePreview) {
            return;
        }

        signatureInput.addEventListener('input', function () {
            signaturePreview.textContent = signatureInput.value.trim() || 'Nama kandidat';
        });
    });
</script>
@endpush
