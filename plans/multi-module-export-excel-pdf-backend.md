# Plan: Export Excel & PDF (Backend) — Modul AO, LO, Kelompok Sahabat, Ketua KS, Sekretaris, Penghasilan, Pengelola, Jumlah Keluarga, Kunjungan, Data Transaksi

## Context

- Modul **Anggota** sudah punya pola: `GET .../export/excel`, `GET .../export/pdf`, `AnggotaService::listForExport`, `AnggotaExportService`, blade PDF, dependency PhpSpreadsheet + DomPDF.
- Data per modul lain **masih sedikit**; prioritas: **fungsi jalan** dengan limit sama filosofi Anggota (Excel lebih besar, PDF lebih kecil + DomPDF).
- Server sudah pernah bermasalah jika file/controller/package tidak lengkap — deploy harus menyertakan **semua file baru** + `composer install`.

## Goal

- Menyediakan **endpoint export Excel + PDF** untuk 10 modul (selain Anggota yang sudah ada), filter **selaras dengan `index`** masing-masing, **limit** untuk cegah OOM/timeout jangka panjang.
- Satu pola kode **DRY** di backend: service generik untuk generate file + per modul hanya **query + mapping kolom**.

## Detailed Specifications

### 1. Service generik (baru)

**File:** `app/Services/TabularExportService.php`

**Tanggung jawab:**

- `excelResponse(string $fileBaseName, string $bannerTitle, array $headerLabels, iterable $rowsAsArrays): StreamedResponse`
  - Pola sama `AnggotaExportService::excelResponse`: judul baris 1, header di baris tetap, data `fromArray` per baris.
- `pdfResponse(string $fileBaseName, string $bannerTitle, array $headerLabels, iterable $rowsAsArrays): Response`
  - `Pdf::loadView('exports.tabular-pdf', [...])` landscape A4.

**File view (baru):** `resources/views/exports/tabular-pdf.blade.php`

- Mirip `exports/anggota-pdf.blade.php` tapi terima `$headers` (array string) dan `$rows` (array of array / Collection array), loop `@foreach` isi sel **tanpa** asumsi model Eloquent.

**Catatan:** `$rowsAsArrays` bisa `array` atau `Collection` of `array` — isi sel selalu skalar/string.

### 2. Trait bantu limit & runtime (baru, opsional tapi disarankan)

**File:** `app/Http/Controllers/Concerns/RelaxesExportTimeouts.php` (atau trait di namespace `App\Http\Controllers`)

**Isi:**

- `relaxExportRuntimeLimits(): void` — salin logika dari `AnggotaController` (`set_time_limit(300)`, `ini_set('max_execution_time', '300')`).
- `exportExcelLimit(Request $request): int` — `min(max(1, $request->integer('limit', 5000)), 10000)`.
- `exportPdfLimit(Request $request): int` — `min(max(1, $request->integer('limit', 800)), 3000)`.

**Refactor kecil (opsional dalam scope yang sama):** `AnggotaController` dipakai trait ini + `AnggotaExportService` bisa diganti pemanggilan `TabularExportService` **atau** dibiarkan seperti sekarang untuk mengurangi risiko regresi — **pilih satu:**  
- **Opsi A (disarankan):** Anggota tetap pakai `AnggotaExportService` (tidak diubah).  
- **Opsi B:** Anggota dialihkan ke `TabularExportService` + hapus duplikasi blade anggota (satu blade generik saja).  

**Plan ini memakai Opsi A** agar scope stabil; refactor Anggota bisa fase terpisah.

### 3. Per modul: `listForExport` di Service

Tambah method publik di **masing-masing** service di bawah, dengan signature:

```php
/**
 * @param array<string, mixed> $filters
 * @return \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>
 */
public function listForExport(array $filters, int $limit): Collection
```

**Aturan:** Duplikasi logika filter dari `paginate()` — refactor ke `protected function applyFilters(Builder $query, array $filters): void` bila perlu (sama pola `AnggotaService`).

