@extends('layouts.app')

@section('content')
<!-- Header -->
<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5">
        <h4 class="text-white display-4 mb-4">Detail Lamaran</h4>
    </div>
</div>

<!-- Content -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- Card Lowongan -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <h4 class="fw-bold mb-0">Detail Lowongan</h4>
                        <a href="{{ route('lamaran.index') }}" class="ms-auto btn btn-link text-decoration-none">Tutup</a>

                    </div>
                    <p class="mb-1"><strong>Posisi:</strong> {{ $lamaran->lowongan->nama_lowongan }}</p>
                    <p class="mb-1"><strong>Status Lamaran:</strong>
                        @if ($lamaran->status_lamaran == '1')
                        <span class="badge bg-success">Aktif</span>
                        @else
                        <span class="badge bg-secondary">Tidak Aktif</span>
                        @endif
                    </p>
                </div>
            </div>

            <h4 class="fw-bold py-3">📝 Status Lamaran</h4>

            @forelse($riwayat_proses as $index => $proses)
            @php
            $collapseId = 'collapseProses' . $index;
            @endphp
            <div class="row">
                <div class="col-auto text-center flex-column d-none d-sm-flex">
                    <div class="row h-50">
                        <div class="col border-end">&nbsp;</div>
                        <div class="col">&nbsp;</div>
                    </div>
                    <h5 class="m-2">
                        <span class="badge rounded-pill  {{ $loop->first ? 'bg-primary' : 'bg-light' }}">&nbsp;</span>
                    </h5>
                    <div class="row h-50">
                        <div class="col border-end">&nbsp;</div>
                        <div class="col">&nbsp;</div>
                    </div>
                </div>
                <div class="col py-2">
                    <div class="card {{ $loop->first ? 'border-primary shadow' : 'border-light shadow-sm' }}">
                        <div class="card-body">
                            <h4 class="card-title {{ $loop->first ? 'text-primary' : '' }}">{{ $proses->status_proses }}</h4>
                            <div class="float-right mb-3 {{ $loop->first ? 'text-primary' : '' }}">{{ tanggalIndoHari($proses->tanggal_proses) }}</div>
                            <button class="btn btn-sm {{ $loop->first ? 'btn-outline-primary ' : 'btn-outline-light text-dark' }}" type="button" data-bs-target="#detail-{{ $proses->id }}" data-bs-toggle="collapse">Lihat Detail ▼</button>
                            <div class="collapse border" id="detail-{{ $proses->id }}">
                                <div class="p-2 font-monospace">
                                    {!! nl2br(e($proses->pesan)) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="alert alert-info text-center">
                Belum ada riwayat proses lamaran.
            </div>
            @endforelse

            <!--/row-->
        </div>
    </div>
</div>

@endsection