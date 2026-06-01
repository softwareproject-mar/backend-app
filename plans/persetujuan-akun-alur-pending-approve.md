# Plan: Alur Persetujuan Akun (pending → approve → aktif)

## Context

- Kebutuhan bisnis: pendaftar self-service harus **`registration_status = pending`** dan **`is_active = false`** sampai admin atau super admin menyetujui lewat **Persetujuan Akun**; setelah **Setujui**, **`is_active = true`** dan status pendaftaran **`approved`**.
- Di codebase saat ini, **`AuthController::register`**, **`AuthService::register`**, dan **`UserApprovalController`** (approve/reject) sudah mengimplementasikan inti alur tersebut.
- Risiko yang tersisa: (1) data produksi yang inkonsisten (`approved` + nonaktif), (2) token Sanctum yang dikeluarkan saat register masih bisa mengakses endpoint **`auth:sanctum`** selain login (login sudah dicek `is_active`), (3) jalur **`PATCH /api/super-admin/users/{id}`** dapat mengaktifkan user dan men-set **`approved`** tanpa melalui halaman Persetujuan.

## Goal

1. Menegaskan dan memverifikasi end-to-end: daftar → muncul di daftar pending → approve → aktif + approved; tolak → rejected, tetap nonaktif.
2. Menyelaraskan lingkungan produksi (migrate, deploy, klien mobile/API base URL).
3. (Opsional, jika disetujui) Memperketat API agar user dengan **`pending` / `rejected`** tidak dapat memakai token untuk resource bisnis sampai disetujui.

## Detailed Specifications

### Baseline (sudah ada — hanya verifikasi)

| Area | File / komponen | Perilaku yang diharapkan |
|------|------------------|---------------------------|
| Register OTP | `app/Http/Controllers/Api/AuthController.php` method `register` | Setelah OTP valid: paksa `role = user`, `is_active = false`, `registration_status = pending` sebelum `AuthService::register`. |
| Register service | `app/Services/AuthService.php` method `register` | Untuk `role === user`: paksa `$isActive = false`; tulis `registration_status` dari `$data` jika ada. |
| Daftar pending | `app/Http/Controllers/Api/UserApprovalController.php` method `index` | Query `registration_status = pending`. |
| Statistik | method `stats` | Hitung `pending` dan `rejected`. |
| Setujui | method `approve` | Hanya jika `pending`; update `is_active = true`, `registration_status = approved`; dispatch `SendAccountActivationJob`. |
| Tolak | method `reject` | Hanya jika `pending`; update `registration_status = rejected` (tanpa mengaktifkan). |
| Akses endpoint persetujuan | `routes/api.php` | Grup `auth:sanctum` + middleware alias `admin` (`EnsureUserIsAdmin`: role `admin` atau `super_admin`). |
| Login | `app/Services/AuthService.php` method `login` | Gagal jika `! $user->is_active`. |

### Web Admin (sudah ada — verifikasi)

| Area | Path | Catatan |
|------|------|---------|
| Halaman Persetujuan | `Web Admin` — `PersetujuanAkun.tsx` | Memanggil `/users/pending`, `/users/rejection-stats` setara, approve/reject POST. |
| Super admin | `routes.tsx`, `SuperAdminLayout.tsx` | Route `/super-admin/persetujuan-akun` dan item menu. |

### Opsional — hardening API (hanya jika checklist opsional disetujui)

| Item | Spesifikasi |
|------|-------------|
| Middleware baru | Class di `app/Http/Middleware/` (nama disarankan: **`EnsureUserCanUseMemberApi`** atau serupa) yang: jika user terautentikasi memiliki `role === 'user'`, maka izinkan request hanya jika **`is_active === true`** dan **`registration_status ===` nilai `approved`** pada model `User`; jika tidak, respons JSON 403 dengan pesan konsisten (bahasa Indonesia, singkat). |
| Pengecualian route | Route berikut **tidak** boleh dikenai middleware ini: `POST /auth/logout`, `GET /auth/me` (agar klien bisa membaca status akun dan logout). Semua route lain di grup `auth:sanctum` yang bukan `admin` / `super_admin` **dikenai** middleware ini. |
| Registrasi alias | `bootstrap/app.php` — daftarkan alias middleware (mis. `member_approved`) mengikuti pola `admin` / `super_admin`. |
| Pengelompokan route | `routes/api.php` — pecah atau nested group: setelah `auth:sanctum`, satu subgroup untuk `logout` + `me` tanpa middleware baru; subgroup lain untuk resource anggota/kunjungan/dll. dengan middleware baru. |

### Opsional — selaras super admin dengan “hanya Persetujuan”

