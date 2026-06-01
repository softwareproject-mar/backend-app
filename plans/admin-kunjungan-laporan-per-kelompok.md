# Riset & cakupan fitur: Laporan Data Kunjungan (role admin) — aplikasi Kelompok Sahabat Obor Mas

Dokumen ini merangkum **pemahaman kebutuhan**, **kondisi codebase saat ini**, dan **file/backend yang kemungkinan disentuh** jika fitur dilaksanakan. Bukan spesifikasi implementasi final; untuk checklist eksekusi gunakan alur PLAN terpisah setelah disetujui.

---

## 1. Ringkasan kebutuhan (dari diskusi)

### 1.1 Konteks peran

- **Admin** membuka **report** Data Kunjungan (bukan alur input seperti anggota).
- **Export** (PDF/Excel) untuk admin **tetap diperlukan** (sesuai kesepakatan sebelumnya).
- **Tidak** ada transaksi CRUD dari admin di data kunjungan (tambah/ubah/hapus) — hanya baca + navigasi drill-down.

### 1.2 Alur UI tiga tingkat (target)

**Tingkat A — Ringkasan per kelompok**

Tampilan pertama setelah admin membuka report kunjungan:

| Informasi | Keterangan |
|-----------|------------|
| ID kelompok | Identitas kelompok (mis. `ID_KEL` / `ID_KEL_SAH` yang dipakai di data) |
| Nama kelompok | Nama tampilan kelompok (dari master kelompok) |
| Frekuensi | Jumlah kunjungan **yang dilakukan oleh anggota-anggota di kelompok tersebut** (agregat per kelompok) |
| Aksi | Tombol **Detail** → membuka Tingkat B untuk kelompok yang dipilih |

**Tingkat B — Ringkasan per anggota (dalam satu kelompok)**

Setelah klik Detail dari Tingkat A, untuk kelompok terpilih:

| Informasi | Keterangan |
|-----------|------------|
| Nama anggota | Nama dari master anggota |
| Frekuensi | Banyaknya **inputan kunjungan** yang dibuat/diisi oleh anggota tersebut (biasanya per `NO_AGT` atau kombinasi dengan `created_by` — perlu disepakati aturan bisnis) |
| Tanggal terakhir mengisi | Tanggal kunjungan terbaru dari baris-baris milik anggota tersebut |
| Aksi | Tombol **Detail** → membuka Tingkat C |

**Tingkat C — Daftar inputan / baris kunjungan**

Setelah klik Detail dari Tingkat B, daftar setiap **kunjungan** (satu baris per input), dengan field setara tabel saat ini:

- Nomor anggota  
- Nama anggota  
- Tanggal kunjungan  
- Kegiatan  
- Latitude, Longitude  
- **Lihat peta** (perilaku seperti sekarang: buka map eksternal)  
- **Foto** — klik memunculkan preview gambar (pakem URL foto seperti implementasi existing)

Intinya: **laporan drill-down** kelompok → anggota → baris kunjungan; isi **semua** yang relevan untuk kelompok itu, **bukan** hanya milik user yang sedang login.

---

## 2. Kondisi codebase saat ini (riset)

### 2.1 Backend — data & aturan simpan

- Tabel/model: `DataKunjungan` (`data_kunjungan`) memiliki antara lain `NO_AGT`, `ID_KEL_SAH`, `TGL_KUN`, `KEGIATAN`, `LATITUDE`, `LONGITUDE`, `FOTO_PATH`, `created_by` (untuk alur anggota).
- Untuk **anggota terbatas** (`MemberScope::isRestrictedMemberUser`), pada **create** service mengisi **`ID_KEL_SAH`** dengan **`memberKelompokId`** (kelompok terikat akun/anggota) dan memvalidasi `NO_AGT` anggota dari kelompok yang sama.
- **List** untuk admin: `MemberScope::mergeOwnershipFilterForCrud` **hanya** menambahkan filter `created_by` untuk role user terbatas; untuk **admin** filter itu **tidak** diterapkan → admin bisa mengambil **seluruh** baris yang memenuhi query (data global untuk agregasi laporan **secara prinsip** tersedia lewat `GET /data-kunjungan`).

### 2.2 Backend — response API ke klien

