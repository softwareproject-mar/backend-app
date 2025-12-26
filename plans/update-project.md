Buat Authentikasi (login, logout, dan sign up)

Sign up dan sign in menggunakan email dan password dengan ketentuan:

Password Policy

Minimal 8 karakter

Wajib menggunakan kombinasi:

Huruf kecil (a–z)

Huruf besar (A–Z)

Angka (0–9)

Karakter khusus (!@#$%^&*?_+−)

Email Policy

Email harus domain @‌gmail.com atau kalau bisa set ke yahoo juga bisa

Contoh valid: nama@gmail.com atau nama@yahoo.com

Buat forgot password → OTP to gmail

GET table data_trs

CRUD table ketua_ks

CRUD table sekre_ks

CRUD data_lo

CRUD data_ao

Untuk table anggota, cuma input id anggota, kemudian kolom lain muncul, kemudian isi/pilih id ks

CRUD table kel_sah

CRUD table data_jlh_keluarga

CRUD table data_penghasilan

Target → CRUD (Master / Planning Data)

Realisasi → GET (Data Aktual / Transaksional)

Dashboard → GET Gabungan (Join Target + Realisasi)

 

Catatan:

Kebutuhan Generate ID Otomatis

Saat user melakukan input data baru pada tabel:

ketua_ks

sekre_ks

kel_sah

data_lo

data_ao

data_pengelola

Format ID (Total 12 Digit)

Contoh:

6 digit awal itu adalah Kode Obormas → 016005

Digit ke-7 itu adalah Kode Role sesuai jenis entitas 0160055

5 digit akhir itu Running number + leading zero00001 sampai 99999

12 digit id nya untuk id_ao jadi: 016005500001