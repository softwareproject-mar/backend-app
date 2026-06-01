# Plan: Buka update/delete role user untuk semua modul backend

## Context
- Aplikasi mobile sudah memanggil endpoint `PUT/PATCH` dan `DELETE` dari sisi frontend.
- Di backend, route CRUD tersedia via `Route::apiResource(...)`, tetapi sebagian controller masih menambahkan pembatasan akses role `user` (langsung atau berbasis ownership/scope).
- Scope terbaru dari user: semua modul yang dipakai aplikasi backend harus memungkinkan role `user` melakukan update/delete, tidak terbatas hanya create.
- Modul yang relevan dari route API saat ini:
  - `anggota`
  - `kel-sah`
  - `data-ao`
  - `data-lo`
  - `data-kunjungan`
  - `data-jlh-keluarga`
  - `data-penghasilan`
  - `data-trs`
  - tambahan master lain yang dipakai UI: `data-pengelola`, `ketua-ks`, `sekretaris-ks`

## Goal
- Role `user` dapat mengeksekusi `update` dan `delete` pada seluruh modul backend yang dipakai aplikasi.
- Tidak ada lagi blokir akses khusus role `user` yang menyebabkan aksi update/delete gagal.
- Route dan kontrak request/response tetap kompatibel dengan frontend saat ini.

## Detailed Specifications

### A. Normalisasi kebijakan akses role `user`
- Terapkan kebijakan akses seragam: role `user` tidak diblokir untuk method `show/update/destroy`.
- Hapus/longgarkan validasi ownership khusus member pada controller yang masih memanggil:
  - `OwnerScope::assertMemberOwnsCreatedBy(...)`
  - `OwnerScope::assertMemberUserCanAccessKelompok(...)`
  - `abort_if(MemberScope::isRestrictedMemberUser(...), 403, ...)`
  - `abort_unless(..., 403)` dengan basis scope member.

### B. Controller yang harus diubah
- `app/Http/Controllers/Api/DataKunjunganController.php`
  - `show`, `update`, `destroy`: hapus guard ownership member (`assertMemberOwnsCreatedBy`).
  - `index`/export: hilangkan filter khusus user dari `kunjunganListFilters` jika masih mengunci ke `created_by`.
- `app/Http/Controllers/Api/DataJlhKeluargaController.php`
  - `show`, `update`, `destroy`: hapus `assertMemberOwnsCreatedBy`.
  - `index`, `exportExcel`, `exportPdf`: hapus `mergeCreatedByFilterForMemberUser`.
- `app/Http/Controllers/Api/DataPenghasilanController.php`
  - `show`, `update`, `destroy`: hapus `assertMemberOwnsCreatedBy`.
  - `index`, `exportExcel`, `exportPdf`: hapus `mergeCreatedByFilterForMemberUser`.
- `app/Http/Controllers/Api/DataTrsController.php`
  - `show`, `update`, `destroy`: hapus `assertMemberOwnsCreatedBy`.
  - `index`, `exportExcel`, `exportPdf`: hapus `mergeCreatedByFilterForMemberUser`.

### C. Sinkronisasi dengan modul yang sudah lebih dulu disentuh
- Pastikan `app/Http/Controllers/Api/AnggotaController.php` tidak lagi memblokir role `user` (helper akses member tetap no-op).
- Pastikan `app/Http/Controllers/Api/KelSahController.php` tidak lagi memblokir role `user` di `update/destroy`.
- Pastikan `app/Http/Controllers/Api/DataAoController.php` dan `DataLoController.php` tidak lagi pakai guard ownership role `user` untuk `show/update/destroy`.

### D. Modul tambahan yang perlu audit final
- `app/Http/Controllers/Api/DataPengelolaController.php`
- `app/Http/Controllers/Api/KetuaKsController.php`
- `app/Http/Controllers/Api/SekretarisKsController.php`
- Jika ketiga modul di atas punya guard role `user` untuk update/delete, samakan kebijakan dengan modul lain.

### E. Routing dan kontrak API
- `routes/api.php` tidak perlu menambah route baru.
- Pastikan seluruh `apiResource` untuk modul di atas masih expose:
  - `PUT|PATCH .../{resource}`
  - `DELETE .../{resource}`
- Tidak ada perubahan path URL agar frontend tidak perlu diubah.

### F. Verifikasi
- Jalankan validasi route list per modul (`php artisan route:list --path=api/<module>`).
- Jalankan lint check untuk file controller yang diedit.
- Uji manual role `user`:
  - update + delete pada semua modul yang dipakai aplikasi.
- Uji smoke role `admin/super_admin`:
  - memastikan tidak ada regresi CRUD.

## Implementation Checklist
1. Audit `DataKunjunganController` dan hapus guard ownership role user pada show/update/destroy.
2. Hapus filter `mergeCreatedByFilterForMemberUser` dari index/export di `DataKunjunganController`.
3. Audit `DataJlhKeluargaController` dan hapus guard ownership role user pada show/update/destroy.
4. Hapus filter `mergeCreatedByFilterForMemberUser` dari index/export di `DataJlhKeluargaController`.
5. Audit `DataPenghasilanController` dan hapus guard ownership role user pada show/update/destroy.
6. Hapus filter `mergeCreatedByFilterForMemberUser` dari index/export di `DataPenghasilanController`.
7. Audit `DataTrsController` dan hapus guard ownership role user pada show/update/destroy.
8. Hapus filter `mergeCreatedByFilterForMemberUser` dari index/export di `DataTrsController`.
9. Verifikasi ulang `AnggotaController` (no-op guard tetap aktif sesuai plan).
10. Verifikasi ulang `KelSahController` (tidak ada blok langsung role user pada update/destroy).
11. Verifikasi ulang `DataAoController` (tanpa guard member pada show/update/destroy).
12. Verifikasi ulang `DataLoController` (tanpa guard member pada show/update/destroy).
13. Audit `DataPengelolaController`, `KetuaKsController`, `SekretarisKsController` untuk guard role user; ubah jika masih membatasi update/delete.
14. Jalankan route verification per modul utama: anggota, kel-sah, data-ao, data-lo, data-kunjungan, data-jlh-keluarga, data-penghasilan, data-trs.
15. Jalankan lint check untuk semua controller yang berubah.
16. Uji manual seluruh aksi update/delete sebagai role user di aplikasi.
17. Uji smoke CRUD sebagai admin/super_admin.

## Risks / Catatan
- Membuka akses update/delete lintas semua modul untuk role `user` meningkatkan risiko perubahan data antar anggota (jika memang butuh isolasi data).
- Jika kebijakan bisnis sebenarnya membutuhkan pembatasan ownership, plan ini harus direvisi menjadi model permission yang lebih granular.
- Potensi pesan error `Resource not found` dari layer deploy/proxy tetap perlu divalidasi di runtime setelah akses backend dibuka penuh.
