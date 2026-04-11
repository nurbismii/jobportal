@extends('layouts.user-auth')

@section('content-login')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #cce6f7, #f8f9fa);">
    <div class="row w-100 shadow-lg rounded-4 overflow-hidden" style="max-width: 980px; background-color: #fff;">

        <div class="col-md-5 d-none d-md-flex flex-column justify-content-center align-items-center text-white p-4" style="background: linear-gradient(135deg, #007bff, #0056b3);">
            <img src="{{ asset('img/logo-vdni1.png') }}" alt="VDNI Logo" class="mb-4" style="height: 60px;">
            <h4 class="fw-bold text-center">Pemulihan Akun V-HIRE</h4>
            <p class="text-center text-white-50 mt-2 mb-0">Pilih jalur pemulihan sesuai akses yang masih Anda miliki.</p>
        </div>

        <div class="col-md-7 p-5">
            <div class="mb-4 text-center">
                <h3 class="fw-bold text-primary">Lupa Akun</h3>
                <p class="text-muted small mb-0">Jika email lama masih aktif, gunakan pemulihan otomatis. Jika email lama sudah tidak bisa diakses, ajukan pemulihan manual ke tim HR.</p>
            </div>

            <div class="border rounded-4 p-4 mb-4 bg-light">
                <div class="mb-3">
                    <h5 class="fw-bold text-dark mb-1">1. Masih bisa akses email lama</h5>
                    <p class="text-muted small mb-0">Masukkan No KTP dan email lama yang terdaftar. Sistem akan mengirim tautan pemulihan ke email lama tersebut.</p>
                </div>

                <form method="POST" action="{{ route('lupa-akun.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="no_ktp" class="form-label">No KTP</label>
                        <input type="text" id="no_ktp" name="no_ktp"
                            class="form-control @error('no_ktp') is-invalid @enderror"
                            value="{{ old('no_ktp') }}" placeholder="16 digit NIK" inputmode="numeric" maxlength="16" required>
                        @error('no_ktp')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Lama</label>
                        <input type="email" id="email" name="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}" placeholder="contoh@email.com" required>
                        @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary rounded-pill fw-bold">
                            Kirim Tautan Pemulihan
                        </button>
                    </div>
                </form>
            </div>

            <div class="border rounded-4 p-4 bg-white shadow-sm">
                <div class="mb-3">
                    <h5 class="fw-bold text-dark mb-1">2. Email lama sudah tidak bisa diakses</h5>
                    <p class="text-muted small mb-0">Ajukan pemulihan manual. Tim HR akan meninjau permintaan Anda sebelum email akun diganti.</p>
                </div>

                <form method="POST" action="{{ route('lupa-akun.request') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="no_ktp_manual" class="form-label">No KTP</label>
                        <input type="text" id="no_ktp_manual" name="no_ktp_manual"
                            class="form-control @error('no_ktp_manual') is-invalid @enderror"
                            value="{{ old('no_ktp_manual') }}" placeholder="16 digit NIK" inputmode="numeric" maxlength="16" required>
                        @error('no_ktp_manual')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="name_manual" class="form-label">Nama Lengkap</label>
                        <input type="text" id="name_manual" name="name_manual"
                            class="form-control @error('name_manual') is-invalid @enderror"
                            value="{{ old('name_manual') }}" placeholder="Sesuai data akun" required>
                        @error('name_manual')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email_baru" class="form-label">Email Baru Aktif</label>
                        <input type="email" id="email_baru" name="email_baru"
                            class="form-control @error('email_baru') is-invalid @enderror"
                            value="{{ old('email_baru') }}" placeholder="email baru untuk dihubungi" required>
                        @error('email_baru')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="no_telp_manual" class="form-label">No Telepon Aktif</label>
                        <input type="text" id="no_telp_manual" name="no_telp_manual"
                            class="form-control @error('no_telp_manual') is-invalid @enderror"
                            value="{{ old('no_telp_manual') }}" placeholder="Opsional, tapi disarankan">
                        @error('no_telp_manual')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="keterangan_manual" class="form-label">Keterangan</label>
                        <textarea id="keterangan_manual" name="keterangan_manual"
                            class="form-control @error('keterangan_manual') is-invalid @enderror"
                            rows="3" placeholder="Contoh: email lama sudah tidak aktif dan lupa kata sandi">{{ old('keterangan_manual') }}</textarea>
                        @error('keterangan_manual')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="small text-muted mb-3">
                        Demi keamanan, permintaan ini tidak langsung mengubah email akun. Tim HR akan melakukan verifikasi manual terlebih dahulu.
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary rounded-pill fw-bold">
                            Ajukan Pemulihan Manual
                        </button>
                    </div>
                </form>
            </div>

            <div class="text-center small mt-4">
                Sudah punya akun? <a href="{{ route('login') }}" class="fw-bold text-primary text-decoration-none">Masuk</a>
            </div>

            <div class="text-center text-muted mt-4 small">
                © {{ date('Y') }} HRD PT VDNI
            </div>
        </div>
    </div>
</div>

<script>
    ['no_ktp', 'no_ktp_manual'].forEach(function(fieldId) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        field.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });
</script>
@endsection
