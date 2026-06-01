Catatan Perbaikan Modul Target Kelompok
1. Validasi Kolom Tanggal Target (TGL_TGT)
Saat ini kolom TGL_TGT masih bisa diinput bebas oleh user. Padahal TGL_TGT yang dimaksud adalah tanggal berakhirnya target/periode target tersebut, bukan tanggal harian saat admin melakukan input data.
Perbaikan yang diinginkan:
- TGL_TGT harus mengikuti periode target bulanan.
- Tanggal yang dapat dipilih hanya tanggal akhir bulan/periode berlaku target.
Contoh:
- Januari → 31-01-YYYY
- Februari → 28/29-02-YYYY
- Maret → 31-03-YYYY
Tujuan: Agar data target konsisten dan merepresentasikan periode target bulanan.
2. Pemilihan ID Kelompok pada Tabel Modul Tanggal
Saat ini tabel otomatis menampilkan seluruh ID Kelompok dari database.
Perbaikan yang diinginkan:
- ID Kelompok yang masuk ke tabel hanya berdasarkan pilihan admin.
- Admin dapat memilih kelompok mana saja yang ingin dimasukkan melalui pilihan.
- Tidak perlu otomatis menampilkan semua data kelompok dari database semuanya.
Mekanisme yang diharapkan:
- Tambahkan fitur dropdown / multi select untuk memilih ID Kelompok.
- Setelah dipilih, hanya data tersebut yang tampil atau tersimpan pada tabel.
Tujuan: Agar data lebih terkontrol dan tidak memenuhi tabel dengan seluruh kelompok.
3. Form Input Target Kelompok Tidak Required Semua
Saat ini semua field pada form target kelompok wajib diisi.
Perbaikan yang diinginkan:
- Form dapat disimpan meskipun tidak semua field terisi.
- Hanya field tertentu yang benar-benar wajib (jika memang diperlukan).
- Field yang kosong tetap diperbolehkan tersimpan sebagai NULL.

