# Plan: Server Setup PHP 8.3 + Firebird (Ubuntu)

## Context
- Backend Laravel `backend-app` berjalan dengan PHP 8.3.
- Koneksi MySQL di server sudah jalan, tetapi koneksi Firebird via `PDO` gagal dengan error `could not find driver`.
- Server menggunakan OS Ubuntu, aplikasi diakses oleh Flutter melalui `http://103.253.212.105/obormas/api`.

## Goal
- Meng‑install dan meng‑enable **driver PDO Firebird** untuk PHP 8.3 di Ubuntu.
- Memastikan semua ekstensi PHP penting untuk Laravel sudah terpasang.
- Menyusun langkah verifikasi agar koneksi Firebird dari Laravel bisa berjalan lancar.

## Detailed Specifications
- **Lingkungan target**
  - OS: Ubuntu (20.04/22.04 atau yang sejenis).
  - PHP: versi 8.3 (CLI + FPM).
  - Web server: Nginx atau Apache (disesuaikan dengan yang dipakai di server).

- **Paket sistem yang perlu tersedia**
  - Firebird client dan tools:
    - `firebird3.0-server` (atau versi yang tersedia di repo).
    - `firebird3.0-utils`
    - `libfbclient2`
  - PHP 8.3 core & FPM (jika belum ada di server):
    - `php8.3`, `php8.3-cli`, `php8.3-fpm`
  - Ekstensi PHP umum untuk Laravel:
    - `php8.3-mysql`
    - `php8.3-mbstring`
    - `php8.3-xml`
    - `php8.3-curl`
    - `php8.3-bcmath`
    - `php8.3-zip`
    - `php8.3-gd`
    - `php8.3-intl` (opsional tapi direkomendasikan)
    - `php8.3-redis` (jika Redis dipakai)
  - **Driver Firebird untuk PHP 8.3**:
    - `php8.3-interbase` (paket ini biasanya memasang modul `pdo_firebird` dan `interbase`).

- **Perintah instalasi (Ubuntu, sebagai root atau sudo)**
  - Update index paket:
    - `sudo apt update`
  - (Opsional) Tambah repository PHP jika PHP 8.3 belum tersedia dari repo standar:
    - `sudo add-apt-repository ppa:ondrej/php`
    - `sudo apt update`
  - Instal PHP 8.3 + ekstensi Laravel umum:
    - `sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-bcmath php8.3-zip php8.3-gd php8.3-intl`
  - Instal Firebird client & tools:
    - `sudo apt install -y firebird3.0-server firebird3.0-utils libfbclient2`
  - Instal driver Firebird untuk PHP 8.3:
    - `sudo apt install -y php8.3-interbase`

- **Aktivasi dan verifikasi modul PHP**
  - Cek modul Firebird di CLI:
    - `php -m | grep -i firebird`
    - Output yang diharapkan minimal mengandung: `PDO`, `pdo_firebird`, `interbase`.
  - Jika menggunakan PHP-FPM:
    - Cek modul di FPM dengan `php-fpm8.3 -m | grep -i firebird` (jika tersedia).
  - Restart layanan PHP dan web server:
    - Jika pakai Nginx:
      - `sudo systemctl restart php8.3-fpm`
      - `sudo systemctl restart nginx`
    - Jika pakai Apache:
      - `sudo systemctl restart apache2`

- **Verifikasi dari sisi Laravel**
  - SSH ke server, masuk ke root project Laravel (misalnya `/var/www/backend-app`):
    - `cd /var/www/backend-app`
  - Jalankan artisan tinker atau command sederhana untuk mengetes koneksi Firebird:
    - `php artisan tinker`
      - Di dalam tinker (sekadar contoh uji koneksi, tidak disimpan di kode):
        - `DB::connection('firebird')->select('SELECT FIRST 1 NO_AGT FROM ANGGOTA');`
  - Atau buat route sementara / gunakan command artisan khusus untuk menjalankan `FirebirdService::testConnection()` dan cek log.
  - Pastikan variabel `.env` berikut sudah benar dan bisa diakses oleh `config('database.connections.firebird')`:
    - `FIREBIRD_HOST`
    - `FIREBIRD_PORT`
    - `FIREBIRD_DATABASE`
    - `FIREBIRD_USERNAME`
    - `FIREBIRD_PASSWORD`
    - `FIREBIRD_CHARSET`

