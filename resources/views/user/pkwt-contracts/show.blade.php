@extends('layouts.app')

@section('title', 'Tanda Tangan Kontrak')

@push('styles')
<style>
    .secure-contract-viewer {
        background: #f3f4f6;
        border-radius: 10px;
        padding: 14px;
    }

    .secure-contract-page {
        background: #fff;
        border: 1px solid #e5e7eb;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
        margin: 0 auto;
        max-width: 850px;
        min-height: 900px;
        padding: 38px;
        position: relative;
    }

    .secure-contract-page::before {
        color: rgba(15, 23, 42, 0.06);
        content: @json(($contract->display_number ?: $contract->masked_no_ktp) . ' - ' . now()->format('Y-m-d H:i'));
        font-size: 38px;
        font-weight: 700;
        left: 50%;
        pointer-events: none;
        position: absolute;
        top: 50%;
        transform: translate(-50%, -50%) rotate(-28deg);
        white-space: nowrap;
        z-index: 0;
    }

    .secure-contract-content {
        color: #111827;
        font-size: .95rem;
        line-height: 1.7;
        overflow-x: auto;
        position: relative;
        z-index: 1;
    }

    .secure-contract-content p,
    .secure-contract-content ul,
    .secure-contract-content ol,
    .secure-contract-content table,
    .secure-contract-content blockquote {
        margin-bottom: 1rem;
    }

    .secure-contract-content table {
        border-collapse: collapse;
        width: 100%;
    }

    .secure-contract-content td,
    .secure-contract-content th {
        border: 1px solid #d1d5db;
        padding: 6px;
        vertical-align: top;
    }

    .secure-contract-content .contract-signature-slot {
        display: block;
        height: 86px;
        line-height: normal;
        margin: 4px 0;
        text-align: center;
    }

    .secure-contract-content .contract-signature-box {
        border: 0 !important;
        border-collapse: collapse;
        height: 86px;
        margin: 0;
        width: 100%;
    }

    .secure-contract-content .contract-signature-box td {
        border: 0 !important;
        height: 86px;
        padding: 0 !important;
        text-align: center;
        vertical-align: middle;
    }

    .signature-pad {
        background: #fff;
        border: 1px dashed #94a3b8;
        border-radius: 8px;
        height: 220px;
        touch-action: none;
        width: 100%;
    }

    @media (max-width: 576px) {
        .secure-contract-page {
            min-height: 680px;
            padding: 22px;
        }

        .secure-contract-page::before {
            font-size: 22px;
        }
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
        <div class="d-flex align-items-start align-items-md-center flex-column flex-md-row gap-2 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Kontrak Elektronik PKWT 1</h1>
                <div class="text-muted">{{ $contract->display_number ?: '-' }}</div>
            </div>
            <div class="ms-md-auto">
                <a href="{{ route('kontrak-pkwt.index') }}" class="btn btn-light rounded-pill px-4">Kembali</a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="secure-contract-viewer">
                    <div class="secure-contract-page">
                        <div class="secure-contract-content">
                            @if($html)
                                {!! $html !!}
                            @else
                                <div class="text-center text-muted py-5">
                                    Dokumen kontrak belum tersedia untuk ditampilkan.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="mb-3">Ringkasan</h5>
                        <dl class="row mb-0 small">
                            <dt class="col-5">Status</dt>
                            <dd class="col-7">{{ $contract->signature_status_label }}</dd>
                            <dt class="col-5">Tipe</dt>
                            <dd class="col-7">Kontrak PKWT 1</dd>
                            <dt class="col-5">Nama</dt>
                            <dd class="col-7">{{ $contract->nama ?: '-' }}</dd>
                            <dt class="col-5">No KTP</dt>
                            <dd class="col-7">{{ $contract->masked_no_ktp }}</dd>
                            <dt class="col-5">No PKWT</dt>
                            <dd class="col-7">{{ $contract->no_pkwt ?: '-' }}</dd>
                            <dt class="col-5">Jabatan</dt>
                            <dd class="col-7">{{ $contract->jabatan ?: '-' }}</dd>
                            <dt class="col-5">Periode</dt>
                            <dd class="col-7">
                                {{ optional($contract->tanggal_mulai_kontrak)->format('d M Y') ?: '-' }}
                                s/d
                                {{ optional($contract->tanggal_akhir_kontrak)->format('d M Y') ?: '-' }}
                            </dd>
                        </dl>

                        @if($contract->contract_file_path)
                            <a href="{{ route('kontrak-pkwt.download', $contract->id) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100 mt-3">
                                <i class="fa fa-file-pdf me-1"></i> Buka Dokumen Kontrak
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-2">Tanda Tangan</h5>
                        @if($contract->isSignableByCandidate())
                            <p class="small text-muted">Gunakan jari atau mouse di area tanda tangan. Tanda tangan ini hanya berlaku untuk kontrak ini.</p>

                            <form action="{{ route('kontrak-pkwt.sign', $contract->id) }}" method="POST" id="signatureForm">
                                @csrf
                                <canvas id="signaturePad" class="signature-pad"></canvas>
                                <input type="hidden" name="signature_data" id="signatureData">
                                @error('signature_data')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror

                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="clearSignature">
                                        Bersihkan
                                    </button>
                                </div>

                                <div class="form-check mt-3">
                                    <input class="form-check-input @error('consent') is-invalid @enderror" type="checkbox" name="consent" value="1" id="consent">
                                    <label class="form-check-label small" for="consent">
                                        {{ $consentText }}
                                    </label>
                                    @error('consent')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary w-100 mt-3">
                                    Tandatangani Kontrak
                                </button>
                            </form>
                        @elseif($contract->signature_status === 'signed')
                            <div class="alert alert-success small mb-0">
                                Kontrak ini sudah ditandatangani pada {{ optional($contract->signed_at)->format('d M Y H:i') ?: '-' }}.
                            </div>
                        @else
                            <div class="alert alert-secondary small mb-0">
                                Kontrak ini tidak tersedia untuk tanda tangan elektronik saat ini.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var canvas = document.getElementById('signaturePad');
        var form = document.getElementById('signatureForm');

        if (!canvas || !form) {
            return;
        }

        var context = canvas.getContext('2d');
        var drawing = false;
        var hasStroke = false;

        function resizeCanvas() {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            var rect = canvas.getBoundingClientRect();

            canvas.width = rect.width * ratio;
            canvas.height = rect.height * ratio;
            context.setTransform(1, 0, 0, 1, 0, 0);
            context.scale(ratio, ratio);
            context.lineWidth = 2;
            context.lineCap = 'round';
            context.strokeStyle = '#111827';
        }

        function point(event) {
            var rect = canvas.getBoundingClientRect();
            var source = event.touches ? event.touches[0] : event;

            return {
                x: source.clientX - rect.left,
                y: source.clientY - rect.top
            };
        }

        function start(event) {
            drawing = true;
            var p = point(event);
            context.beginPath();
            context.moveTo(p.x, p.y);
            event.preventDefault();
        }

        function move(event) {
            if (!drawing) {
                return;
            }

            var p = point(event);
            context.lineTo(p.x, p.y);
            context.stroke();
            hasStroke = true;
            event.preventDefault();
        }

        function end(event) {
            drawing = false;
            event.preventDefault();
        }

        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', end);
        canvas.addEventListener('mouseleave', end);
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        canvas.addEventListener('touchend', end, { passive: false });
        canvas.addEventListener('touchcancel', end, { passive: false });

        document.getElementById('clearSignature').addEventListener('click', function () {
            context.clearRect(0, 0, canvas.width, canvas.height);
            hasStroke = false;
        });

        form.addEventListener('submit', function (event) {
            if (!hasStroke) {
                event.preventDefault();
                alert('Tanda tangan wajib diisi.');
                return;
            }

            document.getElementById('signatureData').value = canvas.toDataURL('image/png');
        });
    })();
</script>
@endpush
