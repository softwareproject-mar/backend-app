# Modul Target & Realisasi — Requirements (stakeholder) + Research (codebase)

Dokumen ini memadukan **Modul 3 — Target & Realisasi** sesuai teks requirements yang Anda berikan dengan **temuan observasi** codebase (`backend-app`, Web Admin, User Mobile). Bagian requirements disusun supaya **nomor/huruf dan istilah** mengikuti spesifikasi Anda; bagian research mencatat kondisi repo dan **dampak role** (user vs admin) secara eksplisit.

---

## Bagian 1 — Requirements (mirror spesifikasi stakeholder)

### 3. Modul Target & Realisasi (judul modul)

### Platform

| Platform | Catatan di workspace |
|----------|----------------------|
| **Admin Web** | Proyek `Web Admin` (Vite/React) |
| **Admin Mobile APK** | Di workspace ini **tidak** ada repo APK terpisah; umumnya **varian build / role admin** dari codebase mobile yang sama dengan user — keputusan deploy tetap milik tim |
| **User Mobile APK** | Proyek `Kelompok Sahabat Obor Mas` (React/Vite) |

### Sumber data

- Data pengolahan modul bersumber dari tabel **`data_trs`** (transaksi/simpanan per anggota; agregasi realisasi per kelompok memadukan banyak anggota dalam satu kelompok).

### A. Target

- **Admin** dapat menginput **target nominal** untuk **setiap kelompok**.
- Contoh: Target Kelompok A = Rp1.000.000.

### B. Realisasi

- Nilai realisasi diperoleh dari **penjumlahan seluruh** transaksi/simpanan anggota dalam **satu kelompok**.
- Contoh: Anggota A Rp200.000 + Anggota B Rp300.000 + Anggota C Rp300.000 → **Total realisasi** = Rp800.000.

### C. Persentase pencapaian

- Rumus: **(Realisasi / Target) × 100%**.
- Contoh: Target Rp1.000.000, Realisasi Rp800.000 → **80%**.

### Status target (teks persis konsep Anda)

- **On Target** → jika realisasi **sudah mencapai** target.
- **Belum On Target** → jika realisasi **masih di bawah** target.

*(Catatan dokumen: frasa “sudah mencapai” pada praktik umum memuat **realisasi ≥ target**; jika bisnis memerlukan definisi ketat **hanya sama persis**, itu perlu satu kalimat penegasan terpisah.)*

### Informasi yang ditampilkan pada modul

- Nama kelompok  
- Nominal target  
- Total realisasi  
- Persentase pencapaian (%)  
- Status target  
- Jumlah anggota dalam kelompok  

### Visualisasi (jika memungkinkan)

- Progress bar target vs realisasi  
- Bar chart  
- Line chart  

### Tujuan

- **Admin maupun user** dapat **memonitor** perkembangan pencapaian target **lebih mudah**; disinggung preferensi **realtime** — detail teknis (refresh, websocket, dll.) belum dijabarkan di requirements.

---

## Bagian 1B — Dampak role **user** vs **admin** (sesuai requirements + implikasi)

Requirements Anda membedakan **kemampuan input** (hanya admin) vs **monitoring** (admin dan user). Di bawah ini pemetaan yang **selaras dengan teks Anda**; bagian backend saat ini hanya **observasi** (lihat Bagian 2–3).

| Aspek | Admin (Web + Mobile) | User (Mobile) |
|--------|----------------------|---------------|
| **Input target nominal per kelompok** | Ya — secara eksplisit di requirements | Tidak — tidak disebutkan |
| **Lihat: nama kelompok, target, realisasi, %, status, jumlah anggota** | Ya — “admin … memonitor” | Ya — “user dapat memonitor” |
| **Visualisasi (progress / chart)** | Opsional — tidak dibatasi per role di requirements | Opsional — sama |
| **Scope data (semua kelompok vs satu kelompok)** | Tidak dijabarkan di requirements; untuk **admin** biasanya **lintas kelompok**; untuk **user** secara bisnis umumnya **kelompok yang dipakai user** — ini **keputusan kebijakan** yang perlu ditulis eksplisit saat PLAN | Sama — perlu kebijakan scope |

