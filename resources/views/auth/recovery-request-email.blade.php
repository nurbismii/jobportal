<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Pemulihan Akun</title>
</head>

<body style="margin:0;padding:24px;background:#f5f7fb;font-family:'Segoe UI',sans-serif;color:#1f2937;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:700px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="background:#015fc8;padding:24px 32px;color:#ffffff;">
                <h1 style="margin:0;font-size:24px;">Permintaan Pemulihan Akun</h1>
                <p style="margin:8px 0 0;font-size:14px;opacity:.9;">V-HIRE menerima pengajuan pemulihan akun tanpa akses email lama.</p>
            </td>
        </tr>
        <tr>
            <td style="padding:32px;">
                <p style="margin:0 0 20px;font-size:14px;color:#4b5563;">
                    <strong>ID Request:</strong> #{{ $data['request_id'] ?? '-' }}
                </p>

                <h2 style="margin:0 0 16px;font-size:18px;color:#111827;">Data yang diinput pengguna</h2>
                <table width="100%" cellpadding="8" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;">
                    <tr>
                        <td width="35%" style="border-bottom:1px solid #e5e7eb;"><strong>Nama pengaju</strong></td>
                        <td style="border-bottom:1px solid #e5e7eb;">{{ $data['input_name'] }}</td>
                    </tr>
                    <tr>
                        <td width="35%" style="border-bottom:1px solid #e5e7eb;"><strong>No KTP</strong></td>
                        <td style="border-bottom:1px solid #e5e7eb;">{{ $data['input_no_ktp'] }}</td>
                    </tr>
                    <tr>
                        <td width="35%" style="border-bottom:1px solid #e5e7eb;"><strong>Email baru</strong></td>
                        <td style="border-bottom:1px solid #e5e7eb;">{{ $data['input_new_email'] }}</td>
                    </tr>
                    <tr>
                        <td width="35%" style="border-bottom:1px solid #e5e7eb;"><strong>No telepon</strong></td>
                        <td style="border-bottom:1px solid #e5e7eb;">{{ $data['input_phone'] ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td width="35%"><strong>Keterangan</strong></td>
                        <td>{{ $data['input_notes'] ?: '-' }}</td>
                    </tr>
                </table>

                <h2 style="margin:32px 0 16px;font-size:18px;color:#111827;">Data akun yang ditemukan</h2>
                <table width="100%" cellpadding="8" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;">
                    <tr>
                        <td width="35%" style="border-bottom:1px solid #e5e7eb;"><strong>Nama akun</strong></td>
                        <td style="border-bottom:1px solid #e5e7eb;">{{ $data['registered_name'] }}</td>
                    </tr>
                    <tr>
                        <td width="35%" style="border-bottom:1px solid #e5e7eb;"><strong>Email terdaftar</strong></td>
                        <td style="border-bottom:1px solid #e5e7eb;">{{ $data['registered_email'] }}</td>
                    </tr>
                    <tr>
                        <td width="35%" style="border-bottom:1px solid #e5e7eb;"><strong>Status akun</strong></td>
                        <td style="border-bottom:1px solid #e5e7eb;">{{ $data['registered_status_akun'] === 1 ? 'Aktif / Terverifikasi' : 'Belum aktif / Belum verifikasi' }}</td>
                    </tr>
                    <tr>
                        <td width="35%" style="border-bottom:1px solid #e5e7eb;"><strong>Tanggal lahir biodata</strong></td>
                        <td style="border-bottom:1px solid #e5e7eb;">{{ $data['registered_tanggal_lahir'] ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td width="35%"><strong>No telepon biodata</strong></td>
                        <td>{{ $data['registered_phone'] ?: '-' }}</td>
                    </tr>
                </table>

                <p style="margin:24px 0 0;font-size:14px;color:#4b5563;">
                    Tindak lanjuti permintaan ini secara manual sebelum melakukan perubahan email akun.
                </p>
            </td>
        </tr>
    </table>
</body>

</html>
