@extends('layouts.user-auth')

@section('content-login')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="row w-100 shadow" style="max-width: 1000px;">
        <!-- Left Panel -->
        <div class="col-md-6 d-flex flex-column justify-content-center align-items-center text-white" style="background-color: #cce6f7; padding: 60px 30px;">
            <h4 class="fw-bold text-primary text-center">Selamat Datang di Rekrutmen Online</h4>
            <h3 class="fw-bold text-primary text-center mt-2">PT VDNI</h3>
        </div>

        <!-- Right Panel (Register Form) -->
        <div class="col-md-6 bg-white p-5">
            <div class="text-center mb-4">
                <img src="{{ asset('img/logo-vdni.png') }}" alt="VDNI Logo" style="height: 50px;">
            </div>

            <form method="POST" action="{{ route('reset-password.store') }}">
                @csrf
                <div class="mb-3">
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email" value="{{ old('email') }}" required>
                    @error('email')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary fw-bold">Reset Kata Sandi</button>
                </div>

                <div class="text-center">
                    Sudah punya akun? <a href="{{ route('login') }}" class="text-decoration-none fw-bold text-primary">Masuk</a>
                </div>

                <div class="text-center text-muted mt-4" style="font-size: 0.9rem;">
                    © {{ date('Y') }} HRD PT VDNI
                </div>
            </form>
        </div>
    </div>
</div>
@endsection