**Hubungan dengan role teknis backend (`routes/api.php`, ringkas):**

- **`member_approved`** — grup fitur anggota yang sudah disetujui (mis. `GET data-trs`); cocok untuk **user** yang memantau dengan data ter-scope.
- **`admin`** — grup middleware terpisah untuk fitur administrasi (contoh pola: `admin/data-kunjungan/report/...`); cocok untuk **admin** input target + laporan agregat lintas kelompok jika produk memutuskan demikian.
- **`super_admin`** — prefix `super-admin/...` untuk operasi tingkat sistem; modul ini **tidak** mensyaratkan super admin secara eksplisit; bisa dipetakan nanti jika hanya super admin yang boleh set target global.

**Apakah MD ini sudah “sesuai” requirements + dampak role?**

- **Bagian 1** sekarang mengikuti struktur **Modul 3, platform, sumber `data_trs`, A/B/C, status, daftar informasi, visualisasi, tujuan** seperti yang Anda kirim.
- **Bagian 1B** menambahkan apa yang **belum** tertulis di requirements asli (**scope kelompok** untuk user vs admin) sebagai **celah kebijakan** — bukan kontradiksi, melainkan supaya PLAN/EXECUTE tidak salah asumsi.
- **Research (Bagian 2–4)** tetap menjelaskan bahwa implementasi teknis di repo **belum** memenuhi modul ini (gap tabel/API/UI); itu **bukan** ketidaksesuaian dengan requirements, melainkan status pengerjaan.

---

## Bagian 2 — Research: struktur data di backend (`backend-app`)

### 2.1 Tabel `data_trs`

**Migrasi awal:** `database/migrations/2025_12_13_145903_create_data_trs_table.php`

- PK awal: **`NO_AGT`** (satu baris per anggota pada skema lama).
- Kolom numerik/string transaksi antara lain: **`STR_SP`**, `STR_SW`, `STR_SKA`, `STR_SRI`, `STR_SDK`, `STR_PJM`, `STR_BNG`, `PJM_BARU`, `STR_SHR`, `STR_SBJ`, `STR_SJP`, `STR_SPD`, `STR_SRY`, `STR_SMD`, `TGL_LAP`.

**Migrasi lanjutan:** multi-row per `NO_AGT`, surrogate key — lihat `database/migrations/2026_04_08_100000_multi_row_no_agt_surrogate_keys_and_drop_uniques.php` (referensi di grep untuk `data_trs`).

**Model:** `app/Models/DataTrs.php` — extends `FirebirdLegacyModel`, `$table = 'data_trs'`, PK **`ID`** (integer), fillable mencakup kolom-kolom di atas.

**Observasi relevan modul:**

- Requirement menyebut “jumlahkan transaksi/simpanan”; di skema ada **banyak kolom** `STR_*` — **belum ada** di dokumen ini definisi bisnis mana yang dijumlahkan menjadi satu “nominal realisasi” per anggota atau per kelompok (perlu klarifikasi domain).

### 2.2 Tabel `target`

**Migrasi:** `database/migrations/2025_12_13_145903_create_target_table.php`

- Primary key komposit: **`ID_KS`**, **`TGL_TGT`**.
- Banyak kolom target/rekening (bukan satu kolom “nominal target sederhana”): `JLH_AGT_BR`, `STR_SP`, `SLD_SP`, `STR_SW`, …, `REK_*`, dll.

**Model:** `app/Models/Target.php` → `$table = 'target'`.

**Service:** `app/Services/TargetService.php` — paginate dengan filter `ID_KS`, `TGL_TGT`; create/update/delete per pasangan kunci.

### 2.3 Tabel `realisasi`

**Migrasi:** `database/migrations/2025_12_13_145903_create_realisasi_table.php`

- PK komposit: **`ID_KS`**, **`TGL_TGT`**.
- Struktur mirip snapshot realisasi per kelompok per tanggal target (banyak kolom string/numerik).

**Model:** `app/Models/Realisasi.php`.

