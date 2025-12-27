# Plan: CRUD data_pengelola

## Context
- Table `data_pengelola` sudah ada migration dengan kolom: `ID_PENG` (PK, string 12), `NO_AGT` (nullable string 15), `NO_SK` (nullable integer).
- Belum ada model/service/controller/request/resource/route yang menangani table ini.
- Pola CRUD resource lain: route apiResource dalam grup `auth:sanctum`, controller tipis memanggil service, request untuk validasi, resource untuk response, model tanpa timestamps.

## Goal
- Menyediakan CRUD RESTful lengkap untuk `data_pengelola` dengan pola konsisten seperti resource lain.
- Mendukung pagination + filter sederhana pada listing.
- Validasi input sesuai tipe/ukuran kolom migration.

## Detailed Specifications
- `app/Models/DataPengelola.php`
  - Model untuk table `data_pengelola`; `protected $primaryKey = 'ID_PENG'`; `public $incrementing = false`; `protected $keyType = 'string'`; `public $timestamps = false`.
  - `$fillable` berisi `['ID_PENG', 'NO_AGT', 'NO_SK']`.
- `app/Services/DataPengelolaService.php`
  - Method `paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator` yang memfilter opsional `ID_PENG` dan `NO_AGT`.
  - Method `create(array $data): DataPengelola` menggunakan `create`.
  - Method `find(string $id): DataPengelola` menggunakan `findOrFail`.
  - Method `update(string $id, array $data): DataPengelola` menemukan lalu `update`.
  - Method `delete(string $id): void` menemukan lalu `delete`.
- `app/Http/Requests/StoreDataPengelolaRequest.php`
  - Rules: `ID_PENG` required|string|max:12; `NO_AGT` nullable|string|max:15; `NO_SK` nullable|integer.
- `app/Http/Requests/UpdateDataPengelolaRequest.php`
  - Rules: `NO_AGT` nullable|string|max:15; `NO_SK` nullable|integer. (Tidak mengizinkan ubah PK).
- `app/Http/Resources/DataPengelolaResource.php`
  - `toArray` mengembalikan `ID_PENG`, `NO_AGT`, `NO_SK`.
- `app/Http/Controllers/Api/DataPengelolaController.php`
  - `__construct` menerima `DataPengelolaService`.
  - `index(Request $request)`: ambil `per_page` default 15; filter `ID_PENG`, `NO_AGT`; panggil service paginate; kembalikan collection resource.
  - `store(StoreDataPengelolaRequest $request)`: buat record; return resource status 201.
  - `show(string $id)`: find; return resource.
  - `update(UpdateDataPengelolaRequest $request, string $id)`: update; return resource.
  - `destroy(string $id)`: delete; return noContent.
- `routes/api.php`
  - Tambah `Route::apiResource('data-pengelola', DataPengelolaController::class);` dalam grup `auth:sanctum`.

## Implementation Checklist
1. Buat model `app/Models/DataPengelola.php` dengan konfigurasi PK, fillable, timestamps=false.
2. Buat service `app/Services/DataPengelolaService.php` dengan paginate (filter ID_PENG, NO_AGT), create, find, update, delete.
3. Buat request `app/Http/Requests/StoreDataPengelolaRequest.php` dengan rules ID_PENG required max:12; NO_AGT nullable max:15; NO_SK nullable integer.
4. Buat request `app/Http/Requests/UpdateDataPengelolaRequest.php` dengan rules NO_AGT nullable max:15; NO_SK nullable integer.
5. Buat resource `app/Http/Resources/DataPengelolaResource.php` untuk field ID_PENG, NO_AGT, NO_SK.
6. Buat controller `app/Http/Controllers/Api/DataPengelolaController.php` yang memanggil service dan memakai request/resource sesuai pola CRUD.
7. Tambah route `Route::apiResource('data-pengelola', DataPengelolaController::class);` ke `routes/api.php` di dalam middleware `auth:sanctum`.
