# Plan: Global Search Seluruh Modul

## Context
- Requirement dari `masalah.md`: search bar harus bisa mencari terhadap seluruh data/kolom tabel, tidak hanya nama/nomor anggota.
- Kondisi saat ini sudah campuran:
  - Beberapa endpoint backend sudah memakai `CaseInsensitiveSearch`.
  - Sebagian modul Web Admin sudah server-side `search`, sebagian masih client-side filter.
  - Cakupan kolom pencarian belum konsisten antar modul.
- Risiko utama: behavior berbeda antar modul, terutama format tanggal/periode/status.

## Goal
- Menyamakan perilaku search di seluruh modul agar multi-kata dan lintas kolom tabel.
- Menjamin semua modul utama Web Admin mengirim `search` ke backend (server-side), bukan hanya filter lokal sederhana.
- Menstandarkan kolom searchable per modul (termasuk tanggal/bulan/tahun/status) secara eksplisit.

## Detailed Specifications
- `app/Support/CaseInsensitiveSearch.php`
  - Finalisasi helper multi-token agar aman untuk Firebird.
  - Pastikan pemisahan strategi kolom:
    - Kolom identitas/nomor (`NO_AGT`, `ID_*`) = OR antar token.
    - Kolom teks/nama/status/alamat/deskripsi = AND antar token.
  - Tambah helper utilitas untuk normalisasi term yang dipakai konsisten semua service.

- `app/Services/AnggotaService.php`
  - Verifikasi + sesuaikan daftar kolom searchable agar mencakup semua kolom tabel anggota yang relevan untuk list (`NO_AGT`, `NAMA`, `ID_KS`, `ID_KS_ASL`, tanggal terkait bila disimpan string).
  - Pastikan tidak memicu truncation/error pada Firebird untuk kolom fixed char/date.

- `app/Services/KelSahService.php`
  - Pastikan search mencakup seluruh kolom tabel list kelompok yang ditampilkan (`ID_KEL`, `NAMA_KEL`, `ID_KETUA`, `ID_SEK`, `ID_LO`, `ID_AO`, `ALAMAT`, `STAT`, `TGL_STAT`, `ID_PENGELOLA`).

- `app/Services/KetuaKsService.php`
  - Pastikan search mencakup seluruh kolom list (`ID_KET`, `NO_AGT`, `NAMA`, `STAT`, `TGL_STAT`, `NO_SK`).

- `app/Services/SekretarisKsService.php`
  - Pastikan search mencakup seluruh kolom list (`ID_SEKRE`, `NO_AGT`, `NAMA`, `STAT`, `TGL_STAT`, `NO_SK`).

- `app/Services/DataLoService.php`
  - Perluas kolom searchable (`ID_LO`, `NO_AGT`, `ID_TP`, `NAMA`, `STAT`, `TGL_STAT`).

- `app/Services/DataAoService.php`
  - Perluas kolom searchable (`ID_AO`, `NO_AGT`, `NAMA`, `STAT`, `TGL_STAT`).

- `app/Services/DataPengelolaService.php`
  - Perluas kolom searchable (`ID_PENG`, `NO_AGT`, `anggota.NAMA`, `NO_SK`).

- `app/Services/DataKunjunganService.php`
  - Samakan behavior level A/B/C report agar kolom terkait nama/status/periode dapat dicari konsisten terhadap requirement.
  - Validasi bahwa pencarian tidak terbatas nama kelompok/anggota saja.

- `app/Services/FirebirdService.php`
  - Verifikasi endpoint list Firebird (`getAnggotaList`, `getDataTrsNoAgtList`) memakai pola multi-token konsisten dan aman.

- `app/Http/Controllers/Api/ActivityLogController.php`
  - Pastikan search mencakup seluruh kolom list aktivitas yang ditampilkan (mis. `description`, `user_name`, `resource_type`, `action_type`, `status`, timestamp string bila relevan).

- `app/Http/Controllers/Api/UserApprovalController.php`
  - Pastikan search untuk list pending/approved/rejected/all mencakup kolom list yang tampil di UI (minimal `name`, `email`, role/status bila ditampilkan).

- `app/Http/Controllers/Api/SuperAdmin/SystemActivityController.php`
  - Perluas kolom search sesuai kolom tabel halaman Riwayat Sistem.

- `app/Http/Controllers/Api/SuperAdmin/UserManagementController.php`
  - Perluas kolom search sesuai kolom list user management.

- `tests/Feature/CaseInsensitiveSearchApiTest.php`
  - Tambah skenario multi-kata lintas modul (anggota, kel-sah, ketua/sekretaris, lo/ao/pengelola minimal smoke).
  - Tambah skenario status/tanggal/periode yang searchable.

- `tests/Unit/CaseInsensitiveSearchTest.php`
  - Tambah test unit untuk tokenisasi, normalisasi whitespace, strategi AND/OR per kolom.

- `c:\Users\galih\Documents\ui\Web Admin\src\utils\adminSearchMatch.ts`
  - Standarisasi util search client-side hanya untuk modul yang memang harus local filtering.
  - Jaga parity behavior dengan backend (`normalize`, token split, AND-match antar token).

- `c:\Users\galih\Documents\ui\Web Admin\src\hooks\useDebouncedSearchQuery.ts`
  - Dipakai konsisten di semua tab/page yang punya search agar request behavior seragam.

