# Plan: Search & Autocomplete — Case-Insensitive + UX Dropdown

## Context

- **Gejala:** Pencarian `fidelis`, `maumere`, atau nama lengkap seperti `Fidelis Vin Coro` di **tabel Anggota** (Web) mengembalikan **0 baris**, padahal data ada saat list tanpa filter.
- **Penyebab (riset):** Backend memakai SQL `LIKE '%term%'` **tanpa** normalisasi huruf besar/kecil. Di Firebird, `LIKE` umumnya **case-sensitive** → `fidelis` ≠ `Fidelis Vin Coro`.
- **Autocomplete Web:** `SearchableDropdown` + `adminAutocomplete.ts` memanggil API yang sama (`/anggota`, `/kel-sah`, dll.) → masalah identik.
- **Autocomplete / search Mobile:** Banyak screen memanggil `getAnggotaList({ search })`, `getKelSahList({ search })`, dll. — **satu perbaikan backend** menguntungkan Web + mobile.
- **Pengecualian:** Beberapa tab Web (Ketua KS, Kelompok Sahabat, …) memuat `per_page=50000` lalu filter **`toLowerCase()` di browser** — tidak terkena bug case di server (tetap diuji regresi).

## Goal

1. Semua endpoint list yang menerima query **`search`** memakai pencarian **case-insensitive** (nama lengkap, sebagian nama, ID numerik tetap jalan).
2. **Autocomplete** Web (`SearchableDropdown`) dan mobile (API sama) otomatis terbantu setelah backend diperbaiki.
3. UX dropdown: saat tidak ada hasil, tampilkan pesan **"Tidak ada hasil"** (bukan dropdown hilang tanpa feedback).
4. **`search`** dari client di-**trim** di backend; wildcard `%` / `_` di-escape (sudah ada pola `addcslashes` di `KelSahService` — disatukan di helper).

## Kebijakan teknis

| Topik | Keputusan |
|-------|-----------|
| Strategi SQL | `UPPER(kolom) LIKE ?` dengan pola `'%'.mb_strtoupper($term, 'UTF-8').'%'` (kompatibel Firebird + SQLite testing). |
| Kolom | Hanya nama kolom **whitelist** (string literal di service), tidak dari input user — cegah SQL injection. |
| Nama + ID | Tetap **OR** beberapa kolom seperti sekarang; semuanya case-insensitive. |
| `KelSahService` | Pertahankan logika pisah **ID** vs **nama** (`kelSahSearchLikePatterns`), ubah ke case-insensitive. |
| MySQL users table (approval, activity) | Pakai helper yang sama atau `whereRaw` setara — `users` di MySQL, `UPPER` tetap valid. |
| `FirebirdService` (PDO mentah) | Tambah `UPPER(NAMA) LIKE UPPER(?)` / setara pada query anggota. |
| Autocomplete commit | **Tetap wajib pilih opsi** (tidak ubah kontrak form); hanya perbaiki **temukan** opsi. |
| Normalisasi spasi ganda | **Opsional fase 1:** trim saja; jika QA masih gagal pada nama dengan spasi ganda di DB, fase lanjut `preg_replace('/\s+/', '%', $term)` — tidak masuk checklist wajib kecuali diminta saat EXECUTE. |

## Detailed Specifications

### A. Backend — helper baru

**File baru:** `app/Support/CaseInsensitiveSearch.php`

**Method (exact):**

- `public static function escapeLike(string $term): string` — `trim` + `addcslashes($term, '%_\\')`.
- `public static function likePattern(string $term): string` — `'%'.mb_strtoupper(self::escapeLike($term), 'UTF-8').'%'`.
- `public static function applyLikeContains(Builder $query, string $column, string $term, string $boolean = 'and'): void` — `$query->whereRaw('UPPER('.$column.') LIKE ?', [self::likePattern($term)], $boolean)`; `$column` hanya dipanggil dengan literal aman dari service (mis. `'NAMA'`, `'anggota.NAMA'`).
- `public static function applyOrLikeContains($query, callable $groupCallback): void` — wrapper `where(function ($q) { ... })` opsional; boleh inline di tiap service tanpa method ini jika lebih jelas.

**Catatan Firebird:** Kolom di model legacy **UPPER_SNAKE**; grammar project memetakan ke quoted uppercase. Gunakan nama kolom sama seperti query `where('NAMA', 'like', ...)` saat ini.

### B. Backend — service Firebird / Eloquent

Ganti blok `if (! empty($filters['search']))` di file berikut — gunakan `CaseInsensitiveSearch::likePattern($term)` + `whereRaw UPPER(...) LIKE ?` untuk setiap kolom yang sebelumnya `like`:

