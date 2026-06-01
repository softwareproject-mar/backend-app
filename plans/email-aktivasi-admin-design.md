# Plan: Email Aktivasi Akun - Design dari Mockup

## Context

- Template email aktivasi saat ini di `resources/views/emails/account-activation.blade.php` memakai desain sederhana (container abu-abu, success-box hijau basic).
- User memiliki mockup desain baru dengan layout modern: header kuning + ikon centang, blok konten berwarna (biru, hijau, kuning), footer disclaimer.
- Data yang tersedia: `$user` (User model) → `$user->email`, `$user->name`. Mailable tidak perlu diubah.

## Goal

- Mengganti seluruh HTML dan CSS di `account-activation.blade.php` agar sesuai mockup.
- Mempertahankan variable Blade: `$user->email`, `$user->name`, `config('app.name')`, `date('Y')`.
- Email tetap HTML table-safe dan client-compatible (inline style untuk client email).

## Detailed Specifications

### File: `backend-app/resources/views/emails/account-activation.blade.php`

**1. Header Section (Yellow Banner)**
- Background: kuning solid (mis. `#FFD54F` atau `#F9A825`)
- Ikon: centang putih (✓) dalam lingkaran putih dengan border kuning, center
- Judul: "Akun Berhasil Diaktifkan!" (bold, dark grey)
- Subjudul: "Selamat datang di {{ config('app.name') }}"
- Semua elemen center-aligned

**2. Main Content Area (White Background)**
- Salam: "Halo! 👋" (bold)
- Paragraf: "Kabar baik! Akun Anda telah **disetujui dan diaktifkan** oleh Administrator kami. Anda sekarang dapat mengakses semua fitur yang tersedia di sistem."
  - Kata "disetujui dan diaktifkan" dibold atau warna biru gelap

**3. Block: Detail Akun Terdaftar**
- Container: background biru muda (e.g. `#E3F2FD`), border biru muda, border-radius
- Ikon amplop: ✉️ (emoji) atau character/entity
- Title: "Detail Akun Terdaftar"
- Subtitle: "Email untuk login"
- Field email: box putih dengan border abu, berisi `{{ $user->email }}`

**4. Block: Akun Anda Sudah Aktif**
- Container: background hijau muda (e.g. `#E8F5E9`), border hijau muda
- Ikon: centang hijau ✓
- Teks: "Gunakan email dan password yang sudah Anda daftarkan untuk login ke aplikasi. Semua layanan sudah dapat diakses sekarang."

**5. Block: Informasi Penting**
- Container: background kuning/krem (e.g. `#FFF8E1`), border oranye muda
- Ikon: ⓘ (info)
- Title: "Informasi Penting"
- 3 bullet points:
  1. "Akun Anda telah berhasil diaktifkan dan siap digunakan untuk mengakses sistem {{ config('app.name') }}."
  2. "Jika Anda tidak merasa melakukan pendaftaran, silakan hubungi administrator segera."
  3. "Jaga kerahasiaan password Anda dan jangan bagikan kepada siapapun."

**6. Footer**
- Teks: "Email ini dikirim secara otomatis oleh sistem. Mohon tidak membalas email ini."
- Copyright: "© {{ date('Y') }} {{ config('app.name') }}. All rights reserved."
- Font size kecil, color abu gelap

### CSS Requirements (inline/embedded)

- `body`: max-width 600px, margin auto, font-family sans-serif, line-height 1.6, color #333
- `.header-yellow`: background kuning, padding, text-align center
- `.icon-check`: lingkaran putih, border kuning, centang di dalam
- `.block-detail`: background #E3F2FD, border 1px solid #BBDEFB, border-radius 8px, padding 16px
- `.block-active`: background #E8F5E9, border 1px solid #C8E6C9, border-radius 8px, padding 16px
- `.block-info`: background #FFF8E1, border 1px solid #FFE082, border-radius 8px, padding 16px
- `.email-field`: background white, border 1px solid #ddd, padding 10px, border-radius 4px, display block
- Spacing antar block: margin 16px 0 atau 20px 0

### Structure HTML (Logical Order)

```
body
  wrapper (max-width 600px)
    header-yellow
      icon-check
      h1: Akun Berhasil Diaktifkan!
      p.subtitle: Selamat datang di ...
    main-content
      p: Halo! 👋
      p: Kabar baik! ...
      block-detail (biru)
        title + subtitle
        email-field: {{ $user->email }}
      block-active (hijau)
        icon + message
      block-info (kuning)
        title
        ul (3 items)
    footer
      disclaimer
      copyright
```

## Implementation Checklist

1. Buka `resources/views/emails/account-activation.blade.php` dan ganti seluruh isi file.
2. Tambahkan DOCTYPE, meta charset, viewport, title "Aktivasi Akun", dan blok `<style>` dengan seluruh class yang diperlukan.
3. Implementasikan section header-yellow: div dengan background kuning, ikon centang (HTML entity atau Unicode ✓), h1 "Akun Berhasil Diaktifkan!", p "Selamat datang di {{ config('app.name') }}".
4. Implementasikan main-content: p "Halo! 👋", paragraf Kabar baik dengan "disetujui dan diaktifkan" bold.
5. Implementasikan block-detail: container biru, title "Detail Akun Terdaftar", subtitle "Email untuk login", div.email-field berisi {{ $user->email }}.
6. Implementasikan block-active: container hijau, centang, teks instruksi login.
7. Implementasikan block-info: container kuning, title "Informasi Penting", ul dengan 3 li sesuai spec.
8. Implementasikan footer: paragraf disclaimer, paragraf copyright © {{ date('Y') }} {{ config('app.name') }}.
9. Pastikan semua variable Blade ({{ }}) benar: $user->email, config('app.name'), date('Y').
10. Verifikasi tidak ada perubahan pada `AccountActivationMail.php` maupun `SendAccountActivationJob.php`.

## Risks / Catatan

- Beberapa client email tidak mendukung border-radius atau box-shadow; pertahankan desain sederhana, avoid complex CSS.
- Gunakan table-based layout opsional jika diperlukan untuk client lama; untuk client modern, div + inline/embedded CSS cukup.
- Emoji (👋, ✓, ✉️, ⓘ) umumnya didukung Gmail/Outlook modern; jika ingin aman, bisa ganti dengan HTML entity atau karakter Unicode.
