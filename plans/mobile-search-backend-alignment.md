# Plan: Mobile Search — Selaras dengan Backend

## Context

- Requirement utama: `masalah.md` — search bar harus mencari **seluruh kolom tabel** (nama, nomor, tanggal, bulan, tahun, status, field lain), bukan hanya nama/nomor anggota.
- Backend (`backend-app`) sudah memakai `app/Support/CaseInsensitiveSearch.php` pada modul master utama (Anggota, Kelompok Sahabat, Ketua/Sekretaris KS, LO, AO, Pengelola, Kunjungan, Activity Log, dll.). Lihat `plans/global-search-all-modules.md`.
- Web Admin sebagian besar sudah server-side `search` + debounce.
- **Mobile** (`Kelompok Sahabat Obor Mas`) masih **campuran**: sebagian screen sudah kirim `search` ke API, sebagian masih **fetch besar + filter lokal** (`includes()`), sehingga tidak mendapat manfaat penuh backend (multi-token, status `A`→`AKTIF`, tanggal, dll.).
- Plan ini fokus **penyesuaian UI mobile**; dependensi backend untuk modul yang belum punya query param `search` dicatat di **Fase B**.

## Goal

1. Setiap modul list di mobile yang punya search bar mengirim `search` ke backend (server-side), dengan debounce dan pagination meta dari API.
2. Hapus filter lokal `array.filter(...includes...)` sebagai sumber utama hasil tabel (boleh tetap untuk UI kecil/non-list).
3. Standarkan hook/util agar perilaku search konsisten antar screen (debounce, reset `page` ke 1, error toast).
4. Service layer mobile menambahkan param `search` di endpoint yang backend sudah support.

## Referensi pola (screen yang sudah benar)

**Pola acuan:** `KetuaKSDataScreen.tsx` / `MemberDataScreen.tsx`

- State: `search` / `searchQuery`, `page`, `itemsPerPage`, `lastPage`, `totalItems`.
- `useEffect` load: `getXxxList({ page, per_page, search: search.trim() || undefined })`.
- Saat user mengetik di search bar: `setPage(1)` (wajib).
- Debounce **350ms** pada dependency `search` (disarankan via hook bersama).
- Export PDF/Excel: kirim `search` yang sama seperti list.
- **Jangan** memakai `filteredData = data.filter(...)` untuk tabel utama.

---

## Matriks modul (audit saat ini)

| Menu / Screen | File | Status search | Backend `search` | Aksi plan |
|---------------|------|---------------|------------------|-----------|
| Anggota (admin) | `MemberDataScreen.tsx` | Server + debounce 350ms | Ya (`AnggotaService`) | Verifikasi + standar hook |
| Anggota (user RO) | `MemberKelompokReadOnlyScreen.tsx` | Server, tanpa debounce | Ya | Tambah debounce + reset page |
| Data LO | `LODataScreen.tsx` | **Client** `filteredLOData` + load `per_page: 100` | Ya (`DataLoService`) | **Refactor penuh** |
| Data AO | `AODataScreen.tsx` | **Client** (sama LO) | Ya (`DataAoService`) | **Refactor penuh** |
| Kelompok Sahabat | `KelompokSahabatScreen.tsx` | Server `getKelSahList({ search })` | Ya | Verifikasi reset page + debounce |
| Ketua KS | `KetuaKSDataScreen.tsx` | Server | Ya | Tambah debounce (opsional polish) |
| Sekretaris KS | `SekretarisKSDataScreen.tsx` | Server | Ya | Tambah debounce (opsional polish) |
| Pengelola | `DataPengelolaScreen.tsx` | Hanya filter `NO_AGT` | Ya (`search` di controller) | Ganti ke `search` global + UI search bar |
| Penghasilan | `DataPenghasilanScreen.tsx` | Hanya `NO_AGT` | **Belum** (hanya `NO_AGT`) | **Fase B** backend + mobile |
| Jumlah Keluarga | `JlhKeluargaDataScreen.tsx` | Hanya `NO_AGT` | **Belum** | **Fase B** |
| Kunjungan | `DataKunjunganContent.tsx` | **Client** + `per_page: 10000` | Ya (`DataKunjunganService`) | **Refactor penuh** |
| Data Transaksi | `DataTrsScreen.tsx` | `NO_AGT` saja | Firebird list punya `search` | **Fase B** + ganti param |
| Riwayat Aktivitas | `RiwayatAktivitasContent.tsx` | **Mock + filter lokal** | Ya (`ActivityLogController`) | Wire API + `search` |
| Target & Realisasi (admin) | `TargetRealisasiAdminScreen.tsx` | Client `rowMatchesSearch` | Endpoint summary terpisah | Out of scope Fase A (lihat catatan) |
| Target & Realisasi (member) | `TargetRealisasiMemberScreen.tsx` | N/A (1 baris `/me`) | N/A | Tidak diubah |

