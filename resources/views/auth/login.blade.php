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

            <form id="form-login" method="POST" action="{{ route('login') }}">
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
                    <button type="submit" id="btn-submit-login" class="btn btn-primary rounded-pill">
                        Masuk
                    </button>
                </div>

                <div class="text-center small mb-2">
                    Belum punya akun? <a href="{{ route('register') }}" class="fw-bold text-primary text-decoration-none">Daftar</a>
                </div>

                <div class="text-center small">
                    <a href="#" onclick="localStorage.removeItem('vhire_tutorial_first_visit'); location.reload();">
                        Tampilkan Panduan
                    </a>
                </div>

                <div class="text-center text-muted mt-4 small">
                    © {{ date('Y') }} PT VDNI. All rights reserved.
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('form-login').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('btn-submit-login');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Proses masuk...';
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        if (!localStorage.getItem("vhire_tutorial_first_visit")) {

            const steps = [{
                    title: "👋 Selamat Datang di V-HIRE",
                    html: `
                        Platform resmi untuk melamar pekerjaan di <b>PT Virtue Dragon Nickel Industry</b>.<br><br>
                        Yuk ikuti panduan singkat ini agar kamu bisa menggunakan sistem dengan benar.
                    `,
                    icon: "info"
                },
                {
                    title: "1. Buat Akun",
                    html: `
                        Untuk mulai melamar, kamu perlu membuat akun :<br><br>
                        • Klik tombol <b>Daftar</b><br>
                        • Isi: Nomor KTP, Nama, Email & Kata Sandi<br>
                        • Pastikan data kamu benar dan aktif<br><br>
                        Setelah itu, cek email dan lakukan <b>verifikasi akun</b> untuk dapat login.
                    `,
                    icon: "info"
                },
                {
                    title: "⚠ Penting Dipahami!",
                    html: `
                        <b>Membuat akun tidak otomatis berarti kamu sudah melamar pekerjaan.</b><br><br>
                        Agar masuk proses seleksi, kamu harus:<br><br>
                        ✔ Melengkapi semua biodata<br>
                        ✔ Mengunggah dokumen<br>
                        ✔ Memilih lowongan dan klik <b>"Lamar"</b><br><br>
                        Jika belum menekan mengirim "Lamaran", sistem tidak menganggap kamu mengikuti rekrutmen.<br><br>
                        <span id="countdown" style="font-size:14px;color:#d33;font-weight:bold;">Silakan baca dulu (15 detik)...</span>
                    `,
                    icon: "warning",
                    lockTime: 15
                },
                {
                    title: "2. Lengkapi Biodata",
                    html: `
                        Setelah login, kamu wajib melengkapi Formulir biodata ini:<br><br>
                        ✔ Data Pribadi<br>
                        ✔ Riwayat Pendidikan<br>
                        ✔ Data Keluarga<br>
                        ✔ Kontak Darurat<br>
                        ✔ Dokumen (KTP, SIM, KK, dll jika ada)<br>
                        ✔ Pemeriksaan Pernyataan (centang keaslian data)<br><br>
                        <b>Catatan: Kamu tidak bisa melamar pekerjaan jika biodata belum 100% lengkap.</b>
                    `,
                    icon: "warning"
                },
                {
                    title: "3. Upload Dokumen",
                    html: `
                        Pastikan dokumen yang kamu unggah:<br><br>
                        • Masih berlaku<br>
                        • Foto jelas dan terbaca<br>
                        • Format: JPG, PNG, atau PDF<br><br>
                        <i>Dokumen yang tidak jelas dapat mempengaruhi proses seleksi.</i>
                    `,
                    icon: "info"
                },
                {
                    title: "4. Melamar Lowongan",
                    html: `
                        Jika biodata lengkap, kamu bisa mulai melamar:<br><br>
                        • Masuk ke menu <b>Lowongan Kerja</b><br>
                        • Pilih posisi yang sesuai<br>
                        • Klik <b>Lamar</b> lalu konfirmasi<br><br>
                        Pastikan kamu membaca deskripsi pekerjaan terlebih dahulu.
                    `,
                    icon: "info"
                },
                {
                    title: "5. Cek Status Lamaran",
                    html: `
                        Proses rekrutmen terdiri dari beberapa tahap.<br><br>
                        Status yang mungkin terlihat:<br>
                        • 🟢 <b>Dalam proses</b> → kamu masih mengikuti proses seleksi<br>
                        • 🔴 <b>Rekrutmen selesai</b> → kamu tidak berhasil di proses terakhir<br><br>
                        Kamu dapat melihat detail progres melalui tombol <b>Baca Detail</b>.
                    `,
                    icon: "info"
                },
                {
                    title: "6. Pengumuman",
                    html: `
                        Semua pengumuman resmi seperti:<br><br>
                        • Lowongan baru<br>
                        • Rekrutmen selesai<br><br>
                        Akan muncul di menu <b>Pengumuman</b>. Pastikan kamu mengecek secara berkala.
                    `,
                    icon: "info"
                },
                {
                    title: "Semua Siap!",
                    html: `
                        Kamu sudah memahami cara penggunaan sistem.<br><br>
                        Silakan klik <b>Daftar</b> atau <b>Login</b> untuk memulai proses lamaran.<br><br>
                        Semoga sukses dan sampai jumpa di proses seleksi! 
                    `,
                    icon: "success"
                }
            ];

            let index = 0;

            function showStep() {
                const lockTime = steps[index].lockTime ?? 0;

                Swal.fire({
                    title: steps[index].title,
                    html: steps[index].html,
                    icon: steps[index].icon,
                    showCancelButton: index > 0,
                    confirmButtonText: index === steps.length - 1 ? "Mulai Sekarang" : "Lanjut ➜",
                    cancelButtonText: "⬅ Kembali",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    width: 600,
                    didOpen: () => {
                        if (lockTime > 0) {
                            const confirmBtn = Swal.getConfirmButton();
                            const cancelBtn = Swal.getCancelButton();
                            const countdownEl = document.getElementById("countdown");

                            confirmBtn.disabled = true;
                            if (cancelBtn) cancelBtn.disabled = true;

                            let remaining = lockTime;
                            const timer = setInterval(() => {
                                remaining--;
                                countdownEl.textContent = `Silakan baca dulu (${remaining} detik)...`;

                                if (remaining <= 0) {
                                    clearInterval(timer);
                                    countdownEl.textContent = "Kamu bisa melanjutkan.";
                                    confirmBtn.disabled = false;
                                    if (cancelBtn) cancelBtn.disabled = false;
                                }
                            }, 1000);
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        index++;
                        if (index < steps.length) {
                            showStep();
                        } else {
                            localStorage.setItem("vhire_tutorial_first_visit", "true");
                        }
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        index--;
                        showStep();
                    }
                });
            }

            showStep();
        }
    });
</script>



@endsection