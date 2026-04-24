@extends('layouts.user-auth')

@section('content-login')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #cce6f7, #f8f9fa);">
    <div class="row w-100 shadow-lg rounded-4 overflow-hidden" style="max-width: 960px; background-color: #fff;">

        <div class="col-md-5 d-none d-md-flex flex-column justify-content-center text-white p-5" style="background: linear-gradient(135deg, #007bff, #0056b3);">
            <img src="{{ asset('img/logo-vdni1.png') }}" alt="VDNI Logo" class="mb-4" style="height: 60px; width: fit-content;">
            <h3 class="fw-bold mb-3">Satu langkah lagi</h3>
            <p class="text-white-50 mb-3">Akun Anda belum aktif sebelum email diverifikasi.</p>
            <div class="small lh-lg">
                <div>1. Cek inbox atau folder spam.</div>
                <div>2. Klik tautan verifikasi terbaru.</div>
                <div>3. Login setelah verifikasi berhasil.</div>
            </div>
        </div>

        <div class="col-md-7 p-5">
            <div class="mb-4">
                <h3 class="fw-bold text-primary mb-2">Verifikasi Email Anda</h3>
                <p class="text-muted mb-0">Silakan cek email kamu untuk tautan verifikasi.</p>
            </div>

            <div class="alert alert-primary border-0 rounded-4 mb-4" role="alert">
                <div class="fw-semibold mb-2">Yang perlu kamu lakukan</div>
                <div class="small">Klik tautan verifikasi yang dikirim ke email kamu. Jika email belum masuk, kirim ulang dari form di bawah.</div>
                <div class="small mt-2">Kirim ulang email verifikasi tersedia setiap {{ $resendCooldownMinutes }} menit dan maksimal {{ $resendDailyLimit }} kali per hari.</div>
            </div>

            <div class="alert alert-warning border-0 rounded-4 mb-4" role="alert">
                <div class="fw-semibold mb-2">Batas waktu verifikasi</div>
                <div class="small">Akun yang belum diverifikasi akan dihapus otomatis setelah {{ $graceHours }} jam. Jika akun sudah terhapus, Anda bisa daftar ulang dengan email dan NIK yang sama.</div>
            </div>

            <form method="POST" action="{{ route('verification.resend.public') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email akun</label>
                    <input id="email" type="email" name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $email) }}"
                        placeholder="contoh@email.com"
                        required>
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid gap-2 mb-3">
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold">Kirim Ulang Email Verifikasi</button>
                    <a href="{{ route('login') }}" class="btn btn-outline-primary rounded-pill">Kembali ke Login</a>
                </div>
            </form>

            <div class="text-center small">
                Belum punya akses email atau akun sudah terhapus?
                <a href="{{ route('register') }}" class="fw-bold text-primary text-decoration-none">Daftar ulang</a>
            </div>

            @if($email)
            <div class="text-center text-muted mt-4 small">
                Email tujuan terakhir: <span class="fw-semibold">{{ $email }}</span>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
