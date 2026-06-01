# Plan: Modul Data Kunjungan (laporan admin) di Web Admin

## Kesimpulan (satu kalimat)

**Satu modul Web Admin** untuk laporan data kunjungan admin dengan **alur dan API sama** seperti `AdminDataKunjunganReport` di aplikasi Kelompok Sahabat Obor Mas, dan **tampilan serta pola halaman** mengikuti modul Web Admin yang sudah ada (Beranda, breadcrumb, tabel, warna brand).

---

## Context

- Backend Laravel sudah menyediakan endpoint laporan admin (`group-summary`, `anggota-summary`), list `GET /api/data-kunjungan` (filter `search`, `ID_KEL_SAH`, `NO_AGT`, dll.), serta export `GET /api/data-kunjungan/export/excel` dan `/export/pdf`. Mutasi `POST`/`PUT`/`DELETE` untuk admin mengembalikan **403** (sama dengan kebijakan mobile).
- Aplikasi mobile memuat implementasi referensi perilaku di `AdminDataKunjunganReport.tsx` + `dataKunjunganService.ts` (drill-down tiga level, search tanpa filter tanggal di laporan admin, export di level ringkasan).
- Web Admin (`Web Admin/`) memakai kartu Beranda + `GET /dashboard/counts`, routing di `routes.tsx`, halaman modul dengan `Breadcrumb` + judul + konten tab (`AnggotaPage` + `AnggotaTab` sebagai pola). Export modul data di Web saat ini umumnya **client-side** dari baris tabel (`exportService.ts` + `EXPORT_CONFIGS`); **kunjungan admin** memakai **export server-side (blob)** seperti mobile — perlu helper HTTP terpisah dari `api.get` JSON.

## Goal

1. Kartu **Data Kunjungan** di Beranda (`/` dan setara `basePath` super-admin) dengan gaya kartu existing, mengarah ke route **`/data-kunjungan`** atau **`/super-admin/data-kunjungan`**.
2. Halaman modul memuat laporan admin tiga level (A → B → C) dengan **query `search` opsional** ke endpoint yang sama dengan mobile; **tanpa** filter tanggal di laporan admin.
3. Tombol export PDF/Excel di **level A** memanggil endpoint export backend (bukan `exportToPdf` dari baris tabel lokal), dengan unduhan file ke browser.
4. UI **read-only**: tidak menampilkan form tambah/ubah/hapus kunjungan untuk admin.

## Keputusan produk (default plan — ubah di dokumen ini jika tidak setuju sebelum EXECUTE)

| ID | Topik | Keputusan default |
|----|--------|-------------------|
| Q1 | Badge angka di kartu Beranda | **(a)** Menambah field `data_kunjungan` pada respons `GET /api/dashboard/counts` (total baris `DataKunjungan`), serta memastikan cabang **member** pada controller mengembalikan key yang sama (nilai **0**) agar kontrak JSON konsisten untuk semua caller. |
| Q2 | Akses `super_admin` | **(b)** Mendaftarkan route **`data-kunjungan`** juga di bawah **`/super-admin/`** dengan komponen halaman yang **sama** seperti admin (`AnggotaPage` pattern: reuse `DataKunjunganPage`). |

## Detailed Specifications

### A. Backend Laravel (`backend-app/`)

| File | Perubahan |
|------|------------|
| `app/Http/Controllers/Api/DashboardCountsController.php` | Pada respons non-member (`response->json data`), tambah key **`data_kunjungan`** berisi integer count model **`DataKunjungan`** (import model jika belum). Pada `memberCounts`: sertakan **`data_kunjungan` => 0** (atau count scoped jika nanti ada kebijakan lain — default **0**). Perbarui `array_fill_keys` agar key list konsisten dengan key admin. |
| `API_DOCUMENTATION.md` | Dokumentasikan field baru `data_kunjungan` pada endpoint dashboard counts (jika bagian counts didokumentasikan). |

**Tidak** mengubah logika laporan di `DataKunjunganService` / `DataKunjunganController` kecuali ditemukan bug saat integrasi (di luar scope plan ini).

### B. Web Admin — routing & Beranda

| File | Perubahan |
|------|------------|
| `src/app/routes.tsx` | Import **`DataKunjunganPage`**. Tambah child route path **`data-kunjungan`** di tree **`/`** (Layout admin) dan duplikat di tree **`/super-admin/`** (sama seperti `anggota`, `data-lo`, …). |
| `src/app/pages/Beranda.tsx` | Perluas interface **`CountsData`** dengan `data_kunjungan: number`. Tambah satu entri **`menuCards`**: `countKey: "data_kunjungan"`, path `` `${basePath}/data-kunjungan` ``, label **"Data Kunjungan"**, deskripsi singkat (mis. "Laporan frekuensi kunjungan per kelompok"), icon Lucide yang relevan (mis. **`MapPin`** atau **`ClipboardList`** — pilih satu dan konsisten), **`bgColor`** unik yang belum dipakai kartu lain (mis. `bg-teal-500` atau `bg-indigo-500`). |

