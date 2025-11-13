<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email Anda</title>
</head>

<body style="margin:0;padding:0;background-color:#f4f4f4;font-family: 'Segoe UI', sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 0 8px rgba(0,0,0,0.05);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background-color:#015fc8;padding:40px 20px;">
                            <img src="{{ asset('img/logo-vdni1.png') }}" alt="Logo VDNI" width="120" style="display:block;">
                        </td>
                    </tr>

                    <!-- Title -->
                    <tr>
                        <td align="center" style="padding:40px 30px 10px;">
                            <h2 style="font-size:24px;color:#333;margin:0;">Halo, {{ $data['name'] }}!</h2>
                        </td>
                    </tr>

                    <!-- Subtext -->
                    <tr>
                        <td align="center" style="padding:0 30px 20px;">
                            <p style="font-size:16px;color:#666;margin:0;">Terima kasih telah mendaftar. Silakan verifikasi email Anda dengan mengklik tombol di bawah ini.</p>
                        </td>
                    </tr>

                    <!-- CTA -->
                    <tr>
                        <td align="center" style="padding:20px;">
                            <a href="{{ url('konfirmasi-email/' . $data['id']) }}"
                                style="background-color:#015fc8;color:#ffffff;padding:14px 28px;border-radius:50px;text-decoration:none;font-weight:600;text-transform:uppercase;display:inline-block;font-size:14px;">
                                Verifikasi Email
                            </a>
                        </td>
                    </tr>

                    <!-- Fallback link -->
                    <tr>
                        <td align="center" style="padding:10px 30px 30px;">
                            <p style="font-size:13px;color:#999;margin:0;">
                                Tidak bisa klik tombol di atas? Salin dan tempelkan link berikut ke browser Anda:<br>
                                <a href="{{ url('konfirmasi-email-token/' . $data['email_verifikasi_token']) }}" style="color:#015fc8;word-break:break-all;">{{ url('konfirmasi-email-token/' . $data['email_verifikasi_token']) }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color:#fafafa;padding:20px 30px;font-size:12px;color:#999;">
                            <p style="margin:0;">Email ini dikirim oleh HR Site VDNI | Konawe, Sulawesi Tenggara</p>
                            <p style="margin:5px 0 0;">Jika Anda tidak merasa melakukan pendaftaran, Anda bisa mengabaikan email ini.</p>
                            <p style="margin:15px 0 0;">
                                Hubungi kami di <a href="mailto:recruitment@vdni.co.id" style="color:#999;text-decoration:underline;">recruitment@vdni.co.id</a>
                            </p>
                            <p style="margin:15px 0 0;">
                                <a href="https://www.instagram.com/hr_vdni?igsh=cTJ6Z2VkeDkwb2pp" target="_blank">
                                    <img src="http://email.aumfusion.com/vespro/img/social/light/instagram.png" width="30" style="margin:0 5px;">
                                </a>
                                <a href="https://www.linkedin.com/company/pt-virtue-dragon-nickel-industry/?viewAsMember=true" target="_blank">
                                    <img src="http://email.aumfusion.com/vespro/img/social/light/linkdin.png" width="30" style="margin:0 5px;">
                                </a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>

</html>