| Service | File | Filter (sama seperti `index` / `paginate`) | Order by (stabil) |
|---------|------|---------------------------------------------|-------------------|
| KelSahService | `app/Services/KelSahService.php` | `ID_KEL`, `ID_LO`, `ID_AO`, `search` | `ID_KEL` |
| DataLoService | `app/Services/DataLoService.php` | `ID_LO`, `NO_AGT`, `search` | `ID_LO` |
| DataAoService | `app/Services/DataAoService.php` | `ID_AO`, `NO_AGT`, `search` | `ID_AO` |
| KetuaKsService | `app/Services/KetuaKsService.php` | `ID_KET`, `NO_AGT`, `NAMA`, `STAT`, `search` | `ID_KET` |
| SekretarisKsService | `app/Services/SekretarisKsService.php` | `ID_SEKRE`, `NO_AGT`, `NAMA`, `STAT`, `search` | `ID_SEKRE` |
| DataPengelolaService | `app/Services/DataPengelolaService.php` | `ID_PENG`, `NO_AGT`, `search` | `ID_PENG` |
| DataPenghasilanService | `app/Services/DataPenghasilanService.php` | `NO_AGT` saja (sesuai paginate sekarang) | `NO_AGT` |
| DataJlhKeluargaService | `app/Services/DataJlhKeluargaService.php` | `NO_AGT` | `NO_AGT` |
| DataKunjunganService | `app/Services/DataKunjunganService.php` | `ID_LO`, `NO_AGT`, `ID_KEL_SAH`, `TGL_KUN`, `KEGIATAN`, `ID_PIC` (sama `paginate`) | `NO_URT` |
| DataTrsService | `app/Services/DataTrsService.php` | `NO_AGT` | `NO_AGT` |

**DataKunjungan:** kolom export selaras `DataKunjunganResource`: `NO_URT`, `ID_LO`, `NO_AGT`, `ID_KEL_SAH`, `TGL_KUN`, `KEGIATAN`, `ID_PIC`, `JLH_PESERTA`, `FOTO_PATH` (string path), `LATITUDE`, `LONGITUDE` — **bukan** embed foto.

**DataTrs:** header label bahasa Indonesia disarankan selaras nama kolom bisnis; urutan kolom ikut `DataTrsResource` keys (NO_AGT, STR_SP, …, TGL_LAP).

### 4. Per modul: method Controller + injeksi

**Inject di constructor:** `TabularExportService $tabularExport` + trait `RelaxesExportTimeouts`.

**Method per controller:**

- `exportExcel(Request $request): StreamedResponse`
- `exportPdf(Request $request): Response`

**Alur:** `relaxExportRuntimeLimits()` → `$filters = $request->only([...])` (mirror `index`) → `$limit = exportExcelLimit` / `exportPdfLimit` → `$rows = $this->service->listForExport($filters, $limit)` → map tiap model ke `array` baris → `$this->tabularExport->excelResponse(...)` / `pdfResponse(...)`.

**File controller yang diubah:**

- `app/Http/Controllers/Api/KelSahController.php`
- `app/Http/Controllers/Api/DataLoController.php`
- `app/Http/Controllers/Api/DataAoController.php`
- `app/Http/Controllers/Api/KetuaKsController.php`
- `app/Http/Controllers/Api/SekretarisKsController.php`
- `app/Http/Controllers/Api/DataPengelolaController.php`
- `app/Http/Controllers/Api/DataPenghasilanController.php`
- `app/Http/Controllers/Api/DataJlhKeluargaController.php`
- `app/Http/Controllers/Api/DataKunjunganController.php`
- `app/Http/Controllers/Api/DataTrsController.php`

**Nama file download:** prefix modul + `Ymd_His`, contoh `KelompokSahabat_20260324_120000.xlsx`.

### 5. Routes (`routes/api.php`)

**Sebelum** masing-masing `Route::apiResource(...)`, tambahkan (urutan penting — sebelum `{id}`):

- `Route::get('kel-sah/export/excel', [KelSahController::class, 'exportExcel']);`
- `Route::get('kel-sah/export/pdf', [KelSahController::class, 'exportPdf']);`
- Idem untuk: `data-lo`, `data-ao`, `ketua-ks`, `sekretaris-ks`, `data-pengelola`, `data-penghasilan`, `data-jlh-keluarga`, `data-kunjungan`, `data-trs`

**Middleware:** tetap grup `auth:sanctum` seperti resource yang ada.

### 6. Frontend (mobile) — luar scope wajib plan backend ini; catatan

- Untuk **fungsi end-to-end di app**, tiap layar perlu memanggil endpoint baru (mirip `anggotaService` + `apiFetchBinary` + `saveBackendExportBlob`).
- Bisa fase **2** setelah backend terverifikasi dengan `curl`/Postman.

