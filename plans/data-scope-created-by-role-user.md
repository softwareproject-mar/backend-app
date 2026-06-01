# Plan: Isolasi data role `user` lewat `created_by` (tanpa `users.no_agt`)

## Context

- Tabel `users` di lingkungan produksi **tidak** memiliki kolom `no_anggota` / `no_agt`; kebijakan produk: **tidak** menautkan akun user ke nomor anggota di tabel `users`.
- Kebutuhan: **`role === 'user'`** hanya melihat (dan mengekspor) data yang **mereka input** → identitas pemisah = **`created_by`** (`users.id`) pada **baris tabel bisnis**.
- **`admin`** dan **`super_admin`**: **tanpa** filter `created_by` → melihat **semua** data (termasuk export Excel/PDF agregat).
- Saat ini beberapa controller memakai **`MemberScope::mergeNoAgtFilterForMemberUser`** / assert `NO_AGT` — **tidak selaras** dengan kebijakan baru dan harus **diganti** untuk modul yang masuk scope rencana ini.

## Catatan skema (penting untuk interpretasi `created_by`)

- **`data_penghasilan`**, **`data_jlh_keluarga`**, **`data_trs`**: primary key = **`NO_AGT`** → **maksimal satu baris per nomor anggota** per tabel. `created_by` = user yang **pertama membuat / memiliki** baris itu untuk keperluan isolasi; update oleh user lain perlu aturan otorisasi (lihat bawah).
- **`data_kunjungan`**: primary key **`NO_URT`** (auto-increment) → banyak baris; `created_by` per kunjungan sangat natural.

## Goal

