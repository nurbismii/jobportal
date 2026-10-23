<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject }}</title>
</head>
<body style="margin:0;padding:24px 12px;background:#f8f9fa;font-family:Segoe UI,Arial,sans-serif;color:#253858;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #e6edf5;border-radius:8px;">
        <tr>
            <td style="height:6px;background:#015fc8;border-radius:8px 8px 0 0;"></td>
        </tr>
        <tr>
            <td style="padding:28px 24px 20px;text-align:center;">
                <img src="https://recruitment.vdnisite.com/img/logo-vdni1.png" width="120" alt="VDNI Logo" style="display:block;margin:0 auto;border:0;">
            </td>
        </tr>
        <tr>
            <td style="padding:4px 28px 28px;">
                <p style="margin:0 0 10px;font-size:12px;font-weight:bold;letter-spacing:1px;color:#015fc8;">INFORMASI REKRUTMEN</p>
                <h1 style="margin:0 0 22px;font-size:23px;line-height:1.4;color:#004085;overflow-wrap:anywhere;">{{ $emailSubject }}</h1>
                <div style="padding:18px 20px;background:#e6f0ff;border-left:5px solid #015fc8;border-radius:6px;font-size:15px;line-height:1.7;color:#004085;overflow-wrap:anywhere;">
                    {!! $messageHtml !!}
                </div>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:26px auto;">
                    <tr>
                        <td style="background:#015fc8;border-radius:6px;text-align:center;">
                            <a href="{{ route('login') }}" target="_blank" style="display:inline-block;padding:14px 24px;font-size:14px;font-weight:bold;color:#ffffff;text-decoration:none;">Buka Portal Rekrutmen</a>
                        </td>
                    </tr>
                </table>
                <p style="margin:0;font-size:12px;line-height:1.6;color:#777777;">Email ini dikirim melalui portal rekrutmen V-HIRE. Mohon tidak membalas email ini.</p>
            </td>
        </tr>
        <tr>
            <td style="padding:20px 24px;border-top:1px solid #e6edf5;text-align:center;font-size:12px;line-height:1.8;color:#777777;">
                <strong style="color:#004085;">HRD Team · V-HIRE</strong><br>
                PT Virtue Dragon Nickel Industry<br>
                Jl. Poros Morosi, Konawe, Sulawesi Tenggara
            </td>
        </tr>
    </table>
</body>
</html>