### C. Web Admin — halaman modul

| File | Perubahan |
|------|------------|
| `src/app/pages/DataKunjunganPage.tsx` | **File baru.** Pola sama `AnggotaPage.tsx`: `useLocation` untuk **`berandaPath`** (`/` vs `/super-admin/beranda` — samakan dengan halaman modul lain yang support super-admin), **`Breadcrumb`**: Beranda → "Data Kunjungan", **`h1`** judul + paragraf deskripsi, render komponen konten utama (satu tab besar). |
| `src/app/components/tabs/DataKunjunganLaporanTab.tsx` | **File baru.** Memuat seluruh UI laporan admin: state **`level`: `'A' \| 'B' \| 'C'`**, state pilihan **`selectedIdKel`**, **`selectedNamaKel`**, **`selectedNoAgt`**, **`selectedNamaAgt`**, **`draftSearch`**, **`appliedSearch`** (terapan search ke API sama konsep mobile: update **`appliedSearch`** saat user menekan **Enter** pada input search di level manapun, atau tombol "Cari" opsional jika ingin selaras modul Web lain — **default: Enter** untuk paritas mobile). **Level A:** `GET /admin/data-kunjungan/report/group-summary` dengan query `search` dari `appliedSearch`. Tabel kolom: ID kelompok, nama kelompok, frekuensi, aksi Detail. **Level B:** `GET /admin/data-kunjungan/report/by-kelompok/{id_kel_sah}/anggota-summary` dengan `search`. Kolom: no anggota, nama, frekuensi, tanggal terakhir, aksi Detail. **Level C:** `GET /data-kunjungan` dengan `page`, `per_page` besar (mis. 5000 seperti mobile), `ID_KEL_SAH`, `NO_AGT`, `search` opsional. Kolom disesuaikan kebutuhan baca saja (tanggal, kegiatan, koordinat, bukti foto link). **Navigasi:** tombol Kembali mengubah level C→B→A dan mereset state relevan. **Export (hanya level A):** dua tombol icon (PDF merah, Excel hijau) sejajar toolbar search — panggil `GET /data-kunjungan/export/pdf` dan `.../export/excel` **tanpa** query search jika perilaku disamakan mobile saat ini (mobile memanggil export `{}`); jika product ingin export terfilter search, tambah query `search` — **default plan: sama mobile = tanpa query tambahan**. |
| `src/services/dataKunjunganAdminService.ts` | **File baru.** Fungsi async murni memakai `api.get` dari `@/services/api`: tipe response untuk baris group/anggota; pembentukan URL dengan `URLSearchParams` untuk `search`; path persis: `/admin/data-kunjungan/report/group-summary`, `/admin/data-kunjungan/report/by-kelompok/${encodeURIComponent(id)}/anggota-summary`, `/data-kunjungan` untuk list (mirror query mobile). |
| `src/services/kunjunganBackendExport.ts` (nama boleh disesuaikan) | **File baru.** Fungsi `downloadKunjunganExcel()` / `downloadKunjunganPdf()` menggunakan **`fetch`** ke `getApiPrefix()` + path relatif, header **`Authorization: Bearer`**, **`Accept`**: blob; baca **`Content-Disposition`** untuk nama file fallback; trigger download via `URL.createObjectURL` + anchor temporary (polos, tanpa dependency baru). |

### D. Gaya & komponen reuse Web

- Tabel: gunakan kelas dari **`@/app/constants/adminTableStyles`** (`ADMIN_TABLE_*`) seperti tab admin lain agar visual konsisten.
- Pagination: gunakan komponen **`Pagination`** dari `@/app/components/Pagination.tsx` dengan **`itemsPerPage`** default **10** (atau 20) dan opsi "Semua" jika cocok dengan panjang data; hitung **`totalPages`** dari panjang array di client (data laporan di-load full per level seperti mobile, bukan server pagination untuk level A/B).
- Loading/error: pola `toast` / teks error konsisten dengan tab lain (`toastApiErrorMessage` dari `@/services/crudToast` jika dipakai di modul lain).
- Pratinjau foto / peta: **minimal** — link `a href` `_blank` ke URL foto / Google Maps jika koordinat ada (tanpa modal kompleks di iterasi pertama), kecuali tim memutuskan reuse komponen gambar yang sudah ada.

### E. Pengujian

| Target | Isi |
|--------|-----|
| Manual | Login admin Web → Beranda → kartu baru → drill A→B→C → kembali → export. |
| Opsional | Jika tim punya E2E, tambah skenario smoke; jika tidak, cukup manual. |
| Backend | `php artisan test` subset atau test baru untuk **`DashboardCountsController`** / response counts bila belum ada — **hanya** jika ditambahkan test file yang memang dipakai project (ikuti pola test existing). |

## Risks / Catatan

