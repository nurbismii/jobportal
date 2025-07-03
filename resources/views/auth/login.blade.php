@extends('layouts.user-auth')

@section('content-login')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #cce6f7, #f8f9fa);">
    <div class="row w-100 shadow-lg rounded-4 overflow-hidden" style="max-width: 900px; background-color: #fff;">

        <!-- Left Side -->
        <div class="col-md-5 d-none d-md-flex flex-column justify-content-center align-items-center text-white p-4" style="background: linear-gradient(135deg, #007bff, #0056b3);">
            <img src="{{ asset('img/logo-vdni1.png') }}" alt="VDNI Logo" class="mb-4" style="height: 60px;">
            <h4 class="fw-bold text-center">Selamat Datang di V-HIRE</h4>
            <p class="text-center text-white-50 mt-2">Platform Rekrutmen Resmi</p>
        </div>

        <!-- Right Side (Login Form) -->
        <div class="col-md-7 p-5">
            <div class="mb-4 text-center">
                <h3 class="fw-bold text-primary">Masuk ke Akun Anda</h3>
                <p class="text-muted small">Silakan login dengan email dan kata sandi Anda</p>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email"
                        class="form-control form-control @error('email') is-invalid @enderror"
                        name="email" value="{{ old('email') }}" required autofocus placeholder="contoh@email.com">
                    @error('email')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input id="password" type="password"
                        class="form-control form-control @error('password') is-invalid @enderror"
                        name="password" required placeholder="Masukkan kata sandi">
                    @error('password')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember"
                            {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label small" for="remember">
                            Ingat Saya
                        </label>
                    </div>
                    <a href="{{ route('reset-password.index') }}" class="small text-decoration-none text-primary">Lupa Kata Sandi?</a>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary rounded-pill">
                        Masuk
                    </button>
                </div>

                <div class="text-center small">
                    Belum punya akun? <a href="{{ route('register') }}" class="fw-bold text-primary text-decoration-none">Daftar</a>
                </div>

                <div class="text-center text-muted mt-4 small">
                    © {{ date('Y') }} PT VDNI. All rights reserved.
                </div>
            </form>
        </div>
    </div>
</div>
@endsection