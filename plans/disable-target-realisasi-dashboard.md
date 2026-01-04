# Plan: Nonaktifkan Target, Realisasi, Dashboard Endpoints

## Context
- Routes aktif saat ini:
  - Target CRUD (GET/POST/GET/PUT/DELETE) via `TargetController`.
  - Realisasi GET (index/show) via `RealisasiController`.
  - Dashboard GET (join) via `DashboardController`.
- Permintaan: hilangkan sementara ketiga endpoint tersebut dari backend.
- Controller/service tetap ada; fokus menonaktifkan akses via route.

## Goal
- Menonaktifkan endpoint Target, Realisasi, Dashboard dari API publik (auth:sanctum group).
- Pastikan route lain tidak terpengaruh.

## Detailed Specifications
- `routes/api.php`
  - Hapus atau komentari pendaftaran route:
    - Target: `Route::get('/target', ...)`, `Route::post('/target', ...)`, `Route::get('/target/{idKs}/{tglTgt}', ...)`, `Route::put('/target/{idKs}/{tglTgt}', ...)`, `Route::delete('/target/{idKs}/{tglTgt}', ...)`.
    - Realisasi: `Route::get('/realisasi', ...)`, `Route::get('/realisasi/{idKs}/{tglTgt}', ...)`.
    - Dashboard: `Route::get('/dashboard', ...)`.
  - Pastikan tidak ada dangling use statements jika controller import hanya untuk tiga fitur ini (namun dapat dibiarkan tanpa efek).
- (Opsional, jika diminta) update dokumentasi API untuk mencerminkan penonaktifan; saat ini tidak diwajibkan.

## Implementation Checklist
1. Buka `routes/api.php` dan hapus/komentari semua route Target (`/target` dan `/target/{idKs}/{tglTgt}`).
2. Hapus/komentari route Realisasi (`/realisasi`, `/realisasi/{idKs}/{tglTgt}`) di `routes/api.php`.
3. Hapus/komentari route Dashboard (`/dashboard`) di `routes/api.php`.
4. Pastikan file tersimpan dan tidak ada route lain yang berubah.
