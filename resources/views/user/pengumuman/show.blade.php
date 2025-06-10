@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
    <div class="container">
        <div class="row">
            <!-- Kolom Detail Lowongan -->
            <div class="col-lg-8 mb-4">
                <div class="alert border-2 border-primary shadow-sm rounded-3">
                    <div class="pb-3 wow fadeInUp" data-wow-delay="0.2s">
                        <h2 class="fw-bold text-primary mb-3">{{ $pengumuman->pengumuman }}</h2>
                        <p class="mb-4">{!! $pengumuman->keterangan !!}</p>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <p class="mb-0 text-primary">Tanggal dibuat : {{ tanggalIndo($pengumuman->created_at) }}</p>
                    </div>
                </div>
            </div>

            <!-- Kolom Pengumuman Lain -->
            <div class="col-lg-4">
                <h5 class="fw-bold text-primary mb-3">Pengumumam Lainnya</h5>
                @forelse ($pengumumans as $item)
                    <div class="card mb-3 shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="card-title mb-2 text-dark">{{ $item->pengumuman }}</h6>
                            <p class="mb-1 small text-muted">Tanggal dibuat : {{ tanggalIndo($item->created_at) }}</p>
                            <a href="{{ route('pengumuman.show', $item->id) }}" class="btn btn-sm btn-outline-primary rounded-pill mt-2">Lihat Detail</a>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">Tidak ada lowongan lain saat ini.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