---

## Detailed Specifications

### A. Infrastruktur bersama (mobile)

**File baru:** `c:\Users\galih\Documents\ui\Kelompok Sahabat Obor Mas\Kelompok Sahabat Obor Mas\src\hooks\useDebouncedValue.ts`

- Export function `useDebouncedValue<T>(value: T, delayMs?: number): T` — default `350`.
- Implementasi: `useState` + `useEffect` + `clearTimeout` (sama konsep Web Admin `useDebouncedSearchQuery`).

**File baru (opsional tipis):** `src\hooks\useServerListSearch.ts`

- Input: `{ debouncedSearch, page, setPage, itemsPerPage, fetcher }`.
- Output: `{ items, loading, error, lastPage, totalItems, reload }`.
- Boleh di-skip jika refactor per screen cukup copy pola Ketua KS (kurangi abstraksi berlebihan).

**Konvensi param API:**

- Query: `search` (bukan `q`), trim, kirim hanya jika `length > 0`.
- Pagination: `page`, `per_page` dari state UI.
- Export: param `search` sama dengan list.

---

### B. Fase A — Modul dengan backend `search` siap

#### B1. `LODataScreen.tsx`

**Hapus:**

- `filteredLOData`, `paginatedLOData` slice lokal.
- `getLoList({ per_page: 100 })` sekali di mount sebagai satu-satunya sumber data.

**Tambah/ubah:**

- State `lastPage`, `totalItems` dari `res.meta`.
- `useDebouncedValue(searchQuery)` → dependency load.
- `useEffect` load:

```ts
getLoList({
  page: currentPage,
  per_page: itemsPerPage,
  search: debouncedSearch.trim() || undefined,
})
```

- `onChange` search: `setSearchQuery(v); setCurrentPage(1);`
- `refresh` setelah CRUD: panggil load dengan `page` aktif + `search` aktif.
- Export PDF/Excel: sudah ada `search: searchQuery` — pastikan pakai **debounced** atau nilai saat export (konsisten dengan list).
- Error: `toast.error` atau `setError` jika API gagal (hindari tabel kosong diam-diam).

**Kolom yang dicari (via backend, bukan UI):** `ID_LO`, `NO_AGT`, `ID_TP`, `NAMA`, `STAT`, `TGL_STAT`.

---

#### B2. `AODataScreen.tsx`

Perubahan **identik** dengan B1, ganti:

- `getLoList` → `getAoList`
- State/types `Lo` → `Ao`

**Kolom backend:** `ID_AO`, `NO_AGT`, `NAMA`, `STAT`, `TGL_STAT`.

---

#### B3. `DataKunjunganContent.tsx`

**Hapus:**

- Fetch `per_page: 10000` (atau angka besar serupa).
- `filteredKunjungan` / filter lokal terbatas (`idLO`, `noAnggota`, `idKelompok`, `kegiatan`).

**Ubah:**

- `getKunjunganList({ page, per_page, search: debouncedSearch })`.
- Pagination dari `meta.last_page`, `meta.total`.
- Search bar: placeholder menjelukan pencarian lintas kolom (LO, anggota, kelompok, tanggal, kegiatan, PIC, jumlah peserta).
- Filter tanggal/status terpisah (jika ada di UI): tetap dikirim sebagai query param backend yang sudah ada di `DataKunjunganController` (jangan digabung ke filter lokal string).

**Service:** `dataKunjunganService.ts` — param `search` sudah ada; pastikan dipakai di screen.

---

#### B4. `RiwayatAktivitasContent.tsx`

**Hapus:**

- Data mock / hardcoded array (jika masih dipakai sebagai sumber utama).
- `filteredActivities` berbasis string lokal saja.

**Ubah:**

- `activityLogService.getActivityLogs({ page, per_page, search: debouncedSearch, status?, date_from?, date_to? })`.
- Map `ActivityLogDto` → shape UI yang sudah dipakai card/list.
- Pagination server-side.
- Filter chip status/tipe: kirim ke backend (`status`, `action_type`, dll.) bukan filter string di client setelah fetch semua.

**Service:** `activityLogService.ts`

- Tambah param opsional `search?: string`, `status?: string`, `action_type?: string`, `date_from?: string`, `date_to?: string` pada `getActivityLogs`.
- Set di `URLSearchParams`.

**Kolom backend:** `description`, `user_name`, `resource_type`, `action_type`, `status`, `created_at`.

---

#### B5. `DataPengelolaScreen.tsx`

**Ubah:**

