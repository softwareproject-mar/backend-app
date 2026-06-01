# Plan: Isolasi data role `user` + tab Riwayat/Profil + perbaikan autocomplete Kunjungan

## Context

- Aplikasi mobile (`Kelompok Sahabat Obor Mas`) membatasi menu untuk `userRole === "user"` ke modul: Penghasilan, Jumlah Keluarga, Kunjungan, Data Transaksi; tab **Riwayat** dan **Profil** disembunyikan di `MainDashboard.tsx`.
- Kebutuhan bisnis: untuk role **`user`**, data yang ditampilkan dan yang boleh diinput **hanya milik anggota yang terkait akun itu**, tidak bercampur data pengguna lain. Admin / super_admin tetap mengakses data agregat seperti sekarang (sesuai endpoint yang ada).
- Tabel bisnis (`data_penghasilan`, `data_jlh_keluarga`, `data_trs`, `data_kunjungan`) memakai **`NO_AGT`** sebagai kunci relasi ke anggota; tabel **`anggota`** punya **`ID_KS`** (identitas kelompok yang dipakai anggota). Tabel **`kel_sah`** memakai **`ID_KEL`** sebagai primary key — diasumsikan **`ID_KS` pada `anggota` merujuk ke `ID_KEL` pada `kel_sah`** (sama domain "kelompok sahabat"); jika di data nyata beda semantik, checklist migrasi/logika perlu disesuaikan sebelum deploy.
- Tabel **`users`** saat ini **tidak** punya kolom penautan ke `NO_AGT`; `UserResource` tidak mengembalikan nomor anggota.
- **Riwayat aktivitas** di backend sudah memfilter non-admin dengan `where('user_id', auth()->id())` — tidak perlu diubah untuk tujuan isolasi log.
- Bug UI: `getKelSahList` mengembalikan `{ items, meta }` tetapi `DataKunjunganContent.tsx` memakai `res.data`; dropdown ID Kelompok kosong. Selain itu JSX memakai `ID_KEL` / `NAMA_KEL` padahal mapper service menghasilkan `KelSahItem` dengan `idKelompok` / `namaKelompok`.

## Goal

1. **Backend**: Untuk `role === 'user'`, semua baca/tulis data modul anggota (penghasilan, jumlah keluarga, transaksi, kunjungan) dan daftar kelompok untuk keperluan relevan **dikunci** ke **`users.no_agt`** (dan turunan `ID_KS` dari baris `anggota`). Klien **tidak boleh** mengganti cakupan dengan query parameter `NO_AGT` / filter lain.
2. **Admin / Super Admin**: Tetap perilaku sekarang (tanpa paksaan filter anggota), termasuk export.
3. **Mobile**: Tampilkan tab **Riwayat** dan **Profil** juga untuk role `user`; hapus logika yang memaksa tab kembali ke home saat user membuka riwayat/profil.
4. **Mobile**: Perbaiki autocomplete **ID Kelompok Sahabat** di form kunjungan agar memakai struktur return API yang benar.

## Detailed Specifications

### 1. Database & model `User`

- **File baru**: migration, mis. `database/migrations/YYYY_MM_DD_HHMMSS_add_no_agt_to_users_table.php`
  - Tambah kolom `no_agt` nullable, `string(15)`, index; optional `foreignId` ke `anggota(NO_AGT)` jika skema DB mendukung (Firebird via Laravel bisa beda — gunakan yang kompatibel dengan project; minimal kolom + index).
- **File**: `app/Models/User.php` — tambah `no_agt` ke `$fillable` (dan cast string jika perlu).

### 2. API user: expose & kelola `no_agt`

- **File**: `app/Http/Resources/UserResource.php` — tambahkan key `no_agt` (nullable string) di `toArray`.
- **File**: `app/Http/Controllers/Api/UserApprovalController.php` — method `approve(Request $request, int $id)`:
  - Validasi optional `no_agt` di body: `nullable`, `string`, `max:15`, **exists:anggota,NO_AGT** (atau rule setara) ketika `user->role === 'user'` dan kebijakan bisnis mewajibkan penautan saat approve; jika lebih fleksibel, izinkan approve tanpa `no_agt` lalu admin mengisi lewat PATCH (lihat bawah).
- **File**: `app/Http/Controllers/Api/SuperAdmin/UserManagementController.php` — method `update`:
  - Tambah validasi `sometimes` untuk `no_agt` (nullable string, exists di `anggota`) agar super admin bisa mengisi/mengosongkan penautan.