- `c:\Users\galih\Documents\ui\Web Admin\src\app\components\tabs\AnggotaTab.tsx`
  - Pastikan server-side search tetap dipakai + error handling UI jelas.

- `c:\Users\galih\Documents\ui\Web Admin\src\app\components\tabs\KelompokSahabatTab.tsx`
  - Ubah ke server-side search penuh atau dokumentasikan fallback local multi-token jika endpoint belum support.

- `c:\Users\galih\Documents\ui\Web Admin\src\app\components\tabs\KetuaKSTab.tsx`
- `c:\Users\galih\Documents\ui\Web Admin\src\app\components\tabs\SekretarisKSTab.tsx`
- `c:\Users\galih\Documents\ui\Web Admin\src\app\components\tabs\DataLOTab.tsx`
- `c:\Users\galih\Documents\ui\Web Admin\src\app\components\tabs\DataAOTab.tsx`
- `c:\Users\galih\Documents\ui\Web Admin\src\app\components\tabs\PengelolaTab.tsx`
  - Pastikan semua tab kirim `search` ke endpoint masing-masing, tidak lagi bertumpu pada `Object.values(...).includes(...)` sebagai sumber utama.

- `c:\Users\galih\Documents\ui\Web Admin\src\app\components\tabs\DataKunjunganLaporanTab.tsx`
  - Verifikasi parameter `search` diterapkan konsisten di level A/B/C sesuai requirement “seluruh field tabel”.

- `c:\Users\galih\Documents\ui\Web Admin\src\app\pages\RiwayatAktivitas.tsx`
- `c:\Users\galih\Documents\ui\Web Admin\src\app\pages\PersetujuanAkun.tsx`
- `c:\Users\galih\Documents\ui\Web Admin\src\app\pages\super-admin\RiwayatSistemPage.tsx`
- `c:\Users\galih\Documents\ui\Web Admin\src\app\pages\super-admin\ManajemenUserPage.tsx`
- `c:\Users\galih\Documents\ui\Web Admin\src\app\pages\super-admin\ManajemenAdminPage.tsx`
- `c:\Users\galih\Documents\ui\Web Admin\src\app\pages\super-admin\ManajemenPerangkatPage.tsx`
  - Audit bahwa search input dikirim ke backend dan backend benar-benar cari lintas kolom tabel yang user lihat.

## Implementation Checklist
1. Audit semua modul list di Web Admin dan mapping endpoint backend per modul.
2. Definisikan daftar kolom searchable per modul berdasarkan kolom tabel UI (single source of truth internal plan).
3. Finalisasi helper `CaseInsensitiveSearch` agar aman di Firebird untuk multi-token lintas tipe kolom.
4. Update `AnggotaService` mengikuti daftar kolom searchable final.
5. Update `KelSahService` mengikuti daftar kolom searchable final.
6. Update `KetuaKsService` mengikuti daftar kolom searchable final.
7. Update `SekretarisKsService` mengikuti daftar kolom searchable final.
8. Update `DataLoService` mengikuti daftar kolom searchable final.
9. Update `DataAoService` mengikuti daftar kolom searchable final.
10. Update `DataPengelolaService` mengikuti daftar kolom searchable final.
11. Update `DataKunjunganService` agar level laporan A/B/C tidak terbatas nama/no anggota saja.
12. Update search scope di `ActivityLogController`.
13. Update search scope di `UserApprovalController`.
14. Update search scope di `SystemActivityController`.
15. Update search scope di `UserManagementController`.
16. Verifikasi `FirebirdService` (`getAnggotaList` + `getDataTrsNoAgtList`) konsisten dengan helper terbaru.
17. Refactor tab master Web Admin (`KetuaKSTab`, `SekretarisKSTab`, `DataLOTab`, `DataAOTab`, `PengelolaTab`, `KelompokSahabatTab`) agar search utama server-side.
18. Verifikasi `AnggotaTab` tetap server-side dan error state terlihat.
19. Verifikasi `DataKunjunganLaporanTab` level A/B/C menerapkan search lintas kolom sesuai backend.
20. Audit halaman non-tab (Riwayat Aktivitas, Persetujuan Akun, halaman Super Admin) agar `search` benar-benar backend-driven.
21. Tambah/rapikan unit test `CaseInsensitiveSearchTest`.
22. Tambah feature test `CaseInsensitiveSearchApiTest` untuk modul tambahan dan kolom status/tanggal/periode.
23. Jalankan test terkait pencarian dan perbaiki jika ada regresi.
24. Uji manual end-to-end per modul dengan kata kunci multi-kata, status, tanggal, bulan, tahun.
25. Final review: pastikan seluruh modul memenuhi requirement “search seluruh field tabel”.

## Risks / Catatan
- Search “seluruh field” untuk kolom date/time numeric perlu normalisasi format yang disepakati (contoh `2026`, `05`, `Mei`).
- Menambah banyak kolom searchable berpotensi menurunkan performa query Firebird; perlu prioritas kolom yang tampil di UI.
- Modul yang saat ini fetch `per_page=50000` berisiko berat; migrasi server-side search harus hati-hati agar UX tidak regress.
- Perlu sinkronisasi definisi “seluruh field” antara backend schema vs kolom yang benar-benar ditampilkan ke user.
