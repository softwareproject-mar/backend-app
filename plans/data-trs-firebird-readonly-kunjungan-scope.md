# Plan: Data TRS baca Firebird + Kunjungan NO_AGT sekelompok (role user)

## Context

- **Data TRS**: Saat ini `GET /data-trs` membaca tabel `data_trs` pada koneksi default model + ada `mergeOwnershipFilterForCrud` untuk role `user`; ada CRUD HTTP untuk admin; `DataTrsService::applyFilters` memanggil `supportsCreatedByFilter()` yang **tidak terdefinisi** di kelas (bug). Impor TRS (`POST import-data-trs-firebird`) menulis dari **`firebird_legacy`** → DB operasional. Kebutuhan bisnis: TRS **hanya baca**, sumber tampilan sama seperti impor (**koneksi `firebird_legacy`**, tabel `DATA_TRS`), tanpa CRUD dari app untuk user maupun admin.
- **Kunjungan**: `DataKunjunganService::create` untuk role `user` **menimpa** `NO_AGT` dengan nomor anggota login — bertentangan dengan kebutuhan: kunjungan boleh atas **anggota lain sekelompok**, kepemilikan baris lewat **`created_by`**, list tetap filter pembuat.

## Goal

1. **TRS**: Endpoint publikasi TRS (list + export) membaca **langsung dari Firebird `firebird_legacy`** (polanya sama dengan `FirebirdService::fetchDataTrsRows` / impor). Hilangkan **POST/PUT/PATCH/DELETE** resource `data-trs` untuk semua role; nonaktifkan atau hapus **POST** `import-data-trs-firebird` (menulis ke DB operasional). Pertahankan GET preview/list Firebird untuk debugging/admin jika masih berguna (opsional).
2. **Kunjungan**: Untuk role `user`, **jangan timpa** `NO_AGT` dari payload; tetap set `created_by`; validasi **`NO_AGT` anggota tersebut berada di kelompok yang sama** dengan user (`MemberScope::memberKelompokId` vs `Anggota.ID_KS`); set `ID_KEL_SAH` konsisten dengan kelompok kunjungan (sama dengan kelompok user jika validasi lolos); syarat akun: minimal **`memberKelompokId`** tidak null (tidak wajib memaksa `memberNoAgt` untuk bisa input).

## Detailed Specifications

### A. `app/Services/FirebirdService.php`

- Tambah method **`paginateDataTrs(array $filters, int $page, int $perPage): array`** dengan return shape `{ data: list<array>, total: int }`:
  - `connect()` ke **`firebird_legacy`** (sudah dipakai constructor).
  - Tabel: `firebirdDataTrsTable()` (sama seperti `fetchDataTrsRows`).
  - Kolom SELECT: sama seperti `fetchDataTrsRows` / `normalizeDataTrsRow`.
  - Filter opsional: `NO_AGT` dari `$filters` (trim; kosong = tanpa filter).
  - Total: `COUNT(*)` dengan WHERE yang sama.
  - Slice: Firebird `ROWS $from TO $to` dengan `$from`/`$to` integer dari pagination (hindari binding untuk clause ROWS; gunakan integer yang sudah di-sanitize).
  - ORDER BY deterministik, mis. `NO_AGT, TGL_LAP`.
  - Setiap baris dinormalisasi dengan **`normalizeDataTrsRow`**.
- Tambah method **`fetchDataTrsForExport(?string $noAgt, int $limit): array`** mengembalikan list baris normalisasi, `ROWS 1 TO $limit`, filter `NO_AGT` opsional.

### B. `app/Http/Controllers/Api/DataTrsController.php`

- Inject **`FirebirdService`** (constructor).
- **`index`**: Bangun `LengthAwarePaginator` dari hasil `paginateDataTrs` (page dari query `page`, `per_page` dibatasi mis. max 500). Map tiap baris array → object/stdClass dengan field **`id`** stabil (mis. hash ringkas dari isi baris atau kombinasi NO_AGT+TGL_LAP+kolom pertama) agar `DataTrsResource` dan klien mobile tetap dapat `id`.
- **`exportExcel`** / **`exportPdf`**: Ambil baris dari **`fetchDataTrsForExport`** dengan `NO_AGT` dari query dan `limit` dari trait `exportExcelLimit` / `exportPdfLimit`; mapping kolom ke array baris sama seperti sekarang (EXPORT_HEADERS).
- **Hapus** method **`store`**, **`update`**, **`destroy`**, **`show`** dari controller (atau biarkan tidak ter-route — lebih bersih hapus).
- Hapus dependency **`DataTrsService`** dari controller jika tidak lagi dipakai untuk TRS publik.
- Hapus import tidak terpakai: `StoreDataTrsRequest`, `UpdateDataTrsRequest`, `MemberScope`, `Response` jika tidak digunakan.

### C. `app/Http/Resources/DataTrsResource.php`

- Pastikan **`id`** ter-resolve untuk resource yang bukan model Eloquent (mis. `data_get($this->resource, 'id')` atau akses properti aman) agar tidak bergantung pada `getKey()` model saja.

