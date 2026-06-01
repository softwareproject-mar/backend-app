# Plan: Buka akses update/delete untuk role user

## Context
- Saat ini fungsi update/delete di aplikasi mobile sudah memanggil endpoint backend dengan benar.
- Di backend, role `user` masih dibatasi pada beberapa controller sehingga aksi update/delete ditolak (langsung atau lewat scope ownership).
- Gejala di UI muncul sebagai `Resource not found`, tetapi observasi kode menunjukkan pembatasan akses terjadi pada backend.

## Goal
- Role `user` dapat melakukan `update` dan `delete` pada modul yang dipakai di aplikasi (`anggota`, `data-ao`, `data-lo`, `kel-sah`) tanpa dibatasi hanya create.
- Endpoint backend tetap aman dan konsisten terhadap validasi data.
- Perubahan bersifat minimal dan terarah pada otorisasi role agar tidak mengganggu flow admin/super_admin.

## Detailed Specifications
- File: `app/Http/Controllers/Api/KelSahController.php`
  - Function: `update(UpdateKelSahRequest $request, string $id)`
  - Ubah aturan yang sekarang menolak role `user` secara mutlak.
  - Hapus `abort_if(MemberScope::isRestrictedMemberUser(...), 403, ...)` pada update.
  - Pertahankan alur update lewat service + response resource.
  - Function: `destroy(string $id)`
  - Hapus `abort_if(MemberScope::isRestrictedMemberUser(...), 403, ...)` pada destroy.
  - Pertahankan alur delete lewat service + `response()->noContent()`.

- File: `app/Http/Controllers/Api/AnggotaController.php`
  - Function: `assertMemberCanAccessAnggota(Request $request, string $noAgt): void`
  - Longgarkan aturan khusus role `user` yang sekarang memblokir akses lintas data.
  - Ubah agar method tidak lagi melempar `403` untuk role `user` pada operasi show/update/destroy.
  - Pertahankan signature method agar perubahan kecil dan kompatibel.

- File: `app/Http/Controllers/Api/DataAoController.php`
  - Function: `show`, `update`, `destroy`
  - Hapus blok validasi ownership berbasis `OwnerScope::noAgtsFromUserOwnedRows(...)` untuk role `user`.
  - Pertahankan service call `find/update/delete` apa adanya.

- File: `app/Http/Controllers/Api/DataLoController.php`
  - Function: `show`, `update`, `destroy`
  - Hapus blok validasi ownership berbasis `OwnerScope::loIdsFromUserKunjungan(...)` untuk role `user`.
  - Pertahankan service call `find/update/delete` apa adanya.

- File: `routes/api.php`
  - Tidak ada perubahan route baru.
  - Tetap gunakan `Route::apiResource(...)` yang sudah ada untuk `PUT`/`DELETE`.

- File: frontend tidak wajib diubah
  - `src/services/*` dan `src/app/components/*` sudah memanggil update/delete.
  - Cukup verifikasi ulang bahwa respons sukses/gagal terbaca benar setelah backend dibuka.

## Implementation Checklist
1. Edit `KelSahController::update` untuk menghapus blok penolakan role `user`.
2. Edit `KelSahController::destroy` untuk menghapus blok penolakan role `user`.
3. Edit `AnggotaController::assertMemberCanAccessAnggota` agar tidak memblokir role `user` pada show/update/destroy.
4. Edit `DataAoController::show` untuk menghapus cek ownership role `user`.
5. Edit `DataAoController::update` untuk menghapus cek ownership role `user`.
6. Edit `DataAoController::destroy` untuk menghapus cek ownership role `user`.
7. Edit `DataLoController::show` untuk menghapus cek ownership role `user`.
8. Edit `DataLoController::update` untuk menghapus cek ownership role `user`.
9. Edit `DataLoController::destroy` untuk menghapus cek ownership role `user`.
10. Jalankan verifikasi route (`apiResource` tetap aktif untuk `PUT/DELETE`).
11. Uji manual dengan akun role `user`: update + delete pada AO.
12. Uji manual dengan akun role `user`: update + delete pada LO.
13. Uji manual dengan akun role `user`: update + delete pada Anggota.
14. Uji manual dengan akun role `user`: update + delete pada Kelompok Sahabat.
15. Uji smoke test akun admin/super_admin untuk memastikan tidak ada regresi.

## Risks / Catatan
- Membuka akses penuh role `user` dapat meningkatkan risiko perubahan data lintas entitas bila tidak ada guard ownership.
- Jika aturan bisnis sebenarnya butuh batasan per pemilik data, maka plan ini terlalu permisif dan perlu revisi requirement.
- Error `Resource not found` bisa tetap muncul jika ada faktor deploy/proxy method `PUT/DELETE` yang belum benar; perlu verifikasi runtime setelah perubahan.
