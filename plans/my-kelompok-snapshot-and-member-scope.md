# Plan: Snapshot kelompok saya + scope anggota/kel_sah (tanpa merusak flow API yang sudah jalan)

## Context

- User login terhubung ke master **`anggota`** lewat **`users.no_agt`** (diisi saat/setelah persetujuan admin, dengan validasi ke **`anggota.NO_AGT`**).
- **`anggota.ID_KS`** menunjuk **`kel_sah.ID_KEL`**. **`kel_sah`** menyimpan **`ID_KETUA`**, **`ID_SEK`**, **`ID_LO`**, **`ID_AO`** yang mereferensi **`ketua_ks`**, **`sekre_ks`**, **`data_lo`**, **`data_ao`** (FK sudah ada di migrasi).
- **`MemberScope::memberKelompokId`** sudah mengimplementasikan resolusi **`no_agt` → `ID_KS`**.
- Aplikasi mobile sudah memakai API existing untuk **simpan/list data** (mis. lewat **`created_by`**); **tidak boleh** perilaku endpoint yang sudah dipakai berubah mendadak tanpa koordinasi rilis.
- Secara bisnis: kelompok dipakai sebagai **pemisah** antar user; form **`NO_AGT`** idealnya **terfilter per kelompok** user — itu boleh ditambahkan **tanpa mengubah** pola simpan yang sudah jalan jika diterapkan bertahap / ter-switch.

## Goal

- **Fase 1 (wajib untuk “tidak merusak flow”):** Menambah **satu endpoint read-only baru** saja — tidak mengubah response atau aturan akses endpoint yang sudah ada.
- **Fase 2 (opsional, ketat):** Menyelaraskan **`kel-sah`** dan **`anggota`** untuk role **`user`** sesuai **`MemberScope`**, **hanya ketika flag konfigurasi diaktifkan** (default **mati**), agar client lama tetap berperilaku seperti hari ini sampai Anda siap.
- **Fase 3 (opsional, terpisah):** Validasi **`NO_AGT`** di form data (Store/Update) agar untuk role **`user`** nomor anggota harus **`ID_KS`** sama — **hanya setelah** app mengirim nilai yang benar; disarankan **flag terpisah** default **mati**.

## Prinsip non-breaking (wajib dipatuhi saat implementasi)

- **Tidak mengubah** kontrak JSON, status code, atau filter default endpoint yang sudah dipakai app **kecuali** flag fase 2/3 **explicit true** di environment.
- **Tidak menghapus** route atau field response yang sudah ada.
- Endpoint snapshot adalah **additive**; app lama yang tidak memanggilnya **tidak terpengaruh**.
- Pemeriksaan **`created_by`** untuk data modul (penghasilan, kunjungan, dll.) **tetap** seperti sekarang; plan ini **tidak** mengganti pola tersebut di fase 1.
- Dokumentasikan di `.env.example` nama flag dan artinya agar tim app tahu kapan menyalakan fase 2/3.

## Konfigurasi (baru)

- **File:** `config/obormas.php` (atau file config khusus jika lebih rapi), dengan key boolean (default **`false`**), dibaca dari **env**:
  - **`strict_member_kelompok_scope`** — mengaktifkan perilaku fase 2 (`kel-sah` index/show + penguncian `anggota` untuk role `user`).
  - **`strict_member_no_agt_same_kelompok`** — mengaktifkan validasi fase 3 pada Form Request yang relevan (daftar file di checklist fase 3).
- **File:** `.env.example` — tambahkan baris terkomentari untuk kedua env di atas.

## Detailed Specifications

### Fase 1 — Snapshot kelompok (selalu diimplementasikan sesuai plan ini)

#### Service

- **File baru:** `app/Services/MemberKelompokSnapshotService.php`
- **Class:** `MemberKelompokSnapshotService`
- **Method utama:** membangun payload untuk satu user:
  - **`$idKel = MemberScope::memberKelompokId($user)`**; jika `null` → exception domain → **HTTP 404** + pesan jelas (tanpa `no_agt` / tidak ada anggota / `ID_KS` kosong).
  - Muat **`Anggota`** untuk **`NO_AGT = memberNoAgt`**, **`KelSah`** untuk **`ID_KEL = $idKel`** (404 jika kelompok tidak ada).
  - Muat pengurus: **`KetuaKs`**, **`SekretarisKs`** (PK = nilai **`ID_SEK`** di `kel_sah`), **`DataLo`**, **`DataAo`** — boleh **`null`** jika kolom null atau baris tidak ada (jangan 500).
  - Paginate **`Anggota`** where **`ID_KS = $idKel`**, order **`NO_AGT` asc**, **`per_page`** default 15, max 100.

#### HTTP

- **File baru:** `app/Http/Controllers/Api/MemberKelompokController.php` — method **`show`**.
- **File baru:** `app/Http/Resources/MemberKelompokSnapshotResource.php` — shape: **`anggota_saya`**, **`kelompok`**, **`ketua`**, **`sekretaris`**, **`lo`**, **`ao`**, **`anggota_sekelompok`** (pagination + **`AnggotaResource`** untuk item).
- **File baru:** `app/Http/Requests/MemberKelompokSnapshotRequest.php` — **`per_page`** optional, 1–100.
- **Route:** `routes/api.php` dalam **`auth:sanctum`** + **`member_approved`**: **`GET me/kelompok`** → **`MemberKelompokController@show`**.

