<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="refresh" content="3; url={{ url('/') }}">
    <title>Verifikasi Berhasil | VDNI</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Icon -->
    <link rel="icon" href="{{ asset('assets/img/backgrounds/icon.png') }}" />

    <style>
        body {
            background-color: #f8f9fa;
        }

        .confetti-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
        }
    </style>
</head>

<body>
    <!-- Confetti Canvas -->
    <canvas class="confetti-container" id="confetti-canvas"></canvas>

    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="text-center">
            <h1 class="text-primary fw-bold mb-3">🎉 Verifikasi Berhasil!</h1>
            <p class="lead text-muted">Email kamu telah berhasil diverifikasi.<br>Akun kamu kini aktif dan siap digunakan.</p>

            <a href="{{ url('/login') }}" class="btn btn-primary mt-3 px-4">
                <i class="fas fa-sign-in-alt me-2"></i>Login Sekarang
            </a>

            <div class="mt-4">
                <a href="{{ url('/') }}" class="text-decoration-none text-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    <footer class="text-center small text-muted py-3">
        &copy; {{ date('Y') }} PT Virtue Dragon Nickel Industry – HR Department
    </footer>

    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" defer></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <!-- Confetti JS -->
    <script>
        const canvas = document.getElementById("confetti-canvas");
        const context = canvas.getContext("2d");

        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const confettiCount = 150;
        const confetti = [];

        for (let i = 0; i < confettiCount; i++) {
            confetti.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height - canvas.height,
                r: Math.random() * 6 + 4,
                d: Math.random() * confettiCount,
                color: `hsl(${Math.floor(Math.random() * 360)}, 100%, 50%)`,
                tilt: Math.floor(Math.random() * 10) - 10,
                tiltAngle: 0,
            });
        }

        function draw() {
            context.clearRect(0, 0, canvas.width, canvas.height);
            confetti.forEach((c, i) => {
                context.beginPath();
                context.lineWidth = c.r / 2;
                context.strokeStyle = c.color;
                context.moveTo(c.x + c.tilt + c.r / 4, c.y);
                context.lineTo(c.x + c.tilt, c.y + c.tilt + c.r / 4);
                context.stroke();
            });

            update();
        }

        function update() {
            confetti.forEach((c, i) => {
                c.tiltAngle += 0.05;
                c.y += (Math.cos(c.d) + 3 + c.r / 2) / 2;
                c.x += Math.sin(c.d);
                c.tilt = Math.sin(c.tiltAngle - i / 3) * 15;

                if (c.y > canvas.height) {
                    c.x = Math.random() * canvas.width;
                    c.y = -20;
                }
            });
        }

        function animate() {
            draw();
            requestAnimationFrame(animate);
        }

        animate();

        // Responsif saat resize
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
    </script>
</body>

</html>