**Service:** `app/Services/RealisasiService.php` — paginate + find; **tidak** terlihat logika agregasi dari `data_trs` di service ini (hanya akses tabel `realisasi`).

### 2.4 Join target + realisasi (dashboard lama)

**File:** `app/Services/DashboardService.php`

- Query: `target as t` **leftJoin** `realisasi as r` pada **`ID_KS`** dan **`TGL_TGT`**.
- Select banyak alias `target_*` dan `realisasi_*`; summary menjumlahkan beberapa kolom agregat (`target_jlh_agt_br`, `realisasi_jlh_agt_br`, dll.).

**Observasi gap vs requirement stakeholder:**

- Dashboard ini mengikat **target** ke tabel **`realisasi`**, bukan ke **agregasi langsung dari `data_trs`** seperti yang dijelaskan di requirement modul baru.
- Skema **target/realisasi** saat ini **periode-bergantung** (`TGL_TGT`), sedangkan contoh requirement menyebut satu angka target nominal per kelompok tanpa menyebut periode.

### 2.5 Relasi kelompok ↔ anggota

**`kel_sah`:** `app/Models/KelSah.php` — PK **`ID_KEL`**, field `NAMA_KEL`, dll.

**`anggota`:** `app/Models/Anggota.php` — PK **`NO_AGT`**, field **`ID_KS`** (kelompok).

**Observasi konsisten dengan kode lain:** `DashboardCountsController` memakai `Anggota::where('ID_KS', $idKs)` dan `KelSah::where('ID_KEL', $idKs)` — diasumsikan **`ID_KS` anggota** selaras dengan **`ID_KEL`** kelompok untuk scope yang sama (sudah juga disebut di plan lain di repo).

### 2.6 API `data_trs` (baca)

**Route aktif (cuplikan):** `routes/api.php` — dalam grup `auth:sanctum` + `member_approved`:

- `GET data-trs` → `DataTrsController@index`
- `GET data-trs/export/excel`, `export/pdf`

**Controller:** `app/Http/Controllers/Api/DataTrsController.php` — memakai **`FirebirdService`** (paginate, export), bukan hanya Eloquent MySQL polos.

**Resource:** `app/Http/Resources/DataTrsResource.php` — mengekspos field per baris transaksi.

**Plan terkait:** `plans/data-trs-firebird-readonly-kunjungan-scope.md` menyebut TRS baca dari **`firebird_legacy`** / normalisasi — relevan untuk konsistensi “sumber kebenaran” realisasi jika dihitung dari baris yang sama dengan yang ditampilkan di app.

### 2.7 API Target / Realisasi / Dashboard (join)

**Controller ada di disk:**

- `app/Http/Controllers/Api/TargetController.php` — CRUD target.
- `app/Http/Controllers/Api/RealisasiController.php` (ada di tree grep).
- Plan nonaktifasi: `plans/disable-target-realisasi-dashboard.md` menjelaskan bahwa route **`/target`**, **`/realisasi`**, **`/dashboard`** (join) **dihapus/dikomentari** dari API publik.

**Observasi pada `routes/api.php` (file saat ini):**

- **Tidak** terlihat import atau registrasi route untuk `TargetController`, `RealisasiController`, atau `DashboardController` join lama — selaras dengan plan penonaktifan.
- Tetap ada endpoint lain bernama “dashboard”: **`GET dashboard/counts`** (`DashboardCountsController`), **`super-admin/dashboard/*`** — ini **bukan** modul target vs realisasi stakeholder.

### 2.8 File backend lain yang relevan untuk pelacakan nanti

