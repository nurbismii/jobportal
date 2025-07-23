@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<style>
    .card:hover {
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        transform: scale(1.01);
        transition: 0.3s;
    }
</style>
@endpush

<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
    <h2 class="m-0 font-weight-bold text-primary">Daftar Lowongan</h2>
    <a href="{{ route('lowongan.create') }}" class="btn btn-primary btn-sm btn-icon-split">
        <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
        <span class="text">Lowongan</span>
    </a>
</div>

<!-- Search Form -->
<form method="GET" action="{{ route('lowongan.index') }}" class="mt-3">
    <div class="input-group mb-3">
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari lowongan...">
        <div class="input-group-append">
            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
        </div>
    </div>
</form>

<!-- Lowongan Cards -->
<div class="row">
    @forelse($lowongans as $data)
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 shadow border-left-primary">
            <div class="card-body d-flex flex-column">
                <h4 class="card-title fw-bold text-primary">{{ $data->nama_lowongan }}</h4>
                <h5 class="card-title text-primary">Jumlah Pelamar saat ini : {{$data->lamarans_count}}</h5>
                <p class="mb-1"><strong>SIM B2 :</strong> {{ $data->status_sim_b2 == 1 ? 'Dibutuhkan' : 'Tidak dibutuhkan' }}</p>
                <p class="mb-1"><strong>Mulai :</strong> {{ tanggalIndo($data->tanggal_mulai) }}</p>
                <p class="mb-3"><strong>Berakhir :</strong> {{ tanggalIndo($data->tanggal_berakhir) }}</p>

                <div class="mt-auto">
                    <a href="{{ route('directToLamaran', $data->id) }}" class="btn btn-secondary btn-sm btn-block mb-2">
                        <i class="fas fa-list mr-1"></i> Pelamar
                    </a>
                    <div class="btn-group btn-group-sm btn-block" role="group">
                        <a href="{{ route('lowongan.edit', $data->id) }}" class="btn btn-success">
                            <i class="fas fa-pen"></i> Edit
                        </a>
                        <a href="{{ route('lowongan.destroy', $data->id) }}" class="btn btn-danger" data-confirm-delete="true">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-info">Tidak ada lowongan ditemukan.</div>
    </div>
    @endforelse
</div>

<!-- Pagination -->
<div class="d-flex justify-content-center mt-4">
    {{ $lowongans->appends(request()->query())->links() }}
</div>

@endsection