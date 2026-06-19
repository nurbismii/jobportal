@extends('layouts.app-pic')

@push('styles')
@include('partials.syarat-ketentuan-styles')
<style>
    .terms-proof-page {
        background: #f3f6fa;
        padding-bottom: 24px;
    }

    .terms-proof-toolbar {
        align-items: center;
        display: flex;
        gap: 10px;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .terms-proof-toolbar h1 {
        color: #1f2937;
        font-size: 22px;
        font-weight: 800;
        margin: 0;
    }

    .terms-proof-card {
        border: 1px solid #d8dee8;
        border-radius: 8px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .terms-proof-header {
        align-items: flex-start;
        background: #ffffff;
        border-bottom: 1px solid #d8dee8;
        display: flex;
        gap: 16px;
        justify-content: space-between;
        padding: 20px 22px;
    }

    .terms-proof-header h2 {
        color: #111827;
        font-size: 20px;
        font-weight: 800;
        margin: 0 0 6px;
    }

    .terms-proof-header p {
        color: #64748b;
        margin: 0;
    }

    .terms-proof-badge {
        background: #e8f3ff;
        border: 1px solid #b8dcff;
        border-radius: 999px;
        color: #0f5fa8;
        display: inline-block;
        font-size: 12px;
        font-weight: 800;
        padding: 6px 10px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .terms-proof-body {
        background: #ffffff;
        padding: 22px;
    }

    .terms-proof-notice {
        background: #fff8e6;
        border: 1px solid #f4d98a;
        border-radius: 6px;
        color: #7a5300;
        font-weight: 700;
        margin-bottom: 16px;
        padding: 12px 14px;
    }

    .terms-proof-meta {
        background: #d8dee8;
        display: grid;
        gap: 1px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-bottom: 20px;
    }

    .terms-proof-meta div {
        background: #f8fafc;
        min-width: 0;
        padding: 13px 15px;
    }

    .terms-proof-meta span {
        color: #64748b;
        display: block;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
        text-transform: uppercase;
    }

    .terms-proof-meta strong {
        color: #111827;
        display: block;
        overflow-wrap: anywhere;
    }

    .terms-proof-print-footer {
        color: #64748b;
        display: none;
        font-size: 11px;
        margin-top: 12px;
        text-align: center;
    }

    @media (max-width: 991.98px) {
        .terms-proof-meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .terms-proof-toolbar,
        .terms-proof-header {
            display: block;
        }

        .terms-proof-toolbar .btn-group,
        .terms-proof-header .terms-proof-badge {
            margin-top: 12px;
        }

        .terms-proof-toolbar .btn,
        .terms-proof-toolbar .btn-group {
            width: 100%;
        }

        .terms-proof-toolbar .btn-group {
            display: grid;
            gap: 8px;
        }

        .terms-proof-meta {
            grid-template-columns: 1fr;
        }
    }

    @media print {
        @page {
            margin: 12mm;
            size: A4;
        }

        body {
            background: #ffffff !important;
        }

        #accordionSidebar,
        .topbar,
        .sticky-footer,
        .scroll-to-top,
        .no-print {
            display: none !important;
        }

        #wrapper,
        #content-wrapper,
        #content,
        .container-fluid {
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        .terms-proof-page,
        .terms-proof-card,
        .terms-proof-body,
        .terms-proof-header {
            background: #ffffff !important;
            border: 0 !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .terms-proof-header {
            border-bottom: 1px solid #111827 !important;
            display: block;
            margin-bottom: 12px !important;
            padding-bottom: 10px !important;
        }

        .terms-proof-header h2 {
            font-size: 16pt;
        }

        .terms-proof-badge {
            border-color: #111827;
            color: #111827;
            margin-top: 8px;
        }

        .terms-proof-notice {
            border-color: #111827;
            color: #111827;
            font-size: 10pt;
            margin-bottom: 10px;
        }

        .terms-proof-meta {
            border: 1px solid #111827;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-bottom: 12px;
        }

        .terms-proof-meta div {
            border: 1px solid #111827;
            padding: 7px 8px;
        }

        .terms-proof-meta span {
            color: #111827;
            font-size: 8pt;
        }

        .terms-proof-meta strong {
            color: #111827;
            font-size: 10pt;
        }

        .terms-document-frame--approved {
            background: #ffffff;
            border: 0;
            box-shadow: none;
            padding: 0;
        }

        .terms-document {
            border: 0;
            box-shadow: none;
            font-size: 11pt;
            max-width: none;
            min-height: 0;
            padding: 0;
        }

        .terms-proof-print-footer {
            display: block;
        }
    }
</style>
@endpush

@section('content-admin')
@php
    $approvedAtText = $approvedAt ? $approvedAt->format('d/m/Y H:i') : '-';
    $printedAtText = $printedAt ? $printedAt->format('d/m/Y H:i') : '-';
    $termsVersionText = $biodata->syarat_ketentuan_id ? '#' . $biodata->syarat_ketentuan_id : '-';
@endphp

<div class="terms-proof-page">
    <div class="terms-proof-toolbar no-print">
        <h1>Bukti Persetujuan Syarat dan Ketentuan</h1>

        <div class="btn-group">
            <a href="{{ route('pengguna.show', $pengguna->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <button type="button" class="btn btn-primary" id="printTermsProof">
                <i class="fas fa-print"></i> Cetak
            </button>
        </div>
    </div>

    <div class="terms-proof-card" id="termsPrintArea">
        <div class="terms-proof-header">
            <div>
                <h2>Dokumen Persetujuan Rekrutmen</h2>
                <p>Snapshot syarat dan ketentuan yang tersimpan saat pengguna melakukan persetujuan.</p>
            </div>

            <span class="terms-proof-badge">Bukti Persetujuan</span>
        </div>

        <div class="terms-proof-body">
            <div class="terms-proof-notice">
                Dokumen ini memakai salinan persetujuan yang tersimpan pada biodata pengguna. Jika master syarat dan ketentuan berubah setelah tanggal persetujuan, isi di halaman ini tetap mengikuti versi yang telah disetujui pengguna.
            </div>

            <div class="terms-proof-meta">
                <div>
                    <span>Nama</span>
                    <strong>{{ $pengguna->name ?? '-' }}</strong>
                </div>
                <div>
                    <span>No. KTP</span>
                    <strong>{{ $pengguna->no_ktp ?? $biodata->no_ktp ?? '-' }}</strong>
                </div>
                <div>
                    <span>Email</span>
                    <strong>{{ $pengguna->email ?? '-' }}</strong>
                </div>
                <div>
                    <span>Disetujui Pada</span>
                    <strong>{{ $approvedAtText }}</strong>
                </div>
                <div>
                    <span>Versi Syarat</span>
                    <strong>{{ $termsVersionText }}</strong>
                </div>
                <div>
                    <span>ID Biodata</span>
                    <strong>{{ $biodata->id }}</strong>
                </div>
                <div>
                    <span>ID Pengguna</span>
                    <strong>{{ $pengguna->id }}</strong>
                </div>
                <div>
                    <span>Dicetak Pada</span>
                    <strong>{{ $printedAtText }}</strong>
                </div>
            </div>

            <div class="terms-document-frame terms-document-frame--approved">
                <article class="terms-document terms-document--approved">
                    {!! $biodata->status_pernyataan !!}
                </article>
            </div>

            <div class="terms-proof-print-footer">
                Dicetak dari V-Hire pada {{ $printedAtText }}.
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const approvedDocument = document.querySelector('.terms-document--approved');
        const printButton = document.getElementById('printTermsProof');

        if (approvedDocument) {
            approvedDocument.querySelectorAll('.terms-document__checkbox:not(input)').forEach(function(checkbox) {
                checkbox.classList.add('terms-document__checkbox--checked');
            });

            approvedDocument.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
                checkbox.checked = true;
                checkbox.disabled = true;
                checkbox.classList.add('terms-document__checkbox', 'terms-document-checkbox');
            });
        }

        if (printButton) {
            printButton.addEventListener('click', function() {
                window.print();
            });
        }
    });
</script>
@endpush