| Area | Path |
|------|------|
| Firebird TRS | `app/Services/FirebirdService.php` (`fetchDataTrsRows`, `paginateDataTrs`, `normalizeDataTrsRow`, dll.) |
| Impor TRS | `app/Http/Controllers/Api/DataTrsImportController.php`, `app/Http/Requests/ImportDataTrsRequest.php` |
| Request validasi target | `app/Http/Requests/StoreTargetRequest.php`, `UpdateTargetRequest.php` |
| Resource API target | `app/Http/Resources/TargetResource.php` |
| Resource realisasi | `app/Http/Resources/RealisasiResource.php` |
| Scope data / ownership | `plans/data-scope-created-by-role-user.md`, `app/Support/OwnerScope.php` |
| Dokumentasi API | `API_DOCUMENTATION.md`, `API_DOCUMENTATION_COMPLETE.md` (entri `data_trs`) |
| Kebutuhan proyek umum | `REQUIREMENTS.md`, `PROJECT_OVERVIEW.md`, `PROJECT_RULES.md` |

---

## Bagian 3 — Research: frontend di workspace

**Kaitan role (requirements):** modul harus hadir di **Admin Web**, **Admin Mobile**, dan **User Mobile** — dengan pemisahan **input target (admin)** vs **monitoring (admin + user)** seperti **Bagian 1B**; saat ini UI khusus modul ini **belum** ada di ketiga jalur (hanya fondasi TRS / admin lain).

### 3.1 User Mobile (`Kelompok Sahabat Obor Mas`)

**Observasi:**

- Ada layar **`DataTrsScreen.tsx`** dan pola pemanggilan API untuk data TRS (pagination, filter).
- **Tidak** ditemukan (dari pencarian string) modul UI khusus “Target & Realisasi”, “persentase pencapaian”, atau konsumsi endpoint `/target` / `/realisasi` / join dashboard lama pada source `src` yang diindeks.

**File contoh yang menyentuh domain TRS:**

- `src/app/components/DataTrsScreen.tsx`
- Service terkait TRS (jika ada di `src/services/*trs*` — disarankan verifikasi glob `*Trs*` / `*trs*` saat implementasi).

### 3.2 Admin Web (`Web Admin`)

**Observasi:**

- Pola modul laporan baru-baru ini: services + tab (contoh: Data Kunjungan admin) — bisa jadi referensi pola navigasi/axios.
- **Tidak** ditemukan referensi `data_trs` / `target` / `realisasi` di hasil grep awal pada folder Web Admin (modul stakeholder **belum** ada).

### 3.3 Admin Mobile APK

**Observasi:**

- Di workspace yang sama tidak ada proyek terpisah bernama “Admin Mobile”; jika APK admin adalah **flavor/build** terpisah dari repo mobile yang sama, modul UI perlu dirancang dengan **pembatasan role** (middleware `admin` / `super_admin` di API sudah dipakai untuk fitur admin lain).

---

## Bagian 4 — Ringkasan gap (fakta, bukan rencana)

1. **Sumber realisasi:** Requirement meminta agregasi dari **`data_trs`**; service `RealisasiService` dan join `DashboardService` saat ini berbasis tabel **`realisasi`**, bukan query agregat dari `data_trs`.
2. **Bentuk target:** Requirement menyebut **satu nominal** per kelompok; skema `target` di DB adalah **wide table** + **`TGL_TGT`** sebagai bagian dari primary key.
3. **Kolom agregasi:** `data_trs` memiliki banyak kolom; requirement contoh memakai angka uang agregat tanpa menentukan **subset kolom** atau aturan (satu “total simpanan” vs beberapa produk).
4. **API publik:** Route CRUD/list target dan realisasi join **tidak** terdaftar di `routes/api.php` saat ini (sesuai plan penonaktifan).
5. **Multi-row `data_trs`:** Migrasi mengizinkan lebih dari satu baris per `NO_AGT` — aturan agregasi “per anggota lalu per kelompok” perlu selaras dengan aturan bisnis (sum semua baris vs baris terakhir per periode, dll.).
6. **Realtime:** Belum ada spesifikasi interval refresh atau mekanisme server push.
7. **Role & otorisasi:** Requirements memisahkan **admin (input + monitor)** vs **user (monitor)**; di repo **belum** ada endpoint/UI modul ini yang mengimplementasikan pemisahan itu — nanti harus selaras dengan pola `member_approved` vs `admin` (lihat **Bagian 1B**).

---

## Bagian 5 — Pertanyaan klarifikasi (untuk stakeholder / analis domain)

