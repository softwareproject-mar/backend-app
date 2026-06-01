# Plan: Modul Target & Realisasi (Admin Web, Admin Mobile, User Mobile)

## Context

- Requirements produk tercatat di `plans/modul-target-realisasi-research.md` (Bagian 1–1B): target nominal per kelompok (input admin), realisasi dari agregasi **`data_trs`**, persentase, status On Target / Belum On Target, daftar field UI, visualisasi opsional, monitoring untuk admin dan user.
- **Baca TRS** saat ini: `GET /api/data-trs` memakai **`FirebirdService`** (koneksi `firebird_legacy` / pola Firebird), bukan skema `realisasi` join `target` lama. Tabel legacy `target` / `realisasi` (wide, `TGL_TGT`) **tidak** dipakai sebagai sumber utama modul baru ini.
- **Penyimpanan target baru**: tidak memakai baris wide tabel `target` existing; perlu **tabel aplikasi** sederhana (nominal per kelompok) pada koneksi yang sama dengan migrasi Laravel yang dipakai untuk tabel `users` / state aplikasi (biasanya **mysql**). Jika `DB_CONNECTION=firebird` saja tanpa MySQL, tim deploy harus menyediakan koneksi sekunder untuk tabel ini (dicatat di Risiko).
- **Role backend**: middleware `admin` mengizinkan `admin` dan `super_admin` (`EnsureUserIsAdmin`). User anggota: `member_approved` + `MemberScope::memberKelompokId()` untuk scope kelompok.

## Keputusan produk yang dikunci dalam plan ini

*(Jika stakeholder tidak setuju, revisi plan sebelum EXECUTE.)*

| Topik | Keputusan |
|--------|-----------|
| **Scope user** | User (`role` = `user`) hanya melihat **satu kelompok** miliknya (`MemberScope::memberKelompokId`). |
| **Scope admin** | `admin` dan `super_admin` melihat **semua kelompok** (ringkasan list). |
| **Input target** | Hanya **`admin`** dan **`super_admin`** (bukan user). |
| **On Target** | `realisasi >= nominal_target` (termasuk sama persis dan di atas). `Belum On Target` jika `realisasi < nominal_target`. |
| **Target nol / kosong** | Jika nominal target `null` atau `0`: `persentase_pencapaian` = `null` (atau `0` di JSON dengan dokumentasi); status = `Belum On Target` atau label khusus **`Tanpa target`** di response — pilih satu dan konsisten di resource (disarankan: **`Tanpa target`** + persentase `null`). |
| **Agregasi realisasi v1** | Jumlahkan nilai numerik kolom **`STR_SP`** saja per baris `DATA_TRS` (Firebird), lalu **SUM** semua baris untuk semua `NO_AGT` yang `anggota.ID_KS` = kelompok tersebut. Daftar kolom dapat dibuat **config** `config/obormas.php` key `target_realisasi_sum_columns` (array, default `['STR_SP']`) agar bisa diubah tanpa deploy logic. |
| **Periode** | **Tidak** ada filter periode di v1: agregasi **kumulatif** seluruh baris TRS per anggota dalam kelompok. Periode dapat ditambah di fase berikutnya. |
| **Realtime v1** | Tidak websocket: **pull** saat buka layar + tombol **refresh** manual; opsional `refetchInterval` ringan di web (mis. 60s) hanya jika disetujui di EXECUTE tanpa mengubah scope plan. |

## Goal

1. Menyediakan **API** aman: admin set/dapatkan target nominal per kelompok; admin & user dapatkan **ringkasan** (nama kelompok, target, realisasi, %, status, jumlah anggota) dengan scope role.
2. Menyediakan **Admin Web** — halaman modul dengan tabel + form edit target + progress bar (chart opsional fase sama jika waktu cukup).
3. Menyediakan **User Mobile** — layar monitoring (read-only) untuk kelompok user + progress bar.
4. Menyediakan **Admin Mobile** — dalam repo yang sama: layar monitoring + input target untuk role admin/super_admin (pola mirip `AdminDataKunjunganReport` / tab terpisah).

### Konsistensi UI / style (wajib saat EXECUTE)

- **Web Admin:** tampilan modul ini **mengikuti pola dan gaya** yang sudah dipakai aplikasi tersebut: layout halaman, tipografi, warna, spacing, tombol, tabel, kartu `Beranda`, dan pola serupa **`DataKunjunganPage`** / tab laporan admin (bukan tema baru terpisah). Gunakan komponen/utilitas CSS yang **sudah ada** di proyek Web Admin.
- **Mobile (user + admin):** layar baru **mengikuti gaya** `MainDashboard`, **`DataTrsScreen`**, **`AdminDataKunjunganReport`**, dan screen data lain (warna brand, header/back, form angka, tabel) — satu bahasa visual dengan APK yang sudah berjalan.
- **Label status** di UI: tampilkan teks **Indonesia** sesuai requirements (**On Target** / **Belum On Target** / **Tanpa target** jika relevan), dipetakan dari kode API (`on_target`, `below_target`, `no_target`).