- **Opsional (admin non-super)**: Jika ada controller manajemen user untuk role `admin`, samakan kemampuan PATCH `no_agt` agar operasional tidak bergantung satu-satunya pada super_admin.

### 3. Helper cakupan anggota (DRY)

- **File baru**: mis. `app/Support/MemberScope.php` (atau trait di `app/Http/Controllers/Concerns/`) dengan fungsi statis atau injeksi kecil:
  - `isRestrictedMemberUser(?User $user): bool` — true jika `$user && $user->role === 'user'`.
  - `memberNoAgt(User $user): ?string` — return `trim($user->no_agt)` atau null.
  - `memberKelompokId(User $user): ?string` — `Anggota::query()->where('NO_AGT', $noAgt)->value('ID_KS')` (null jika tidak ada anggota / no_agt kosong).

### 4. Service layer: filter paksa untuk role `user`

Untuk setiap service di bawah, tambahkan parameter opsional `?User $actor = null` **atau** panggil helper dari controller yang mem-pass `$request->user()` — pilih satu pola konsisten di seluruh codebase (disarankan: **controller** memanggil helper, lalu **merge `$filters`** sebelum `paginate`, agin service tetap tipis).

**Polisi filter index / list / export:**

- Jika `isRestrictedMemberUser($user)`:
  - Jika `memberNoAgt($user)` kosong: return **paginator kosong** (total 0) untuk GET index & export, **bukan** semua data.
  - Jika ada `no_agt`: **paksa** `$filters['NO_AGT'] = $that` (abaikan / timpa nilai `NO_AGT` dari query request).

**File & method** (setidaknya):

- `App\Services\DataPenghasilanService` — `paginate`, `listForExport`
- `App\Services\DataJlhKeluargaService` — `paginate`, `listForExport`
- `App\Services\DataTrsService` — `paginate`, `listForExport`
- `App\Services\DataKunjunganService` — `paginate`, `listForExport`

**File & controller** yang memanggil service di atas:

- `DataPenghasilanController` — `index`, `exportExcel`, `exportPdf`, `show`, `update`, `destroy` (jika ada): setelah load model, untuk user terbatas pastikan `NO_AGT` record === `memberNoAgt` atau 404/403.
- `DataJlhKeluargaController` — sama.
- `DataTrsController` — sama (primary key `NO_AGT` string).
- `DataKunjunganController` — sama (primary key numerik `NO_URT`; cek kolom `NO_AGT` pada record).

### 5. `KelSah` untuk role `user`

- **File**: `App\Services\KelSahService` — extend `applyFilters` atau cabang di `paginate`/`listForExport`:
  - Jika user terbatas dan `memberKelompokId($user)` terisi: paksa `where('ID_KEL', $id)` (atau `whereIn` jika nanti satu anggota punya banyak kelompok — tidak dalam scope MVP).
  - Jika user terbatas tanpa kelompok ter-resolve: paginator kosong.
- **File**: `KelSahController` — `index`, `exportExcel`, `exportPdf`, `show`, `update`, `destroy`: untuk role `user`, **larangan** ubah/hapus kelompok orang lain (403/404); untuk index/export terapkan filter di atas.

### 6. Mutasi (store / update) — cegah spoofing

- **Form Request / Controller** untuk `Store*` / `Update*` pada: DataPenghasilan, DataJlhKeluarga, DataTrs, DataKunjungan:
  - Jika `isRestrictedMemberUser`: set input tervalidasi `NO_AGT` = `memberNoAgt($user)` (override request), atau tolak 422 jika `no_agt` user kosong dengan pesan jelas (bahasa Indonesia).
- **DataKunjungan**: untuk user terbatas, validasi tambahan `ID_KEL_SAH` harus sama dengan `memberKelompokId($user)` (atau nullable jika bisnis mengizinkan tanpa kelompok — default: wajib cocok dengan anggota).

### 7. Mobile — `MainDashboard.tsx`

- Hapus conditional `userRole !== "user"` pada tombol **Riwayat** dan **Profil** (dua blok button).
- Edit `useEffect` yang bergantung pada `[userRole, activeMenu, activeTab]`: **pertahankan** reset `activeMenu` jika user membuka menu admin (id 1–6, 8); **hapus** blok yang memaksa `setActiveTab("home")` ketika `activeTab === "history" || activeTab === "profile"`.

### 8. Mobile — `DataKunjunganContent.tsx`

