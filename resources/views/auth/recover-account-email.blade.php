<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pemulihan Akun</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f7;
            font-family: 'Segoe UI', sans-serif;
        }

        table {
            border-collapse: collapse;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f4f4f7;
            padding: 20px 0;
        }

        .email-content {
            width: 100%;
            max-width: 600px;
            margin: auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .email-header {
            background-color: #015fc8;
            padding: 24px;
            text-align: center;
            color: white;
        }

        .email-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .email-body {
            padding: 32px 24px;
            color: #333333;
            font-size: 16px;
            line-height: 1.6;
        }

        .email-body p {
            margin: 0 0 16px;
        }

        .email-button {
            text-align: center;
            margin: 32px 0;
        }

        .email-button a {
            background-color: #015fc8;
            color: #ffffff;
            padding: 14px 32px;
            font-size: 16px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
        }

        .email-footer {
            padding: 24px;
            font-size: 13px;
            color: #888888;
            text-align: center;
        }

        .logo {
            max-height: 40px;
            margin-bottom: 16px;
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="email-content">
            <div class="email-header">
                <img src="{{ asset('img/logo-vdni1.png') }}" alt="VDNI Logo" class="logo" />
                <h1>Pemulihan Akun Anda</h1>
            </div>

            <div class="email-body">
                <p>Halo, {{ $data['name'] }}.</p>
                <p>Kami menerima permintaan pemulihan akun untuk NIK {{ $data['no_ktp'] }} dengan email {{ $data['email'] }}.</p>
                <p>Klik tombol di bawah ini untuk mengganti email akun dan membuat kata sandi baru.</p>

                <div class="email-button">
                    <a href="{{ url('lupa-akun/token/' . $data['token']) }}" target="_blank">Pulihkan Akun</a>
                </div>

                <p>Tautan ini hanya berlaku selama 60 menit. Jika Anda tidak merasa meminta pemulihan akun, abaikan email ini.</p>
                <p>Terima kasih,<br>Tim SISTEM VDNI</p>
            </div>

            <div class="email-footer">
                &copy; {{ date('Y') }} SISTEM VDNI. Semua hak dilindungi.
            </div>
        </div>
    </div>
</body>

</html>