## Detailed Specifications

### A. Backend — penyimpanan nominal (tabel **`target`** yang sudah ada)

| File | Perubahan |
|------|-----------|
| *(tanpa migrasi tabel baru)* | Nominal monitoring disimpan di tabel **`target`**: kunci **`ID_KS`** = kelompok, **`TGL_TGT`** = nilai sentinel `config('obormas.target_monitoring_tgl_tgt')` (default `__OBORMAS_MONITORING__`, override lewat env `TARGET_MONITORING_TGL_TGT`). Kolom nominal: **`STR_SP`** (double, selaras skema existing). |
| `config/obormas.php` | Key `target_monitoring_tgl_tgt` + `target_realisasi_sum_columns` (sudah ada). |

### B. Backend — model & service

| File | Perubahan |
|------|-----------|
| `app/Models/Target.php` | *(existing)* — dipakai `updateOrCreate` pada pasangan `(ID_KS, TGL_TGT)` sentinel. |
| `app/Services/TargetRealisasiMonitoringService.php` | **Baru**. Baca/tulis nominal lewat model **`Target`** (bukan tabel baru). |
| `app/Services/FirebirdService.php` | Tambah method publik mis. **`sumRealisasiNominalForKelompok(string $idKel, array $columnNames): string`** yang menjalankan SQL agregasi `DATA_TRS` join `anggota` pada `NO_AGT` dengan `WHERE anggota.ID_KS = ?` (perhatikan penamaan kolom Firebird / normalisasi sama seperti `normalizeDataTrsRow`). Return `'0.00'` jika tidak ada baris. |

### C. Backend — HTTP

| File | Perubahan |
|------|-----------|
| `app/Http/Requests/UpsertTargetNominalRequest.php` | Validasi `nominal_target` (numeric, min 0, max wajar). |
| `app/Http/Resources/TargetRealisasiSummaryResource.php` | Shape JSON: `id_kel`, `nama_kelompok`, `jumlah_anggota`, `nominal_target`, `total_realisasi`, `persentase_pencapaian` (float\|null), `status_target` (`on_target` \| `below_target` \| `no_target`). |
| `app/Http/Controllers/Api/Admin/TargetRealisasiController.php` | **Baru**. `index()` — list summary semua kelompok (admin middleware). `show(string $idKel)` — satu baris. `put`/`patch` — upsert nominal (admin only). |
| `app/Http/Controllers/Api/TargetRealisasiMeController.php` | **Baru** (atau satu method di controller di atas dengan route terpisah). `__invoke()` — untuk user: resolve `MemberScope::memberKelompokId`, jika null return 404 atau empty payload sesuai konvensi API proyek; else return `TargetRealisasiSummaryResource`. |
| `routes/api.php` | Dalam grup `middleware('admin')`: `GET admin/target-realisasi/summary`, `GET admin/target-realisasi/kelompok/{id_kel}`, `PUT admin/target-realisasi/kelompok/{id_kel}` (body JSON `{ "nominal_target": number }`). Dalam grup `member_approved`: `GET target-realisasi/me` (hanya GET). |

### D. Backend — testing & dokumen

| File | Perubahan |
|------|-----------|
| `tests/Feature/TargetRealisasiApiTest.php` | **Baru**: admin dapat GET list + PUT target; user (`member_approved`) dapat GET `/target-realisasi/me` dengan mock/fixture; user dapat **403** pada PUT admin; guest **401**. |
| `API_DOCUMENTATION.md` atau `API_DOCUMENTATION_COMPLETE.md` | Entri endpoint baru (method, path, role, contoh response). |

### E. Admin Web

| File | Perubahan |
|------|-----------|
| `src/services/targetRealisasiAdminService.ts` | **Baru**: `fetchTargetRealisasiSummary()`, `fetchTargetRealisasiByKelompok(id)`, `putNominalTarget(id, nominal)`. |
| `src/app/pages/TargetRealisasiPage.tsx` | **Baru**: tabel ringkasan; kolom sesuai requirements; dialog atau inline edit nominal (admin); progress bar per baris; tombol refresh. |
| `src/app/routes.tsx` | Route `target-realisasi` di dalam `ProtectedRoute` admin (duplikat path untuk basename sama seperti `data-kunjungan`). |
| `src/app/pages/Beranda.tsx` | Kartu modul baru mengarah ke `target-realisasi`. |

### F. User Mobile + Admin Mobile (satu repo)