#### Tests fase 1

- **`tests/Feature/MemberKelompokSnapshotTest.php`**: 200 dengan struktur lengkap bila data ada; 404 bila tidak ada kelompok; tidak perlu menguji fase 2 di file ini kecuali ingin satu suite terpisah.

### Fase 2 — Scope `kel-sah` + `anggota` untuk role `user` (hanya jika `strict_member_kelompok_scope === true`)

- **`app/Http/Controllers/Api/KelSahController.php`**
  - **`index`:** jika flag true dan user restricted member, terapkan **`MemberScope::mergeKelSahFilterForMemberUser`**; jika merge `null`, **`MemberScope::emptyPaginator`**. Jika flag false, perilaku **identik** dengan sekarang (tanpa merge ini).
  - **`show`:** jika flag true, **`MemberScope::assertMemberOwnsKelompok`**; jika false, pertahankan **`OwnerScope::assertMemberUserCanAccessKelompok`** seperti kode saat ini.
- **`app/Http/Controllers/Api/AnggotaController.php`**
  - Jika flag true: **`index`** filter **`ID_KS`**; **`show`** assert **`ID_KS`**; **`store`/`update`/`destroy`/export** → **403** untuk role user. Jika flag false, pertahankan perilaku sekarang (**termasuk** no-op **`assertMemberCanAccessAnggota`** jika itu yang ada hari ini).

### Fase 3 — Validasi `NO_AGT` sekelompok (hanya jika `strict_member_no_agt_same_kelompok === true`)

- Tambahkan aturan validasi (Rule kustom atau closure terpusat di **`App\Support`** atau Form Request) pada Store/Update modul yang punya **`NO_AGT`** untuk role **`user`**: nilai harus ada di **`anggota`** dengan **`ID_KS`** = **`MemberScope::memberKelompokId`** (dan user punya kelompok).
- **Daftar file** yang perlu disentuh saat fase 3 diaktifkan (sesuaikan jika ada tambahan di project):  
  `StoreDataPenghasilanRequest`, `UpdateDataPenghasilanRequest`, `StoreDataKunjunganRequest`, `UpdateDataKunjunganRequest`, `StoreDataJlhKeluargaRequest`, `UpdateDataJlhKeluargaRequest`, dan modul lain yang **`NO_AGT`** wajib dan dipakai role user — **hanya** jika flag true.
- Default **false** agar request app yang sudah jalan **tidak** tiba-tiba 422.

## Risks / Catatan

- Menyalakan fase 2 mengubah siapa yang boleh lihat daftar/detail **`kel-sah`** dan master **`anggota`** — koordinasikan dengan rilis app.
- Menyalakan fase 3 dapat menolak payload lama jika **`NO_AGT`** tidak sekelompok — uji end-to-end sebelum production.
- Snapshot **`GET me/kelompok`**: user tanpa **`no_agt`** atau tanpa **`ID_KS`** dapat **404** — itu perilaku baru **hanya** untuk URL baru, bukan merusak endpoint lama.

## Implementation Checklist

### Fase 1 — Aman untuk flow existing (additive)

1. Tambah key **`strict_member_kelompok_scope`** dan **`strict_member_no_agt_same_kelompok`** di `config/obormas.php` (default `false`), plus komentar di `.env.example`.
2. Buat `app/Services/MemberKelompokSnapshotService.php` sesuai spesifikasi fase 1.
3. Buat `app/Http/Resources/MemberKelompokSnapshotResource.php` (reuse Resource existing untuk sub-entitas bila ada).
4. Buat `app/Http/Requests/MemberKelompokSnapshotRequest.php`.
5. Buat `app/Http/Controllers/Api/MemberKelompokController.php` dengan method `show`.
6. Daftarkan `GET me/kelompok` di `routes/api.php` (grup `member_approved`).
7. Buat `tests/Feature/MemberKelompokSnapshotTest.php` dan jalankan `php artisan test` untuk file tersebut.

### Fase 2 — Hanya setelah siap / flag true

8. Di `KelSahController::index` dan `show`, bungkus logika **`MemberScope`** / **`OwnerScope`** dengan pengecekan **`config('obormas.strict_member_kelompok_scope')`** sesuai spesifikasi fase 2.
9. Di `AnggotaController`, bungkus semua perubahan scope/penguncian dengan flag yang sama.
10. Tambah tes feature terpisah (atau perluas suite) yang **`config` di-set true** untuk memverifikasi perilaku ketat fase 2.

### Fase 3 — Hanya setelah app mengirim `NO_AGT` benar / flag true

11. Implementasikan Rule atau helper validasi “`NO_AGT` harus sekelompok” yang no-op jika flag **`strict_member_no_agt_same_kelompok`** false.
12. Terapkan pada Form Request Store/Update modul data yang memakai **`NO_AGT`** (daftar di atas; lengkapi jika ada modul lain).
13. Tes dengan flag true untuk minimal satu modul representatif.

### Penutup

14. Setelah fase 1: deploy API tanpa menyalakan env fase 2/3 → **flow app yang sudah ada tidak berubah**.  
15. Nyalakan fase 2 lalu fase 3 secara bertahap di staging/production sesuai kesiapan client.
