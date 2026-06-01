# Plan: Penyesuaian Tabel Kunjungan + Popup Lihat Gambar

## Context
- Form `DataKunjungan` sudah menangkap data lengkap: ID LO, No Anggota, ID Kelompok, Tanggal, Kegiatan, ID PIC, Peserta, Foto, Latitude, Longitude.
- Tabel di `DataKunjunganContent` belum menampilkan field yang relevan dari form, khususnya foto.
- User meminta agar di tabel ada aksi untuk foto berupa tombol **Lihat** dan menampilkan gambar dalam popup di tampilan yang sama.

## Goal
- Menyelaraskan kolom tabel kunjungan dengan data penting dari form.
- Menambahkan kolom foto dengan tombol **Lihat** untuk setiap row yang memiliki gambar.
- Menampilkan preview gambar dalam modal popup di screen yang sama, tanpa pindah halaman.

## Detailed Specifications
- **File yang diubah:** `c:\Users\galih\Documents\ui\Kelompok Sahabat Obor Mas\Kelompok Sahabat Obor Mas\src\app\components\DataKunjunganContent.tsx`
  - Tambahkan state khusus image preview:
    - `showImagePreview` (boolean)
    - `previewImageUrl` (string | null)
  - Tambahkan handler:
    - `openImagePreview(imageUrl: string)` untuk buka popup
    - `closeImagePreview()` untuk tutup popup dan reset state
  - Update header tabel:
    - Tambahkan kolom baru `Bukti` (atau `Foto`) sebelum kolom `Aksi`
    - Jika disepakati relevan, tambahkan kolom koordinat ringkas (`Lokasi`) agar lebih sesuai dengan form
  - Update body row tabel:
    - Pada kolom `Bukti`:
      - Jika `item.foto` ada: tampilkan tombol `Lihat`
      - Jika tidak ada: tampilkan `-`
    - Tombol `Lihat` memanggil `openImagePreview(item.foto)`
  - Tambahkan modal popup preview gambar:
    - Overlay full-screen semi transparan
    - Card/modal di tengah berisi:
      - Judul singkat (`Preview Bukti Kunjungan`)
      - Komponen `<img>` untuk menampilkan gambar
      - Tombol `Tutup`
    - Mendukung close dengan klik backdrop dan tombol `Tutup`
  - Pastikan jumlah kolom header dan `colSpan` untuk empty/loading state tetap konsisten setelah kolom baru ditambahkan.
  - Pastikan style modal tidak bentrok dengan modal form/edit/delete yang sudah ada (z-index lebih tinggi atau setara secara aman).

## Implementation Checklist
1. Tambahkan state `showImagePreview` dan `previewImageUrl` di `DataKunjunganContent.tsx`.
2. Tambahkan function `openImagePreview` dan `closeImagePreview`.
3. Ubah struktur header tabel untuk menambahkan kolom `Bukti`.
4. Ubah render body row untuk menambahkan cell `Bukti` dengan logika:
   - ada foto -> tombol `Lihat`
   - tidak ada foto -> teks `-`
5. Update nilai `colSpan` pada row loading/empty agar sesuai jumlah kolom terbaru.
6. Tambahkan komponen modal popup preview gambar di bagian bawah render screen.
7. Integrasikan close behavior modal (backdrop click + tombol tutup).
8. Jalankan lint untuk `DataKunjunganContent.tsx` dan pastikan tidak ada error.
9. Verifikasi manual:
   - row dengan foto: tombol `Lihat` muncul dan gambar tampil
   - row tanpa foto: tampil `-`
   - modal bisa ditutup normal
   - tidak mengganggu modal/form lain.

## Risks / Catatan
- URL `item.foto` bisa relatif/absolut tergantung backend; jika ada kasus gambar tidak tampil, perlu normalisasi URL pada layer mapping.
- Bila ukuran gambar besar, popup tetap menampilkan gambar dengan `object-contain` agar tidak merusak layout.
- Perubahan ini fokus UI/UX tabel; tidak mengubah proses upload/simpan foto.
