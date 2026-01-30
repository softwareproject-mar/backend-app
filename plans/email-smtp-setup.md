# Plan: Enable Real Email Delivery (SMTP)

## Context
- Saat ini `MAIL_MAILER=log`, sehingga OTP hanya tercatat di `storage/logs/laravel.log`.
- Pengiriman OTP dilakukan via job `SendOtpEmailJob` + `SendOtpMail`, sehingga butuh queue worker aktif.
- Konfigurasi mailer sudah mendukung SMTP via env (`config/mail.php`), tinggal mengisi env yang benar.

## Goal
- Mengganti mailer dari log ke SMTP untuk mengirim OTP ke email asli.
- Menyiapkan `.env` lokal dengan kredensial SMTP (pakai email pribadi untuk uji coba).
- Memastikan queue worker berjalan agar job email dieksekusi.

## Detailed Specifications
- File `.env` (lokal):
  - Set `MAIL_MAILER=smtp`.
  - Isi `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION` sesuai provider (mis. Gmail: host `smtp.gmail.com`, port `587`, encryption `tls`).
  - Set `MAIL_FROM_ADDRESS` ke email pengirim, `MAIL_FROM_NAME` ke nama aplikasi.
  - Pastikan `QUEUE_CONNECTION=database` (atau `redis` jika tersedia) agar job dikirim ke worker.
- File `config/mail.php`:
  - Tidak ada perubahan kode; pastikan membaca env yang diisi.
- Operasional:
  - Jalankan queue worker `php artisan queue:work` di environment test agar job email diproses.
  - Lakukan uji coba request OTP (register atau forgot password) dan pastikan email masuk ke inbox.
  - Jika ingin fallback, opsional gunakan `MAIL_MAILER=failover` dengan `smtp` + `log`.

## Implementation Checklist
1. Backup/cek `.env` lokal yang akan dipakai untuk uji coba.
2. Update `.env` lokal: set `MAIL_MAILER=smtp`, isi `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, dan pastikan `QUEUE_CONNECTION=database`.
3. (Opsional) Jika perlu fallback, set `MAIL_MAILER=failover` dan definisikan mailers di env agar `smtp` jadi prioritas, `log` sebagai cadangan.
4. Pastikan queue worker berjalan: `php artisan queue:work` di terminal environment uji.
5. Lakukan uji kirim OTP (mis. `POST /auth/request-otp` atau `POST /auth/forgot-password`) dan verifikasi email terkirim ke inbox pribadi.
6. Dokumentasikan hasil uji (berhasil/gagal, log error jika ada) dan sesuaikan env bila perlu.

## Risks / Catatan
- Gmail membutuhkan App Password (2FA wajib); rate limit harian bisa terkena jika spam OTP.
- Pastikan tidak commit kredensial `.env` (hanya lokal). Untuk production gunakan domain terverifikasi agar deliverability baik.
- Queue worker harus tetap hidup; tanpa worker, job email tidak akan terkirim meski SMTP sudah benar.