1. **Definisi nominal realisasi:** Untuk satu anggota, apakah yang dijumlahkan **hanya `STR_SP`**, atau kombinasi tertentu dari kolom `STR_*` / `PJM_BARU`, atau ada rumus resmi dari lembaga?
2. **Periode:** Apakah target dan realisasi **per periode** (bulan/tahun), **sepanjang waktu**, atau mengikuti **`TGL_LAP`** / **`TGL_TGT`** legacy?
3. **On Target:** Apakah definisi di **Bagian 1** (“sudah mencapai”) setara **≥ target**, atau harus **hanya sama persis** dengan target?
4. **Target vs tabel `target` existing:** Apakah modul baru harus **menyimpan** ke tabel `target` yang ada (dengan mapping kolom), atau **tabel/kolom baru** khusus modul monitoring ini?
5. **Sumber baca `data_trs`:** Untuk angka yang konsisten dengan laporan, apakah harus mengikuti **Firebird** (seperti `DataTrsController` sekarang) atau **MySQL** operasional setelah impor?
6. **Scope per role:** Untuk **user** mobile, apakah hanya melihat **kelompok sendiri**; untuk **admin** web/mobile, apakah **semua kelompok** atau subset (lihat **Bagian 1B**)?
7. **Admin Mobile:** Satu codebase dengan User Mobile (beda role/token) atau APK terpisah di luar workspace?

---

## Bagian 6 — Daftar file “untuk dilacak” saat development (checklist inventaris)

### Backend (Laravel) — inti domain

- [ ] `routes/api.php` — penambahan route baru / reaktivasi route (keputusan produk); pemetaan **`admin`** vs **`member_approved`** selaras **Bagian 1B**.
- [ ] `app/Models/DataTrs.php`, `app/Models/Target.php`, `app/Models/Realisasi.php`, `app/Models/KelSah.php`, `app/Models/Anggota.php`
- [ ] `app/Services/FirebirdService.php`
- [ ] `app/Http/Controllers/Api/DataTrsController.php`
- [ ] `app/Http/Resources/DataTrsResource.php`
- [ ] `app/Services/DashboardService.php` (legacy join; acuan perilaku lama bila masih relevan)
- [ ] `app/Services/TargetService.php`, `app/Services/RealisasiService.php`
- [ ] `app/Http/Controllers/Api/TargetController.php`, `RealisasiController.php` (jika ada di disk)
- [ ] Migrasi terkait `data_trs` / `target` / `realisasi`
- [ ] `plans/disable-target-realisasi-dashboard.md` (konteks historis route)
- [ ] `PROJECT_RULES.md`, `REQUIREMENTS.md` (selaras proyek)

### Frontend Web Admin

- [ ] `src/config/api.ts`, `src/services/api.ts` — pola base URL
- [ ] Router/layout sidebar — penempatan menu modul baru
- [ ] Pola modul existing (mis. data kunjungan admin): `src/app/components/tabs/*`, `src/services/*`

### Frontend User Mobile (dan kemungkinan Admin Mobile)

- [ ] `src/app/components/DataTrsScreen.tsx`
- [ ] Navigasi home / menu anggota
- [ ] `src/lib/apiClient` atau setara — auth Bearer

---

## Bagian 7 — Diagram alur data (mental model requirement)

```text
[Kelompok] --1:N-- [Anggota NO_AGT, ID_KS]
      |
      +-- [Target nominal]  (input admin, per kelompok, per aturan periode)
      |
      +-- agregasi <-- sum( transaksi/simpanan dari [data_trs] per anggota dalam kelompok )
                              |
                              v
                    [Total Realisasi] -> [%] -> [Status]
```

---

*Dokumen research ini dibuat untuk mendukung tahap PLAN berikutnya. **Bagian 1–1B** menyelaraskan requirements + dampak admin vs user. **Bagian 4–5** mencatat gap teknis domain/API dan pertanyaan lanjutan. Jawaban eksplisit untuk scope role (butir 6 Bagian 5) dan agregasi `data_trs` mengurangi risiko salah implementasi.*