### D. `routes/api.php`

- Ganti `Route::apiResource('data-trs', ...)` menjadi **hanya**:
  - `GET data-trs` → `index`
  - `GET data-trs/export/excel` dan `GET data-trs/export/pdf` (urutan GET export sebelum pola `{id}` jika ada — tetap seperti pola modul lain).
- **Hapus** route **`POST import-data-trs-firebird`** (impor ke MySQL tidak sesuai kebutuhan). Pertahankan **`GET import-data-trs-firebird`** dan **`GET .../{noAgt}`** preview jika masih berguna untuk admin (hanya baca Firebird — tidak wajib dihapus).

### E. `app/Services/DataTrsService.php`

- Setelah controller tidak memakai untuk read path: pertahankan hanya jika masih dipakai oleh job/command lain; jika tidak, bisa dihapus dead code **create/update/delete/find/paginate** atau disederhanakan. **Minimal**: perbaiki bug **`supportsCreatedByFilter`** dengan menghapus pemanggilan atau mengimplementasikan — lebih baik **hapus path Eloquent read** dari penggunaan API utama sesuun bagian B.

### F. `app/Http/Controllers/Api/DataTrsImportController.php`

- Method **`import`**: tidak ter-route jika POST dihapus; opsional sisakan method dengan `@deprecated` atau hapus method `import` untuk hindari salah pakai.

### G. `app/Services/DataKunjunganService.php` — method **`create`**

- Untuk `MemberScope::isRestrictedMemberUser($user)`:
  - **Hapus** assignment **`$data['NO_AGT'] = $memberNoAgt`** dan blok yang memaksa nomor login.
  - Tetap set **`$data['created_by']`** ke `$user->id`.
  - Validasi: **`memberKelompokId($user)`** harus tidak null; jika null → `abort(422, ...)`.
  - Validasi **`NO_AGT`** dari `$data` (setelah trim): wajib ada; query **`Anggota`** untuk `NO_AGT` tersebut; **`trim(ID_KS)` harus sama dengan `memberKelompokId`** → jika tidak → `abort(403)` atau `422` dengan pesan konsisten.
  - Set **`$data['ID_KEL_SAH']`** = **`memberKelompokId`** (setelah validasi, sama dengan kelompok anggota dikunjungi).
  - Tetap **`$data['ID_LO'] = null`** jika itu kebijakan bisnis yang dipertahankan.
- Tambahkan **`use App\Models\Anggota`** jika lookup dilakukan di service.

### H. `app/Services/DataKunjunganService.php` — method **`update`** (role user)

- Saat ini ada `unset($data['ID_KEL_SAH'])` dengan komentar “Kelompok mengikuti data user”. Setelah create mengizinkan NO_AGT anggota lain, **pertimbangkan**: tetap **larang ubah** `NO_AGT` / `ID_KEL_SAH` untuk user terbatas (sudah ada unset `NO_AGT`) — **cukup** pastikan tidak ada logika lain yang mengikat ke `memberNoAgt` saja. Plan: **tidak ubah** perilaku update kecuali pesan/komentar diselaraskan (optional).

### I. Tests

- **`tests/Feature/MultiRowNoAgtChildRecordsTest.php`**: Test yang **`POST /api/data-trs`** untuk membuat dua baris — **hapus atau ganti** menjadi skip/assert bahwa POST tidak lagi didukung (405/404) atau hapus file test tersebut jika hanya untuk TRS create.
- Tambah atau sesuaikan **feature test** untuk `GET /api/data-trs` yang mock/stub Firebird jika lingkungan CI tidak punya Firebird (opsional — bisa ditandai `group`/`skip` jika koneksi tidak ada).

### J. Dokumentasi (opsional, hanya jika Anda minta)

- **Tidak** mengubah `API_DOCUMENTATION.md` kecuali user meminta eksplisit (sesuai preferensi mengurangi markdown tak diminta).

### K. `app/Http/Requests/StoreDataKunjunganRequest.php` (gap dari audit)

- Saat ini rules memakai **`Rule::requiredIf(! $isMemberUser)`** pada **`NO_AGT`**, artinya untuk **`role === 'user'`**, **`NO_AGT` tidak wajib** — bentrok dengan kebutuhan “input nomor anggota (boleh anggota lain sekelompok)” dan dengan validasi service (bagian G) yang menganggap **`NO_AGT`** ada.
- **Perbaikan wajib dalam plan**: untuk role **`user`**, set **`NO_AGT`** menjadi **`required`** (tetap **`exists:anggota,NO_AGT`** + **`NoAgtBelongsToMemberKelompok`**).
- **`NoAgtBelongsToMemberKelompok`** hanya jalan jika **`config('obormas.strict_member_no_agt_same_kelompok')`** true — validasi kelompok di **`DataKunjunganService::create`** (bagian G) menjadi **sumber kebenaran** untuk user terbatas agar aturan sama meskipun flag false; secara opsional bisa dokumentasikan penyelarasan default **`STRICT_MEMBER_NO_AGT_SAME_KELOMPOK=true`** di `.env`.

---

