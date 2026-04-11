<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Berhasil Dipulihkan</title>
</head>

<body style="margin:0;padding:24px;background:#f5f7fb;font-family:'Segoe UI',sans-serif;color:#1f2937;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="background:#015fc8;padding:24px 32px;color:#ffffff;">
                <h1 style="margin:0;font-size:24px;">Akun V-HIRE Berhasil Dipulihkan</h1>
                <p style="margin:8px 0 0;font-size:14px;opacity:.9;">Berikut akun login terbaru yang telah dibuat oleh admin.</p>
            </td>
        </tr>
        <tr>
            <td style="padding:32px;">
                <p style="margin:0 0 16px;">Halo, {{ $data['name'] }}.</p>
                <p style="margin:0 0 16px;">Permintaan lupa akun Anda telah disetujui. Silakan gunakan data akun berikut untuk login:</p>

                <table width="100%" cellpadding="10" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;margin:24px 0;">
                    <tr>
                        <td width="35%" style="border-bottom:1px solid #e5e7eb;"><strong>No KTP</strong></td>
                        <td style="border-bottom:1px solid #e5e7eb;">{{ $data['no_ktp'] }}</td>
                    </tr>
                    <tr>
                        <td width="35%" style="border-bottom:1px solid #e5e7eb;"><strong>Email login baru</strong></td>
                        <td style="border-bottom:1px solid #e5e7eb;">{{ $data['email'] }}</td>
                    </tr>
                    <tr>
                        <td width="35%"><strong>Password sementara</strong></td>
                        <td>{{ $data['password'] }}</td>
                    </tr>
                </table>

                <div style="margin:32px 0;text-align:center;">
                    <a href="{{ $data['login_url'] }}" style="display:inline-block;background:#015fc8;color:#fff;text-decoration:none;padding:14px 30px;border-radius:999px;font-weight:600;">
                        Login Sekarang
                    </a>
                </div>

                <p style="margin:0 0 12px;">Demi keamanan akun, segera ganti password setelah berhasil login.</p>
                <p style="margin:0;color:#6b7280;font-size:14px;">Jika Anda tidak merasa mengajukan pemulihan akun ini, segera hubungi tim recruitment.</p>
            </td>
        </tr>
    </table>
</body>

</html>
