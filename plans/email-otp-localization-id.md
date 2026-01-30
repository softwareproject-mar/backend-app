# Plan: Lokalisasi Email OTP ke Bahasa Indonesia

## Context
- Email OTP saat ini memakai Bahasa Inggris di view `resources/views/emails/otp.blade.php` (heading, salam, penjelasan OTP, info kedaluwarsa, catatan).
- Subject email di `app/Mail/SendOtpMail.php` memakai prefix “Your OTP Code” atau “Password Reset OTP” + `config('app.name')`.
- Ada teks rusak pada label “Important” di view (karakter aneh).

## Goal
- Mengubah seluruh konten email OTP menjadi Bahasa Indonesia yang jelas dan natural.
- Menyesuaikan subjek email OTP ke Bahasa Indonesia sesuai konteks register/password reset.
- Membersihkan teks rusak pada label “Important”.

## Detailed Specifications
- File: `resources/views/emails/otp.blade.php`
  - Ubah heading “Email Verification” menjadi Bahasa Indonesia.
  - Ubah salam “Hello,” menjadi salam Bahasa Indonesia.
  - Terjemahkan paragraf penjelasan permintaan OTP dan instruksi memasukkan kode.
  - Ubah label “Important” dan isi info kedaluwarsa ke Bahasa Indonesia, hilangkan karakter aneh.
  - Ubah catatan “If you did not request this code...” ke Bahasa Indonesia.
  - Ubah footer automated message ke Bahasa Indonesia.
- File: `app/Mail/SendOtpMail.php`
  - Ubah subject prefix:
    - `register` → “Kode OTP Anda”
    - `password_reset` → “OTP Reset Kata Sandi”
  - Tetap gabungkan dengan `config('app.name')` setelah prefix.

## Implementation Checklist
1. Update `resources/views/emails/otp.blade.php`: terjemahkan heading, salam, paragraf OTP, info kedaluwarsa, catatan, dan footer ke Bahasa Indonesia; perbaiki label “Important”.
2. Update `app/Mail/SendOtpMail.php`: ubah subject prefix ke Bahasa Indonesia untuk purpose register dan password reset, tetap memakai `config('app.name')`.