- `DataKunjunganResource` saat ini mengembalikan: `NO_URT`, `NO_AGT`, `TGL_KUN`, `KEGIATAN`, `ID_PIC`, `JLH_PESERTA`, field foto (`foto_path`, `foto_url`, `foto_api_url`), `latitude`, `longitude`.
- **`ID_KEL_SAH` dan `ID_LO` tidak** ada di array `toArray` resource tersebut — padahal di DB dan service create/update merujuk ke `ID_KEL_SAH`.
- Master kelompok: model `KelSah` (`kel_sah`) punya `ID_KEL`, `NAMA_KEL` (nama untuk ditampilkan di Tingkat A).
- Master anggota: model `Anggota` (`anggota`) punya `NO_AGT`, `NAMA`, `ID_KS` (penautan ke kelompok operasional anggota).

### 2.3 Aplikasi mobile — UI saat ini

- `DataKunjunganContent.tsx`: satu layar dengan **Tambah**, export, tabel flat, **Edit/Hapus** untuk semua role yang membuka layar; **tidak** ada mode khusus admin report bertingkat.
- `dataKunjunganService.ts`: mapping DTO mengharapkan `ID_KEL_SAH` untuk `idKelompok`, tetapi jika API tidak mengirim field itu, nilai kelompok dari response **kosong/tidak andal**.

### 2.4 Kesenjangan (gap) terhadap kebutuhan target

| Kebutuhan | Status |
|-----------|--------|
| UI tingkat A/B/C | **Belum ada** |
| Agregasi frekuensi per kelompok & per anggota di UI | **Belum ada** (bisa dihitung di klien **jika** API menyediakan `ID_KEL_SAH` + nama kelompok + nama anggota per baris, atau lewat endpoint agregasi baru) |
| `ID_KEL_SAH` + nama kelompok di payload list/detail | **Belum** di `DataKunjunganResource` |
| Nama anggota per baris kunjungan di payload | **Belum** di resource (hanya `NO_AGT`); nama bisa di-join dari `anggota` di backend atau dilookup di klien dengan endpoint lain |
| Memblokir CRUD admin di API | **Belum** eksplisit (controller tidak membedakan role untuk store/update/destroy pada potongan yang diperiksa) |
| Menyembunyikan tombol Tambah/Edit/Hapus untuk admin di mobile | **Belum** |

---

## 3. Logika agregasi (konsep data, bukan kode)

- **Frekuensi per kelompok (Tingkat A):** hitung jumlah baris `data_kunjungan` dengan `ID_KEL_SAH` (atau kunci kelompok yang disepakati) sama, untuk baris yang termasuk laporan (perlu aturan: hanya baris dengan `ID_KEL_SAH` terisi? hanya dari input anggota? — disepakati dengan bisnis).
- **Frekuensi per anggota dalam kelompok (Tingkat B):** kelompokkan baris di kelompok terpilih menurut **`NO_AGT`** (dan/atau `created_by` jika ingin membedakan “siapa yang login mengirim” vs “nomor anggota yang diisi” — Anda menyebut fokus ke anggota kelompok; kandidat utama **`NO_AGT`**).
- **Tanggal terakhir (Tingkat B):** `MAX(TGL_KUN)` per `NO_AGT` dalam kelompok (dengan parsing tanggal konsisten di DB/aplikasi).
- **Tingkat C:** filter baris dengan `ID_KEL_SAH` = kelompok terpilih dan `NO_AGT` = anggota terpilih (atau setara), urutkan mis. menurun menurut tanggal.

---

## 4. File & area backend yang **kira-kira** disentuh

Berikut daftar **kemungkinan** besar (bisa bertambah/berkurang setelah PLAN detail).

### Backend (Laravel — `c:\laragon\www\backend-app`)

| Area | File / lokasi | Alasan singkat |
|------|----------------|----------------|
| Payload list/detail kunjungan | `app/Http/Resources/DataKunjunganResource.php` | Menyertakan `ID_KEL_SAH`, mungkin `ID_LO`, dan/atau field join nama kelompok & nama anggota |
| Query/service | `app/Services/DataKunjunganService.php` | Agregasi server-side, filter by kelompok, eager load relasi, atau method baru untuk ringkasan |
| Controller & route | `app/Http/Controllers/Api/DataKunjunganController.php`, `routes/api.php` | Endpoint baru (mis. ringkasan per kelompok / per anggota) atau memperluas `index`; middleware `admin` jika hanya untuk admin |
| Otorisasi | `app/Http/Middleware/EnsureUserIsAdmin.php` (atau policy), controller `authorize` | Memastikan endpoint agregasi hanya admin; optional: melarang `store`/`update`/`destroy` untuk role admin |
| Form request | `app/Http/Requests/StoreDataKunjunganRequest.php`, `UpdateDataKunjunganRequest.php` | Jika admin dilarang mutasi: bisa lewat policy atau cek role di `authorize()` |
| Model / relasi | `app/Models/DataKunjungan.php`, `KelSah.php`, `Anggota.php` | Relasi Eloquent atau accessor untuk join nama |
| Dokumentasi API | `API_DOCUMENTATION.md` (jika dipelihara) | Mendokumentasikan field baru / endpoint baru |
| Uji | `tests/Feature/...` | Regresi list, hak akses admin vs user, export |