1. Tambah kolom **`created_by`** (nullable, FK ke `users.id`, terindeks) pada tabel bisnis yang dipakai anggota di app, minimal: **`data_penghasilan`**, **`data_jlh_keluarga`**, **`data_trs`**, **`data_kunjungan`**.
2. Untuk **`role === 'user'`**: **index**, **show**, **update**, **destroy**, **export Excel/PDF** hanya untuk baris dengan **`created_by = auth()->id()`**; baris dengan `created_by` **NULL** (data lama) **tidak** tampil untuk user (kecuali kebijakan backfill terpisah).
3. Untuk **`admin`** / **`super_admin`**: perilaku **tetap global** (filter hanya dari query request seperti sekarang, tanpa paksaan `created_by`).
4. **Hapus / nonaktifkan** untuk modul di atas pemakaian **`mergeNoAgtFilterForMemberUser`** dan assert kepemilikan berbasis **`NO_AGT` vs users.no_agt`** pada controller terkait (ganti ke assert **`created_by`**).
5. **Referensi & edge case** (kelompok sahabat, LO/AO, anggota autocomplete): diberi perilaku konsisten agar user tidak mendapat “semua master” (lihat bagian terpisah).

## Detailed Specifications

### 1. Helper / concern (DRY)

- **File**: `app/Support/MemberScope.php` **atau** file baru `app/Support/OwnerScope.php` (pilih **satu** nama agar tidak membingungkan; disarankan **`OwnerScope`** karena tidak lagi berpusat pada “member/no_agt”).
- Fungsi statis minimal:
  - `isRestrictedMemberUser(?User $user): bool` — boleh reuse dari `MemberScope` atau duplikasi minimal (satu sumber kebenaran).
  - `mergeCreatedByFilterForMemberUser(?User $user, array $filters): ?array` — jika restricted: set `$filters['created_by'] = $user->id`; jika bukan restricted: kembalikan `$filters` tanpa ubah.
  - `assertMemberOwnsCreatedBy(?User $user, ?int $createdById): void` — jika restricted: wajib `$createdById === $user->id`, selain itu abort 403 (atau 404 jika ingin menyembunyikan eksistensi).
- **Pertahankan** `MemberScope::emptyPaginator` atau pindahkan ke `OwnerScope` jika `MemberScope` dikurangi peran.

### 2. Database migrations

- **File baru** (satu migration atau per tabel, sesuai konvensi project): tambah kolom:
  - `created_by` — `unsignedBigInteger`, **nullable**, **indexed**
  - foreign key ke `users(id)` **opsional** jika DB/engine mempersulit; minimal index untuk performa query filter
- Tabel yang **wajib** dalam cakupan MVP sesuai permintaan user:
  - `data_penghasilan`
  - `data_jlh_keluarga`
  - `data_trs`
  - `data_kunjungan`
- **Tidak** menambah `no_agt` pada `users` dalam rencana ini.

### 3. Model Eloquent

- **File**: `app/Models/DataPenghasilan.php`, `DataJlhKeluarga.php`, `DataTrs.php`, `DataKunjungan.php`
  - Tambah `created_by` ke `$fillable` (atau set hanya lewat service agar tidak mass-assignment liar — pilih satu pola konsisten).
  - Relasi opsional: `creator()` belongsTo `User`.

### 4. Service layer — `applyFilters` / `paginate` / `listForExport`

Untuk setiap service terkait, pastikan filter `created_by` diterapkan jika ada di array `$filters`:

- `app/Services/DataPenghasilanService.php` — `applyFilters`, `paginate`, `listForExport`
- `app/Services/DataJlhKeluargaService.php` — sama
- `app/Services/DataTrsService.php` — sama
- `app/Services/DataKunjunganService.php` — sama

### 5. Controller layer — ganti dari `no_agt` user ke `created_by`

**File & method** (polanya sama per resource):

- `app/Http/Controllers/Api/DataPenghasilanController.php`  
  - `index`: ganti `MemberScope::mergeNoAgtFilterForMemberUser` → `OwnerScope::mergeCreatedByFilterForMemberUser` (atau setara); jika hasil null tidak terjadi untuk user dengan id, tetap handle paginator kosong hanya jika dibutuhkan aturan bisnis.
  - `store`: untuk **semua** role yang create lewat API, set `created_by = $request->user()->id` pada payload ke service (bukan hanya user); **jangan** ambil `created_by` dari body client untuk role user.
  - `show`, `update`, `destroy`: setelah load model, `assertMemberOwnsCreatedBy` untuk role user; admin/super_admin lewat.
  - `exportExcel`, `exportPdf`: terapkan **filter `created_by` yang sama** seperti `index` (hanya data milik user untuk role user).

- `app/Http/Controllers/Api/DataJlhKeluargaController.php` — pola identik.

- `app/Http/Controllers/Api/DataTrsController.php` — pola identik (termasuk export).

- `app/Http/Controllers/Api/DataKunjunganController.php`  
  - Hapus / ganti logika `applyMemberKunjunganPayload` yang memaksa `NO_AGT` / kelompok dari `users.no_agt` / `memberKelompokId`.  
  - Ganti dengan: untuk role user, **validasi bisnis** yang masih diperlukan (mis. field wajib) **tanpa** mengambil `no_agt` dari tabel `users`.  
  - `store` / `update`: set `created_by` pada create; untuk update, assert pemilik `created_by` untuk user.  
  - `index` / export: merge filter `created_by` untuk user.

### 6. Form Request

- Pastikan **`created_by`** tidak bisa di-spoof dari klien untuk role user: **strip** dari `validated()` sebelum create/update jika dikirim, atau tidak pernah ada di rules untuk public.

### 7. Resource API (response JSON)

- **Opsional** (nice): expose `created_by` di resource jika UI perlu; tidak wajib untuk MVP.

### 8. Master / referensi (agar tidak “bocor semua data”)

Ini **di luar** “satu baris = satu input” tapi diperlukan agar UX aman:

- **`KelSahController` / `KelSahService`**: untuk role user, **ganti** `mergeKelSahFilterForMemberUser` (berbasis anggota) dengan filter: mis. **`ID_KEL` IN** (distinct `ID_KEL_SAH` dari `data_kunjungan` where `created_by = auth()->id()`). Jika kosong → paginator kosong. Admin tetap tanpa paksaan ini.
- **`AnggotaController`** (jika role user boleh akses index untuk autocomplete): batasi ke **`NO_AGT IN`** gabungan distinct dari keempat tabel bisnis di atas where `created_by = auth()->id()`, atau **403** untuk index anggota bagi user jika produk tidak membutuhkan list penuh (pilih satu di implementasi; default rencana: **batasi subset** agar autocomplete tidak menampilkan semua anggota).
- **`DataLoController` / `DataAoController`** (jika dipakai autocomplete kunjungan): pola serupa — batasi ke ID yang pernah muncul di `data_kunjungan` milik user, atau dokumentasikan pengecualian jika hanya admin yang memanggil.

### 9. Frontend

- **Mobile / Web Admin**: jika API sudah benar, **tidak wajib** ubah; opsional sembunyikan filter global yang membingungkan untuk user.
- **Verifikasi**: login sebagai user → list & export hanya baris sendiri; login admin → semua.

### 10. `MemberScope` legacy

- Setelah modul di atas pindah ke `created_by`, **bersihkan** pemanggilan `mergeNoAgtFilterForMemberUser` / assert `NO_AGT` terhadap user pada controller-controller tersebut agar tidak dobel logika.
- Biarkan `MemberScope` jika masih dipakai modul lain yang **belum** dimigrasi dalam fase ini (dokumentasikan sisa utang teknis).

## Implementation Checklist (urut, atomik)

1. Buat migration menambah kolom `created_by` (nullable, indexed, FK ke `users` jika memungkinkan) pada `data_penghasilan`, `data_jlh_keluarga`, `data_trs`, `data_kunjungan`.
2. Jalankan migration di environment dev; pastikan tidak bentrok dengan Firebird/MySQL yang dipakai project.
3. Tambah `OwnerScope` (atau perluas helper yang disepakati) berisi `mergeCreatedByFilterForMemberUser` dan `assertMemberOwnsCreatedBy` (+ reuse `isRestrictedMemberUser` atau definisi role terpusat).
4. Update model `DataPenghasilan`, `DataJlhKeluarga`, `DataTrs`, `DataKunjungan` untuk `created_by` (+ relasi opsional).
5. Update `DataPenghasilanService::applyFilters` (+ `paginate`/`listForExport` memakai filter) agar menghormati key `created_by`.
6. Update `DataPenghasilanController`: index/show/update/destroy/store/export — ganti logika `MemberScope` no_agt → `created_by`; set `created_by` saat store.
7. Ulangi langkah 5–6 untuk `DataJlhKeluargaService` + `DataJlhKeluargaController`.
8. Ulangi langkah 5–6 untuk `DataTrsService` + `DataTrsController` (termasuk export).
9. Update `DataKunjunganService::applyFilters` (+ export path) untuk `created_by`.
10. Update `DataKunjunganController`: hapus ketergantungan payload pada `users.no_agt` / `memberKelompokId`; terapkan `created_by` pada store/update; index/export/show/update/destroy konsisten.
11. Update `KelSahController` + `KelSahService` (atau filter di controller) untuk role user: filter kelompok dari `data_kunjungan` milik user; admin tidak terfilter paksa.
12. Tentukan dan implementasikan kebijakan `AnggotaController` (+ LO/AO jika perlu) untuk role user (subset dari data milik user atau 403).
13. Audit Form Request terkait agar `created_by` tidak bisa di-spoof dari klien.
14. Uji manual: user A hanya lihat & export barisnya; user B sama; admin lihat semua; baris lama `created_by` null tidak muncul untuk user.
15. Dokumentasikan di README atau komentar singkat: data lama perlu backfill `created_by` jika ingin muncul untuk user.

## Risks / Catatan

- **PK `NO_AGT`** pada tiga tabel: dua user **tidak** bisa punya dua baris berbeda untuk **nomor anggota yang sama**; konflik create/update harus ditangani (422/409 atau hanya admin yang boleh mengubah baris “orang lain”).
- **Kunjungan** tanpa penautan `users.no_agt`: user bisa mengisi `NO_AGT` / kelompok di form — **validasi bisnis** (format, exists) tetap diperlukan; **bukan** “paksa dari profil user”.
- **Performa** filter kelompok via subquery: pertimbangkan index pada `data_kunjungan(created_by, ID_KEL_SAH)`.
