# Plan: Target & Realisasi per field — Backend + Web Admin (Fase 1)

## Context

- **Sudah ada:** trigger PROD `TRG_DATA_TRS_SYNC_REALISASI` + `SP_SYNC_REALISASI_KS` mengisi tabel `REALISASI` per `ID_KS` (`TGL_TGT = DATE '2099-01-01'`), kolom 8 setoran + `PCR_PJM`/`BNG_PJM`.
- **Sudah ada:** API `GET/PUT admin/target-realisasi/*` dan halaman Web Admin — tetapi hanya **satu nominal** (`STR_SP`) per kelompok; realisasi dari **Firebird legacy SUM**, bukan tabel `REALISASI`.
- **Kebutuhan produk:** Admin **input target per field patokan**; layar menampilkan **per baris field** tiga kolom: **Target | Realisasi | Persentase** (contoh 1.000.000 / 800.000 / 80%).
- **Scope plan ini:** `backend-app` + **Web Admin** saja. **Mobile tidak diubah** (tetap endpoint lama sampai fase berikutnya).

## Goal

1. Admin dapat **menyimpan target** untuk **9 field fase 1** per kelompok: `JLH_AGT_BR` + 8 setoran (`STR_SP` … `STR_SKA`).
2. API mengembalikan **per field**: `target`, `realisasi`, `persentase`, `status` (+ label Indonesia).
3. **Realisasi** 8 setoran dibaca dari tabel **`realisasi`** (PROD / `DB_CONNECTION`), selaras trigger.
4. Web Admin: **daftar kelompok** + **halaman detail** berisi tabel field (Target / Realisasi / %).
5. Selaraskan **`TGL_TGT`** monitoring dengan PROD (`2099-01-01`).

## Asumsi / keputusan plan (default)

| # | Keputusan | Alasan |
|---|-----------|--------|
| 1 | **Fase 1 field:** `JLH_AGT_BR` + 8 setoran saja | Sesuai permintaan user (baris 73–83 dokumen konsep); `PCR_PJM`, `BNG_PJM`, `ASR_PKK` fase 2 |
| 2 | **`TGL_TGT`:** `2099-01-01` via env `TARGET_MONITORING_TGL_TGT` | Selaras trigger Firebird PROD (DATE); ganti dari string `__OBORMAS_MONITORING__` |
| 3 | **UI Web Admin:** list kelompok → klik → **detail per kelompok** (tabel field) | Jelas untuk 9 baris × 3 angka; form target di detail/modal |
| 4 | **List admin:** semua baris `KEL_SAH` (seperti sekarang) | Konsisten dengan `listSummariesForAdmin()` |
| 5 | **`JLH_AGT_BR` realisasi fase 1:** `COUNT(anggota)` dengan `ID_KS` = kelompok | Bukan SUM TRS; aturan "anggota baru" bisa diperketat fase 2 |
| 6 | **Backward API:** endpoint lama tetap ada tapi response **diperluas** atau deprecate field tunggal | Hindari break mobile sementara: pertahankan `nominal_target` = `STR_SP` di summary list |

---

## Detailed Specifications

### A. Konstanta field patokan (backend)

**File baru:** `app/Support/TargetRealisasiFieldCatalog.php`

- Class `final` dengan:
  - `public const MONITORING_FIELDS_PHASE1 = ['JLH_AGT_BR', 'STR_SP', 'STR_SW', 'STR_SHR', 'STR_SMD', 'STR_SPD', 'STR_SBJ', 'STR_SRY', 'STR_SKA'];`
  - `public static function labels(): array` — map key → label ID (dari dokumen konsep).
  - `public static function isAllowedTargetKey(string $key): bool`
  - `public static function realisasiSourceColumn(string $key): string` — untuk fase 1 sama dengan key (kecuali dokumentasi `JLH_AGT_BR` khusus).

### B. Config & TGL_TGT

**File:** `config/obormas.php`

- Ubah default `target_monitoring_tgl_tgt` menjadi `'2099-01-01'` (string tanggal ISO).
- Tambah komentar: di Firebird PROD kolom `TGL_TGT` bertipe **DATE**; nilai harus bisa di-parse ke date.

**File:** `app/Services/TargetRealisasiMonitoringService.php`

- Method `monitoringTglTgt(): string` — tetap baca config.
- Method baru `protected function monitoringTglTgtForQuery(): mixed` — return string `Y-m-d` untuk binding; jika DB driver/Firebird perlu, cast di query `where('TGL_TGT', $value)`.

**File contoh `.env` (dokumentasi di plan, tidak wajib commit `.env`):**

```env
TARGET_MONITORING_TGL_TGT=2099-01-01
```

### C. Service layer — baca/tulis target + realisasi per field

**File:** `app/Services/TargetRealisasiMonitoringService.php` — refactor besar.

**Hapus ketergantungan** `FirebirdService` untuk realisasi fase 1 (constructor hanya DB models).

**Method baru / ubah:**

| Method | Perilaku |
|--------|----------|
| `getKelompokDetail(string $idKel): array` | Payload lengkap untuk detail + form |
| `upsertTargetsForKelompok(string $idKel, array $targets): Target` | Validasi keys ⊆ phase1; `updateOrCreate` pada `target` dengan semua kolom numeric |
| `listSummariesForAdmin(): array` | Ringkasan per kelompok: tetap `id_kel`, `nama_kelompok`, `jumlah_anggota`; tambah `has_target`, `overall_persentase` opsional (rata-rata field yang punya target) atau tetap pakai `STR_SP` saja untuk kolom list |
| `getSummaryForKelompok(string $idKel): array` | Delegasi ke `getKelompokDetail` atau subset untuk mobile |

**Baca target:**

```php
Target::query()->where('ID_KS', $id)->where('TGL_TGT', $tgl)->first();
```

**Baca realisasi:**

```php
Realisasi::query()->where('ID_KS', $id)->where('TGL_TGT', $tgl)->first();
```

**Per field loop** (`TargetRealisasiFieldCatalog::MONITORING_FIELDS_PHASE1`):

- `target_value` = nilai kolom di model `Target` (string/decimal → format 2 desimal untuk response).
- `realisasi_value`:
  - Jika key `JLH_AGT_BR` → `(string) memberCountForKelompok($id)` (integer count).
  - Else → nilai kolom di model `Realisasi` atau `'0.00'` jika baris/kolom null.
- `persentase` = `computePercent(target, realisasi)` — return `null` jika target ≤ 0.
- `status` = `on_target` \| `below_target` \| `no_target` (sama logic existing per field).

**Normalisasi input target (PUT):**

- Terima associative array hanya key yang diizinkan.
- Setiap nilai: `numeric min:0`, simpan sebagai string/decimal konsisten (number_format 2 desimal atau integer untuk `JLH_AGT_BR`).
- Kolom target lain di baris yang sama **tidak di-null-kan** jika tidak dikirim (partial update: hanya update key yang ada di payload).

**Deprecate / internal:**

- `setNominalTarget()` → panggil `upsertTargetsForKelompok($id, ['STR_SP' => $nominal])` untuk kompatibilitas.
- `nominalTargetStringForKelompok()` → tetap untuk backward compat summary.

### D. API — Request, Resource, Controller

**File baru:** `app/Http/Requests/UpsertTargetKelompokRequest.php`

```php
'targets' => ['required', 'array'],
'targets.JLH_AGT_BR' => ['nullable', 'numeric', 'min:0'],
'targets.STR_SP' => ['nullable', 'numeric', 'min:0'],
// ... setiap key phase1 nullable numeric min:0
```

Atau validasi dinamis: loop `TargetRealisasiFieldCatalog::MONITORING_FIELDS_PHASE1`.

**File baru:** `app/Http/Resources/TargetRealisasiFieldResource.php`

```php
return [
  'key' => $key,
  'label' => $label,
  'target' => $targetFormatted,      // string|null
  'realisasi' => $realisasiFormatted,
  'persentase' => $pct,               // float|null
  'status' => $status,
];
```

**File baru:** `app/Http/Resources/TargetRealisasiKelompokResource.php`

```php
return [
  'id_kel' => ...,
  'nama_kelompok' => ...,
  'tgl_tgt' => ...,
  'jumlah_anggota' => ...,
  'fields' => TargetRealisasiFieldResource::collection(...),
  // backward compat satu baris:
  'nominal_target' => field STR_SP target,
  'total_realisasi' => field STR_SP realisasi,
  'persentase_pencapaian' => field STR_SP persentase,
  'status_target' => field STR_SP status,
];
```

**File ubah:** `app/Http/Resources/TargetRealisasiSummaryResource.php`

- Tetap untuk **list**; tambah optional `has_target: bool`, `str_sp_persentase` — atau minimal tidak breaking.

**File ubah:** `app/Http/Controllers/Api/Admin/TargetRealisasiController.php`

| Method | Perubahan |
|--------|-----------|
| `index()` | List ringkasan (boleh tetap payload lama + field baru opsional) |
| `show($id_kel)` | Return `TargetRealisasiKelompokResource` (**detail per field**) |
| `update($id_kel)` | Terima `UpsertTargetKelompokRequest` → `upsertTargetsForKelompok` → return detail |

**Route** (`routes/api.php`) — tidak ubah path:

- `GET admin/target-realisasi/kelompok/{id_kel}` → detail per field
- `PUT admin/target-realisasi/kelompok/{id_kel}` → body `{ "targets": { "STR_SP": "1000000", ... } }`

**Mobile endpoint** `GET target-realisasi/me` — **tidak diubah** di fase ini (tetap summary lama); catat di Risks.

### E. Model `Realisasi`

**File:** `app/Models/Realisasi.php` — pastikan `$fillable` mencakup 8 setoran (sudah ada). Tidak wajib ubah jika sudah lengkap.

### F. Tests

**File ubah:** `tests/Feature/TargetRealisasiApiTest.php`

- Hapus mock `FirebirdService` untuk test realisasi per field.
- Seed: `Target`, `Realisasi`, `Anggota`, `KelSah` dengan `TGL_TGT = '2099-01-01'` (sqlite).
- Test `PUT` dengan multiple targets → assert kolom tersimpan.
- Test `GET kelompok/{id}` → assert `fields` length 9, persentase STR_SP benar.
- Test backward: `nominal_target` di response = STR_SP.

**File baru (opsional):** `tests/Unit/TargetRealisasiFieldCatalogTest.php` — labels & allowed keys.

### G. API Documentation

**File:** `API_DOCUMENTATION.md` — section Target & Realisasi:

- Dokumentasikan response `fields[]`.
- Contoh PUT body `targets`.
- Catat `TGL_TGT` monitoring.

---

### H. Web Admin

**File ubah:** `src/services/targetRealisasiAdminService.ts`

```typescript
export interface TargetRealisasiFieldRow {
  key: string;
  label: string;
  target: string | null;
  realisasi: string;
  persentase: number | null;
  status: "on_target" | "below_target" | "no_target";
}

export interface TargetRealisasiKelompokDetail {
  id_kel: string;
  nama_kelompok: string;
  tgl_tgt: string;
  jumlah_anggota: number;
  fields: TargetRealisasiFieldRow[];
  // backward
  nominal_target?: string | null;
  total_realisasi?: string;
  persentase_pencapaian?: number | null;
  status_target?: string;
}

export async function fetchTargetRealisasiDetail(idKel: string): Promise<TargetRealisasiKelompokDetail>
export async function putTargetsForKelompok(idKel: string, targets: Record<string, string | number>): Promise<TargetRealisasiKelompokDetail>
```

- `fetchTargetRealisasiSummary()` — tetap untuk list.
- `putNominalTarget` — deprecate atau wrap ke `putTargetsForKelompok` dengan hanya STR_SP.

**File baru:** `src/app/pages/TargetRealisasiDetailPage.tsx`

- Route param `id_kel` (dari URL).
- Load `fetchTargetRealisasiDetail(id_kel)`.
- Tabel:

| Field (label) | Target | Realisasi | % | Status |
|---------------|--------|-----------|---|--------|

- Tombol **Ubah target** → `DataModal` dengan 9 input numerik (pre-fill dari `fields[].target`).
- Save → `putTargetsForKelompok`.
- Breadcrumb: Beranda → Target & Realisasi → [Nama kelompok].
- Format rupiah untuk field setoran; `JLH_AGT_BR` format angka bulat (bukan Rp).

**File ubah:** `src/app/pages/TargetRealisasiPage.tsx`

- List tetap: kolom Kelompok, Anggota, ringkasan (bisa STR_SP % saja atau "Lihat detail").
- Hapus modal edit nominal tunggal **atau** hanya untuk quick-edit STR_SP.
- Tombol/aksi: **Detail** (navigate) + optional **Tambah** → pilih kelompok → navigate ke detail dengan mode edit.
- Link: `navigate(\`/admin/target-realisasi/${row.id_kel}\`)` (sesuaikan prefix super-admin).

**File ubah:** `src/app/routes.tsx`

```tsx
{ path: "target-realisasi", Component: TargetRealisasiPage },
{ path: "target-realisasi/:id_kel", Component: TargetRealisasiDetailPage },
```

Duplikasi untuk `super-admin` prefix jika ada nested route.

**UX form Tambah (kelompok belum punya target):**

- Dari list: Tambah → dropdown kelompok → redirect ke detail `?edit=1` → form kosong 9 field.

---

## Response API contoh (kontrak)

### `GET /api/admin/target-realisasi/kelompok/016005200005`

```json
{
  "data": {
    "id_kel": "016005200005",
    "nama_kelompok": "Kelompok X",
    "tgl_tgt": "2099-01-01",
    "jumlah_anggota": 12,
    "fields": [
      {
        "key": "JLH_AGT_BR",
        "label": "Jumlah Anggota Baru",
        "target": "10.00",
        "realisasi": "12.00",
        "persentase": 120.0,
        "status": "on_target"
      },
      {
        "key": "STR_SP",
        "label": "Setoran Simpanan Pokok",
        "target": "1000000.00",
        "realisasi": "800000.00",
        "persentase": 80.0,
        "status": "below_target"
      }
    ],
    "nominal_target": "1000000.00",
    "total_realisasi": "800000.00",
    "persentase_pencapaian": 80.0,
    "status_target": "below_target"
  }
}
```

### `PUT /api/admin/target-realisasi/kelompok/016005200005`

```json
{
  "targets": {
    "JLH_AGT_BR": 10,
    "STR_SP": 1000000,
    "STR_SW": 500000,
    "STR_SHR": 0,
    "STR_SMD": 0,
    "STR_SPD": 0,
    "STR_SBJ": 0,
    "STR_SRY": 0,
    "STR_SKA": 0
  }
}
```

---

## Risks / Catatan

| Risiko | Mitigasi |
|--------|----------|
| `TGL_TGT` DATE vs string di sqlite test | Test pakai `'2099-01-01'`; Firebird PROD sama dengan trigger |
| Target lama pakai `__OBORMAS_MONITORING__` | Migrasi data manual sekali di PROD atau script UPDATE target SET TGL_TGT = '2099-01-01' WHERE TGL_TGT = '__OBORMAS_MONITORING__' |
| Realisasi kosong untuk kelompok tanpa `ID_KS` anggota | Tampilkan 0.00; trigger mengisi saat data lengkap |
| `JLH_AGT_BR` definisi realisasi bisnis | Fase 1 = count anggota; dokumentasikan di API doc |
| Mobile masih API lama | Tidak diubah; `/me` tetap summary STR_SP sampai fase mobile |
| Kolom `realisasi` di sqlite test | Seed manual di feature test |

---

## Implementation Checklist