**Catatan desain (belum diputus di dokumen ini):**  
- **Opsi A — Hanya memperkaya `GET /data-kunjungan`:** klien melakukan agregasi Tingkat A/B. Risiko: volume data besar untuk admin.  
- **Opsi B — Endpoint khusus laporan:** mis. `GET .../data-kunjungan/report/by-kelompok` dan nested endpoint per anggota — lebih ringan di klien, lebih banyak kerja backend.

---

## 5. File aplikasi mobile yang **kira-kira** disentuh

Path proyek: `Kelompok Sahabat Obor Mas\Kelompok Sahabat Obor Mas\` (workspace UI).

| File | Alasan singkat |
|------|----------------|
| `src/app/components/DataKunjunganContent.tsx` | Memecah atau mengganti alur: mode `user` vs mode admin report; hilangkan CRUD untuk admin; tiga tingkat navigasi |
| `src/app/components/KelompokSahabatScreen.tsx` | Jika tab kunjungan mem-pass props mode/role ke konten |
| `src/app/components/MainDashboard.tsx` | Jika routing menu kunjungan perlu props berbeda untuk admin |
| `src/services/dataKunjunganService.ts` | Tipe DTO, fungsi list baru, parameter filter `ID_KEL_SAH`, pemanggilan endpoint agregasi jika ada |
| `src/state/authStore.tsx` (atau setara) | Membaca `role` untuk bercabang UI |

Kemungkinan tambahan: komponen baru khusus laporan (`AdminKunjunganReport*.tsx`) agar `DataKunjunganContent` tidak terlalu besar — keputusan di fase PLAN.

---

## 6. Checklist pemahaman (validasi dengan Anda)

- [ ] Frekuensi **per kelompok** = jumlah **baris** `data_kunjungan` dengan `ID_KEL_SAH` kelompok itu (bukan jumlah anggota unik)?  
- [ ] Frekuensi **per anggota** = jumlah baris dengan **`NO_AGT`** sama di dalam kelompok itu?  
- [ ] Baris kunjungan lama tanpa `ID_KEL_SAH` (jika ada) — ditampilkan di laporan “Tanpa kelompok” atau diabaikan?  
- [ ] Export admin = **seluruh** data sesuai filter laporan atau hanya tampilan tingkat saat ini?

---

## 7. Referensi cepat ke file yang sudah dibahas di riset

- Mobile: `DataKunjunganContent.tsx`, `dataKunjunganService.ts`, `KelompokSahabatScreen.tsx`, `MainDashboard.tsx`  
- Backend: `DataKunjunganController.php`, `DataKunjunganService.php`, `DataKunjunganResource.php`, `MemberScope.php`, model `DataKunjungan`, `KelSah`, `Anggota`, `routes/api.php`

---

*Bagian di atas: riset & pemetaan cakupan awal.*

---

# Plan: Implementasi laporan Data Kunjungan (admin) — drill-down + blok mutasi

## Context

- Aplikasi mobile **Kelompok Sahabat Obor Mas** memakai `DataKunjunganContent` untuk semua role; admin saat ini memiliki UI CRUD dan tabel flat yang sama dengan anggota.
- Backend `GET /data-kunjungan` sudah mengembalikan daftar kunjungan; `DataKunjunganResource` belum mengekspos `ID_KEL_SAH` / nama kelompok / nama anggota; mutasi `store` / `update` / `destroy` belum diblokir eksplisit untuk admin.
- Kebutuhan produk: **tiga tingkat laporan** (kelompok → anggota → baris kunjungan), **export tetap**, **admin tidak boleh mutasi** data kunjungan.

## Goal

- Menyediakan **API khusus laporan** untuk admin (ringkasan per kelompok & per anggota) tanpa bentrok routing dengan `apiResource('data-kunjungan')`.
- Memperkaya **payload baris kunjungan** (minimal untuk Tingkat C + konsistensi klien) dengan identitas kelompok dan nama anggota.
- Memblokir **POST / PUT(PATCH) / DELETE** data kunjungan untuk peran non-anggota (`admin`, `super_admin`, dan peran non-`user` lain jika ada).
- Menambah **UI mobile** mode admin: tiga layar drill-down + tombol export; tanpa Tambah/Edit/Hapus.

## Keputusan produk yang dikunci di plan ini

| Topik | Keputusan |
|-------|-----------|
| Frekuensi per kelompok (Tingkat A) | Jumlah **baris** `data_kunjungan` per nilai `ID_KEL_SAH` yang **tidak null dan tidak string kosong**. |
| Baris tanpa `ID_KEL_SAH` | **Tidak** masuk ringkasan Tingkat A pada versi ini (tidak ada bucket “Tanpa kelompok”). |
| Frekuensi per anggota (Tingkat B) | Jumlah baris per **`NO_AGT`** di dalam `ID_KEL_SAH` yang dipilih. |
| Tanggal terakhir (Tingkat B) | **Maksimum** `TGL_KUN` per `NO_AGT` dalam kelompok tersebut (perbandingan sebagai string/ISO mengikuti penyimpanan DB saat ini; implementasi harus konsisten dengan tipe kolom). |
| Tingkat C | Daftar baris kunjungan dengan filter query **`ID_KEL_SAH`** + **`NO_AGT`** pada endpoint list yang sudah ada (`GET /data-kunjungan`), dengan payload baris yang sudah diperkaya (`nama_anggota`, `nama_kelompok`, `ID_KEL_SAH`). |
| Siapa yang boleh mutasi | Hanya pengguna **`MemberScope::isRestrictedMemberUser` === true** (role anggota `user` dengan aturan yang sudah dipakai di codebase). **`admin` dan `super_admin` tidak boleh** `store` / `update` / `destroy`. |
| URL endpoint laporan admin | Prefix **`admin/`** agar tidak tabrakan dengan parameter `{data_kunjungan}` pada resource. |
| Export admin | Tetap memakai **`GET /data-kunjungan/export/excel`** dan **`GET /data-kunjungan/export/pdf`** di grup `member_approved` (tanpa mengubah URL); isi baris mengikuti `listForExport` yang sudah ada. **Tidak** menambah kolom export pada iterasi ini (bisa iterasi berikutnya). |

---

## Detailed Specifications

### Backend — Laravel (`c:\laragon\www\backend-app`)

#### A. Route baru (middleware `admin` + `auth:sanctum`)

- **File:** `routes/api.php`
- **Letak:** Di dalam grup `Route::middleware('admin')->group(function () { ... });` yang sudah ada (bersibling dengan route `users/...`), **tambah** dua route **GET**:
  1. `admin/data-kunjungan/report/group-summary` → method controller baru, mis. `DataKunjunganController@reportGroupSummary`.
  2. `admin/data-kunjungan/report/by-kelompok/{id_kel_sah}/anggota-summary` → method baru, mis. `DataKunjunganController@reportAnggotaSummaryForKelompok`.
- **Nama parameter path:** `id_kel_sah` (string, sama semantik dengan `ID_KEL_SAH` / `ID_KEL` di `kel_sah`).

#### B. Controller

- **File:** `app/Http/Controllers/Api/DataKunjunganController.php`
- **Method baru:**
  - `reportGroupSummary(Request $request): JsonResponse` — memanggil service, mengembalikan JSON bentuk `{ "data": [ { "id_kel_sah": string, "nama_kelompok": string, "frekuensi": int }, ... ] }` (kunci snake_case atau camelCase **pilih satu** dan konsisten dengan resource lain proyek ini — cek pola `UserResource` / response existing; dokumentasikan di response).
  - `reportAnggotaSummaryForKelompok(Request $request, string $id_kel_sah): JsonResponse` — memanggil service dengan `id_kel_sah`, response `{ "data": [ { "no_agt": string, "nama_anggota": string, "frekuensi": int, "tanggal_terakhir": string|null }, ... ] }`.
- **Method existing `store`, `update`, `destroy`:** di awal method, jika **bukan** `MemberScope::isRestrictedMemberUser($request->user())`, return **403** JSON dengan pesan konsisten (bahasa Indonesia, satu kalimat) — **tanpa** mengubah signature route.

#### C. Service

- **File:** `app/Services/DataKunjunganService.php`
- **Method baru (nama persis untuk implementasi):**
  1. `public function reportGroupSummaryRows(): \Illuminate\Support\Collection` — query agregasi `ID_KEL_SAH`, `COUNT(*) as frekuensi`, filter `ID_KEL_SAH` not null dan not empty string; join ke tabel `kel_sah` pada `data_kunjungan.ID_KEL_SAH = kel_sah.ID_KEL` untuk mengambil `NAMA_KEL` sebagai nama kelompok; urutkan ascending menurut `id_kel_sah` atau `nama_kelompok` (pilih **nama_kelompok** ascending untuk UX).
  2. `public function reportAnggotaSummaryForKelompok(string $idKelSah): \Illuminate\Support\Collection` — filter `ID_KEL_SAH = $idKelSah`, group by `NO_AGT`, `COUNT(*)`, `MAX(TGL_KUN)`; left join `anggota` pada `NO_AGT` untuk `NAMA` sebagai nama anggota; exclude baris dengan `NO_AGT` null/empty jika ada.
- **Catatan implementasi:** gunakan query builder / model yang konsisten dengan driver **Firebird** (model basis `FirebirdLegacyModel`); verifikasi nama kolom/tabel case-sensitivity di environment nyata.

#### D. Resource (payload baris kunjungan)

- **File:** `app/Http/Resources/DataKunjunganResource.php`
- **Perubahan `toArray`:** tambahkan key berikut (nama key mengikuti konvensi response API proyek — jika resource lain memakai UPPER untuk field bisnis, samakan; jika snake_case, gunakan snake_case konsisten):
  - `ID_KEL_SAH` (nullable string) dari model.
  - `nama_kelompok` (nullable string) — dari relasi/load: join `kel_sah` atau lookup sekali di resource jika model menyediakan accessor; **prefer** eager load di query `paginate` agar N+1 tidak terjadi.
  - `nama_anggota` (nullable string) — dari join `anggota` pada `NO_AGT`.
- **File terkait query index:** `DataKunjunganService::paginate` — untuk **semua** caller `paginate`, tambahkan left join yang sama agar resource selalu punya data nama (anggota non-restricted dan restricted). Pastikan filter `created_by` untuk user anggota **tetap** diterapkan seperti sekarang.

#### E. Form Request (opsional ganda dengan controller guard)

- **File:** `app/Http/Requests/StoreDataKunjunganRequest.php`, `app/Http/Requests/UpdateDataKunjunganRequest.php`
- **`authorize()`:** return `false` (atau `Gate::deny`) jika user ada dan **bukan** restricted member user, agar layer validasi selaras dengan controller.

#### F. Uji otomatis

- **File baru atau tambah pada file feature yang ada:** mis. `tests/Feature/AdminDataKunjunganReportTest.php` (nama bebas asal jelas).
- **Skenario minimal:**
  - User `admin`: `GET admin/data-kunjungan/report/group-summary` → **200**; `GET admin/.../anggota-summary` dengan id yang punya data → **200**.
  - User `user` (anggota): endpoint laporan admin di atas → **403**.
  - User `admin`: `POST /data-kunjungan` (dengan payload minimal valid jika sulit, atau tanpa file) → **403** (bukan 422 dari validasi bisnis utama).
  - User `user` yang approved: `POST` kunjungan tetap bisa **201** (uji minimal jika fixture sudah ada; jika tidak, setidaknya uji 403 untuk admin).

#### G. Dokumentasi API

- **File:** `API_DOCUMENTATION.md` — tambahkan deskripsi dua endpoint `admin/data-kunjungan/report/...` dan field baru pada resource list/show.

---

### Frontend — Mobile (`Kelompok Sahabat Obor Mas\Kelompok Sahabat Obor Mas`)

#### H. Service klien

- **File:** `src/services/dataKunjunganService.ts`
- **Tambah fungsi async:**
  1. `getAdminReportGroupSummary(): Promise<...>` → `GET` `{API_BASE}/admin/data-kunjungan/report/group-summary` dengan `auth: true`.
  2. `getAdminReportAnggotaSummary(idKelSah: string): Promise<...>` → `GET` `admin/data-kunjungan/report/by-kelompok/${encodeURIComponent(idKelSah)}/anggota-summary`.
- **Perbarui tipe** `DataKunjunganDto` / mapping `mapDtoToItem` agar mencakup `nama_kelompok`, `nama_anggota` dari response (nama properti mengikuti backend setelah implementasi).
- **`KunjunganItem`:** tambahkan field opsional `namaAnggota?: string`, `namaKelompok?: string` (atau snake_case sesuai gaya file — konsisten dengan file).

#### I. Komponen UI

- **File baru:** `src/app/components/AdminDataKunjunganReport.tsx` (nama persis ini)
  - **State:** tingkat tampilan `A | B | C`; `selectedIdKelSah`, `selectedNoAgt`, `selectedNamaKelompok`, `selectedNamaAnggota` (untuk header/breadcrumb teks).
  - **Tingkat A:** daftar kartu/tabel: kolom ID kelompok, nama kelompok, frekuensi, tombol **Detail**.
  - **Tingkat B:** tombol kembali ke A; daftar: nama anggota, frekuensi, tanggal terakhir, tombol **Detail**.
  - **Tingkat C:** tombol kembali ke B; panggil `getKunjunganList` dengan `ID_KEL_SAH` + `NO_AGT` filter (pastikan service mengirim query string yang sudah didukung backend `kunjunganListFilters`); tampilkan kolom: No, Nomor Anggota, **Nama anggota**, Tanggal kunjungan, Kegiatan, Latitude, Longitude, **Lihat peta**, **Bukti** (preview sama seperti `DataKunjunganContent` — reuse fungsi `buildGoogleMapsUrl` / preview dengan **mengekstrak** helper ke file kecil `kunjunganViewUtils.ts` **hanya jika** duplikasi > ~30 baris; jika tidak, duplikasi singkat diterima untuk iterasi ini).
  - **Export:** di Tingkat A (dan opsional di B/C), tombol export PDF/Excel memanggil `exportKunjunganExcel` / `exportKunjunganPdf` **tanpa** filter tambahan (seluruh data sesuai backend export untuk admin).
- **File:** `src/app/components/DataKunjunganContent.tsx`
  - Di awal render: jika `useAuth().user?.role === 'user'` (strict string `'user'` sama seperti `HomeContent`), render isi **existing** (form + tabel CRUD) tidak diubah perilaku anggota.
  - Jika role **bukan** `'user'` (admin / super_admin / undefined treated as staff — untuk `undefined` tampilkan komponen admin atau loading: pilih **tampilkan `AdminDataKunjunganReport` hanya jika role `admin` atau `super_admin`**, jika `undefined` tampilkan placeholder “Memuat…” atau tidak render report sampai role terisi — spesifikasi: **jika `user?.role` bukan `'user'`, render `AdminDataKunjunganReport`**).
- **File:** `src/app/components/KelompokSahabatScreen.tsx` — **tidak wajib** diubah jika `DataKunjunganContent` sudah bercabang internal; hanya ubah jika props diperlukan (default: **no change**).

---

## Implementation Checklist (urut eksekusi)

1. Backend: Di `DataKunjunganService.php`, implementasikan method **`reportGroupSummaryRows()`** sesuai spesifikasi agregasi per `ID_KEL_SAH` + join `kel_sah` untuk nama.
2. Backend: Di `DataKunjunganService.php`, implementasikan method **`reportAnggotaSummaryForKelompok(string $idKelSah)`** sesuai spesifikasi group by `NO_AGT` + join `anggota`.
3. Backend: Di `DataKunjunganService.php`, perluas **`paginate`** agar query index meng-**left join** `kel_sah` dan `anggota` (atau subquery setara) dan memilih kolom nama untuk dipakai resource, **tanpa** mengubah perilaku filter `MemberScope::mergeOwnershipFilterForCrud` untuk user anggota.
4. Backend: Di `DataKunjunganResource.php`, tambahkan key **`ID_KEL_SAH`**, **`nama_kelompok`**, **`nama_anggota`** pada `toArray()` dengan sumber dari model/attribute hasil join.
5. Backend: Di `DataKunjunganController.php`, tambahkan method **`reportGroupSummary`** yang memanggil `reportGroupSummaryRows` dan mengembalikan JSON terstruktur.
6. Backend: Di `DataKunjunganController.php`, tambahkan method **`reportAnggotaSummaryForKelompok`** yang memvalidasi `id_kel_sah` tidak kosong, memanggil service, mengembalikan JSON terstruktur.
7. Backend: Di `DataKunjunganController.php`, pada **`store`**, **`update`**, **`destroy`**, tambahkan guard paling awal: jika bukan restricted member user → response **403** JSON.
8. Backend: Di `StoreDataKunjunganRequest.php` dan `UpdateDataKunjunganRequest.php`, set **`authorize()`** agar mengembalikan `false` untuk user yang bukan restricted member user.
9. Backend: Di `routes/api.php`, daftarkan **`GET admin/data-kunjungan/report/group-summary`** dan **`GET admin/data-kunjungan/report/by-kelompok/{id_kel_sah}/anggota-summary`** di dalam grup middleware **`admin`** (sibling route user approval).
10. Backend: Tambah file tes **`tests/Feature/AdminDataKunjunganReportTest.php`** (atau nama setara) dengan asersi 200/403 sesuai checklist skenario di bagian F.
11. Backend: Perbarui **`API_DOCUMENTATION.md`** untuk endpoint baru dan field resource baru.
12. Mobile: Di `dataKunjunganService.ts`, tambahkan tipe response untuk kedua endpoint admin dan fungsi **`getAdminReportGroupSummary`** serta **`getAdminReportAnggotaSummary`**.
13. Mobile: Di `dataKunjunganService.ts`, perbarui **`DataKunjunganDto`**, **`mapDtoToItem`**, dan **`KunjunganItem`** untuk field nama kelompok / nama anggota dari API.
14. Mobile: Buat file **`src/app/components/AdminDataKunjunganReport.tsx`** dengan state tingkat A/B/C dan pemanggilan API dari langkah 12.
15. Mobile: Implementasikan **Tingkat A** UI (daftar ringkasan kelompok + tombol Detail) memakai data dari `getAdminReportGroupSummary`.
16. Mobile: Implementasikan **Tingkat B** UI (daftar ringkasan anggota + tombol Detail + navigasi kembali ke A) memakai `getAdminReportAnggotaSummary` dengan `selectedIdKelSah`.
17. Mobile: Implementasikan **Tingkat C** UI memakai **`getKunjunganList`** dengan parameter `ID_KEL_SAH` dan `NO_AGT` sesuai pilihan di B; tampilkan kolom wajib + **Lihat peta** + **Bukti** dengan perilaku setara `DataKunjunganContent`.
18. Mobile: Di **`AdminDataKunjunganReport.tsx`**, tambahkan kontrol **export PDF/Excel** (reuse fungsi export yang ada) di tingkat A (default cukup di A).
19. Mobile: Di **`DataKunjunganContent.tsx`**, tambahkan cabang render: `role === 'user'` → konten lama; selain itu → **`<AdminDataKunjunganReport />`**.
20. Manual QA: login admin — tidak ada tombol Tambah/Edit/Hapus pada alur laporan; drill-down A→B→C; foto dan peta berfungsi; export jalan; login anggota — perilaku input kunjungan tetap seperti sebelumnya.
21. Manual QA: percobaan `POST` kunjungan sebagai admin (Postman) memastikan **403**.

---

## Risks / Catatan

- **Firebird / legacy model:** join dan `MAX(TGL_KUN)` harus diuji di DB nyata; perbedaan case nama kolom bisa menyebabkan error runtime.
- **Volume data:** `GET /data-kunjungan` dengan `per_page` besar untuk Tingkat C bisa berat; jika perlu, iterasi berikutnya membatasi `per_page` atau menambah endpoint detail per anggota dengan paginasi.
- **Baris tanpa `ID_KEL_SAH`:** tidak tampil di laporan admin v1; jika bisnis butuh, tambah bucket di plan revisi.
- **Role `undefined` di mobile:** pastikan tidak flash UI salah saat auth loading.

---

## Ringkasan untuk persetujuan

| Urutan besar | Isi |
|--------------|-----|
| 1 | Service + resource + query join untuk nama |
| 2 | Controller report + guard mutasi + Form Request authorize |
| 3 | Route `admin/...` |
| 4 | Tes feature |
| 5 | Mobile service + komponen baru + cabang di `DataKunjunganContent` |
| 6 | QA manual |

**Setelah Anda menyetujui plan ini**, perintahkan **`ENTER EXECUTE MODE`** untuk implementasi mengikuti checklist di atas (tanpa deviasi kecuali kembali ke PLAN jika blocker teknis).

*Akhir dokumen — riset (atas) + plan implementasi (bawah).*