- Ganti state `noAgtFilter` → `searchQuery` (atau tetap satu field search universal).
- `getDataPengelolaList({ page, per_page, search })` — **bukan** hanya `NO_AGT`.
- Placeholder: "Cari ID pengelola, nomor anggota, nama, no SK, status…"
- Debounce + reset page.
- Export: `search` param (service export sudah support di beberapa modul — verifikasi `exportDataPengelola*`).

**Opsional:** pertahankan filter eksak `NO_AGT` hanya jika ada kebutuhan bisnis terpisah; default satu search bar global.

---

#### B6. Modul sudah server-side — polish

| File | Perubahan |
|------|-----------|
| `KelompokSahabatScreen.tsx` | Pastikan `setCurrentPage(1)` saat `searchQuery` berubah; debounce 350ms pada load list utama |
| `KetuaKSDataScreen.tsx` | Debounce pada `search` di `useEffect` load |
| `SekretarisKSDataScreen.tsx` | Sama |
| `MemberDataScreen.tsx` | Sudah debounce — pastikan `setCurrentPage(1)` on search change |
| `MemberKelompokReadOnlyScreen.tsx` | Tambah debounce; reset page on search |

Autocomplete form (Ketua/Sekretaris/LO/AO di modal Kelompok Sahabat): **tidak diubah** — tetap `per_page: 20` + `search` keyword terpisah.

---

### C. Fase B — Backend belum expose `search` (prasyarat sebelum mobile full search)

Modul berikut masih `NO_AGT` exact/partial di controller, **belum** `CaseInsensitiveSearch` lintas kolom:

| Backend | File controller | Perlu |
|---------|-----------------|-------|
| Data Penghasilan | `DataPenghasilanController.php` | Terima `search`, terapkan di `DataPenghasilanService::paginate` pada `NO_AGT`, `PENGHASILAN`, `PENGELUARAN`, `TGL_DATA` |
| Jumlah Keluarga | `DataJlhKeluargaController.php` | Sama pola |
| Data Transaksi | endpoint list Firebird / `DataTrsController` | `search` via `FirebirdService` + kolom transaksi yang ditampilkan |

**Setelah Fase B backend deploy:**

| Mobile screen | Service | Perubahan |
|---------------|---------|-----------|
| `DataPenghasilanScreen.tsx` | `dataPenghasilanService.ts` | Tambah `search?` di `getDataPenghasilanList`; ganti `NO_AGT` filter UI → `search` |
| `JlhKeluargaDataScreen.tsx` | `dataJlhKeluargaService.ts` | Sama |
| `DataTrsScreen.tsx` | `dataTrsService.ts` | `getDataTrsList({ search })`; placeholder umum |

---

### D. Out of scope / catatan khusus

- **`TargetRealisasiAdminScreen.tsx`:** List agregat target/realisasi; client filter `rowMatchesSearch` masih masuk akal sampai backend menambah `search` pada endpoint monitoring. Tidak masuk Fase A mobile master CRUD.
- **`kelompokAnggotaSearch.ts`:** Autocomplete anggota (min 3 karakter) — tetap terpisah dari search bar list.
- **Deploy:** Mobile hanya bergantung pada backend ter-deploy; tanpa deploy backend, LO/AO/Kunjungan tetap akan error/empty jika query tidak cocok.

---

## Implementation Checklist

### Fase A — Infrastruktur

1. Buat `src/hooks/useDebouncedValue.ts` di project mobile (delay default 350ms).

### Fase A — Screen prioritas tinggi

2. Refactor `LODataScreen.tsx`: server-side `getLoList` + pagination meta; hapus `filteredLOData` / slice lokal.
3. Refactor `AODataScreen.tsx`: sama seperti item 2 dengan `getAoList`.
4. Refactor `DataKunjunganContent.tsx`: server-side `getKunjunganList` + hapus fetch massal & filter lokal.
5. Update `activityLogService.ts`: tambah query params `search` (+ filter opsional).
6. Refactor `RiwayatAktivitasContent.tsx`: data dari API + server search + pagination.
7. Refactor `DataPengelolaScreen.tsx`: list pakai param `search` backend, bukan hanya `NO_AGT`.

### Fase A — Polish modul yang sudah server-side

8. `KelompokSahabatScreen.tsx`: debounce list + reset `currentPage` saat search berubah.
9. `KetuaKSDataScreen.tsx`: debounce pada load list.
10. `SekretarisKSDataScreen.tsx`: debounce pada load list.
11. `MemberKelompokReadOnlyScreen.tsx`: debounce + reset page.
12. Verifikasi `MemberDataScreen.tsx`: reset page on search (jika belum).

### Fase B — Backend (dependency, bisa PR terpisah di `backend-app`)

13. Tambah `search` + `CaseInsensitiveSearch` di `DataPenghasilanService` + controller `index`/export.
14. Tambah `search` di `DataJlhKeluargaService` + controller.
15. Tambah/verifikasi `search` di list Data Transaksi (Firebird).