| File | Perubahan |
|------|-----------|
| `src/services/targetRealisasiService.ts` | **Baru**: `getTargetRealisasiMe()`, untuk admin juga `getAdminTargetRealisasiSummary()`, `putAdminTargetNominal(idKel, nominal)` memanggil path `/api/admin/...` (sesuai prefix `getApiPrefix` mobile). |
| `src/app/components/TargetRealisasiMemberScreen.tsx` | **Baru**: read-only monitoring + progress bar; panggil `getTargetRealisasiMe`. |
| `src/app/components/TargetRealisasiAdminScreen.tsx` | **Baru**: list + edit nominal (reuse pola form angka); hanya render jika role admin/super_admin (deteksi dari context auth yang sudah ada di app). |
| `src/app/components/MainDashboard.tsx` | Menu item baru untuk user (monitoring); menu admin untuk list + edit (mirip pemisahan `AdminDataKunjunganReport`). |

### G. Visualisasi lanjutan (opsional dalam scope plan yang sama)

- **Progress bar**: **wajib** di plan ini untuk Web + Mobile (ringan, CSS).
- **Bar chart / line chart**: jika library chart **sudah** terpasang di Web Admin / Mobile, tambahkan **satu** chart (mis. bar per kelompok di admin web); jika belum ada dependency, **skip** chart di EXECUTE dan catat di Risiko sebagai follow-up — **tidak** memblokir endpoint dan tabel.

## Implementation Checklist (urut eksekusi)

1. Tambah key `target_monitoring_tgl_tgt` + `target_realisasi_sum_columns` di `config/obormas.php` (sudah).
2. ~~Buat migrasi `simpanan_target_kelompok`~~ *(dibatalkan — pakai tabel `target`)*.
3. ~~Model `SimpananTargetKelompok`~~ — gunakan `App\Models\Target`.
4. Implementasikan `FirebirdService::sumRealisasiNominalForKelompok` (atau nama final yang sama di service) dengan SQL join `DATA_TRS` ↔ `anggota` filter `ID_KS`, menjumlahkan kolom dari config.
5. Buat `app/Services/TargetRealisasiMonitoringService.php` dengan semua method di spesifikasi B.
6. Buat `TargetRealisasiSummaryResource.php`.
7. Buat `UpsertTargetNominalRequest.php` (boleh satu class saja).
8. Buat `app/Http/Controllers/Api/Admin/TargetRealisasiController.php` dengan `index`, `show`, `update`.
9. Buat `app/Http/Controllers/Api/TargetRealisasiMeController.php` dengan `__invoke`.
10. Daftarkan route di `routes/api.php` sesuasi blok middleware **admin** vs **member_approved**.
11. Tulis `tests/Feature/TargetRealisasiApiTest.php` dan pastikan lulus (`php artisan test --filter=TargetRealisasi`).
12. Update dokumentasi API (`API_DOCUMENTATION.md` atau lengkap).
13. **Web Admin**: tambah `targetRealisasiAdminService.ts`.
14. **Web Admin**: tambah `TargetRealisasiPage.tsx` (tabel + edit + progress) dengan **style konsisten** halaman/komponen existing (lihat subbagian *Konsistensi UI* di atas).
15. **Web Admin**: registrasi route di `routes.tsx` dan kartu di `Beranda.tsx` (ikon/deskripsi selaras kartu modul lain).
16. **Mobile**: tambah `targetRealisasiService.ts`.
17. **Mobile**: tambah `TargetRealisasiMemberScreen.tsx` dan wiring menu user (**gaya** konsisten `DataTrsScreen` / dashboard).
18. **Mobile**: tambah `TargetRealisasiAdminScreen.tsx` dan wiring menu admin (**gaya** konsisten `AdminDataKunjunganReport` / form admin existing).
19. **Mobile**: sesuaikan `MainDashboard.tsx` untuk tab/menu indeks baru.
20. Verifikasi manual: admin web PUT nominal → user mobile GET me menampilkan angka yang konsisten; angka realisasi selaras dengan sample `data_trs` di Firebird.

## Risks / Catatan

- **Hybrid DB**: Jika `anggota` / `DATA_TRS` di Firebird dan tabel target di MySQL, tidak ada transaksi silang; konsistensi eventual OK untuk modul monitoring.
- **Performa**: `SUM` per kelompok untuk banyak kelompok di `index` admin bisa berat — v1 boleh **N+1 query** dengan caching per request atau optimasi batch di fase 2 jika terukur lambat.
- **Kolom agregasi**: Default `STR_SP` bisa salah secara bisnis; mitigasi: config + dokumentasi + revisi satu baris config saat domain memutuskan rumus baru.
- **Chart**: bergantung dependency existing; otherwise deliver tanpa chart tanpa mengubah checklist inti endpoint + UI tabel + progress bar.

---

**Syarat lanjut ke EXECUTE:** Anda menyetujui plan ini secara eksplisit (termasuk keputusan produk di tabel “Keputusan produk”) dan mengirim perintah **`ENTER EXECUTE MODE`**. Jika ada perubahan keputusan (mis. hanya `super_admin` yang boleh edit target), revisi plan ini dulu.
