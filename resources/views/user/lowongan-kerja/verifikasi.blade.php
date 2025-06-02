@extends('layouts.app')

@section('content')
<div class="container py-5">
    @if (!empty($emptyFields))
    <div class="alert alert-primary border-1 border-primary shadow-sm rounded-3 p-4">
        <div class="d-flex align-items-start">
            <div class="me-3">
                <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
            </div>
            <div>
                <h4 class="mb-2 text-dark fw-bold">Pemeriksaan Data! Lengkapi Data Sebelum Melamar</h4>
                <p class="mb-3 text-danger">
                    Beberapa informasi wajib belum lengkap. Mohon lengkapi data berikut terlebih dahulu:
                </p>
                <ul class="mb-0 ps-3">
                    @foreach ($emptyFields as $field)
                    <li class="mb-1">{{ $field }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <a href="{{ route('biodata.index') }}" class="btn btn-primary">Lengkapi Data...</a>
    </div>
    @endif
</div>
@endsection