| File | Kolom yang di-search (tetap OR) |
|------|----------------------------------|
| `app/Services/AnggotaService.php` | `NO_AGT`, `NAMA`, `ID_KS`, `ID_KS_ASL`, `TGL_MTS`, `TGL_AKTIF`, `TGL_JA` |
| `app/Services/KelSahService.php` | Refactor `kelSahSearchLikePatterns` + `applyFilters` → `NAMA_KEL`, `ID_KEL` case-insensitive |
| `app/Services/KetuaKsService.php` | `ID_KET`, `NAMA`, `NO_AGT` |
| `app/Services/SekretarisKsService.php` | `ID_SEKRE`, `NAMA`, `NO_AGT` |
| `app/Services/DataLoService.php` | `ID_LO`, `NAMA`, `NO_AGT` (+ kolom lain jika ada di blok search) |
| `app/Services/DataAoService.php` | `ID_AO`, `NAMA`, `NO_AGT` (+ kolom lain jika ada) |
| `app/Services/DataPengelolaService.php` | `data_pengelola.ID_PENG`, `data_pengelola.NO_AGT`, `anggota.NAMA` |
| `app/Services/DataKunjunganService.php` | `applyListNameSearchFilter` + `applyKunjunganReportLineFilters` — `kel_sah.NAMA_KEL`, `anggota.NAMA` |

**File:** `app/Services/FirebirdService.php`

- Pada query list anggota dengan `search` (sekitar baris 163–167 dan 347–349): ganti ke `UPPER(NAMA) LIKE ?` dan `UPPER(NO_AGT) LIKE ?` dengan pola uppercase dari helper (atau duplikasi minimal 2 baris PDO jika helper hanya untuk Builder).

### C. Backend — controller (MySQL / users)

| File | Perubahan |
|------|-----------|
| `app/Http/Controllers/Api/ActivityLogController.php` | `description`, `user_name` → case-insensitive |
| `app/Http/Controllers/Api/SuperAdmin/SystemActivityController.php` | `description`, `user_name`, `resource_type` |
| `app/Http/Controllers/Api/SuperAdmin/UserManagementController.php` | `name`, `email` |
| `app/Http/Controllers/Api/UserApprovalController.php` | `name`, `email` (semua method index yang pakai search) |

Ekstrak ke private method controller atau panggil `CaseInsensitiveSearch` pada query builder `User` / `ActivityLog`.

### D. Backend — verifikasi controller index

**Tidak ubah** daftar filter kecuali pastikan `search` sudah diteruskan (sudah OK untuk `AnggotaController`):

- `KelSahController`, `KetuaKsController`, `SekretarisKsController`, `DataLoController`, `DataAoController`, `DataPengelolaController`, `DataKunjunganController` — hanya konfirmasi `search` ada di `only([...])` (perbaiki jika ada yang terlewat saat EXECUTE).

### E. Backend — tests

**File baru:** `tests/Feature/CaseInsensitiveSearchApiTest.php` (atau perluas `tests/Feature/AnggotaApiTest.php` jika sudah ada)

Minimal:

1. Seed `Anggota` dengan `NAMA` = `Fidelis Vin Coro`, `NO_AGT` unik.
2. `GET /api/anggota?search=fidelis` → assert contains row.
3. `GET /api/anggota?search=FIDELIS VIN` → assert contains row.
4. `GET /api/anggota?search=016005` (substring ID) → assert contains row jika ID cocok.

**File:** `tests/Unit/CaseInsensitiveSearchTest.php` — `likePattern('fidelis')` menghasilkan `%FIDELIS%`, escape `%` dan `_`.

Jalankan: `vendor\bin\phpunit tests/Feature/CaseInsensitiveSearchApiTest.php tests/Unit/CaseInsensitiveSearchTest.php`

### F. Web Admin — `SearchableDropdown`

**File:** `src/app/components/SearchableDropdown.tsx`

1. Di `runAsyncFetch` setelah fetch: **jangan** `setIsOpen(false)` hanya karena `rows.length === 0`; set `setIsOpen(true)` jika `q.length >= minSearchLength` (tampilkan panel kosong dengan pesan).
2. Di render dropdown: jika `isAsync && !asyncLoading && displayOptions.length === 0 && searchTerm.trim().length >= minSearchLength` → tampilkan `<div>Tidak ada hasil</div>` (teks Indonesia, class sama dengan state loading).
3. Mode sync (`!isAsync`): perilaku sama jika `filteredOptions.length === 0` dan panjang cukup.

**Tidak wajib** mengubah `adminAutocomplete.ts` jika backend sudah case-insensitive (opsional: `trim()` pada `q` sebelum request — sudah ada di beberapa fungsi).