### 7. Header label (harus konsisten per modul)

Rujuk `Kelompok Sahabat Obor Mas/src/config/exportConfig.ts` untuk AO, LO, Pengelola, Ketua KS, Sekretaris, Kelompok Sahabat.  
Tambah definisi header untuk modul yang **belum** ada di exportConfig saat implementasi frontend (Penghasilan, Jlh Keluarga, Kunjungan, Transaksi) — di backend pakai array literal yang sama urutan dengan mapping.

**Penghasilan (usulan header):** `Nomor Anggota`, `Penghasilan`, `Pengeluaran`, `Tanggal Data`  
**Jumlah keluarga:** `Nomor Anggota`, `Jumlah Anggota Keluarga`, `Tanggal`  
**Kunjungan:** selaras kolom di atas.  
**Transaksi:** gunakan label singkat untuk STR_* atau nama kolom DB jika belum ada glosarium.

## Risks / Catatan

- **504 / memory** jika nanti data besar — limit sudah ada; Nginx `fastcgi_read_timeout` & PHP-FPM `request_terminate_timeout` tetap relevan.
- **Deploy manual:** upload `TabularExportService.php`, trait, `tabular-pdf.blade.php`, semua perubahan service/controller/route; `composer` tidak bertambah jika pakai library yang sama Anggota.
- **DataTrs** hanya read: export tetap GET dengan auth.
- **ForceJsonResponse:** pastikan path berisi `/export/` tidak dipaksa `Accept: application/json` (sudah diperbaiki di middleware jika file itu ikut di-deploy).

---

## Implementation Checklist (urut, atomik)

1. Buat `resources/views/exports/tabular-pdf.blade.php` (judul + tabel dari `$headers` + `$rows`).
2. Buat `app/Services/TabularExportService.php` dengan `excelResponse` dan `pdfResponse`.
3. Buat trait `app/Http/Controllers/Concerns/RelaxesExportTimeouts.php` berisi `relaxExportRuntimeLimits`, `exportExcelLimit`, `exportPdfLimit`.
4. Refactor `KelSahService`: ekstrak `applyFilters` dari `paginate` (jika belum), tambah `listForExport`.
5. Refactor `DataLoService`: sama, tambah `listForExport`.
6. Refactor `DataAoService`: sama, tambah `listForExport`.
7. Refactor `KetuaKsService`: sama, tambah `listForExport`.
8. Refactor `SekretarisKsService`: sama, tambah `listForExport`.
9. Refactor `DataPengelolaService`: sama, tambah `listForExport`.
10. Tambah `listForExport` pada `DataPenghasilanService` (filter `NO_AGT` saja).
11. Tambah `listForExport` pada `DataJlhKeluargaService` (filter `NO_AGT` saja).
12. Tambah `listForExport` pada `DataKunjunganService` (filter sama `paginate`, order `NO_URT`).
13. Tambah `listForExport` pada `DataTrsService` (filter `NO_AGT`, order `NO_AGT`).
14. Update `KelSahController`: constructor inject `TabularExportService`, pakai trait, implement `exportExcel` / `exportPdf` + mapping kolom ke array.
15. Update `DataLoController`: sama.
16. Update `DataAoController`: sama.
17. Update `KetuaKsController`: sama.
18. Update `SekretarisKsController`: sama.
19. Update `DataPengelolaController`: sama.
20. Update `DataPenghasilanController`: sama.
21. Update `DataJlhKeluargaController`: sama.
22. Update `DataKunjunganController`: sama.
23. Update `DataTrsController`: sama.
24. Edit `routes/api.php`: daftar 20 route `GET` export (10 modul × 2) **sebelum** `apiResource` masing-masing.
25. Jalankan `php artisan route:list` dan filter `--path=export` untuk verifikasi 22 route Anggota+lain atau minimal 20 baru + 2 anggota.
26. Uji manual: `curl` dengan Bearer token ke satu modul (excel + pdf) cek HTTP 200 dan `Content-Type`.
27. (Opsional fase 2) Wire frontend per layar memanggil endpoint baru.

---

**Setelah user menyetujui plan ini:** sinyal `ENTER EXECUTE MODE` untuk implementasi kode sesuai checklist di atas.
