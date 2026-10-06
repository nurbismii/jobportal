@extends('layouts.app-pic')
@section('content-admin')
@php
    $user = auth()->user();
    $modules = collect(config('admin_access.modules'))->filter(fn ($definition, $module) => $user->hasModulePermission($module));
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 text-gray-800 mb-2">Beranda Internal</h1>
        <p class="text-muted mb-2">Selamat datang, {{ $user->name }}. Pilih modul untuk mulai bekerja.</p>
    </div>
    @if($user->role === 'admin')
        <a href="{{ route('internal-accounts.index') }}" class="btn btn-outline-primary">Kelola akun dan izin</a>
    @endif
</div>
<h2 class="h5 text-gray-800 mb-3">Modul yang dapat diakses</h2>
<div class="row">
    @forelse($modules as $module => $definition)
        @php($actions = collect($definition['actions'])->filter(fn ($action) => $user->hasModulePermission($module, $action)))
        <div class="col-12 col-md-6 col-xl-4 mb-4">
            <div class="card shadow-sm border-left-primary h-100">
                <div class="card-body d-flex flex-column">
                    <h3 class="h5 text-gray-800 mb-3">{{ $definition['label'] }}</h3>
                    <div class="mb-3">
                        @if($actions->count() === 1)
                            <span class="badge badge-info">Hanya baca</span>
                        @else
                            @foreach($actions as $action)
                                <span class="badge badge-{{ $action === 'delete' ? 'danger' : 'light' }}">{{ config('admin_access.actions.' . $action) }}</span>
                            @endforeach
                        @endif
                    </div>
                    <a href="{{ route($definition['route']) }}" class="btn btn-outline-primary mt-auto align-self-start" aria-label="Buka {{ $definition['label'] }}">Buka modul <i class="fas fa-arrow-right ml-2" aria-hidden="true"></i></a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm"><div class="card-body text-center py-5">
                <i class="fas fa-lock fa-2x text-muted mb-3" aria-hidden="true"></i>
                <h3 class="h5 text-gray-800">Belum ada akses modul</h3>
                <p class="text-muted mb-0">Hubungi Administrator untuk mengatur izin akun Anda.</p>
            </div></div>
        </div>
    @endforelse
</div>
@endsection
