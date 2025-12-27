# Plan: Forgot Password with OTP (3 Endpoints)

## Context
- Saat ini hanya ada flow OTP untuk registrasi: `/auth/request-otp` dan `/auth/register` memakai `OtpService` + `email_verifications`.
- Tidak ada endpoint forgot/reset password. Tabel `password_reset_tokens` sudah ada (default Laravel).
- OTP disimpan tanpa konteks/purpose, dan `storeOtp` menghapus OTP existing per email.

## Goal
- Menambah flow reset password berbasis email + OTP dengan dua layar (OTP lalu password baru).
- Menyediakan 3 endpoint: kirim OTP reset, verifikasi OTP reset (keluarkan token reset), dan reset password dengan token tersebut.
- Reuse mekanisme OTP yang ada tanpa mengganggu flow registrasi.

## Detailed Specifications
- **Database**
  - Tambah kolom `purpose` di `email_verifications` (default `register`, indexed) untuk bedakan OTP register vs password reset.
- **Model**
  - `app/Models/EmailVerification.php`: tambahkan `purpose` ke `$fillable`, `casts` jika perlu, dan scope `forPurpose($purpose)` untuk filter.
- **Service**
  - `app/Services/OtpService.php`: ubah signature `storeOtp` & `verifyOtp` agar menerima `$purpose = 'register'`; saat simpan dan query sertakan `purpose`; `checkRateLimit` tetap per email.
  - (Opsional namun disarankan) `app/Services/PasswordResetService.php` baru untuk kelola token reset: buat token random, simpan hash ke `password_reset_tokens`, TTL (misal 60 menit), verifikasi & revoke.
- **Requests (Form Request)**
  - `app/Http/Requests/ForgotPasswordRequest.php`: validasi `email` exists:users,email.
  - `app/Http/Requests/VerifyResetOtpRequest.php`: validasi `email` exists:users,email dan `otp` string size:6.
  - `app/Http/Requests/ResetPasswordRequest.php`: `email` exists:users,email, `reset_token` required, `password` min:8 confirmed.
- **Controller**
  - `app/Http/Controllers/Api/AuthController.php`: tambah methods:
    - `forgotPassword(ForgotPasswordRequest $request)`: cek rate limit, generate + store OTP dengan purpose `password_reset`, dispatch email job, kembalikan expires_in.
    - `verifyResetOtp(VerifyResetOtpRequest $request)`: verifikasi OTP dengan purpose `password_reset`; jika valid, buat `reset_token` via `PasswordResetService`, hapus OTP untuk purpose tersebut, return `reset_token`.
    - `resetPassword(ResetPasswordRequest $request)`: validasi `reset_token` via service; update password user (hash), revoke token(s) untuk email, return success message.
  - Update existing register flow untuk melewatkan purpose `register` ke OtpService (store & verify).
- **Routes**
  - `routes/api.php`: tambah public routes `POST /auth/forgot-password`, `POST /auth/verify-reset-otp`, `POST /auth/reset-password` ke AuthController.
- **Mail/Job**
  - Reuse `SendOtpEmailJob` / `SendOtpMail`; tambahkan subject atau view variable optional untuk context reset password (minimal: subject “Password Reset OTP” ketika purpose `password_reset`).
- **Config**
  - Tambah config (misal `config/password_reset.php`) untuk TTL token reset (e.g., 60 minutes).
- **Docs**
  - Update `API_DOCUMENTATION_COMPLETE.md` section Authentication: tambah tiga endpoint baru dengan request/response sample dan notes mailer=log.

## Implementation Checklist
1. Buat migration baru untuk menambah kolom `purpose` (default `register`, index) di tabel `email_verifications`.
2. Update `app/Models/EmailVerification.php` menambahkan `purpose` ke `$fillable` dan scope `forPurpose`.
3. Refactor `app/Services/OtpService.php` agar `storeOtp`/`verifyOtp` menerima `$purpose = 'register'` dan filter by purpose; update pemanggilan di register flow agar memakai purpose register.
4. Tambah `app/Services/PasswordResetService.php` untuk generate/verify/revoke token berbasis `password_reset_tokens` dengan TTL config.
5. Tambah form requests: `ForgotPasswordRequest`, `VerifyResetOtpRequest`, `ResetPasswordRequest` dengan validasi sesuai.
6. Update `app/Http/Controllers/Api/AuthController.php` menambah methods forgotPassword, verifyResetOtp, resetPassword; gunakan OtpService dengan purpose `password_reset` dan PasswordResetService; pastikan register flow melewatkan purpose register.
7. Tambah routes publik di `routes/api.php` untuk tiga endpoint baru.
8. (Opsional ringan) Update `SendOtpMail`/`SendOtpEmailJob` untuk subject konteks password reset bila purpose `password_reset`.
9. Tambah config `config/password_reset.php` (TTL token) dan gunakan di service.
10. Update `API_DOCUMENTATION_COMPLETE.md` dengan tiga endpoint baru beserta contoh request/response.