| Item | Spesifikasi |
|------|-------------|
| `UserManagementController::update` | Jika produk memutuskan **aktivasi pendaftar baru hanya lewat Persetujuan**: saat `is_active` di-set `true` untuk user dengan `role === user`, hanya izinkan jika `registration_status === pending` **tidak** boleh langsung jadi `approved` dari sini, atau wajibkan alur `approve` terpisah. Alternatif minimal: dokumentasi bahwa toggle Manajemen User adalah bypass sengaja. Keputusan harus eksplisit sebelum mengubah kode. |

### Data & deploy (wajib untuk produksi)

| Item | Spesifikasi |
|------|-------------|
| Migrasi repair | Jalankan migrasi `2026_03_28_120000_repair_registration_status_for_pending_signups.php` di server yang sudah punya kolom `registration_status`, agar user `role = user`, `is_active = false`, `registration_status = approved` menjadi `pending`. |
| Konsistensi pendaftar baru | Pastikan klien pendaftaran memanggil **`POST /api/auth/register`** ke instance Laravel yang sudah berisi perubahan `AuthController` terbaru. |

## Implementation Checklist

### Wajib (verifikasi & operasional)

1. Bandingkan perilaku `AuthController::register` dan `AuthService::register` dengan tabel di bagian **Baseline** di atas; pastikan tidak ada regresi di branch yang akan di-deploy.
2. Bandingkan `UserApprovalController` (approve, reject, index, stats) dengan tabel **Baseline**.
3. Di lingkungan staging/produksi: jalankan `php artisan migrate` hingga migrasi repair terbaru untuk `registration_status` tereksekusi.
4. Setelah migrate, sampling query DB: user baru hasil register OTP harus berpasangan `registration_status = pending` dan `is_active = 0`.
5. Uji manual Web Admin: login sebagai admin atau super_admin → buka Persetujuan Akun → pastikan baris `pending` tampil → Setujui → di DB `is_active = 1` dan `registration_status = approved` → user dapat login.
6. Uji tolak: user `pending` → Tolak → `registration_status = rejected`, `is_active` tetap 0; user tidak dapat login.
7. Pastikan aplikasi mobile (jika ada) memakai base URL API yang sama dengan backend yang sudah di-deploy.

### Opsional — hardening member API (hanya jika disetujui terpisah)

8. Tambah class middleware sesuai spesifikasi **Middleware baru** di atas.
9. Daftarkan alias middleware di `bootstrap/app.php`.
10. Restrukturisasi `routes/api.php` agar `auth/me` dan `auth/logout` bebas dari middleware tersebut; semua route member lain memakai middleware tersebut.
11. Uji: user `pending` dengan token dari register memanggil endpoint resource (mis. `GET /api/anggota`) → harus 403; `GET /api/auth/me` → 200 dengan body yang mencerminkan status; login tetap ditolak sampai `is_active` true.

### Opsional — kebijakan super admin (hanya jika disetujui terpisah)

12. Dokumentasikan di README/plan internal apakah **`PATCH /super-admin/users/{id}`** boleh mengaktifkan user `pending` tanpa flow Persetujuan, atau ubah `UserManagementController::update` sesuai keputusan di tabel **Opsional — selaras super admin**.

## Risks / Catatan

- Menambah middleware tanpa pengecualian yang benar dapat memutus klien yang mengandalkan token pasca-register untuk polling `auth/me`.
- Mengubah `UserManagementController::update` dapat mengganggu operasional super admin yang biasa memakai toggle cepat di Manajemen User.
- Perbaikan data satu kali via migrasi tidak menggantikan deploy kode register yang benar; keduanya diperlukan.

---

## IMPLEMENTATION CHECKLIST (urut eksekusi)

1. Verifikasi isi `AuthController::register` dan `AuthService::register` sesuai baseline plan (tanpa mengubah kecuali ada penyimpangan).
2. Verifikasi isi `UserApprovalController` (stats, index, approve, reject) sesuai baseline plan.
3. Jalankan migrasi di server target termasuk `2026_03_28_120000_repair_registration_status_for_pending_signups`.
4. Verifikasi DB: pendaftar baru OTP memiliki `pending` + nonaktif.
5. Uji E2E Web Admin: daftar (atau gunakan user pending) → tampil di Persetujuan → approve → login sukses.
6. Uji E2E Web Admin: reject → tidak bisa login; tidak muncul di pending.
7. Konfirmasi base URL klien pendaftaran = backend ter-deploy.
8. *(Opsional)* Implementasi middleware pembatasan member + registrasi alias + restrukturisasi route + tes 403 pada resource.
9. *(Opsional)* Dokumentasi atau perubahan `UserManagementController::update` sesuai kebijakan bypass super admin.
