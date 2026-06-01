<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Akun</title>
    <style>
        body {
            margin: 0;
            padding: 24px 16px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f3f4f6;
            color: #374151;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        .shell {
            max-width: 448px;
            margin: 0 auto;
        }
        .card {
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #eab308;
            padding: 40px 24px;
            text-align: center;
        }
        .icon-wrap {
            display: inline-block;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background-color: #ffffff;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.12);
            line-height: 64px;
            margin-bottom: 16px;
            font-size: 28px;
            color: #22c55e;
        }
        .title {
            margin: 0 0 8px 0;
            font-size: 24px;
            font-weight: 700;
            color: #1e3a8a;
        }
        .subtitle {
            margin: 0;
            font-size: 14px;
            color: #1e3a8a;
        }
        .body {
            padding: 24px;
        }
        .text {
            font-size: 14px;
            color: #374151;
            margin: 0 0 16px 0;
        }
        .strong {
            font-weight: 600;
            color: #2563eb;
        }
        .email-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 20px;
        }
        .email-label {
            font-size: 14px;
            font-weight: 600;
            color: #1e3a8a;
            margin: 0 0 4px 0;
        }
        .email-hint {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 12px 0;
        }
        .email-row {
            width: 100%;
            border-collapse: collapse;
        }
        .email-row td {
            vertical-align: middle;
        }
        .mail-badge {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background-color: #2563eb;
            text-align: center;
            line-height: 36px;
            font-size: 16px;
        }
        .email-value {
            padding-left: 12px;
            font-size: 14px;
            font-weight: 500;
            color: #111827;
            word-break: break-all;
        }
        .success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #166534;
        }
        .info {
            background-color: #fffbeb;
            border-left: 4px solid #fbbf24;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 24px;
        }
        .info-title {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin: 0 0 12px 0;
        }
        .info ul {
            margin: 0;
            padding-left: 18px;
            font-size: 12px;
            color: #374151;
            line-height: 1.6;
        }
        .info li {
            margin-bottom: 8px;
        }
        .footer {
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .footer-muted {
            font-size: 12px;
            color: #6b7280;
            text-align: center;
            margin: 0 0 8px 0;
            line-height: 1.6;
        }
        .footer-copy {
            font-size: 12px;
            color: #9ca3af;
            text-align: center;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="card">
            <div class="header">
                <div class="icon-wrap">&#10003;</div>
                <h1 class="title">Akun Berhasil Diaktifkan!</h1>
                <p class="subtitle">Selamat datang di {{ config('app.name') }}</p>
            </div>

            <div class="body">
                <p class="text"><strong>Halo! &#128075;</strong></p>

                <p class="text">
                    Kabar baik! Akun Anda telah <span class="strong">disetujui dan diaktifkan</span> oleh Administrator kami. Anda sekarang dapat mengakses semua fitur yang tersedia di sistem.
                </p>

                <div class="email-box">
                    <p class="email-label">&#128231; Detail Akun Terdaftar</p>
                    <p class="email-hint">Email untuk login</p>
                    <table class="email-row" role="presentation" cellpadding="0" cellspacing="0">
                        <tr>
                            <td width="40" style="width:40px;">
                                <div class="mail-badge">&#128231;</div>
                            </td>
                            <td class="email-value">{{ $user->email }}</td>
                        </tr>
                    </table>
                </div>

                <div class="success">
                    <strong>&#10003;</strong> Gunakan email dan password yang sudah Anda daftarkan untuk login ke aplikasi. Semua layanan sudah dapat diakses sekarang.
                </div>

                <div class="info">
                    <p class="info-title">&#9432; Informasi Penting</p>
                    <ul>
                        <li>Akun Anda telah berhasil diaktifkan dan siap digunakan untuk mengakses sistem {{ config('app.name') }}.</li>
                        <li>Jika Anda tidak merasa melakukan pendaftaran, silakan hubungi administrator segera.</li>
                        <li>Jaga kerahasiaan password Anda dan jangan bagikan kepada siapapun.</li>
                    </ul>
                </div>

                <div class="footer">
                    <p class="footer-muted">
                        Email ini dikirim secara otomatis oleh sistem. Mohon tidak membalas email ini.
                    </p>
                    <p class="footer-copy">
                        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