- Pada handler autocomplete `getKelSahList`, ganti `setKelOptions(res.data || [])` menjadi memakai array yang benar dari hasil fungsi (`res.items` sesuai `getKelSahList` di `kelSahService.ts`).
- Sesuaikan tipe state `kelOptions` ke bentuk yang dipakai di render (`KelSahItem` atau properti konsisten).
- Di `map` dropdown, ganti akses field ke **`row.idKelompok` / `row.namaKelompok`** (atau destructuring setara), dan di `onClick` set `setFormIdKelompok(row.idKelompok)` serta label tampilan yang sesuai.

### 9. Mobile — auth store / tipe user (opsional tapi disarankan)

- **File**: `src/state/authStore.tsx` (atau tipe user terpusat): tambah field optional `no_agt?: string | null` agar selaras dengan `UserResource` setelah backend deploy.

### 10. Web Admin (jika dipakai untuk approve / edit user)

- Layar approve / edit user: field input **Nomor anggota (`no_agt`)** dengan validasi client-side format dasar; kirim ke API `approve` atau `PATCH users/{id}` sesuai perubahan backend.
- Path file spesifik mengikuti struktur Web Admin yang ada (grep `approve` / `users`).

## Implementation Checklist (urut, atomik)

1. Buat migration menambah kolom `users.no_agt` (nullable, indexed); jalankan migrate di environment dev.
2. Update `app/Models/User.php` (`fillable` + dokumentasi singkat kolom).
3. Update `app/Http/Resources/UserResource.php` menambahkan `no_agt`.
4. Buat `app/Support/MemberScope.php` (atau concern setara) berisi `isRestrictedMemberUser`, `memberNoAgt`, `memberKelompokId`.
5. Update `UserApprovalController::approve` agar menerima body optional `no_agt` dengan validasi `exists:anggota,NO_AGT` ketika diisi; persist ke user yang di-approve.
6. Update `UserManagementController::update` agar mengizinkan PATCH `no_agt` dengan validasi exists di `anggota`.
7. Update `DataPenghasilanController` (index + export + show/update/destroy): terapkan merge filter / otorisasi record untuk role `user` memakai helper.
8. Update `DataPenghasilanService` hanya jika memilih meneruskan `$user` ke dalam service; jika tidak, cukup controller — pastikan **satu pola** untuk semua modul berikut.
9. Update `DataJlhKeluargaController` + service (pola sama dengan langkah 7–8).
10. Update `DataTrsController` + service (pola sama; perhatikan PK string `NO_AGT`).
11. Update `DataKunjunganController` + service (pola sama; otorisasi by `NO_AGT` pada record).
12. Update `Store*` / `Update*` requests atau controller untuk override `NO_AGT` pada role `user` dan validasi `ID_KEL_SAH` vs `memberKelompokId` untuk kunjungan.
13. Update `KelSahService` + `KelSahController` untuk filter index/export dan otorisasi show/update/destroy bagi role `user`.
14. Uji manual dengan token `user` (dengan `no_agt` terisi): list & export hanya satu `NO_AGT`; percobaan akses ID lain harus gagal.
15. Uji manual dengan token `admin`/`super_admin`: perilaku tidak regresi (tetap lihat semua sesuai filter query).
16. Edit `MainDashboard.tsx`: tampilkan Riwayat & Profil untuk `user`; sesuaikan `useEffect` tanpa memaksa keluar dari tab history/profile.
17. Edit `DataKunjunganContent.tsx`: perbaiki sumber array autocomplete (`items`) dan nama field (`idKelompok`, `namaKelompok`).
18. (Opsional) Perbarui tipe user di `authStore` dan parsing login/me response agar menyimpan `no_agt`.
19. (Opsional) Web Admin: form `no_agt` pada alur approve / edit user + wiring API.
20. Regression singkat: login `user` tanpa `no_agt` — pastikan list kosong dan pesan/UX tidak membingungkan (pertimbangkan toast di mobile jika API meta kosong).

## Risks / Catatan

- **ID_KS vs ID_KEL**: Jika di produksi `ID_KS` bukan sama dengan `ID_KEL`, filter kelompok untuk user akan salah — verifikasi dengan sample DB sebelum rilis.
- **User lama**: Semua user `role=user` perlu diisi `no_agt` oleh admin; sampai diisi, modul data akan kosong — komunikasikan ke operator.
- **Firebird / FK**: Migration foreign key ke `anggota` bisa tidak didukung sama seperti MySQL; gunakan kolom saja jika perlu.
- **Cap v2 controllers**: Jika route API memakai `*Controllerv2`, pastikan perubahan diterapkan pada path yang benar atau hapus duplikasi agar tidak ada endpoint tanpa scope.
