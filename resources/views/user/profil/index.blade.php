@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ versioned_asset('user/css/vhire-custom.css') }}">
@endpush

@section('content')
@php
    $user = auth()->user();

    $accountIsActive = (int) $user->status_akun === 1;
    $identityUpdateLocked = $user->hasActiveEmploymentStatusLock();

    $profileName = old('nama', $user->name);
    $profileEmail = old('email', $user->email);
    $profileKtp = old('no_ktp', $user->no_ktp);

    $initial = strtoupper(substr(trim($profileName ?: 'U'), 0, 1));
@endphp

<div class="container clean-profile-page">
    <div class="clean-profile-shell">

        <div class="clean-profile-topbar">
            <div>
                <span class="clean-profile-eyebrow">Pengaturan Akun</span>
                <h4 class="clean-profile-title">Profil Pengguna</h4>
                <p class="clean-profile-subtitle">
                    Perbarui informasi akun dan keamanan password.
                </p>
            </div>

            <span class="clean-profile-status {{ $accountIsActive ? 'is-active' : 'is-inactive' }}">
                <i class="fa {{ $accountIsActive ? 'fa-check-circle' : 'fa-user-clock' }}"></i>
                {{ $accountIsActive ? 'Akun Aktif' : 'Akun Tidak Aktif' }}
            </span>
        </div>

        <div class="clean-profile-card">
            <div class="clean-profile-user">
                <div class="clean-profile-avatar">
                    {{ $initial }}
                </div>

                <div class="clean-profile-user-info">
                    <h5>{{ $profileName }}</h5>
                    <p>{{ $profileEmail }}</p>
                </div>
            </div>

            @if($identityUpdateLocked)
                <div class="clean-profile-alert">
                    <i class="fa fa-lock"></i>
                    <div>
                        <strong>Identitas akun terkunci</strong>
                        <span>Nama dan No. KTP tidak dapat diubah karena akun tercatat aktif bekerja.</span>
                    </div>
                </div>
            @endif

            <form action="{{ route('profil.update', $user->id) }}" method="POST" class="clean-profile-form needs-validation" novalidate>
                @csrf
                @method('PATCH')

                <div class="clean-profile-section">
                    <div class="clean-profile-section-head">
                        <h6>Data Akun</h6>
                        <p>Informasi utama yang digunakan pada akun kamu.</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nama" class="form-label clean-profile-label">Nama Lengkap</label>
                            <input
                                type="text"
                                id="nama"
                                name="nama"
                                class="form-control clean-profile-control @error('nama') is-invalid @enderror"
                                value="{{ $profileName }}"
                                required
                                autocomplete="name"
                                @if($identityUpdateLocked) disabled @endif>

                            @if($identityUpdateLocked)
                                <input type="hidden" name="nama" value="{{ $profileName }}">
                            @endif

                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="no_ktp" class="form-label clean-profile-label">No. KTP</label>
                            <input
                                type="text"
                                id="no_ktp"
                                name="no_ktp"
                                class="form-control clean-profile-control @error('no_ktp') is-invalid @enderror"
                                value="{{ $profileKtp }}"
                                maxlength="16"
                                required
                                autocomplete="off"
                                @if($identityUpdateLocked) disabled @endif>

                            @if($identityUpdateLocked)
                                <input type="hidden" name="no_ktp" value="{{ $profileKtp }}">
                            @endif

                            @error('no_ktp')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="email" class="form-label clean-profile-label">Alamat Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control clean-profile-control @error('email') is-invalid @enderror"
                                value="{{ $profileEmail }}"
                                required
                                readonly
                                autocomplete="email">

                            <div class="clean-profile-help">
                                Email digunakan sebagai identitas login dan tidak dapat diubah dari halaman ini.
                            </div>

                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="clean-profile-divider"></div>

                <div class="clean-profile-section">
                    <div class="clean-profile-section-head">
                        <h6>Keamanan Akun</h6>
                        <p>Kosongkan password jika tidak ingin mengganti kata sandi.</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label clean-profile-label">
                                Password Baru <span>(opsional)</span>
                            </label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control clean-profile-control @error('password') is-invalid @enderror"
                                autocomplete="new-password">

                            <div class="clean-profile-help">
                                Gunakan minimal 8 karakter.
                            </div>

                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label clean-profile-label">
                                Konfirmasi Password
                            </label>
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                class="form-control clean-profile-control"
                                autocomplete="new-password">

                            <div class="clean-profile-help">
                                Masukkan ulang password baru.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="clean-profile-actions">
                    <a href="{{ route('beranda') }}" class="btn btn-light">
                        Tutup
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection