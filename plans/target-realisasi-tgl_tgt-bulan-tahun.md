# Plan: TGL_TGT — Bulan & Tahun + List Per Periode (Semua Kelompok)

## Context

- **Sumber requirement:** `update.md` poin 1 + penyelarasan dengan trigger Firebird **`SP_SYNC_REALISASI_KS_BULAN`** (realisasi per bulan, `TGL_TGT` = MAX `TGL_LAP` bulan itu; bukan `2099-01-01`).
- **Kondisi saat ini:**
  - Admin (Web + mobile) memakai `<input type="date">` bebas untuk **Tanggal / periode target**.
  - Backend `normalizeMonitoringTgl()` default config `2099-01-01`.
  - **`listSummariesForAdmin()`** → **satu baris per `ID_KS`** (`collectMonitoringKelompokIds()`), sehingga kelompok dengan 2+ baris `TARGET` / `REALISASI` (mis. Sikka) hanya tampil **sekali**; label tanggal menampilkan **2099** walau DB sudah punya `2026-01-31`, `2026-02-28`, dll.
  - Pairing target–realisasi: equality `TGL_TGT` persis atau fallback ke baris terbaru — **tidak** per kalender **YYYY-MM**.
- **Gap (target input):** `TGL_TGT` target = **akhir bulan**; form hanya bulan + tahun.
- **Gap (list & read):** **Semua kelompok** yang punya lebih dari satu periode di `TARGET` dan/atau `REALISASI` harus tampil **satu baris per periode bulanan**, bukan satu baris per kelompok.

## Goal

1. Form admin hanya menampilkan pemilihan **Bulan** + **Tahun** (bukan date picker bebas).
2. Sistem **otomatis** menghitung dan menyimpan `tgl_tgt` = **hari terakhir** bulan yang dipilih (`Y-m-d`).
3. Backend **menolak** `tgl_tgt` yang bukan akhir bulan (keamanan kontrak API).
4. Tampilan list/detail/user menampilkan periode sebagai **bulan-tahun** yang mudah dibaca (`period_label`, dll.).
5. **List ringkasan admin (Web + mobile):** untuk **setiap kelompok**, tampilkan **N baris** = jumlah periode kalender unik `(YYYY-MM)` dari **gabungan** baris `TARGET` + `REALISASI` (berlaku **semua** `ID_KS`, tidak khusus satu kelompok).
6. **Baca target & realisasi** per baris list memakai **tahun + bulan** yang sama (`EXTRACT` / helper), bukan default `2099-01-01` dan bukan hanya `ORDER BY TGL_TGT DESC` satu baris.
7. **Tidak** mengubah scope poin 2 & 3 `update.md` (filter kelompok, field optional) dalam plan ini.

## Kebijakan teknis (keputusan plan)

| Topik | Keputusan |
|-------|-----------|
| Kontrak API | Tetap `tgl_tgt` string `Y-m-d` per baris (untuk **target**: akhir bulan; untuk **tampilan list**: `tgl_tgt` = `TargetPeriod::lastDayOfMonth(year, month)` periode baris itu). |
| Default form baru | **Bulan & tahun berjalan** → `tgl_tgt` = akhir bulan ini (bukan `2099-01-01`). |
| Buka form ubah | Prefill bulan/tahun dari `row.period_year` / `row.period_month` atau parse `row.tgl_tgt`. |
| **Kunci baris list** | `(id_kel, period_year, period_month)` — unik di seluruh response summary. |
| **Sumber periode list** | `UNION` DISTINCT `(ID_KS, tahun, bulan)` dari tabel **`target`** dan **`realisasi`**, untuk **semua kelompok**. Abaikan sentinel `TGL_TGT = 2099-01-01` saat membentuk kunci periode. |
| **Satu kelompok, 2 periode di DB** | **2 baris** di UI (contoh Sikka: Jan 2026 + Feb 2026). Jika target punya `2025-01` dan realisasi hanya `2026-02`, bisa **2–3 baris** sesuai union periode — masing-masing baris load target/realisasi untuk bulan itu saja. |
| GET `/summary` | **Diubah:** tidak lagi loop `collectMonitoringKelompokIds()` sekali per ID; gunakan `collectMonitoringPeriodRows()` → satu `getKelompokDetail($id, $periodEndYmd, …)` per periode. |
| GET/PUT `/kelompok/{id}?tgl_tgt=` | `tgl_tgt` query = **akhir bulan** periode yang diminta; resolve target & realisasi by **YYYY-MM** (bukan equality ke tanggal operasional realisasi). |
| Pairing realisasi | `WHERE ID_KS = ? AND EXTRACT(YEAR FROM TGL_TGT) = ? AND EXTRACT(MONTH FROM TGL_TGT) = ?` (selaras trigger: `TGL_TGT` realisasi bisa `2026-02-28`, target bisa `2026-02-28` akhir bulan — bulan sama). |
| Default `2099` | **Tidak dipakai** di path list/summary dan detail dengan periode eksplisit. `monitoringTglTgt()` hanya fallback terakhir jika caller legacy tanpa periode (deprecate untuk admin list). |
| Role `user` | List member ikut **multi-periode** per kelompok; label bulan-tahun. |
| Migrasi SQL `2099` di Firebird | Opsional manual (sudah backfill procedure); plan app tidak wajib DELETE `2099`. |

---

## Detailed Specifications

### A. Backend (`c:\laragon\www\backend-app`)

#### A1. Helper periode (file baru)

**File:** `app/Support/TargetPeriod.php`

**Class / methods (exact):**
- `public static function lastDayOfMonth(int $year, int $month): string` — return `Y-m-d` (UTC/date logic aman leap year).
- `public static function isEndOfMonth(string $ymd): bool`
- `public static function fromYearMonth(int $year, int $month): string` — alias ke `lastDayOfMonth`
- `public static function parseToYearMonth(?string $ymd): ?array` — return `['year' => int, 'month' => int]` atau `null`
- `public static function currentPeriodEnd(): string` — akhir bulan kalender berjalan (timezone app `config/app.php` timezone)

#### A2. Validasi request

**File:** `app/Http/Requests/UpsertTargetKelompokRequest.php`

- Tambah rule custom / `after` validator: jika `tgl_tgt` diisi, harus `TargetPeriod::isEndOfMonth($value)`.
- Pesan error Indonesia: mis. *"Tanggal target harus akhir bulan (periode bulanan)."*

#### A3. Service monitoring

**File:** `app/Services/TargetRealisasiMonitoringService.php`

**Konstanta sentinel (private):** `LEGACY_MONITORING_DATE = '2099-01-01'` — diabaikan saat enumerasi periode.

**Method baru (exact):**

- `private function isLegacyMonitoringDate(string $ymd): bool`
- `private function periodKeyFromYmd(string $ymd): ?array` — `['year' => int, 'month' => int]` atau null
- `public function collectMonitoringPeriodRows(): \Illuminate\Support\Collection`
  - Return collection of `['id_kel' => string, 'year' => int, 'month' => int, 'tgl_tgt' => string]` ( `tgl_tgt` = `TargetPeriod::lastDayOfMonth(year, month)` ).
  - Query logic:
    1. Dari `Target`: `SELECT DISTINCT ID_KS, EXTRACT(YEAR FROM TGL_TGT), EXTRACT(MONTH FROM TGL_TGT)` — skip baris `TGL_TGT` legacy 2099.
    2. Dari `Realisasi`: sama.
    3. Merge ke map keyed `normalizeKelompokIdForMatch(ID_KS)|year|month` — **union**, bukan irisan periode.
    4. Sort: `id_kel` ASC, `year` DESC, `month` DESC.
  - **Semua kelompok** yang punya minimal satu periode di salah satu tabel ikut terdaftar (tidak dibatasi contoh Sikka).

- `private function resolveTargetRowForPeriod(string $idKel, int $year, int $month, bool $allowFallback): array` — ganti / tambah selain `resolveTargetRowForMonitoring`:
  - Primary: `Target` where `ID_KS` + EXTRACT year/month `TGL_TGT` = period.
  - Fallback (hanya jika `$allowFallback` dan primary kosong): baris target terbaru **dalam bulan yang sama** tidak ada → optional baris terbaru same ID (dokumentasikan di response `tgl_baris_target`).

- `private function resolveRealisasiRowForPeriod(string $idKel, int $year, int $month): ?Realisasi`:
  - `Realisasi::where ID_KS` + EXTRACT year/month = period, `orderByDesc('TGL_TGT')`, `first()` (jika trigger benar hanya satu baris per bulan).
  - **Jangan** query `TGL_TGT = 2099-01-01` di path ini.

**Ubah method existing:**

- `listSummariesForAdmin()`:
  - Ganti loop `collectMonitoringKelompokIds()` → `foreach ($this->collectMonitoringPeriodRows() as $periodRow)`.
  - Panggil `getKelompokDetail($periodRow['id_kel'], $periodRow['tgl_tgt'], true, true)`.
  - Setiap item summary = satu periode (kelompok yang sama bisa muncul berkali-kali).

- `getKelompokDetail(string $idKel, ?string $tglTgt, …)`:
  - Setelah `normalizeMonitoringTgl` / validasi akhir bulan: derive `$year`, `$month` dari `TargetPeriod::parseToYearMonth($tgl)`.
  - Panggil `resolveTargetRowForPeriod` + `resolveRealisasiRowForPeriod` (bukan `resolveRealisasiRowForMonitoring` dengan 2099).
  - Return tambahan: `period_year`, `period_month`, `tgl_tgt` = **akhir bulan periode baris** (bukan 2099).
  - Field `tgl_baris_target`: isi hanya jika target diambil dari baris dengan `TGL_TGT` berbeda dari akhir bulan canonical tetapi masih **bulan yang sama** (edge); jika beda bulan, jangan fallback silang-bulan di path detail eksplisit.

- `normalizeMonitoringTgl(?string $tglTgt): string`:
  - Jika null/empty pada **PUT** path → `TargetPeriod::currentPeriodEnd()` (bukan 2099).
  - Jika eksplisit bukan akhir bulan → **ValidationException** (GET show → 422 via controller).

- `collectMonitoringKelompokIds()`: tetap ada untuk diagnostics / export legacy; dokumentasikan deprecated untuk list UI.

- Tambah `public function assertEndOfMonthOrFail(string $ymd): string` untuk PUT.

**Member / user list:** jika ada method list untuk role user, samakan pola **per periode** (file controller terkait — cek `TargetRealisasiController` atau member endpoint).

**File:** `app/Http/Controllers/Api/Admin/TargetRealisasiController.php`

- `show()`: jika query `tgl_tgt` ada dan bukan akhir bulan → response 422 JSON message.
- `update()`: mengandalkan Form Request (sudah validasi).

#### A4. Resource (opsional tapi direkomendasikan)

**Files:**
- `app/Http/Resources/TargetRealisasiKelompokResource.php`
- `app/Http/Resources/TargetRealisasiSummaryResource.php`

- Tambah `period_year`, `period_month` di `toArray()` dari `tgl_tgt` via `TargetPeriod::parseToYearMonth`.

#### A5. Tests

**File:** `tests/Feature/TargetRealisasiApiTest.php`

- Test PUT dengan `tgl_tgt=2026-03-31` → success.
- Test PUT dengan `tgl_tgt=2026-03-15` → 422.
- Test PUT Februari kabisat `2024-02-29` → success.
- Test GET detail `?tgl_tgt=2026-03-15` → 422.
- Test helper edge: Feb non-leap → `2023-02-28`.

#### A6. Dokumentasi

**File:** `API_DOCUMENTATION.md` (bagian Target & Realisasi)

- Dokumentasikan: `tgl_tgt` harus **tanggal akhir bulan**.
- UI admin: pilih bulan+tahun; nilai yang dikirim contoh `2026-03-31`.

**File:** `config/obormas.php`

- Perbarui komentar `target_monitoring_tgl_tgt`: masih dipakai untuk **read default** / legacy; input admin baru memakai akhir bulan aktual.

---

### B. Web Admin (`c:\Users\galih\Documents\ui\Web Admin`)

#### B1. Util periode

**File baru:** `src/lib/targetPeriod.ts`

**Exports (exact names):**
- `lastDayOfMonth(year: number, month: number): string`
- `parseYearMonthFromTglTgt(tgl: string | null | undefined): { year: number; month: number } | null`
- `currentYearMonth(): { year: number; month: number }`
- `formatPeriodLabel(year: number, month: number): string` — locale `id-ID`, mis. "Maret 2026"
- `formatPeriodLabelFromTglTgt(tgl: string | null | undefined): string`

#### B2. Komponen UI reusable

**File baru:** `src/app/components/TargetPeriodSelect.tsx`

**Props:**
- `year: number`
- `month: number` (1–12)
- `onChange: (year: number, month: number) => void`
- `disabled?: boolean`
- `label?: string` default `"Periode target"`

**UI:** dua `<select>` (Bulan nama Indonesia, Tahun rentang mis. tahun berjalan ± 5), **bukan** `<input type="date">`.

#### B3. Halaman daftar + modal

**File:** `src/app/pages/TargetRealisasiPage.tsx`

- Ganti state `tglTgtInput` (string) dengan:
  - `periodYear: number`
  - `periodMonth: number`
- Derived: `const tglTgtInput = useMemo(() => lastDayOfMonth(periodYear, periodMonth), [...])`
- Ganti blok `<input type="date">` dengan `<TargetPeriodSelect />`.
- `openModalNew`: set period ke `currentYearMonth()`.
- `openModalForRow`: pakai `row.period_year` / `row.period_month` jika ada, else parse `row.tgl_tgt`.
- `handleTglTgtChange` → rename `handlePeriodChange(year, month)` → panggil `loadTargetsForKelompok(id, lastDayOfMonth(...))`.
- Kolom tabel **Tanggal / periode target**: `formatPeriodLabel(row.period_year, row.period_month)` atau `formatPeriodLabelFromTglTgt(row.tgl_tgt)` — **hapus** tampilan default "Januari 2099".
- **React key baris tabel:** `` `${row.id_kel}-${row.period_year}-${row.period_month}` `` (bukan hanya `id_kel`) — agar Sikka dua periode = dua baris stabil.
- **Expand panel setoran:** key expanded state sama `(id_kel, year, month)`.
- Subtitle "Batas target: …" hanya jika `tgl_baris_target` beda dari akhir bulan canonical (opsional).

#### B4. Halaman detail

**File:** `src/app/pages/TargetRealisasiDetailPage.tsx`

- Header periode: `formatPeriodLabelFromTglTgt(detail?.tgl_tgt)`.
- Modal **Ubah target**: tambah `<TargetPeriodSelect />`; state period year/month; on save `putTargetsForKelompok(..., lastDayOfMonth(...))` bukan hanya `detail.tgl_tgt` statis.
- URL `?tgl_tgt=` tetap didukung (deep link); load tetap pakai query penuh.

#### B5. Service types (opsional)

**File:** `src/services/targetRealisasiAdminService.ts`

- Tambah `period_year?`, `period_month?` pada interface detail/summary.
- `DEFAULT_TARGET_MONITORING_TGL_TGT` — tetap ada untuk kompatibilitas read; komentar bahwa form default memakai `currentYearMonth()`.

---

### C. Aplikasi mobile (`Kelompok Sahabat Obor Mas`)

#### C1. Util periode

**File baru:** `src/app/lib/target/targetPeriod.ts`  
(Mirror logic `Web Admin/src/lib/targetPeriod.ts` — hindari dependency silang antar repo.)

#### C2. Komponen

**File baru:** `src/app/components/TargetPeriodSelect.tsx`  
Props sama dengan Web; styling selaras form app (rounded-xl, label text-xs).

#### C3. Screen admin

**File:** `src/app/components/TargetRealisasiAdminScreen.tsx`

- Ganti `tglTgtInput` + `<input type="date">` dengan `periodYear` / `periodMonth` + `TargetPeriodSelect`.
- Default modal baru: akhir bulan berjalan.
- `useEffect` load detail: dependency `lastDayOfMonth(periodYear, periodMonth)`.
- Tabel kolom periode: `formatPeriodLabelFromTglTgt`.
- **List:** terima banyak baris per `id_kel` (sama seperti Web); `FlatList` `keyExtractor` = `id_kel + period_year + period_month`.

**File:** `src/app/components/TargetRealisasiAdminDetailScreen.tsx`

- Modal ubah target: `TargetPeriodSelect` menggantikan `type="date"`.
- State & save sama pola.

#### C4. Screen user (read-only)

**File:** `src/app/components/TargetRealisasiMemberScreen.tsx`

- Ganti / tambah tampilan `formatPeriodLabelFromTglTgt(row.tgl_tgt)` untuk baris "Periode:" (bukan hanya tanggal panjang).

**File:** `src/app/lib/target/targetRealisasiFormat.ts`

- Re-export atau panggil `formatPeriodLabelFromTglTgt` dari `targetPeriod.ts` (hindari duplikasi).

#### C5. Service

**File:** `src/services/targetRealisasiService.ts`  
- Tambah field opsional `period_year`, `period_month` pada DTO jika backend mengirim.

---

## Implementation Checklist

