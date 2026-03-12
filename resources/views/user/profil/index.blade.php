@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-12">

            <div class="card shadow rounded-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white">Profil</h5>
                    <a href="{{ route('beranda') }}" class="btn btn-sm btn-light text-primary">Tutup</a>
                </div>

                <form action="{{ route('profil.update', auth()->user()->id) }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    @method('PATCH')

                    <div class="card-body">
                        {{-- Nama --}}
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Lengkap</label>
                            <input type="text" id="nama" name="nama"
                                class="form-control @error('nama') is-invalid @enderror"
                                value="{{ old('nama', auth()->user()->name) }}"
                                required autocomplete="name">
                            @error('nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- No KTP --}}
                        <div class="mb-3">
                            <label for="no_ktp" class="form-label">No. KTP</label>
                            <input type="text" id="no_ktp" name="no_ktp" class="form-control @error('no_ktp') is-invalid @enderror"
                                value="{{ old('no_ktp', auth()->user()->no_ktp) }}"
                                maxlength="16" required autocomplete="off">
                            @error('no_ktp')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="mb-3">
                            <label for="email" class="form-label">Alamat Email</label>
                            <input type="email" id="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', auth()->user()->email) }}"
                                required autocomplete="email" readonly>
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="mb-3">
                            <label for="password" class="form-label">Password Baru <small class="text-muted">(kosongkan jika tidak ingin diubah)</small></label>
                            <input type="password" id="password" name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                autocomplete="new-password">
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Konfirmasi Password --}}
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                class="form-control"
                                autocomplete="new-password">
                        </div>

                        {{-- Status Akun --}}
                        <div class="mb-3">
                            <label class="form-label">Status Akun</label>
                            <div>
                                @if (auth()->user()->status_akun == 1)
                                <span class="badge bg-success">Aktif</span>
                                @else
                                <span class="badge bg-secondary">Tidak Aktif</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection