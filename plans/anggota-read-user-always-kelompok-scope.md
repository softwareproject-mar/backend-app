# Plan: Role `user` — baca `GET /anggota` & `show` selalu terbatas kelompok (tanpa tergantung env strict)

## Context

- Modul **Anggota** di app mobile (**`MemberKelompokReadOnlyScreen`**) memanggil **`GET /anggota`** tanpa `ID_KS` di klien; pembatasan kelompok sepenuhnya di server.
- Saat ini **`AnggotaController::index`** dan **`assertMemberCanAccessAnggota`** (dipakai **`show`**, **`update`**, **`destroy`**) hanya menerapkan filter/cek kelompok jika **`config('obormas.strict_member_kelompok_scope')`** benar.
- Jika env **`STRICT_MEMBER_KELOMPOK_SCOPE`** tidak aktif, **role `user`** secara teoritis bisa melihat **semua** master anggota lewat **`index`** dan **`show`** — tidak memenuhi kebutuhan “hanya kelompoknya saja” untuk modul tersebut.
- Opsi **tanpa ubah app**: tetap wajibkan env — rentan human error. Rencana ini memilih **pembatasan baca anggota untuk `role user` selalu aktif**, sementara **blokir CRUD/export** master anggota untuk member **tetap** mengikuti flag **`strict_member_kelompok_scope`** (perilaku yang sudah ada).

## Goal

1. **`GET /api/anggota`** (index): untuk **`MemberScope::isRestrictedMemberUser`**, **selalu** set filter **`ID_KS`** dari **`MemberScope::memberKelompokId`** (atau paginator kosong jika tidak bisa resolve), **tanpa** mengecek **`strict_member_kelompok_scope`**.
2. **`GET /api/anggota/{id}`** (show) dan pemanggilan **`assertMemberCanAccessAnggota`** lainnya: untuk **restricted member**, **selalu** verifikasi record berada di kelompok user yang sama, **tanpa** mengecek **`strict_member_kelompok_scope`**.
3. **`store` / `update` / `destroy` / export** pada **`AnggotaController`**: **tetap** memakai **`config('obormas.strict_member_kelompok_scope') && MemberScope::isRestrictedMemberUser`** untuk **`abort_if(403, ...)`** — **tidak diubah** di plan ini.

## Detailed Specifications

### File: `app/Http/Controllers/Api/AnggotaController.php`

1. Method **`index`**: Ganti kondisi dari  
   `config('obormas.strict_member_kelompok_scope') && MemberScope::isRestrictedMemberUser($request->user())`  
   menjadi hanya  
   `MemberScope::isRestrictedMemberUser($request->user())`  
   untuk blok yang mengisi **`$filters['ID_KS']`** / empty paginator. Isi blok (pemanggilan **`memberKelompokId`**, **`emptyPaginator`**) tetap sama.

2. Method private **`assertMemberCanAccessAnggota`**: Ganti guard awal dari  
   `if (! config('obormas.strict_member_kelompok_scope') || ! MemberScope::isRestrictedMemberUser($request->user())) { return; }`  
   menjadi  
   `if (! MemberScope::isRestrictedMemberUser($request->user())) { return; }`  
   Sisanya (bandingkan **`ID_KS`**, **`abort(403, ...)`**) **tidak diubah**.

### File: `config/obormas.php`

- Perbarui komentar blok **`strict_member_kelompok_scope`**: jelaskan bahwa untuk **role `user`**, **listing & detail `anggota`** sudah **selalu** ter-scope kelompok di controller; flag ini tetap mengatur **blok CRUD/export master anggota** dan modul lain yang sudah memakai flag tersebut (mis. **`KelSahController`**).

### File: `backend-app/.env.example`

- Satu kalimat di komentar **`STRICT_MEMBER_KELOMPOK_SCOPE`**: untuk **role `user`**, **`GET /anggota`** (dan detail) **sudah terbatas kelompok tanpa env**; env tetap dipakai untuk **larangan ubah/hapus/tambah master anggota** dan perilaku modul lain yang memakai flag sama.

### File: `tests/Feature/` (baru atau perluas)

- Tambah file tes fitur, mis. **`tests/Feature/MemberAnggotaReadAlwaysKelompokScopedTest.php`**, dengan **`RefreshDatabase`**, **`Sanctum::actingAs`** user **`role` => `user`**, **`no_agt`** menunjuk ke **`Anggota`** dengan **`ID_KS` = `KEL001`**, dan **`config(['obormas.strict_member_kelompok_scope' => false])`** di dalam tes (atau default false).
- **Assert 1:** **`GET /api/anggota?per_page=50`** → hanya data dengan **`ID_KS`** `KEL001` (bukan baris kelompok lain).
- **Assert 2:** **`GET /api/anggota/{NO_AGT_LAIN}`** dengan **`ID_KS`** beda → **`403`**.
- **Assert 3 (opsional regresi):** dengan strict **false**, **`POST /api/anggota`** (body minimal valid jika ada factory) tetap **403** untuk member **karena** **`abort_if`** store masih memakai **strict** — hanya jika route dan request valid tersedia di test harness; jika memerlukan payload berat, cukup assert **1** dan **2**.

### Frontend

- **Tidak ada perubahan** wajib: **`MemberKelompokReadOnlyScreen`** dan **`getAnggotaList`** sudah sesuai.

## Implementation Checklist

1. Edit **`AnggotaController::index`**: hilangkan ketergantungan **`config('obormas.strict_member_kelompok_scope')`** pada kondisi filter **`ID_KS`** untuk restricted member; pertahankan logika **`memberKelompokId`** / paginator kosong.
2. Edit **`assertMemberCanAccessAnggota`**: jalankan cek kelompok untuk restricted member **tanpa** syarat **`strict_member_kelompok_scope`**.
3. Edit **`config/obormas.php`**: perjelas komentar **`strict_member_kelompok_scope`** vs perilaku baca **`anggota`**.
4. Edit **`.env.example`**: perjelas peran env vs baca **`GET /anggota`** untuk user.
5. Tambah tes fitur **`MemberAnggotaReadAlwaysKelompokScopedTest.php`** (minimal: index terfilter + show kelompok lain **403** dengan **`strict_member_kelompok_scope` => false**).
6. Jalankan **`php artisan test`** (atau file tes baru saja) di **`backend-app`**.

## Risks / Catatan

- **Breaking change** bagi siapa pun yang mengandalkan **`role user`** melihat **seluruh** master anggota saat **`STRICT_MEMBER_KELOMPOK_SCOPE=false`**. Itu dianggap **perbaikan keamanan** selaras kebutuhan modul anggota user.
- Modul lain (**kel-sah**, dll.) **tetap** mengikuti flag strict seperti sekarang kecuali diubah di plan terpisah.
- User tanpa **`no_agt`** / tanpa match **`Anggota`**: tetap **list kosong** / **403** pada detail — sama seperti saat strict aktif.
