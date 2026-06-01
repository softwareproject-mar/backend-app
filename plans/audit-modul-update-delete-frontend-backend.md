# Audit Modul Update/Delete (User)

## Ringkasan
- Backend: endpoint `update/delete` untuk role user sudah dibuka di modul utama.
- Frontend: sebagian modul sudah benar-benar memanggil API update/delete, sebagian belum.

## Status per Modul

### 1) Sudah end-to-end (UI -> API update/delete)
- `anggota`
- `kel-sah`
- `data-ao`
- `data-lo`
- `data-pengelola`
- `ketua-ks`
- `sekretaris-ks`
- `data-jlh-keluarga` (sudah, dengan fallback ID agar kompatibel)
- `data-penghasilan` (sudah, dengan fallback ID agar kompatibel)

### 2) Belum end-to-end (masih gap di frontend)
- `data-kunjungan`
  - UI edit saat ini menyimpan lewat `createKunjungan(...)` (bukan update endpoint).
  - Hapus saat ini hanya menghapus state lokal (`setData(...)`), tidak memanggil API delete.
  - Service belum punya fungsi `updateKunjungan(...)` dan `deleteKunjungan(...)`.

- `data-trs`
  - Screen saat ini hanya list + export.
  - Belum ada form/tombol update/delete.
  - Service belum expose `update/delete` untuk dipakai UI.

## Catatan Error "Resource not found"
- Penyebab yang sudah ditemukan di modul numerik (`penghasilan`, `jlh-keluarga`, `trs`) adalah mismatch ID.
- Patch fallback ID sudah diterapkan agar saat `id` tidak tersedia dari API, frontend pakai `NO_AGT` sebagai `recordId`.
