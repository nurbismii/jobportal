<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Status Lamaran - PT VDNI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>

<body style="font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px; color: #333;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; margin:auto; background-color:#ffffff; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.05);">
        <tr>
            <td style="padding: 20px; text-align: center;">
                <img src="https://recruitment.vdni.top/img/logo-vdni1.png" alt="Logo VDNI" width="120" style="display:block; margin:auto;">
            </td>
        </tr>
        <tr>
            <td style="padding: 0 30px 10px 30px;">
                <p style="font-size: 16px;">Halo <strong>{{ $user->name }}</strong>,</p>
                @if (!empty($pesan))
                <div style="margin-top: 20px; font-size: 14px; white-space: pre-line; background-color: #e6f0ff; color: #004085; padding: 15px; border-left: 4px solid #015fc8; border-radius: 6px;">
                    {!! nl2br(e($pesan)) !!}
                </div>
                @endif
                <p style="font-size: 15px;">Kami ingin memberitahukan bahwa status lamaran kamu telah diperbarui. Berikut adalah detailnya:</p>

                <p style="margin-bottom: 5px;"><strong>Posisi:</strong><br> {{ $lamaran->lowongan->nama_lowongan ?? 'Posisi tidak tersedia' }}</p>

                <p style="margin-bottom: 5px;"><strong>Status Saat Ini:</strong></p>
                <p style="background-color: #e6f0ff; color: #004085; padding: 10px 15px; border-radius: 6px; font-weight: bold; display: inline-block;">
                    {{ $status }}
                </p>

                <p style="margin-top: 20px;">Untuk informasi selengkapnya, silakan login ke portal rekrutmen kami.</p>

                <div style="text-align: center; margin: 20px 0;">
                    <a href="https://recruitment.vdni.top/login" target="_blank" style="background-color: #015fc8; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 15px;">
                        Buka Portal Rekrutmen
                    </a>
                </div>

                <p style="font-size: 14px;">Jika kamu tidak merasa mengajukan lamaran ini, abaikan email ini.</p>

                <p style="font-size: 12px; color: #777;">Email ini dikirim otomatis, mohon tidak membalas.</p>
            </td>
        </tr>
        <tr>
            <td style="padding: 20px 30px; font-size: 13px; color: #777; text-align: center;">
                Terima kasih telah melamar di perusahaan kami.<br>
                <strong>HRD Team - V-HIRE (PT Virtue Dragon Nickel Industry)</strong><br>
                <small>Jl. Poros Morosi, Konawe, Sulawesi Tenggara</small>
            </td>
        </tr>
    </table>
</body>

</html>