- **Export blob:** `api.get` hanya JSON; salah implementasi akan merusak parse — wajib gunakan helper fetch terpisah untuk binary.
- **Counts:** anggota response `dashboard/counts` harus selalu menyertakan key baru untuk menghindari `undefined` di badge.
- **Role:** pastikan token user yang mengakses Web memang diizinkan middleware untuk route `admin/data-kunjungan/*` (sudah ada di backend); jika 403, tampilkan pesan dari body error.

---

## Implementation Checklist (urut, atomik)

1. Backend: di `DashboardCountsController.php`, import `App\Models\DataKunjungan` (atau path model yang benar di project).
2. Backend: tambahkan elemen array `'data_kunjungan' => DataKunjungan::count()` pada respons JSON utama (non-member).
3. Backend: pada `memberCounts`, tambahkan key `data_kunjungan` dengan nilai `0` pada struktur `data` yang dikembalikan (termasuk cabang `idKs === null` jika `array_fill_keys` perlu disesuaikan).
4. Backend: perbarui `API_DOCUMENTATION.md` untuk response `GET /api/dashboard/counts` agar mencantumkan field `data_kunjungan`.
5. Web Admin: di `Beranda.tsx`, tambahkan `data_kunjungan` pada interface `CountsData`.
6. Web Admin: di `Beranda.tsx`, tambahkan satu objek pada array `menuCards` untuk Data Kunjungan (path, label, deskripsi, icon, `bgColor`, `countKey: "data_kunjungan"`).
7. Web Admin: buat file `src/services/dataKunjunganAdminService.ts` berisi tipe TypeScript untuk baris group summary dan anggota summary (mirror field JSON backend camel/snake sesuai response nyata), plus fungsi `fetchAdminKunjunganGroupSummary`, `fetchAdminKunjunganAnggotaSummary`, `fetchDataKunjunganListForAdmin` yang memanggil `api.get` dengan path dan query yang benar.
8. Web Admin: buat file helper export blob (mis. `src/services/kunjunganBackendExport.ts`) dengan dua fungsi unduhan untuk path `/data-kunjungan/export/excel` dan `/data-kunjungan/export/pdf` memakai token dari `getToken()` dan `getApiPrefix()`.
9. Web Admin: buat `src/app/components/tabs/DataKunjunganLaporanTab.tsx` berisi state level A/B/C, state pilihan kelompok/anggota, state `draftSearch`/`appliedSearch`, efek/`useCallback` untuk memuat data per level dari service langkah 7, handler kembali, handler drill-down, input search dengan **Enter** memanggil set `appliedSearch` dan reset halaman pagination client.
10. Web Admin: di `DataKunjunganLaporanTab.tsx`, render tabel level A dengan kelas `ADMIN_TABLE_*`, kolom wajib, tombol Detail memanggil transisi ke level B dengan menyimpan `id_kel_sah` dan nama kelompok.
11. Web Admin: di `DataKunjunganLaporanTab.tsx`, render tabel level B dengan pagination client menggunakan komponen `Pagination`, tombol Detail ke level C dengan `NO_AGT` dan nama anggota.
12. Web Admin: di `DataKunjunganLaporanTab.tsx`, render tabel level C dengan kolom read-only dan link bukti/peta minimal; pagination client.
13. Web Admin: di `DataKunjunganLaporanTab.tsx`, pada **level A** saja, tampilkan toolbar satu baris: input search + tombol export PDF + Excel memanggil helper langkah 8, dengan disabled state saat proses unduh.
14. Web Admin: buat `src/app/pages/DataKunjunganPage.tsx` dengan `Breadcrumb`, judul, deskripsi, dan render `<DataKunjunganLaporanTab />`.
15. Web Admin: di `routes.tsx`, import `DataKunjunganPage` dan daftarkan route `data-kunjungan` sebagai child di bawah `/` dan di bawah `/super-admin/`.
16. Verifikasi manual: build frontend (`pnpm run build` atau perintah yang dipakai proyek Web Admin) tanpa error TypeScript.
17. Verifikasi manual: login admin/super_admin, uji alur checklist acceptance di bagian bawah dokumen versi sebelumnya (drill-down, search, export, tidak ada tombol CRUD).

---

## Acceptance criteria (verifikasi pasca-implementasi)

- [ ] Kartu Data Kunjungan tampil di Beranda dengan badge angka = total dari API counts.
- [ ] Route `/data-kunjungan` dan `/super-admin/data-kunjungan` membuka halaman yang sama secara fungsional.
- [ ] Level A/B/C memuat data dari endpoint yang benar; search mengirim query `search` bila `appliedSearch` tidak kosong.
- [ ] Export mengunduh file tanpa error 401/403 untuk role yang diizinkan.
- [ ] Tidak ada form create/edit/delete kunjungan pada halaman ini.

---

*Dokumen ini menggantikan versi ruang-lingkup awal menjadi plan eksekusi. Setelah user menyetujui plan dan memerintahkan `ENTER EXECUTE MODE`, implementasi wajib mengikuti checklist berurutan di atas.*
