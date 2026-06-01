<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $purpose === 'password_reset' ? 'Verifikasi Ubah Kata Sandi' : 'Verifikasi Email' }}</title>
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
            margin: 0 0 12px 0;
        }
        .text-last {
            margin-bottom: 20px;
        }
        .strong {
            font-weight: 600;
        }
        .email-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 24px;
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
        .otp-wrap {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 16px;
            padding: 24px 16px;
            margin: 0 0 20px 0;
            text-align: center;
        }
        .otp-code {
            margin: 0;
            font-size: 36px;
            font-weight: 700;
            color: #111827;
            letter-spacing: 0.3em;
        }
        .notice {
            background-color: #fffbeb;
            border-left: 4px solid #fbbf24;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
        }
        .notice-inner {
            width: 100%;
            border-collapse: collapse;
        }
        .notice-inner td.icon-cell {
            width: 24px;
            vertical-align: top;
            padding-top: 2px;
            font-size: 14px;
        }
        .notice-title {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin: 0 0 4px 0;
        }
        .notice-text {
            font-size: 12px;
            color: #374151;
            margin: 0;
            line-height: 1.6;
        }
        .info {
            background-color: #eff6ff;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 24px;
        }
        .info-inner {
            width: 100%;
            border-collapse: collapse;
        }
        .info-inner td.icon-cell {
            width: 24px;
            vertical-align: top;
            padding-top: 2px;
            color: #2563eb;
            font-size: 14px;
        }
        .info-text {
            font-size: 12px;
            color: #374151;
            margin: 0 0 8px 0;
            line-height: 1.6;
        }
        .info-text:last-child {
            margin-bottom: 0;
        }
        .info-lead {
            font-weight: 600;
            color: #1e3a8a;
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
        @media only screen and (max-width: 480px) {
            .otp-code {
                font-size: 28px;
                letter-spacing: 0.2em;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="card">
            <div class="header">
                <div class="icon-wrap">&#10003;</div>
                @if($purpose === 'password_reset')
                    <h1 class="title">Verifikasi Ubah Kata Sandi</h1>
                @else
                    <h1 class="title">Verifikasi Email</h1>
                @endif
                <p class="subtitle">{{ config('app.name') }}</p>
            </div>

            <div class="body">
                <p class="text">Halo,</p>

                @if($purpose === 'password_reset')
                    <p class="text text-last">
                        Kami menerima permintaan untuk mengubah kata sandi akun Anda pada sistem
                        <span class="strong">{{ config('app.name') }}</span>
                        menggunakan alamat email berikut:
                    </p>
                @else
                    <p class="text text-last">
                        Kami menerima permintaan untuk mendaftarkan akun pada sistem
                        <span class="strong">{{ config('app.name') }}</span>
                        menggunakan alamat email berikut:
                    </p>
                @endif

                <div class="email-box">
                    <table class="email-row" role="presentation" cellpadding="0" cellspacing="0">
                        <tr>
                            <td width="40" style="width:40px;">
                                <div class="mail-badge">&#128231;</div>
                            </td>
                            <td class="email-value">{{ $email }}</td>
                        </tr>
                    </table>
                </div>

                @if($purpose === 'password_reset')
                    <p class="text text-last">
                        Untuk melanjutkan proses ubah kata sandi, silakan gunakan kode verifikasi (OTP) berikut:
                    </p>
                @else
                    <p class="text text-last">
                        Untuk melanjutkan proses pendaftaran akun, silakan gunakan kode verifikasi (OTP) berikut:
                    </p>
                @endif

                <div class="otp-wrap">
                    <p class="otp-code">{{ $otp }}</p>
                </div>

                @if($purpose === 'password_reset')
                    <p class="text text-last">
                        Masukkan kode verifikasi tersebut pada halaman ubah kata sandi untuk melanjutkan proses pengaturan kata sandi baru Anda.
                    </p>

                    <div class="notice">
                        <table class="notice-inner" role="presentation" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="icon-cell">&#128338;</td>
                                <td>
                                    <p class="notice-title">Penting</p>
                                    <p class="notice-text">
                                        Kode OTP ini berlaku selama <span class="strong">{{ $expiryMinutes }} menit</span> sejak email ini dikirim.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="info">
                        <table class="info-inner" role="presentation" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="icon-cell">&#9432;</td>
                                <td>
                                    <p class="info-text">
                                        <span class="info-lead">Jangan bagikan kode OTP ini kepada siapapun</span>
                                        termasuk pihak yang mengaku dari {{ config('app.name') }}.
                                    </p>
                                    <p class="info-text">
                                        Jika Anda tidak merasa melakukan permintaan perubahan kata sandi, silakan abaikan email ini. Tidak ada tindakan lebih lanjut yang diperlukan.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                @else
                    <p class="text text-last">
                        Masukkan kode verifikasi tersebut pada halaman pendaftaran untuk menyelesaikan proses verifikasi email dan aktivasi akun Anda.
                    </p>

                    <div class="notice">
                        <table class="notice-inner" role="presentation" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="icon-cell">&#128338;</td>
                                <td>
                                    <p class="notice-title">Penting</p>
                                    <p class="notice-text">
                                        Kode OTP ini berlaku selama <span class="strong">{{ $expiryMinutes }} menit</span> sejak email ini dikirim.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="info">
                        <table class="info-inner" role="presentation" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="icon-cell">&#9432;</td>
                                <td>
                                    <p class="info-text">
                                        <span class="info-lead">Jangan bagikan kode OTP ini kepada siapapun</span>
                                        termasuk pihak yang mengaku dari {{ config('app.name') }}.
                                    </p>
                                    <p class="info-text">
                                        Jika Anda tidak merasa melakukan pendaftaran akun, silakan abaikan email ini. Tidak ada tindakan lebih lanjut yang diperlukan.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                @endif

                <div class="footer">
                    <p class="footer-muted">
                        Email ini dikirim secara otomatis oleh sistem {{ config('app.name') }}, mohon tidak membalas email ini.
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