### Fase B — Mobile (setelah 13–15 deploy)

16. Update `dataPenghasilanService.ts` + `DataPenghasilanScreen.tsx` ke `search` global.
17. Update `dataJlhKeluargaService.ts` + `JlhKeluargaDataScreen.tsx`.
18. Update `dataTrsService.ts` + `DataTrsScreen.tsx`.

### Verifikasi

19. Uji manual per modul Fase A: `rosalia sapi`, `fidelis vin`, `aktif`, `blokir`, `2026`, `non aktif`, ID (`KS001`), tanggal status.
20. Uji error API: pastikan toast/pesan error, bukan tabel kosong tanpa feedback.
21. Uji export dengan keyword search aktif — hasil export sesuai filter backend.

---

## Risks / Catatan

- Menghapus fetch `per_page: 100` / `10000` mengubah UX: user harus mengetik untuk menemukan data di halaman jauh — ini **diinginkan** agar konsisten dengan backend dan performa.
- Tanpa debounce, tiap keystroke memicu request Firebird — risiko lambat/error `-303`; wajib debounce 350ms.
- **Fase B** harus diselesaikan agar Penghasilan / Jumlah Keluarga / Transaksi memenuhi `masalah.md` sepenuhnya.
- `RiwayatAktivitasContent` saat ini mungkin masih mock: migrasi ke API bisa mengubah tampilan data (pastikan mapping field benar).

---

## IMPLEMENTATION CHECKLIST (sequential, atomik)

1. Create file `Kelompok Sahabat Obor Mas/src/hooks/useDebouncedValue.ts` with `useDebouncedValue(value, 350)`.
2. In `LODataScreen.tsx`, remove `filteredLOData` and client-side `slice` pagination.
3. In `LODataScreen.tsx`, add `lastPage`/`totalItems` from API meta.
4. In `LODataScreen.tsx`, wire `useDebouncedValue(searchQuery)` into load `useEffect`.
5. In `LODataScreen.tsx`, change `getLoList` calls to `{ page: currentPage, per_page: itemsPerPage, search }`.
6. In `LODataScreen.tsx`, on search input change call `setCurrentPage(1)`.
7. In `LODataScreen.tsx`, update refresh-after-CRUD to use current page + debounced search.
8. In `LODataScreen.tsx`, ensure export uses same search as list.
9. Repeat items 2–8 for `AODataScreen.tsx` using `getAoList`.
10. In `DataKunjunganContent.tsx`, remove large `per_page` fetch and local string filter.
11. In `DataKunjunganContent.tsx`, implement server pagination with `getKunjunganList({ page, per_page, search })`.
12. In `DataKunjunganContent.tsx`, add debounced search and reset page on keyword change.
13. In `activityLogService.ts`, add `search` (and optional filters) to `getActivityLogs` URLSearchParams.
14. In `RiwayatAktivitasContent.tsx`, replace mock/local-only filter with `getActivityLogs` + pagination.
15. In `RiwayatAktivitasContent.tsx`, add debounced search bar wired to API `search`.
16. In `DataPengelolaScreen.tsx`, replace list filter `NO_AGT` with universal `search` query param.
17. In `DataPengelolaScreen.tsx`, add visible search input if missing; debounce + reset page.
18. In `KelompokSahabatScreen.tsx`, debounce main `getKelSahList` load; reset page on search change.
19. In `KetuaKSDataScreen.tsx`, debounce main list `useEffect` on `search`.
20. In `SekretarisKSDataScreen.tsx`, debounce main list `useEffect` on `search`.
21. In `MemberKelompokReadOnlyScreen.tsx`, add debounce and reset page on search change.
22. Verify `MemberDataScreen.tsx` resets `currentPage` when `searchQuery` changes.
23. Manual test LO module (multi-word, status, date).
24. Manual test AO module.
25. Manual test Kunjungan module.
26. Manual test Riwayat Aktivitas module.
27. Manual test Pengelola + Kelompok Sahabat + Ketua/Sekretaris + Anggota screens.
28. (Fase B backend) Add `search` to Data Penghasilan list service + controller.
29. (Fase B backend) Add `search` to Jumlah Keluarga list service + controller.
30. (Fase B backend) Add `search` to Data Transaksi list endpoint.
31. (Fase B mobile) Update `dataPenghasilanService.ts` and `DataPenghasilanScreen.tsx` for global `search`.
32. (Fase B mobile) Update `dataJlhKeluargaService.ts` and `JlhKeluargaDataScreen.tsx`.
33. (Fase B mobile) Update `dataTrsService.ts` and `DataTrsScreen.tsx`.
34. Final regression: deploy backend + mobile build; test production API base URL.
