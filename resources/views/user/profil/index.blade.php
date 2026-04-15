@extends('layouts.app')

@section('content')

@push('styles')
<link rel="stylesheet" href="{{ asset('user/css/vhire-custom.css') }}">
@endpush

@php
    $user = auth()->user();
    $accountIsActive = (int) $user->status_akun === 1;
@endphp

<div class="container account-profile-page">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="account-profile-card">
                <div class="account-profile-card__body">
                    <div class="account-profile-header">
                        <div class="account-profile-header__main">
                            <div class="account-profile-header__icon">
                                <i class="fa fa-user"></i>
                            </div>
                            <div>
                                <span class="account-profile-header__eyebrow">Pengaturan Akun</span>
                                <h1 class="account-profile-header__title">Profil Pengguna</h1>
                                <p class="account-profile-header__subtitle">
                                    Perbarui informasi akun Anda agar data tetap akurat dan proses lamaran berjalan dengan lancar.
                                </p>
                            </div>
                        </div>

                        <span class="account-status-pill {{ $accountIsActive ? 'account-status-pill--active' : 'account-status-pill--inactive' }}">
                            <i class="fa {{ $accountIsActive ? 'fa-check-circle' : 'fa-user-clock' }}"></i>
                            {{ $accountIsActive ? 'Akun Aktif' : 'Akun Tidak Aktif' }}
                        </span>
                    </div>

                    <div class="account-profile-summary">
                        <div class="account-profile-summary__item">
                            <span class="account-profile-summary__icon">
                                <i class="fa fa-user-circle"></i>
                            </span>
                            <div>
                                <span class="account-profile-summary__label">Nama Pengguna</span>
                                <span class="account-profile-summary__value">{{ old('nama', $user->name) }}</span>
                            </div>
                        </div>

                        <div class="account-profile-summary__item">
                            <span class="account-profile-summary__icon">
                                <i class="fa fa-envelope"></i>
                            </span>
                            <div>
                                <span class="account-profile-summary__label">Email Terdaftar</span>
                                <span class="account-profile-summary__value">{{ old('email', $user->email) }}</span>
                            </div>
                        </div>

                        <div class="account-profile-summary__item">
                            <span class="account-profile-summary__icon">
                                <i class="fa fa-id-card"></i>
                            </span>
                            <div>
                                <span class="account-profile-summary__label">Nomor KTP</span>
                                <span class="account-profile-summary__value">{{ old('no_ktp', $user->no_ktp) }}</span>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('profil.update', $user->id) }}" method="POST" class="account-profile-form needs-validation" novalidate>
                        @csrf
                        @method('PATCH')

                        <section class="account-profile-section">
                            <div class="account-profile-section__header">
                                <span class="account-profile-section__eyebrow">Informasi Dasar</span>
                                <h2 class="account-profile-section__title">Data Akun</h2>
                                <p class="account-profile-section__text">
                                    Pastikan nama lengkap dan nomor KTP sesuai dengan data resmi yang Anda gunakan untuk melamar pekerjaan.
                                </p>
                            </div>

                            <div class="account-profile-grid">
                                <div class="account-profile-field">
                                    <label for="nama" class="account-profile-label">Nama Lengkap</label>
                                    <input
                                        type="text"
                                        id="nama"
                                        name="nama"
                                        class="form-control account-profile-input @error('nama') is-invalid @enderror"
                                        value="{{ old('nama', $user->name) }}"
                                        required
                                        autocomplete="name">
                                    @error('nama')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="account-profile-field">
                                    <label for="no_ktp" class="account-profile-label">No. KTP</label>
                                    <input
                                        type="text"
                                        id="no_ktp"
                                        name="no_ktp"
                                        class="form-control account-profile-input @error('no_ktp') is-invalid @enderror"
                                        value="{{ old('no_ktp', $user->no_ktp) }}"
                                        maxlength="16"
                                        required
                                        autocomplete="off">
                                    @error('no_ktp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="account-profile-field account-profile-field--full">
                                    <label for="email" class="account-profile-label">Alamat Email</label>
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        class="form-control account-profile-input @error('email') is-invalid @enderror"
                                        value="{{ old('email', $user->email) }}"
                                        required
                                        autocomplete="email"
                                        readonly>
                                    <p class="account-profile-hint">Email digunakan sebagai identitas login dan saat ini tidak dapat diubah dari halaman ini.</p>
                                    @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </section>

                        <section class="account-profile-section">
                            <div class="account-profile-section__header">
                                <span class="account-profile-section__eyebrow">Keamanan</span>
                                <h2 class="account-profile-section__title">Ubah Password</h2>
                                <p class="account-profile-section__text">
                                    Kosongkan kolom password jika Anda tidak ingin mengganti kata sandi akun saat ini.
                                </p>
                            </div>

                            <div class="account-profile-grid">
                                <div class="account-profile-field">
                                    <label for="password" class="account-profile-label">
                                        Password Baru
                                        <small>(opsional)</small>
                                    </label>
                                    <input
                                        type="password"
                                        id="password"
                                        name="password"
                                        class="form-control account-profile-input @error('password') is-invalid @enderror"
                                        autocomplete="new-password">
                                    <p class="account-profile-hint">Gunakan minimal 8 karakter untuk keamanan yang lebih baik.</p>
                                    @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="account-profile-field">
                                    <label for="password_confirmation" class="account-profile-label">Konfirmasi Password Baru</label>
                                    <input
                                        type="password"
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        class="form-control account-profile-input"
                                        autocomplete="new-password">
                                    <p class="account-profile-hint">Masukkan ulang password baru agar perubahan dapat dikonfirmasi.</p>
                                </div>
                            </div>
                        </section>

                        <div class="account-profile-actions">
                            <p class="account-profile-actions__text">
                                Perubahan akan langsung diterapkan setelah Anda menekan tombol simpan.
                            </p>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('beranda') }}" class="btn btn-light">Tutup</a>
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
