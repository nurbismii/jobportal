@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ versioned_asset('user/css/vhire-custom.css') }}">
@include('partials.syarat-ketentuan-styles')
@endpush

@section('content')
@php
    $approvedAtText = $approvedAt ? $approvedAt->format('d/m/Y H:i') : '-';
@endphp

<div class="container terms-access-page py-5">
    <div class="terms-access-shell">
        <div class="terms-access-header">
            <div>
                <span class="terms-access-eyebrow">Dokumen Persetujuan</span>
                <h4 class="terms-access-title">Syarat dan Ketentuan yang Disetujui</h4>
                <p class="terms-access-subtitle">
                    Halaman ini menampilkan snapshot syarat dan ketentuan rekrutmen yang tersimpan saat Anda melakukan persetujuan.
                </p>
            </div>

            <a href="{{ route('biodata.index') }}#step6" class="btn btn-light">
                Kembali ke Upload Berkas
            </a>
        </div>

        <div class="terms-access-meta">
            <div>
                <span>Nama</span>
                <strong>{{ auth()->user()->name }}</strong>
            </div>
            <div>
                <span>No. KTP</span>
                <strong>{{ auth()->user()->no_ktp }}</strong>
            </div>
            <div>
                <span>Disetujui Pada</span>
                <strong>{{ $approvedAtText }}</strong>
            </div>
        </div>

        <div class="terms-document-frame terms-document-frame--approved">
            <article class="terms-document terms-document--approved">
                {!! $biodata->status_pernyataan !!}
            </article>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const approvedDocument = document.querySelector('.terms-document--approved');

        if (!approvedDocument) {
            return;
        }

        approvedDocument.querySelectorAll('.terms-document__checkbox:not(input)').forEach(function(checkbox) {
            checkbox.classList.add('terms-document__checkbox--checked');
        });

        approvedDocument.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
            checkbox.checked = true;
            checkbox.disabled = true;
            checkbox.classList.add('terms-document__checkbox', 'terms-document-checkbox');
        });
    });
</script>
@endpush
