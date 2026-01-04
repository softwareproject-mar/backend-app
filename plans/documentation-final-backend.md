# Plan: Dokumentasi Final Backend (Endpoint untuk Frontend)

## Context
- Backend Laravel dengan grup route public (auth/register/login/forgot-password/verify/reset) dan protected dengan `auth:sanctum`.
- Resource aktif: data-kunjungan, anggota, kel-sah, data-lo, data-ao, data-jlh-keluarga, data-pengelola, ketua-ks, sekretaris-ks, data-penghasilan, data-trs (read-only), plus auth profile/logout.
- Endpoint Target/Realisasi/Dashboard sudah dihapus dari `routes/api.php` dan tidak perlu didokumentasikan.

## Goal
- Menyusun dokumentasi ringkas untuk frontend: base URL, auth method/headers, daftar endpoint beserta method, path, deskripsi singkat, dan kebutuhan auth.
- Hanya mencakup endpoint yang ada saat ini (setelah penghapusan Target/Realisasi/Dashboard).

## Detailed Specifications
- Buat file `documentation-final-backend.md` (root project).
- Isi minimal:
  - Base URL (`/api`) dan headers wajib (Content-Type, Accept, Authorization Bearer token untuk protected).
  - Seksi Public Endpoints: daftar POST auth (request-otp, register, login, forgot-password, verify-reset-otp, reset-password).
  - Seksi Protected Endpoints (auth:sanctum):
    - Auth: logout (POST), me (GET).
    - Data Kunjungan: apiResource CRUD.
    - Anggota: apiResource CRUD.
    - Kel Sah: apiResource CRUD.
    - Data LO: apiResource CRUD.
    - Data AO: apiResource CRUD.
    - Data Jumlah Keluarga: apiResource CRUD.
    - Data Pengelola: apiResource CRUD.
    - Ketua KS: apiResource CRUD.
    - Sekretaris KS: apiResource CRUD.
    - Data Penghasilan: apiResource CRUD.
    - Data TRS: apiResource read-only (index, show).
  - Catatan pagination: `?per_page=` dan filter field sesuai controller (cukup sebut opsional filter utama per resource secara singkat).
  - Status Target/Realisasi/Dashboard: dinyatakan tidak tersedia/nonaktif.
- Format: heading jelas, tabel atau bullet per resource dengan method/path/desc/auth info.

## Implementation Checklist
1. Buat file `documentation-final-backend.md` di root.
2. Tambahkan section Base Info (URL, headers, auth).
3. Tambahkan section Public Endpoints dengan daftar POST auth terkait.
4. Tambahkan section Protected Endpoints (per resource) dengan method/path/desc + catatan filter/pagination.
5. Tambahkan catatan bahwa Target/Realisasi/Dashboard sedang dinonaktifkan.
