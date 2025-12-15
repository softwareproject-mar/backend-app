# Plan: CRUD Data Kunjungan

## Context
- Project masih skeleton Laravel 12; belum ada `routes/api.php`, controller, request, resource, service untuk CRUD.
- Migration tabel terkait berada di `database/migrations/generated/` sehingga tidak dieksekusi oleh migrator default.
- Tabel `data_kunjungan` sudah didefinisikan dengan kolom utama `NO_URT` (integer, primary, non-auto increment); user mengizinkan menjadikannya auto-increment dan meminta data existing tidak hilang.

## Goal
- Memindahkan seluruh migration dari folder `generated` ke path utama agar dijalankan Laravel.
- Menyesuaikan struktur tabel `data_kunjungan` sehingga `NO_URT` auto-increment tanpa menghapus data.
- Menyediakan CRUD API `data_kunjungan` sesuai requirement (RESTful, JSON, validasi).

## Detailed Specifications
- **Migrations**
  - Pindahkan semua file dari `database/migrations/generated/*.php` ke `database/migrations/` dengan nama file tetap agar urutan timestamp terjaga.
  - Tambah migration baru `database/migrations/<timestamp>_alter_data_kunjungan_no_urt_autoincrement.php`:
    - Mengubah kolom `NO_URT` menjadi auto-increment (MySQL): gunakan raw SQL `ALTER TABLE data_kunjungan MODIFY NO_URT INT UNSIGNED NOT NULL AUTO_INCREMENT;`
    - Pastikan tidak menghapus data dan tidak menjatuhkan tabel.
- **Model**
  - Buat `app/Models/DataKunjungan.php`:
    - `protected $table = 'data_kunjungan';`
    - `protected $primaryKey = 'NO_URT';` dengan `$incrementing = true`, `$keyType = 'int'`.
    - `public $timestamps = false;`
    - `protected $fillable = ['ID_LO','NO_AGT','ID_KEL_SAH','TGL_KUN','KEGIATAN','ID_PIC','JLH_PESERTA'];`
- **Requests**
  - `app/Http/Requests/StoreDataKunjunganRequest.php` dan `UpdateDataKunjunganRequest.php`:
    - Rules minimal: `ID_LO` nullable|string|max:12, `NO_AGT` nullable|string|max:15, `ID_KEL_SAH` nullable|string|max:12, `TGL_KUN` nullable|string|max:50, `KEGIATAN` nullable|string|max:50, `ID_PIC` nullable|string|max:50, `JLH_PESERTA` nullable|integer`.
    - `authorize()` return true.
- **Resource**
  - `app/Http/Resources/DataKunjunganResource.php` untuk shape JSON sesuai kolom tabel (tanpa timestamps).
- **Service Layer**
  - `app/Services/DataKunjunganService.php` dengan method:
    - `paginate(array $filters, int $perPage = 15)`
    - `create(array $data)`
    - `find(int $id)`
    - `update(int $id, array $data)`
    - `delete(int $id)`
    - Terapkan query dengan Eloquent dan error handling sederhana (throw ModelNotFoundException bila tidak ditemukan).
- **Controller**
  - `app/Http/Controllers/Api/DataKunjunganController.php`:
    - Methods: `index`, `store`, `show`, `update`, `destroy`.
    - Gunakan Form Request + Service + Resource.
    - Response JSON sesuai format project rules: success dengan `data` (resource/s) dan `meta` untuk pagination.
- **Routing**
  - Buat `routes/api.php` bila belum ada.
  - Tambah group prefix `/api/v1` (middleware `api`), route resource: `Route::apiResource('data-kunjungan', DataKunjunganController::class);`
- **Error Handling & Security**
  - Gunakan Form Request untuk validasi.
  - Pastikan mass assignment hanya pada fillable.
  - Tidak ada perubahan yang menghapus data; migration alter kolom memakai `ALTER TABLE` non-destructive.

## Implementation Checklist
1. Buat folder `plans/` (jika belum ada) dan simpan plan ini.
2. Pindahkan seluruh migration dari `database/migrations/generated/` ke `database/migrations/` dengan nama file yang sama dan pastikan urutan timestamp tetap.
3. Tambah migration baru untuk mengubah kolom `NO_URT` menjadi auto-increment menggunakan `ALTER TABLE ... MODIFY NO_URT INT UNSIGNED NOT NULL AUTO_INCREMENT;` tanpa menghapus data.
4. Buat model `app/Models/DataKunjungan.php` dengan konfigurasi tabel, primary key, fillable, dan tanpa timestamps.
5. Tambah Form Request `StoreDataKunjunganRequest` dan `UpdateDataKunjunganRequest` dengan aturan validasi kolom tabel yang ada.
6. Buat Resource `app/Http/Resources/DataKunjunganResource.php` untuk serialisasi field tabel.
7. Buat Service `app/Services/DataKunjunganService.php` yang menangani operasi CRUD dan pagination.
8. Buat Controller `app/Http/Controllers/Api/DataKunjunganController.php` yang memanggil service, menggunakan Form Request, dan mengembalikan Resource/pagination dengan format JSON standar.
9. Tambah `routes/api.php` (jika belum ada) dengan prefix `/api/v1` dan `Route::apiResource('data-kunjungan', DataKunjunganController::class);`
10. (Opsional verifikasi) Jalankan migration di environment dev/staging setelah backup untuk memastikan perubahan kolom tidak menghilangkan data.
