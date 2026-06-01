# Plan: Activity Log Kelompok Sahabat & Nama Display

## Context
- Riwayat Sistem masih menampilkan teks `keluarga sejahtera` untuk resource `kel_sah`, padahal istilah domain yang benar adalah **Kelompok Sahabat**.
- Beberapa deskripsi log masih menampilkan identifier teknis (`ID_*`, `NO_AGT`) atau fallback `Unknown`, bukan nama yang mudah dipahami user.
- `ActivityLogResource` hanya mem-forward `description` dari DB, jadi perbaikan harus dilakukan di level service saat menyusun deskripsi log.

## Goal
- Semua deskripsi log untuk `kel_sah` memakai istilah **Kelompok Sahabat** (bukan `keluarga sejahtera`).
- Deskripsi log pada modul terkait menampilkan **nama** (person/group) sebagai label utama, bukan `NO_AGT`/ID teknis.
- Fallback `Unknown` diminimalkan dengan resolusi nama berbasis relasi/model yang tersedia.

## Detailed Specifications
- **File:** `app/Services/ActivityLogService.php`
  - Tambah helper statis terpusat untuk menyusun label deskriptif:
    - `public static function kelompokSahabatLabel(?string $idKel, ?string $namaKel = null): string`
    - `public static function anggotaNamaByNoAgt(?string $noAgt): ?string`
    - `public static function anggotaLabelByNoAgt(?string $noAgt): string`
  - Aturan helper:
    - Prioritas nama > identifier.
    - Normalisasi trim + null-safe.
    - Fallback final berupa kata netral (`Tanpa nama`) jika lookup gagal.

- **File:** `app/Services/KelSahService.php`
  - Fungsi `create`, `update`, `delete`:
    - Ganti frasa `keluarga sejahtera` menjadi `kelompok sahabat`.
    - Deskripsi pakai `NAMA_KEL` sebagai label utama.
    - `resource_id` tetap `ID_KEL` (untuk trace teknis), hanya `description` yang dipoles.

- **File:** `app/Services/DataJlhKeluargaService.php`
  - Fungsi `create`, `update`, `delete`:
    - Ganti deskripsi berbasis `NO_AGT` / `$id` menjadi nama anggota via helper `ActivityLogService::anggotaLabelByNoAgt(...)`.
    - Untuk `update`/`delete`, ambil kandidat `NO_AGT` dari record lama sebelum perubahan.

- **File:** `app/Services/DataPenghasilanService.php`
  - Fungsi `create`, `update`, `delete`:
    - Samakan pola seperti `DataJlhKeluargaService` (nama anggota sebagai label utama).

- **File:** `app/Services/DataTrsService.php`
  - Fungsi `create`, `update`, `delete`:
    - Samakan pola seperti `DataJlhKeluargaService` (nama anggota sebagai label utama).

- **File:** `app/Services/DataAoService.php`
  - Fungsi `create`, `update`, `delete`:
    - Gunakan field `NAMA` sebagai label utama (sudah ada di model).
    - Fallback ke lookup anggota by `NO_AGT` jika `NAMA` kosong.

- **File:** `app/Services/DataLoService.php`
  - Fungsi `create`, `update`, `delete`:
    - Gunakan field `NAMA` sebagai label utama.
    - Fallback ke lookup anggota by `NO_AGT` jika `NAMA` kosong.

- **File:** `app/Services/DataKunjunganService.php`
  - Fungsi `create`, `update`, `delete`:
    - Jika saat ini deskripsi memakai `ID_KEL_SAH`, ubah ke nama kelompok (`NAMA_KEL`) dengan lookup ke `KelSah`.

- **File:** `app/Services/DataPengelolaService.php`
  - Fungsi `create`, `update`, `delete`:
    - Hapus fallback langsung `Unknown` berbasis `$data['NAMA']` yang tidak konsisten.
    - Gunakan nama anggota dari `NO_AGT` (lookup `Anggota`) atau `anggota_nama` hasil join saat record tersedia.

- **File:** `tests/Feature/*` (tambahan test coverage khusus activity log)
  - Tambah/ubah test agar assert deskripsi:
    - Mengandung istilah `kelompok sahabat`.
    - Tidak menampilkan frasa `keluarga sejahtera`.
    - Tidak menampilkan `NO_AGT` mentah pada modul yang sudah dimigrasi ke nama.

## Implementation Checklist
1. Buat helper label terpusat di `app/Services/ActivityLogService.php` untuk nama kelompok/anggota + fallback.
2. Refactor `app/Services/KelSahService.php` (create/update/delete) agar wording jadi `kelompok sahabat` dan label utama `NAMA_KEL`.
3. Refactor `app/Services/DataJlhKeluargaService.php` agar deskripsi create/update/delete memakai nama anggota, bukan `NO_AGT`/ID.
4. Refactor `app/Services/DataPenghasilanService.php` agar deskripsi create/update/delete memakai nama anggota, bukan `NO_AGT`/ID.
5. Refactor `app/Services/DataTrsService.php` agar deskripsi create/update/delete memakai nama anggota, bukan `NO_AGT`/ID.
6. Refactor `app/Services/DataAoService.php` agar deskripsi create/update/delete memakai nama AO (fallback lookup anggota jika perlu).
7. Refactor `app/Services/DataLoService.php` agar deskripsi create/update/delete memakai nama LO (fallback lookup anggota jika perlu).
8. Refactor `app/Services/DataKunjunganService.php` agar deskripsi create/update/delete memakai nama kelompok (`NAMA_KEL`), bukan `ID_KEL_SAH`.
9. Refactor `app/Services/DataPengelolaService.php` agar fallback `Unknown` diganti resolusi nama berbasis anggota/join.
10. Tambahkan/adjust test feature untuk verifikasi wording `kelompok sahabat` dan deskripsi berbasis nama.
11. Jalankan pengecekan lint + test terkait activity log dan pastikan tidak ada regresi endpoint Riwayat Sistem.

## Risks / Catatan
- Data log lama di tabel `activity_logs` tidak otomatis berubah; yang berubah hanya log baru setelah deploy.
- Lookup nama tambahan (mis. by `NO_AGT`, by `ID_KEL`) menambah query; perlu dijaga agar tetap ringan (prefer reuse record lama jika sudah ada di memori).
- Konsistensi bahasa harus seragam antar service agar Riwayat Sistem tidak campur istilah lama vs baru.
