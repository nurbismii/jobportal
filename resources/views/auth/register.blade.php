@extends('layouts.user-auth')

@section('content-login')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #cce6f7, #f8f9fa);">
    <div class="row w-100 shadow-lg rounded-4 overflow-hidden" style="max-width: 900px; background-color: #fff;">

        <!-- Left Panel -->
        <div class="col-md-5 d-none d-md-flex flex-column justify-content-center align-items-center text-white p-4" style="background: linear-gradient(135deg, #007bff, #0056b3);">
            <img src="{{ asset('img/logo-vdni1.png') }}" alt="VDNI Logo" class="mb-4" style="height: 60px;">
            <h4 class="fw-bold text-center">Pendaftaran V-HIRE</h4>
            <p class="text-center text-white-50 mt-2">Rekrutmen Resmi</p>
        </div>

        <!-- Right Panel -->
        <div class="col-md-7 p-5">
            <div class="mb-4 text-center">
                <h3 class="fw-bold text-primary">Buat Akun Baru</h3>
                <p class="text-muted small">Silakan isi data Anda dengan benar</p>
            </div>

            <form id="form-register" method="POST" action="{{ route('pendaftaran.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="no_ktp" class="form-label">No KTP
                        <sup class="text-danger">*</sup>
                    </label>
                    <input type="text" maxlength="16" id="no_ktp" name="no_ktp"
                        class="form-control form-control @error('no_ktp') is-invalid @enderror"
                        value="{{ old('no_ktp') }}" placeholder="16 digit NIK" required>
                    @error('no_ktp')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col">
                        <label for="first_name" class="form-label">Nama Depan
                            <sup class="text-danger">*</sup>
                        </label>
                        <input type="text" id="first_name" name="first_name"
                            class="form-control form-control @error('first_name') is-invalid @enderror"
                            value="{{ old('first_name') }}" placeholder="Nama Depan" required>
                        @error('first_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col">
                        <label for="last_name" class="form-label">Nama Belakang
                            <sup class="text-muted">(Opsional)</sup>
                        </label>
                        <input type="text" id="last_name" name="last_name"
                            class="form-control form-control @error('last_name') is-invalid @enderror"
                            value="{{ old('last_name') }}" placeholder="Nama Belakang" required>
                        @error('last_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email
                        <sup class="text-danger">*</sup>
                    </label>
                    <input type="email" id="email" name="email"
                        class="form-control form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}" placeholder="contoh@email.com" required>
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 position-relative">
                    <label for="password" class="form-label">Kata Sandi
                        <sub class="text-muted">(Minimal 8 karakter)</sub>
                    </label>
                    <input type="password" id="password" name="password"
                        class="form-control form-control @error('password') is-invalid @enderror"
                        placeholder="Minimal 8 karakter" required>
                    @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 position-relative">
                    <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi
                        <sup class="text-danger">*</sup>
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        class="form-control form-control" placeholder="Ulangi kata sandi" required>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" id="btn-submit-register" class="btn btn-primary rounded-pill fw-bold">
                        Buat Akun
                    </button>
                </div>

                <div class="text-center small">
                    Sudah punya akun? <a href="{{ route('login') }}" class="fw-bold text-primary text-decoration-none">Masuk</a>
                </div>

                <div class="text-center text-muted mt-4 small">
                    © {{ date('Y') }} HRD PT VDNI
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('form-register').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('btn-submit-register');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengirim...';
    });
</script>
@endsection