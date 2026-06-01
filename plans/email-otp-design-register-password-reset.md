# Plan: Email OTP - Style Verifikasi + Isian Register & Lupa Password

## Context

- Template OTP saat ini: `resources/views/emails/otp.blade.php` — satu template untuk register dan password_reset, style lama (container abu, otp-box biru).
- Mailable `SendOtpMail` menerima `$otp`, `$email`, `$purpose` ('register' | 'password_reset') tetapi **tidak** mengirim `$purpose` ke view.
- User meminta: style mengikuti email verifikasi (header kuning, field biru, box kuning/biru), isian register sesuai mockup gambar, isian lupa password sesuai teks yang diberikan.

## Goal

- Mengganti style `otp.blade.php` agar sama dengan email aktivasi/verifikasi (header kuning, ikon centang hijau, field email biru, OTP prominent, box penting kuning & biru).
- Menampilkan konten berbeda berdasarkan `$purpose`: register vs password_reset.
- Variabel tetap: `$otp`, `$email`, `$expiryMinutes`; tambah `$purpose` ke view.

## Detailed Specifications

### 1. File: `app/Mail/SendOtpMail.php`

- Di method `content()`, pada array `with`, tambahkan: `'purpose' => $this->purpose`.
- Tidak mengubah constructor, envelope, atau view name.

### 2. File: `resources/views/emails/otp.blade.php`

**Layout & style (sama untuk kedua purpose):**

- `lang="id"`, title dinamis: "Verifikasi Email" untuk register, "Verifikasi Ubah Kata Sandi" untuk password_reset (bisa pakai conditional di `<title>`).
- Style: reuse pola account-activation — body max-width 600px; `.wrapper`; `.header-yellow` (#F9A825); `.icon-check` (lingkaran putih, centang hijau); `.main-content` putih; `.block-detail` biru (#E3F2FD) untuk email; `.otp-box` (border biru muda, font besar, letter-spacing untuk angka); `.block-warning` kuning (#FFF8E1) untuk "Penting" expiry; `.block-info` biru (#E3F2FD) untuk keamanan/abaikan; `.footer` kecil abu.

**Konten OTP Register (`$purpose === 'register'`):**

- Header: ikon centang hijau, h1 "Verifikasi Email", subtitle "{{ config('app.name') }}".
- Body: "Halo,"; paragraf "Kami menerima permintaan untuk mendaftarkan akun pada sistem {{ config('app.name') }} menggunakan alamat email berikut:".
- Block detail: field email (ikon amplop &#128231;) + "{{ $email }}".
- Paragraf: "Untuk melanjutkan proses pendaftaran akun, silakan gunakan kode verifikasi (OTP) berikut:".
- OTP box: tampilkan {{ $otp }} (besar, bold, letter-spacing).
- Paragraf: "Masukkan kode verifikasi tersebut pada halaman pendaftaran untuk menyelesaikan proses verifikasi email dan aktivasi akun Anda."
- Block penting 1 (kuning, ikon jam): "Penting" + "Kode OTP ini berlaku selama {{ $expiryMinutes }} menit sejak email ini dikirim."
- Block penting 2 (biru, ikon info): "Jangan bagikan kode OTP ini kepada siapapun termasuk pihak yang mengaku dari {{ config('app.name') }}. Jika Anda tidak merasa melakukan pendaftaran akun, silakan abaikan email ini. Tidak ada tindakan lebih lanjut yang diperlukan."
- Footer: "Email ini dikirim secara otomatis oleh sistem {{ config('app.name') }}, mohon tidak membalas email ini." + "© {{ date('Y') }} {{ config('app.name') }}. All rights reserved."

**Konten OTP Lupa Password (`$purpose === 'password_reset'`):**

- Header: ikon centang hijau, h1 "Verifikasi Ubah Kata Sandi", subtitle "{{ config('app.name') }}".
- Body: "Halo,"; paragraf "Kami menerima permintaan untuk mengubah kata sandi akun Anda pada sistem {{ config('app.name') }} menggunakan alamat email berikut:".
- Block detail: field email + "{{ $email }}".
- Paragraf: "Untuk melanjutkan proses ubah kata sandi, silakan gunakan kode verifikasi (OTP) berikut:".
- OTP box: {{ $otp }}.
- Paragraf: "Masukkan kode verifikasi tersebut pada halaman ubah kata sandi untuk melanjutkan proses pengaturan kata sandi baru Anda."
- Block Penting (satu blok atau dua): tiga poin — (1) Kode OTP berlaku {{ $expiryMinutes }} menit; (2) Jangan bagikan kepada siapapun termasuk pihak yang mengaku dari {{ config('app.name') }}; (3) Jika tidak merasa meminta perubahan kata sandi, abaikan email ini. Tidak ada tindakan lebih lanjut yang diperlukan.
- Footer: sama seperti register.

**Struktur Blade:**

- Gunakan `@if($purpose === 'password_reset')` dan `@else` untuk membedakan judul header, paragraf intro, paragraf instruksi, dan teks blok penting. Elemen shared: wrapper, header-yellow (h1 + subtitle conditional), main-content, block email, otp-box, block(s) penting, footer.

## Implementation Checklist

1. **SendOtpMail.php:** Di method `content()`, tambah `'purpose' => $this->purpose` ke array `with`.
2. **otp.blade.php:** Ganti seluruh isi file dengan DOCTYPE, meta, title (conditional: Verifikasi Ubah Kata Sandi vs Verifikasi Email), dan blok `<style>` berisi: body, .wrapper, .header-yellow, .icon-check, .main-content, .block-detail, .email-field, .otp-box (border biru, font-size besar, letter-spacing), .block-warning (kuning), .block-info (biru), .footer.
3. **otp.blade.php:** Tambah struktur HTML: .wrapper > .header-yellow (icon-check, h1, subtitle) + .main-content. Di dalam main-content gunakan @if($purpose === 'password_reset') ... @else ... @endif untuk semua teks yang berbeda.
4. **otp.blade.php (register):** Di blok @else: h1 "Verifikasi Email", subtitle config('app.name'); intro "mendaftarkan akun"; field email; paragraf OTP pendaftaran; div.otp-box {{ $otp }}; paragraf "Masukkan kode... pendaftaran... verifikasi email dan aktivasi akun"; block kuning "Penting" + expiry; block biru jangan bagikan + abaikan pendaftaran.
5. **otp.blade.php (password_reset):** Di blok @if: h1 "Verifikasi Ubah Kata Sandi", subtitle config('app.name'); intro "mengubah kata sandi"; field email; paragraf OTP ubah kata sandi; div.otp-box {{ $otp }}; paragraf "Masukkan kode... halaman ubah kata sandi... kata sandi baru"; block Penting berisi 3 poin (expiry, jangan bagikan, abaikan jika tidak meminta).
6. **otp.blade.php:** Footer satu untuk kedua purpose: disclaimer + copyright dengan config('app.name') dan date('Y').
7. Verifikasi variabel: $otp, $email, $expiryMinutes, $purpose, config('app.name'), date('Y').

## Risks / Catatan

- Pastikan setiap pemanggil SendOtpMail mengirim purpose yang benar ('register' atau 'password_reset').
- Email client: hindari CSS kompleks; gunakan inline/embedded sederhana.
- Ikon: pakai HTML entity/Unicode (&#128231;, &#9432;, dll) agar kompatibel.
