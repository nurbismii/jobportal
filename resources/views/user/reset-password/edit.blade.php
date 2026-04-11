@extends('layouts.user-auth')

@section('content-login')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #cce6f7, #f8f9fa);">
    <div class="row w-100 shadow-lg rounded-4 overflow-hidden" style="max-width: 900px; background-color: #fff;">

        <!-- Left Panel -->
        <div class="col-md-5 d-none d-md-flex flex-column justify-content-center align-items-center text-white p-4" style="background: linear-gradient(135deg, #007bff, #0056b3);">
            <img src="{{ asset('img/logo-vdni.png') }}" alt="VDNI Logo" class="mb-4" style="height: 60px;">
            <h4 class="fw-bold text-center">Pulihkan Akun</h4>
            <p class="text-center text-white-50 mt-2">PT VDNI - Rekrutmen Online</p>
        </div>

        <!-- Right Panel -->
        <div class="col-md-7 p-5">
            <div class="mb-4 text-center">
                <h3 class="fw-bold text-primary">Perbarui Email dan Kata Sandi</h3>
                <p class="text-muted small">Gunakan email baru yang aktif. Setelah disimpan, Anda wajib verifikasi ulang sebelum login.</p>
            </div>

            <form method="POST" action="{{ route('lupa-akun.update', $token) }}">
                @csrf
                @method('PATCH')

                <div class="mb-3">
                    <label for="email" class="form-label">Email Baru</label>
                    <input type="email" id="email" name="email"
                        class="form-control form-control-lg @error('email') is-invalid @enderror"
                        value="{{ old('email', $user->email) }}" placeholder="contoh@email.com" required>
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 position-relative">
                    <label for="password" class="form-label">Kata Sandi Baru</label>
                    <input type="password" id="password" name="password"
                        class="form-control form-control-lg @error('password') is-invalid @enderror"
                        placeholder="Minimal 8 karakter" required>
                    <span class="position-absolute top-50 end-0 translate-middle-y me-3">
                        <i class="bi bi-eye-slash toggle-password" data-target="password"></i>
                    </span>
                    @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 position-relative">
                    <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        class="form-control form-control-lg" placeholder="Ulangi kata sandi" required>
                    <span class="position-absolute top-50 end-0 translate-middle-y me-3">
                        <i class="bi bi-eye-slash toggle-password" data-target="password_confirmation"></i>
                    </span>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold">Reset Kata Sandi</button>
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

@push('scripts')
<script>
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', function() {
            const target = document.getElementById(this.dataset.target);
            if (target.type === 'password') {
                target.type = 'text';
                this.classList.remove('bi-eye-slash');
                this.classList.add('bi-eye');
            } else {
                target.type = 'password';
                this.classList.remove('bi-eye');
                this.classList.add('bi-eye-slash');
            }
        });
    });
</script>
@endpush
@endsection
