<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftar menunggu persetujuan</title>
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
            color: #1e3a8a;
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
            margin: 0 0 8px 0;
        }
        .detail-row {
            font-size: 13px;
            color: #374151;
            margin: 0 0 6px 0;
            padding-left: 4px;
        }
        .detail-key {
            color: #6b7280;
            display: inline-block;
            min-width: 120px;
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
        .warn {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #92400e;
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
                <div class="icon-wrap">&#128276;</div>
                @if($isReminder)
                    <h1 class="title">Pengingat persetujuan</h1>
                    <p class="subtitle">{{ config('app.name') }} — Web Administrasi</p>
                @else
                    <h1 class="title">Pendaftar baru</h1>
                    <p class="subtitle">Perlu tindakan di menu Persetujuan Akun</p>
                @endif
            </div>

            <div class="body">
                <p class="text"><strong>Halo Administrator,</strong></p>

                @if($isReminder)
                    <p class="text">
                        Ini adalah <span class="strong">pengingat otomatis (H+3)</span>: masih ada pendaftar yang berstatus
                        <span class="strong">menunggu persetujuan</span> dan belum diproses.
                    </p>
                @else
                    <p class="text">
                        Ada <span class="strong">pendaftar baru</span> yang telah memverifikasi email dan menunggu persetujuan Anda
                        sebelum akunnya dapat digunakan sepenuhnya.
                    </p>
                @endif

                <div class="email-box">
                    <p class="email-label">&#128100; Akun yang perlu diaktifkan</p>
                    <p class="detail-row"><span class="detail-key">Nama</span> {{ $pendingUser->name }}</p>
                    <p class="detail-row"><span class="detail-key">Email</span> {{ $pendingUser->email }}</p>
                    <p class="detail-row"><span class="detail-key">Tanggal daftar</span> {{ $pendingUser->created_at?->timezone(config('app.timezone'))->format('d/m/Y') }}</p>
                    <p class="detail-row"><span class="detail-key">ID pengguna</span> {{ $pendingUser->id }}</p>
                </div>

                @if($isReminder)
                    <div class="warn">
                        <strong>&#9200;</strong> Mohon tinjau di <strong>Persetujuan Akun</strong> pada Web Administrasi: setujui untuk mengaktifkan akun atau tolak sesuai kebijakan.
                    </div>
                @else
                    <div class="success">
                        <strong>&#10003;</strong> Silakan buka halaman <strong>Persetujuan Akun</strong> di panel admin atau super admin untuk menyetujui atau menolak pendaftaran ini.
                    </div>
                @endif

                <div class="info">
                    <p class="info-title">&#9432; Informasi</p>
                    <ul>
                        <li>Pendaftar belum dapat login penuh ke fitur aplikasi sampai akun disetujui dan diaktifkan.</li>
                        <li>Setelah disetujui, sistem dapat mengirim email aktivasi ke alamat pendaftar (sesuai pengaturan server).</li>
                        <li>Jika pendaftar sudah diproses, abaikan email pengingat ini (pesan dikirim otomatis berdasarkan jadwal).</li>
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
