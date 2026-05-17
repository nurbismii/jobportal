@extends('layouts.app')

@section('content')
<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5" style="max-width: 900px;">
        <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Kontrak PKWT 1</h4>
    </div>
</div>

<div class="container-fluid py-5 bg-light">
    <div class="container py-5">
        <div class="row g-4 justify-content-center">
            @forelse($contracts as $contract)
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h3 class="h4 fw-bold mb-1">{{ $contract->jabatan ?: 'PKWT 1' }}</h3>
                                <div class="text-muted small">{{ $contract->kode_kontrak ?: $contract->no_pkwt }}</div>
                            </div>
                            <span class="badge bg-warning text-dark">{{ ucwords(str_replace('_', ' ', $contract->signature_status)) }}</span>
                        </div>
                        <div class="row small text-muted mb-3">
                            <div class="col-sm-6 mb-2">
                                <strong>Mulai:</strong> {{ optional($contract->tanggal_mulai_kontrak)->format('d-m-Y') ?: '-' }}
                            </div>
                            <div class="col-sm-6 mb-2">
                                <strong>Akhir:</strong> {{ optional($contract->tanggal_akhir_kontrak)->format('d-m-Y') ?: '-' }}
                            </div>
                            <div class="col-sm-6 mb-2">
                                <strong>Durasi:</strong> {{ $contract->durasi_kontrak ?: '-' }}
                            </div>
                            <div class="col-sm-6 mb-2">
                                <strong>No KTP:</strong> {{ $contract->masked_no_ktp }}
                            </div>
                        </div>
                        <a href="{{ route('kontrak-pkwt.show', $contract->id) }}" class="btn btn-primary rounded-pill px-4">
                            Lihat Kontrak
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="text-center p-5 my-4 border rounded-3 shadow-sm bg-white">
                    <i class="fa fa-file-signature fa-3x text-primary mb-3"></i>
                    <h4 class="fw-bold mb-2">Belum ada kontrak yang perlu ditandatangani</h4>
                    <p class="text-muted mb-0">Kontrak akan tampil di sini selama proses onboarding jika HR/admin mengaktifkan metode tanda tangan elektronik.</p>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
