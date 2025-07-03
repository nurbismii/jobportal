@extends('layouts.user-auth')

@section('content-login')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #cce6f7, #f8f9fa);">
    <div class="row w-100 shadow-lg rounded-4 overflow-hidden" style="max-width: 900px; background-color: #fff;">

        <!-- Left Panel -->
        <div class="col-md-5 d-none d-md-flex flex-column justify-content-center align-items-center text-white p-4" style="background: linear-gradient(135deg, #007bff, #0056b3);">
            <img src="{{ asset('img/logo-vdni1.png') }}" alt="VDNI Logo" class="mb-4" style="height: 60px;">
            <h4 class="fw-bold text-center">Rekrutmen Online</h4>
            <p class="text-center text-white-50 mt-2">Platform Rekrutmen Resmi</p>
        </div>

        <!-- Right Panel -->
        <div class="col-md-7 p-5">
            <div class="mb-4 text-center">
                <h3 class="fw-bold text-primary">Reset Kata Sandi</h3>
                <p class="text-muted small">Masukkan email Anda untuk mengatur ulang kata sandi</p>
            </div>

            <form method="POST" action="{{ route('reset-password.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email"
                        class="form-control form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}" placeholder="contoh@email.com" required>
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold">
                        Reset Kata Sandi
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
@endsection