## Kesesuaian dengan tujuan keseluruhan (audit mendalam)

| Tujuan bisnis (dari diskusi) | Status vs plan / kode saat ini |
|------------------------------|--------------------------------|
| **TRS**: tidak ada CRUD user/admin; baca dari **`firebird_legacy`** seperti sumber impor | **Tercakup** oleh bagian A–F + checklist; menggantikan read dari model `data_trs` + menghapus endpoint tulis. |
| **TRS**: tampil tanpa filter pembuat (`created_by`) — data dipakai bersama | **Tercakup**: list/export langsung Firebird tanpa `mergeOwnershipFilterForCrud`. |
| **Import TRS POST** menulis ke DB operasional | **Tercakup**: hapus route POST (selaras “tidak ada jalur tulis TRS di app”). |
| **Jlh keluarga / Penghasilan**: list role **`user`** per **`created_by`** | **Sudah selaras di kode** (`mergeOwnershipFilterForCrud` + `applyFilters` tanpa gate Schema rusak). **Tidak** perlu task baru di checklist kecuali regresi manual. |
| **Kunjungan**: list per **`created_by`** | **Sudah selaras** (`DataKunjunganService::paginate`). |
| **Kunjungan**: boleh **`NO_AGT`** anggota lain sekelompok; pemilik baris = **`created_by`** | **Tercakup** bagian G + **bagian K** (Form Request). |
| **Modul anggota (user)**: satu kelompok | **Sudah selaras** (`AnggotaController@index` + `memberKelompokId`). **Di luar** checklist ini (tidak ada perubahan direncanakan). |
| **Autocomplete mobile** (jlh / penghasilan / kunjungan): sekelompok | **Sudah selaras** di app (`kelompokAnggotaSearch`). **Di luar** backend checklist kecuali kontrak API berubah (TRS **`id`** sintetis — pastikan mobile `dataTrsService` tetap jalan). |

## Implementation Checklist (sequential)

1. Tambah **`paginateDataTrs`** dan **`fetchDataTrsForExport`** di **`app/Services/FirebirdService.php`** sesuai spesifikasi bagian A.
2. Refactor **`DataTrsController`**: inject **`FirebirdService`**; implement **`index`** dan **`exportExcel`** / **`exportPdf`** memakai Firebird; bangun **`LengthAwarePaginator`** dengan URL/query request; attach **`id`** sintetis per baris.
3. Sesuaikan **`DataTrsResource`** untuk **`id`** dari object/array non-model (bagian C).
4. Ubah **`routes/api.php`**: ganti **`apiResource('data-trs')`** dengan route **GET saja** untuk index + export; hapus **`POST import-data-trs-firebird`**.
5. Hapus method **`store`**, **`update`**, **`destroy`**, **`show`** dari **`DataTrsController`** dan bersihkan import/use.
6. Bereskan **`DataTrsService`**: hapus seluruh kelas atau sisakan stub kosong hanya jika masih direferensi — di **`app/`** saat ini hanya dipakai **`DataTrsController`**; setelah refactor bisa **hapus file** atau kurangi ke nol pemakaian (prioritas: hilangkan pemanggilan **`supportsCreatedByFilter`** yang tidak ada).
7. Sesuaikan **`DataTrsImportController`**: hapus atau nonaktifkan method **`import`** yang tidak ter-route (sesuai langkah 4).
8. Edit **`StoreDataKunjunganRequest`** agar **`NO_AGT`** **required** untuk role **`user`** (bagian K).
9. Edit **`DataKunjunganService::create`** sesuai bagian G (hapus overwrite **`NO_AGT`**; validasi kelompok via **`Anggota`**).
10. Update **`tests/Feature/MultiRowNoAgtChildRecordsTest.php`** sesuai bagian I.
11. Verifikasi **`src/services/dataTrsService.ts`** / **`DataTrsScreen.tsx`** (mobile): bentuk respons **`data[].id`** masih dipakai — uji manual atau smoke test setelah **`id`** sintetis.
12. Jalankan **`php artisan route:list`** / **`php -l`** pada file terubah; jalankan **`php artisan test`** (setidaknya test yang relevan).

## Risks / Catatan

- **Firebird tidak tersedia** di environment (mis. CI): `GET /data-trs` akan error — pertimbangkan graceful degradation atau test yang di-skip.
- **Performa**: export besar dari Firebird — sudah ada `relaxExportRuntimeLimits` dan batas `limit`.
- **`id` sintetis**: Pastikan **unik per baris** di satu response dan stabil antar request jika UI menyimpan referensi (gunakan hash konten baris, bukan nomor urut halaman saja, untuk mengurangi tabrakan).
- **Deprecation**: Klien yang masih memanggil **POST/PUT/DELETE data-trs** atau **POST import-data-trs-firebird** akan rusak — itu **disengaja** sesuai goal.
- **Duplikasi validasi kunjungan**: **`StoreDataKunjunganRequest`** + **`DataKunjunganService::create`** sama-sama bisa mengecek kelompok — dual-layer disengaja jika Rule bergantung config; service wajib konsisten untuk user terbatas.
