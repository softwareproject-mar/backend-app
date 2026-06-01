# Plan: Modul Anggota read-only untuk role user (app mobile)

## Context

- Aplikasi **Kelompok Sahabat Obor Mas** (Vite + React + Capacitor) untuk `role === "user"` hanya menampilkan 4 tile: Penghasilan, Jumlah Keluarga, Kunjungan, Data Transaksi (`USER_MENU_IDS` di `HomeContent.tsx`).
- Menu master **Anggota** (`id: 1`) memakai `MemberDataScreen` (CRUD + export) dan diblok untuk user lewat `ADMIN_ONLY_MENU_IDS` + filter menu.
- Backend `AnggotaController::index` sudah memaksa filter **`ID_KS`** untuk member terbatas **hanya jika** `config('obormas.strict_member_kelompok_scope')` bernilai true (`STRICT_MEMBER_KELOMPOK_SCOPE` di `.env`). Tanpa itu, `GET /anggota` untuk role user tidak otomatis sekelompok.

## Goal

- Menambah **satu modul di beranda** khusus role user: **daftar anggota sekelompok**, **tanpa** create / update / delete / export.
- Memakai API yang ada: **`GET /anggota`** (pagination + `search` query, sama seperti `anggotaService.getAnggotaList`).
- Menjaga pemisahan jelas dari layar admin: **tidak** membuka `MemberDataScreen` untuk user.

## Detailed Specifications

### A. ID menu & navigasi (frontend)

- **Path proyek UI:** `Kelompok Sahabat Obor Mas/Kelompok Sahabat Obor Mas/` (root dengan `src/`).
- **Menu ID baru:** `12` (hindari bentrok dengan `id: 1` milik master Anggota admin).
- **Label tile:** `"Anggota"` (atau `"Daftar Anggota"` jika ingin beda dari admin secara teks; default plan: **`"Anggota"`**).
- **Icon / warna:** ikuti pola `menuItems` di `HomeContent.tsx` — gunakan icon `Users` dan warna yang konsisten dengan card admin Anggota (`#3b82f6`) agar branding seragam.
- **`HomeContent.tsx`:**
  - Tambah satu objek ke array `menuItems`: `{ id: 12, label: "Anggota", icon: Users, color: "#3b82f6" }` (atau label alternatif di atas).
  - Perluas `USER_MENU_IDS` menjadi `[7, 9, 10, 11, 12]` (urutan bebas; disarankan letakkan `12` setelah modul existing atau di posisi yang masuk akal UX, mis. setelah Penghasilan).
  - Untuk **non-user** (`userRole !== "user"`): `visibleMenuItems` harus **menyembunyikan** `id: 12` (filter `item.id !== 12`), karena admin sudah punya `id: 1` — **satu tile Anggota** untuk admin tetap.
- **`MainDashboard.tsx`:**
  - Import komponen baru `MemberKelompokReadOnlyScreen` dari `./MemberKelompokReadOnlyScreen`.
  - Render: `activeTab === "home" && activeMenu === 12` → `<MemberKelompokReadOnlyScreen onBack={handleBackToHome} />`.
  - **Jangan** masukkan `12` ke array `ADMIN_ONLY_MENU_IDS` (biarkan user bisa membuka modul ini).

### B. Komponen layar baru

- **File baru:** `src/app/components/MemberKelompokReadOnlyScreen.tsx`.
- **Props:** `{ onBack: () => void }` (sama pola dengan layar data lain).
- **Perilaku:**
  - Header dengan tombol kembali + judul (mis. **"Anggota Kelompok"**).
  - Search input dengan **debounce ~350ms** (sama seperti `MemberDataScreen` untuk konsistensi API).
  - Panggil `getAnggotaList({ page, per_page, search })` dari `../../services/anggotaService`.
  - Mapping baris API → tampilan: **sama** dengan `MemberDataScreen` (`NO_AGT` → noAnggota, `NAMA`, `ID_KS` sebagai ID KS, `ID_LO`, `ID_AO`, `ID_KS_ASL`, `TGL_MTS`, `TGL_AKTIF`, `TGL_JA`) — lihat blok `mapped` di `MemberDataScreen.tsx` sekitar baris yang memetakan `AnggotaDto` ke `Member`.
  - Tabel: kolom **No**, **Nomor Anggota**, **Nama**, **ID KS**, **ID LO**, **ID AO**, **ID KS Asal**, **Tanggal MTS**, **Tahun Aktif**, **Tanggal JA** — **tanpa** kolom aksi Edit/Hapus, **tanpa** tombol Tambah, **tanpa** export.
  - Pagination: minimal **prev/next** + indikator halaman + `per_page` selectable (boleh menyalin pola UI pagination dari `MemberDataScreen` yang sudah ada).
  - Error state: tampilkan `e?.body?.message` atau fallback teks ramah.
  - **Opsional UX (disarankan dalam implementasi yang sama):** satu aksi per baris **"Detail"** (ikon mata atau teks) membuka **dialog/modal read-only** dengan semua field di atas; data boleh dari baris yang sudah dimuat (tanpa `getAnggotaDetail`) untuk mengurangi request — cukup jika isi modal identik dengan data list.

### C. Backend & konfigurasi

- **Tidak wajib mengubah kode Laravel** jika kebijakan deployment sudah **`STRICT_MEMBER_KELOMPOK_SCOPE=true`** (filter `ID_KS` + `show` + blok CRUD/export sudah ada).
- **Dokumentasi env:** di `backend-app/.env.example`, perjelas komentar di sekitar `STRICT_MEMBER_KELOMPOK_SCOPE` bahwa untuk **app mobile**, flag ini harus aktif agar modul Anggota user hanya melihat anggota **sekelompok** (satu kalimat, tanpa mengubah nilai default di kode).

### D. Out of scope (plan ini)

- Mengubah `MemberDataScreen.tsx` untuk mode ganda admin/member.
- Endpoint API baru (`/my-kelompok/anggota`, dll.).
- Mengaktifkan create/update/delete untuk role user.
- Perubahan default `strict_member_kelompok_scope` di `config/obormas.php` menjadi `true` (breaking untuk consumer API lama).

## Implementation Checklist

1. Buat file `src/app/components/MemberKelompokReadOnlyScreen.tsx` dengan props `onBack`, state loading/error, debounced search, pemanggilan `getAnggotaList`, mapping `AnggotaDto` seperti `MemberDataScreen`, tabel read-only + pagination, tanpa CRUD/export.
2. (Opsional tapi disarankan) Tambah dialog detail read-only per baris di `MemberKelompokReadOnlyScreen.tsx`.
3. Edit `src/app/components/HomeContent.tsx`: tambah entri `menuItems` dengan `id: 12`; set `USER_MENU_IDS` memuat `12`; untuk role non-user, filter `visibleMenuItems` agar `id !== 12`.
4. Edit `src/app/components/MainDashboard.tsx`: import `MemberKelompokReadOnlyScreen`; render ketika `activeMenu === 12` pada tab home; pastikan `ADMIN_ONLY_MENU_IDS` tidak berisi `12`.
5. Edit `backend-app/.env.example`: perjelas komentar untuk `STRICT_MEMBER_KELOMPOK_SCOPE` terkait modul Anggota di app mobile (anggota sekelompok).

## Risks / Catatan

- Jika **`STRICT_MEMBER_KELOMPOK_SCOPE` false** di server production, user bisa melihat **seluruh** master anggota lewat modul baru — **risiko data**. Mitigasi operasional: aktifkan env; mitigasi teknis lanjutan (di luar checklist ini): pertimbangkan filter `ID_KS` untuk `role user` **tanpa** bergantung env.
- User tanpa `no_agt` / kelompok tidak ter-resolve: backend mengembalikan **list kosong** — UI harus tetap menampilkan empty state yang jelas (bukan error 500).
- Setelah deploy, uji manual: login sebagai `user`, buka modul Anggota, pastikan hanya anggota `ID_KS` sama; login admin pastikan tile ganda Anggota tidak muncul (hanya `id: 1`).
