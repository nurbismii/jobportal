@extends('layouts.user-auth')

@section('content-login')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center" style="background-color: #f8f9fa;">
    <div class="row w-100 shadow" style="max-width: 1000px;">
        <!-- Left Side -->
        <div class="col-md-6 d-flex flex-column justify-content-center align-items-center text-white" style="background-color: #cce6f7; padding: 60px 30px;">
            <h4 class="fw-bold text-primary text-center">Selamat Datang di Rekrutmen Online</h4>
            <h3 class="fw-bold text-primary text-center mt-2">PT VDNI</h3>
        </div>

        <!-- Right Side (Login Form) -->
        <div class="col-md-6 bg-white p-5">
            <div class="text-center mb-4">
                <img src="{{ asset('img/logo-vdni.png') }}" alt="VDNI Logo" style="height: 50px;">
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                        value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="Email">

                    @error('email')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="mb-3">
                    <input id="password" type="password"
                        class="form-control @error('password') is-invalid @enderror" name="password" required
                        autocomplete="current-password" placeholder="Kata Sandi">

                    @error('password')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember"
                            {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">
                            Ingat Saya
                        </label>
                    </div>
                    <a href="{{ route('password.request') }}" class="text-decoration-none">Lupa Kata Sandi?</a>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary">
                        Masuk
                    </button>
                </div>

                <div class="text-center mb-3">atau</div>

                <div class="d-grid mb-3">
                    <a href="" class="btn btn-outline-primary">
                        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" style="width: 20px; margin-right: 8px;">
                        Log in dengan Google
                    </a>
                </div>

                <div class="text-center">
                    Belum memiliki akun? <a href="{{ route('register') }}" class="text-decoration-none fw-bold">Daftar</a>
                </div>

                <div class="text-center text-muted mt-4" style="font-size: 0.9rem;">
                    © {{ date('Y') }} PT VDNI
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
