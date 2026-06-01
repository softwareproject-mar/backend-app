# Plan: Autocomplete anggota — `search` di API index + filter `ID_KS` (kunjungan)

## Context

- `GET /anggota` di **`AnggotaController::index`** saat ini hanya mengisi filter dari `only(['NO_AGT','ID_KS','ID_LO'])` — **`search` tidak ikut**, padahal **`AnggotaService::applyFilters`** sudah mendukung `search`. Akibatnya autocomplete yang mengirim `?search=...` **tidak** difilter di server seperti yang diharapkan.
- Untuk **role `user`** dengan **`STRICT_MEMBER_KELOMPOK_SCOPE=true`**, **`ID_KS`** sudah dipaksa dari **`MemberScope::memberKelompokId`** (client tidak perlu kirim).
- Untuk **non-user (admin)** di form **Kunjungan**, urutan field saat ini: **ID LO → Nomor Anggota → ID Kelompok**, sehingga saat mengisi anggota, **`ID_KS` belum tersedia** dari form untuk dibawa ke `getAnggotaList`.

## Goal

1. **`index`** memproses **`search`** sama seperti export, agar autocomplete konsisten.
2. **Admin** (dan pemanggil non-member) dapat membatasi daftar anggota dengan query **`ID_KS`** bila konteks form sudah punya nilai kelompok.
3. Form **Kunjungan**: **ID Kelompok Sahabat** diisi **sebelum** **Nomor Anggota** agar filter `ID_KS` bisa dipakai saat autocomplete.

## Detailed Specifications

### A. Backend — `app/Http/Controllers/Api/AnggotaController.php`

- Di method **`index`**, ganti baris pembentuk `$filters` menjadi:
  - `$filters = $request->only(['NO_AGT', 'ID_KS', 'ID_LO', 'search']);`
- **Tidak mengubah** blok berikutnya: jika **`strict_member_kelompok_scope`** dan **`MemberScope::isRestrictedMemberUser`**, tetap **set** `$filters['ID_KS'] = hasil `memberKelompokId` (menimpa nilai `ID_KS` dari query client — perilaku aman untuk anggota).

### B. Frontend — `src/services/anggotaService.ts`

- Perluas parameter opsional **`getAnggotaList`**:
  - Tambah **`idKs?: string`** (atau nama sama dengan kontrak API: kirim sebagai query **`ID_KS`**).
  - Jika `idKs` ada dan string tidak kosong setelah trim, tambahkan ke `URLSearchParams` dengan key **`ID_KS`** (sesuai key yang dibaca Laravel `only('ID_KS')`).

### C. Frontend — `src/app/components/DataKunjunganContent.tsx`

1. **Urutan field** di dalam form tambah/edit kunjungan (blok yang sama untuk LO / anggota / kelompok): pindahkan seluruh blok **“ID Kelompok Sahabat”** (label, input, dropdown) **ke atas** blok **“Nomor Anggota”**, tetap **di bawah** blok **“ID LO”** (urutan akhir: **ID LO → ID Kelompok Sahabat → Nomor Anggota**). State (`kelSearch`, `formIdKelompok`, dll.) **tidak diubah nama**, hanya urutan JSX.
2. Pada pemanggilan **`getAnggotaList`** di dalam handler autocomplete nomor anggota, sertakan **`ID_KS`** dari state **`formIdKelompok`** jika **`formIdKelompok.trim()` tidak kosong** — map ke argumen baru **`idKs`** di service. Jika kosong, jangan kirim parameter (perilaku sama seperti sekarang untuk admin yang belum pilih kelompok).

### D. Dokumentasi env — `backend-app/.env.example`

- Di komentar **`STRICT_MEMBER_KELOMPOK_SCOPE`**, tambahkan satu kalimat: untuk **role user**, filter ini juga memastikan **`GET /anggota`** (termasuk autocomplete) hanya mengembalikan anggota **sekelompok**; **`ID_KS` dari query diabaikan/ditimpa** oleh server untuk role tersebut.

### E. Out of scope (plan ini)

- Menambah pemilih kelompok di form **Penghasilan**, **Jlh Keluarga**, **Ketua/Sekretaris KS**, **Pengelola**, **LO**, **AO** (bisa fase berikutnya dengan pola yang sama).
- Filter **`ID_LO`** dari client untuk autocomplete anggota (API sudah mendukung; belum di-wire di service).
- Mengubah default **`strict_member_kelompok_scope`** ke `true` di `config/obormas.php`.

## Implementation Checklist

1. Edit **`app/Http/Controllers/Api/AnggotaController.php`** method **`index`**: sertakan **`search`** di **`$request->only([...])`** bersama **`NO_AGT`**, **`ID_KS`**, **`ID_LO`**.
2. Edit **`src/services/anggotaService.ts`**: tambah opsi **`idKs`** pada **`getAnggotaList`**, set query **`ID_KS`** jika nilai terisi.
3. Edit **`src/app/components/DataKunjunganContent.tsx`**: geser blok UI **ID Kelompok Sahabat** ke **atas** blok **Nomor Anggota** (tetap setelah **ID LO**).
4. Edit **`DataKunjunganContent.tsx`**: pada **`getAnggotaList`** untuk autocomplete anggota, pass **`idKs: formIdKelompok.trim() || undefined`** (atau setara) ketika ada nilai.
5. Edit **`backend-app/.env.example`**: perkaya komentar **`STRICT_MEMBER_KELOMPOK_SCOPE`** sesuai bagian D.
6. Jalankan **`npm run build`** pada project app mobile; opsional: **`php artisan test`** atau smoke **`GET /api/anggota?search=...&ID_KS=...`** dengan token admin vs user.

## Risks / Catatan

- Admin yang belum mengisi **ID Kelompok** di form kunjungan akan tetap mendapat daftar anggota **tanpa** filter `ID_KS` (sengaja).
- Setelah reorder, alur isi form berubah — beri tahu pengguna QA untuk mengisi **kelompok dulu** bila ingin daftar anggota terpersempit.
- **Role user** dengan strict scope: tidak perlu mengisi kelompok dulu untuk batasan server; filter kelompok tetap dari **`no_agt`**.