### G. Web Admin — tidak wajib ubah (regresi manual)

| Area | Alasan |
|------|--------|
| `AnggotaTab.tsx` | Sudah kirim `search` ke API — cukup backend |
| Tab dengan filter client `toLowerCase` | Tetap jalan; uji manual tidak rusak |
| `ManajemenUserPage` autocomplete | Pakai `fetchAnggotaDropdownOptions` — ikut backend |

### H. Mobile — tidak wajib ubah kode (regresi manual)

Setelah backend deploy, uji manual pada:

- `MemberDataScreen`, `KetuaKSDataScreen`, `SekretarisKSDataScreen`, `DataPengelolaScreen`, `JlhKeluargaDataScreen`, `DataPenghasilanScreen`, `KelompokSahabatScreen` (5 autocomplete), `TargetRealisasiAdminScreen`, `DataKunjunganContent`, `KelompokSahabatScreen` (list search).

**Opsional EXECUTE:** tambah pesan "Tidak ada hasil" di pola dropdown mobile yang meniru Web (out of scope checklist wajib — hanya jika waktu memungkinkan).

### I. Dokumentasi

**File:** `API_DOCUMENTATION.md` — pada bagian query `search` untuk resource master:

- Catatan: pencarian **tidak peka huruf besar/kecil**; mendukung substring nama lengkap dan bagian ID.

## Implementation Checklist

1. Buat `app/Support/CaseInsensitiveSearch.php` dengan `escapeLike`, `likePattern`, `applyLikeContains`.
2. Refactor `AnggotaService::applyFilters` — semua kolom search pakai helper.
3. Refactor `KelSahService` — `kelSahSearchLikePatterns` + `applyFilters` case-insensitive.
4. Refactor `KetuaKsService::applyFilters` search block.
5. Refactor `SekretarisKsService::applyFilters` search block.
6. Refactor `DataLoService::applyFilters` search block.
7. Refactor `DataAoService::applyFilters` search block.
8. Refactor `DataPengelolaService::applyFilters` search block.
9. Refactor `DataKunjunganService` — `applyListNameSearchFilter` dan `applyKunjunganReportLineFilters`.
10. Perbarui `FirebirdService` — query PDO anggota dengan `UPPER` + pola helper.
11. Perbarui `ActivityLogController` search — case-insensitive.
12. Perbarui `SystemActivityController` search — case-insensitive.
13. Perbarui `UserManagementController` search — case-insensitive.
14. Perbarui `UserApprovalController` — semua blok `search` — case-insensitive.
15. Audit cepat controller index: pastikan `search` ada di `$request->only` untuk resource terkait (perbaiki jika ada yang hilang).
16. Tambah `tests/Unit/CaseInsensitiveSearchTest.php`.
17. Tambah `tests/Feature/CaseInsensitiveSearchApiTest.php` (anggota: fidelis, FIDELIS VIN, substring ID).
18. Jalankan PHPUnit terkait; pastikan hijau di SQLite.
19. Web: edit `SearchableDropdown.tsx` — pesan "Tidak ada hasil" + jangan tutup panel saat 0 hasil async/sync.
20. Update `API_DOCUMENTATION.md` — perilaku `search` case-insensitive.
21. Manual QA Web: Anggota search `fidelis`, `Fidelis Vin Coro`, `000`; Target modal kelompok `maumere` / `Maumere`; autocomplete pilih dari dropdown.
22. Manual QA Mobile: ketua/anggota/kelompok autocomplete dengan nama kecil dan nama lengkap.

## Risks / Catatan

- **Performa:** `UPPER(kolom)` bisa menghambat index; acceptable untuk `per_page` kecil (15–100) dan autocomplete 30. Jika lambat di PROD, indeks/Firebird computed column bisa fase berikutnya.
- **SQLite vs Firebird:** Test CI pakai SQLite; verifikasi smoke di Firebird PROD setelah deploy.
- **Kolom tanggal di Anggota search:** Tetap di-search sebagai string (perilaku lama); hanya ditambah case-insensitive jika kolom berisi teks — tanggal numerik tidak terpengaruh negatif.
- **Plan terpisah:** `plans/autocomplete-datepicker-ux-fix.md` (dropdown buka ulang setelah pilih) — **tidak digabung**; bisa dikerjakan berurutan setelah plan ini.

## Out of scope

- Refactor satu komponen autocomplete mobile bersama.
- Search multi-kata (AND per kata) / full-text search.
- Mengubah AnggotaTab Web ke client-side filter.
- Wajib pilih vs auto-commit saat satu hasil (bisa INNOVATE terpisah).