- **Catatan kompatibilitas**
  - Nama paket Firebird (`firebird3.0-*`) bisa berbeda jika Ubuntu versi baru memakai Firebird 4; sesuaikan dengan versi yang tersedia saat menjalankan `apt search firebird`.
  - Jika web server dan PHP-FPM memakai versi berbeda (misalnya Apache dengan mod_php8.3), pastikan semua instalasi paket dan restart layanan mengacu ke versi yang sama (`php8.3-*`).

## Implementation Checklist
1. Pastikan server menggunakan Ubuntu dan PHP 8.3 (cek dengan `php -v` di server).
2. Jalankan `sudo apt update` untuk memperbarui index paket.
3. (Jika PHP 8.3 belum tersedia dari repo standar) tambahkan PPA `ondrej/php` dengan `sudo add-apt-repository ppa:ondrej/php` lalu `sudo apt update` lagi.
4. Instal PHP 8.3 dan ekstensi PHP umum untuk Laravel dengan `sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-bcmath php8.3-zip php8.3-gd php8.3-intl`.
5. Instal Firebird client dan library dengan `sudo apt install -y firebird3.0-server firebird3.0-utils libfbclient2` (atau versi paket Firebird yang tersedia).
6. Instal driver Firebird untuk PHP 8.3 dengan `sudo apt install -y php8.3-interbase`.
7. Verifikasi modul terpasang dengan `php -m | grep -i firebird` dan pastikan terlihat `PDO`, `pdo_firebird`, dan `interbase`.
8. Restart layanan PHP-FPM dan web server (Nginx atau Apache) menggunakan `sudo systemctl restart php8.3-fpm` dan `sudo systemctl restart nginx` atau `sudo systemctl restart apache2`.
9. SSH ke root project Laravel (`cd /var/www/backend-app` atau path yang sesuai di server).
10. Pastikan `.env` di server sudah berisi konfigurasi `FIREBIRD_HOST`, `FIREBIRD_PORT`, `FIREBIRD_DATABASE`, `FIREBIRD_USERNAME`, `FIREBIRD_PASSWORD`, dan `FIREBIRD_CHARSET` yang benar.
11. Jalankan `php artisan config:clear` dan `php artisan cache:clear` agar konfigurasi database terbaru terbaca.
12. Uji koneksi Firebird dari Laravel menggunakan `php artisan tinker` dan menjalankan `DB::connection('firebird')->select('SELECT FIRST 1 NO_AGT FROM ANGGOTA');` atau dengan memanggil endpoint `import-anggota-firebird` dari Postman/Flutter.
13. Pantau log Laravel (`storage/logs/laravel.log`) untuk memastikan tidak ada lagi error `could not find driver` dan bahwa query ke Firebird berhasil dijalankan.

## Risks / Catatan
- Instalasi paket `firebird3.0-server` akan men‑setup instance Firebird lokal; pastikan tidak bentrok dengan arsitektur yang sudah ada (jika hanya butuh client, fokus pada `libfbclient2` namun paket server sering mempermudah pemenuhan dependensi).
- Pastikan versi PHP di CLI dan yang dipakai web server (PHP-FPM/Apache module) sama‑sama 8.3; perbedaan versi bisa menyebabkan satu lingkungan punya `pdo_firebird` dan yang lain tidak.
- Setelah semua langkah selesai, jika masih muncul error lain (misalnya timeout atau credential salah), lakukan pengecekan ulang pada konfigurasi `.env` dan akses jaringan ke host Firebird `103.253.212.105:3050`.