1. Tambah `app/Support/TargetRealisasiFieldCatalog.php` (9 field + labels).
2. Update `config/obormas.php` default `target_monitoring_tgl_tgt` → `2099-01-01`.
3. Refactor `TargetRealisasiMonitoringService`: hapus `FirebirdService`; baca `Target` + `Realisasi`; loop field; `upsertTargetsForKelompok`; `getKelompokDetail`.
4. Buat `UpsertTargetKelompokRequest.php`.
5. Buat `TargetRealisasiFieldResource.php` dan `TargetRealisasiKelompokResource.php`.
6. Update `TargetRealisasiSummaryResource.php` (minimal breaking).
7. Update `TargetRealisasiController@show` dan `@update`; pertahankan `setNominalTarget` compat via service.
8. Update `tests/Feature/TargetRealisasiApiTest.php` (tanpa Firebird mock; assert fields[]).
9. Update `API_DOCUMENTATION.md` section Target & Realisasi.
10. Update `targetRealisasiAdminService.ts` (types + fetchDetail + putTargets).
11. Buat `TargetRealisasiDetailPage.tsx` (tabel field + modal edit 9 input).
12. Update `TargetRealisasiPage.tsx` (navigasi detail; sederhanakan modal).
13. Update `routes.tsx` (admin + super-admin) route `target-realisasi/:id_kel`.
14. Verifikasi manual: bandingkan `REALISASI` di DBeaver vs kolom realisasi di UI untuk 2 kelompok pilot.
15. (Opsional PROD) SQL sekali: selaraskan baris `target` lama ke `TGL_TGT = '2099-01-01'`.

---

## IMPLEMENTATION CHECKLIST (sequential, atomic)

1. Create file `app/Support/TargetRealisasiFieldCatalog.php` with `MONITORING_FIELDS_PHASE1`, `labels()`, `isAllowedTargetKey()`.
2. Edit `config/obormas.php`: set default `target_monitoring_tgl_tgt` to `2099-01-01`; update comment block.
3. Edit `app/Services/TargetRealisasiMonitoringService.php`: remove `FirebirdService` from constructor.
4. Add private method `resolveMonitoringTglTgtValue()` for query binding in service.
5. Add private method `formatDecimal(?string $v): ?string` and `computePercentAndStatus` reuse per field.
6. Add method `getKelompokDetail(string $idKel): array` building `fields` array via catalog loop.
7. Add method `upsertTargetsForKelompok(string $idKel, array $targets): Target` with `updateOrCreate` on composite key.
8. Refactor `listSummariesForAdmin()` to use STR_SP from `getKelompokDetail` or lightweight query.
9. Refactor `setNominalTarget()` to delegate to `upsertTargetsForKelompok` with STR_SP only.
10. Create `app/Http/Requests/UpsertTargetKelompokRequest.php` with dynamic rules for 9 keys.
11. Create `app/Http/Resources/TargetRealisasiFieldResource.php`.
12. Create `app/Http/Resources/TargetRealisasiKelompokResource.php`.
13. Edit `app/Http/Controllers/Api/Admin/TargetRealisasiController.php`: `show` returns KelompokResource; `update` uses new Request.
14. Deprecate `UpsertTargetNominalRequest` usage in controller (keep file or alias validation).
15. Update `tests/Feature/TargetRealisasiApiTest.php`: seed Realisasi row; test PUT multi-field; test GET fields length 9.
16. Run `php artisan test --filter=TargetRealisasiApiTest`.
17. Update `API_DOCUMENTATION.md` Target & Realisasi endpoints and examples.
18. Edit `Web Admin/src/services/targetRealisasiAdminService.ts`: add types and `fetchTargetRealisasiDetail`, `putTargetsForKelompok`.
19. Create `Web Admin/src/app/pages/TargetRealisasiDetailPage.tsx` with field table and edit modal.
20. Edit `Web Admin/src/app/pages/TargetRealisasiPage.tsx`: add Detail navigation; adjust Tambah flow.
21. Edit `Web Admin/src/app/routes.tsx`: register `target-realisasi/:id_kel` for admin and super-admin groups.
22. Manual test Web Admin against local/staging API with kelompok `016005200005`.
23. Document in commit/notes: mobile unchanged; PROD env `TARGET_MONITORING_TGL_TGT=2099-01-01`.
