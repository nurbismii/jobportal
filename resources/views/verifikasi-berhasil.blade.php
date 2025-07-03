<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="refresh" content="3; url={{ url('/') }}">
    <title>Verifikasi Berhasil | VDNI</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="icon" href="{{ asset('assets/img/backgrounds/icon.png') }}" />
    <style>
        body {
            background-color: #f8f9fa;
        }

        .checkmark-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: block;
            stroke-width: 2;
            stroke: #4caf50;
            stroke-miterlimit: 10;
            margin: 0 auto 20px auto;
            box-shadow: inset 0px 0px 0px #4caf50;
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }

        .checkmark-circle__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke .4s cubic-bezier(.65, .05, .36, 1) .8s forwards;
        }

        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes scale {

            0%,
            100% {
                transform: none;
            }

            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }

        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px #4caf50;
            }
        }
    </style>
</head>

<body>
    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="text-center">
            <svg class="checkmark-circle" viewBox="0 0 52 52">
                <circle cx="26" cy="26" r="25" fill="none" />
                <path class="checkmark-circle__check" fill="none" stroke="#fff" stroke-width="4" d="M14 27l7 7 16-16" />
            </svg>
            <h1 class="text-success fw-bold mb-3">Verifikasi Berhasil!</h1>
            <p class="lead text-muted">Email kamu telah berhasil diverifikasi.<br>Akun kamu kini aktif dan siap digunakan.</p>
            <a href="{{ url('/login') }}" class="btn btn-success mt-3 px-4">Login Sekarang</a>
            <div class="mt-4">
                <a href="{{ url('/') }}" class="text-decoration-none text-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    <footer class="text-center small text-muted py-3">
        &copy; {{ date('Y') }} PT Virtue Dragon Nickel Industry. All rights reserved.
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>

</html>