1. Backend: buat `app/Support/TargetPeriod.php` dengan method `lastDayOfMonth`, `isEndOfMonth`, `parseToYearMonth`, `currentPeriodEnd`.
2. Backend: update `UpsertTargetKelompokRequest` — validasi `tgl_tgt` wajib akhir bulan jika diisi.
3. Backend: update `TargetRealisasiController::show` — 422 jika query `tgl_tgt` bukan akhir bulan.
4. Backend: `TargetRealisasiMonitoringService::collectMonitoringPeriodRows()` — union periode semua kelompok; skip `2099-01-01`.
5. Backend: `resolveTargetRowForPeriod` + `resolveRealisasiRowForPeriod` (match EXTRACT year/month).
6. Backend: refactor `listSummariesForAdmin()` — loop per `(id_kel, year, month)`, bukan per `id_kel` saja.
7. Backend: refactor `getKelompokDetail()` — pakai resolver periode; response `period_year`, `period_month`; default null → `currentPeriodEnd()` bukan 2099.
8. Backend: update `TargetRealisasiKelompokResource` dan `TargetRealisasiSummaryResource` (`period_year`, `period_month`, `period_label` opsional).
9. Backend: endpoint member/summary (jika ada) ikut multi-periode.
10. Backend: tambah test `TargetRealisasiApiTest` — kelompok dengan 2 baris TARGET + 2 REALISASI bulan berbeda → summary count = 2; realisasi Feb tidak tertukar Jan.
11. Backend: test akhir bulan, reject tengah bulan, Feb kabisat.
12. Backend: update `API_DOCUMENTATION.md` — summary multi-baris per periode; pairing YYYY-MM.
13. Backend: update komentar `config/obormas.php` — `target_monitoring_tgl_tgt` legacy, bukan default list.
14. Web Admin: buat `src/lib/targetPeriod.ts`.
15. Web Admin: buat `src/app/components/TargetPeriodSelect.tsx`.
16. Web Admin: refactor `TargetRealisasiPage.tsx` — period state, row key per periode, label tanpa 2099.
17. Web Admin: refactor `TargetRealisasiDetailPage.tsx` — label periode + period select di modal ubah.
18. Web Admin: update `targetRealisasiAdminService.ts` types (`period_year`, `period_month`).
19. Mobile: buat `src/app/lib/target/targetPeriod.ts`.
20. Mobile: buat `src/app/components/TargetPeriodSelect.tsx`.
21. Mobile: refactor `TargetRealisasiAdminScreen.tsx` — multi-row list + key per periode.
22. Mobile: refactor `TargetRealisasiAdminDetailScreen.tsx`.
23. Mobile: update `TargetRealisasiMemberScreen.tsx` — multi-periode + label bulan tahun.
24. Mobile: selaraskan `targetRealisasiService.ts` DTO.
25. Verifikasi manual DB: kelompok dengan 2+ periode di TARGET/REALISASI → Web tampil 2+ baris (uji minimal 2 kelompok berbeda, bukan hanya Sikka).
26. Verifikasi manual: baris Feb menampilkan realisasi Feb (bukan hanya fallback baris terbaru secara global).
27. Verifikasi manual: form simpan Maret 2026 → `tgl_tgt=2026-03-31`.
28. Verifikasi manual: PUT tanggal tengah bulan → 422.

---

## Risks / Catatan

- **Realisasi Firebird:** asumsikan trigger **`SP_SYNC_REALISASI_KS_BULAN`** aktif; `TGL_TGT` realisasi = MAX `TGL_LAP` per bulan (bisa beda hari dengan target akhir bulan, tetap satu bulan).
- **Union periode:** kelompok bisa tampil periode hanya-target atau hanya-realisasi (mis. target `2025-01` tanpa realisasi bulan itu) — realisasi kolom = 0; acceptable.
- **Volume list:** jumlah baris = Σ periode per kelompok; bisa lebih besar dari jumlah kelompok — pertimbangkan pagination di fase berikutnya (out of scope kecuali sudah ada pola di project).
- **Timezone:** hitung akhir bulan pakai timezone aplikasi konsisten (backend PHP + frontend derive yang sama) untuk hindari off-by-one.
- **Rentang tahun dropdown:** tentukan min/max (mis. 2020–2035) agar UX stabil.
- **Export PDF/Excel:** pastikan ikut struktur multi-baris (satu blok per periode per kelompok).

## Out of scope (plan ini)

- `update.md` poin 2: filter/multi-select kelompok di tabel.
- `update.md` poin 3: field target tidak semua required.
- Skrip SQL DELETE massal baris `2099` di Firebird (opsional manual DBA).
