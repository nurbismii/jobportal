<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Status Lamaran - PT VDNI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>

<body style="background:#f8f9fa; margin:0; padding:20px; font-family:Segoe UI,Arial,sans-serif;">

    <table width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;margin:auto;background:#ffffff;border-radius:8px;">

        <!-- Header Logo -->
        <tr>
            <td style="padding:25px;text-align:center;">
                <img src="https://recruitment.vdni.top/img/logo-vdni1.png" width="120" alt="VDNI Logo" style="display:block;margin:auto;">
            </td>
        </tr>

        <!-- Content -->
        <tr>
            <td style="padding:0 30px 30px 30px;">

                <p style="font-size:16px;margin-top:0;">
                    Halo <strong>{{ $user->name }}</strong>,
                </p>

                @if (!empty($pesan))
                <div style="
                    margin-top:15px;
                    font-size:14px;
                    background:#e6f0ff;
                    color:#004085;
                    padding:15px;
                    border-left:5px solid #015fc8;
                    border-radius:6px;
                    line-height:1.6;
                ">
                    {!! $pesan !!}
                </div>
                @endif

                <p style="font-size:15px; margin-top:20px;">
                    Kami ingin memberitahukan bahwa status lamaran kamu telah diperbarui. Berikut detailnya:
                </p>

                <p style="margin-top:15px;">
                    <strong>Posisi:</strong><br>
                    {{ $lamaran->lowongan->nama_lowongan ?? 'Posisi tidak tersedia' }}
                </p>

                <p style="margin-top:10px;font-weight:bold;">Status Saat Ini:</p>

                <p style="
                    background:#e6f0ff;
                    color:#004085;
                    padding:10px 15px;
                    border-radius:6px;
                    font-weight:bold;
                    display:inline-block;
                ">
                    {{ $status }}
                </p>

                <!-- Call To Action -->
                <div style="text-align:center; margin:30px 0;">
                    <a href="https://recruitment.vdni.top/login" target="_blank"
                        style="background:#015fc8;color:#fff;padding:14px 26px;text-decoration:none;border-radius:6px;font-size:15px;font-weight:bold;display:inline-block;">
                        Buka Portal Rekrutmen
                    </a>
                </div>

                <p style="font-size:14px;">
                    Jika kamu tidak merasa mengajukan lamaran ini, abaikan email ini.
                </p>

                <p style="font-size:12px;color:#777;margin-top:10px;">
                    Email ini dikirim otomatis — mohon tidak membalas.
                </p>

            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="padding:20px 30px;text-align:center;font-size:13px;color:#777;border-top:1px solid #eee;">
                Terima kasih telah melamar di perusahaan kami.<br>
                <strong>HRD Team - V-HIRE (PT Virtue Dragon Nickel Industry)</strong><br>
                <small>Jl. Poros Morosi, Konawe, Sulawesi Tenggara</small>
            </td>
        </tr>

    </table>

</body>

</html>