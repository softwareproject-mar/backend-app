# Plan: Email ke admin — pendaftar baru & pengingat H+3

## Context

- Pendaftar self-service berakhir dengan `registration_status = pending` dan `is_active = false` (`AuthController::register`).
- Belum ada notifikasi email ke administrator saat ada pendaftar baru.
- Template visual yang diinginkan mengikuti gaya `resources/views/emails/account-activation.blade.php` (card, header, warna, struktur serupa); isi teks berbeda (untuk admin, bukan “akun Anda diaktifkan”).
- Email aktivasi ke **user** tetap seperti sekarang (`SendAccountActivationJob` setelah approve).

## Goal

1. Saat registrasi berhasil (user baru **pending**), kirim email ke **semua** akun dengan **`role`** yang dianggap administrator panel: **`admin`** dan **`super_admin`** (keduanya punya akses Persetujuan di sistem ini). Jika ke depan hanya ingin literal `role = admin` saja, cukup ubah query penerima menjadi `where('role', 'admin')` pada langkah terkait.
2. **Satu** email pengingat untuk pendaftaran yang sama, **3 hari kalender** setelah waktu pendaftar dibuat (`created_at`), **hanya jika** `registration_status` masih **`pending`** (belum disetujui / ditolak).
3. Isi email (langsung + pengingat) memuat informasi akun yang menunggu tindakan: minimal **nama**, **email pendaftar**, **tanggal registrasi**; opsional **ID user** untuk rujukan internal.

## Detailed Specifications

### Konfigurasi

- Tidak wajib variabel `.env` baru untuk daftar penerima (penerima dari query `users`). Opsional nanti: `ADMIN_PENDING_NOTIFY_ENABLED=true` untuk mematikan di non-produksi — **tidak** masuk scope wajib plan ini kecuali disetujui saat eksekusi.

### File baru

1. **`app/Mail/PendingRegistrationAdminMail.php`**
   - Constructor: `User $pendingUser` (user yang baru daftar / yang dicek), `bool $isReminder = false`.
   - `envelope()`: subject berbeda untuk notifikasi pertama vs pengingat (mis. “Pendaftar baru menunggu persetujuan” vs “Pengingat: pendaftar masih menunggu persetujuan”).
   - `content()`: view `emails.pending-registration-admin`.

2. **`resources/views/emails/pending-registration-admin.blade.php`**
   - Salin struktur HTML/CSS dari `account-activation.blade.php` (`.shell`, `.card`, `.header`, `.body`, `.email-box`, `.info`, `.footer`) dan sesuaikan:
     - Judul/subjudul untuk admin (bukan “Akun berhasil diaktifkan”).
     - Paragraf yang menjelaskan ada pendaftar yang perlu ditinjau di Web Admin / Persetujuan Akun.
     - Blok detail: `$pendingUser->name`, `$pendingUser->email`, `$pendingUser->created_at` (format tanggal ramah pembaca).
     - Jika `$isReminder` true, tampilkan kalimat pengingat (satu paragraf) tanpa mengubah layout dasar.
   - Variabel view: `$pendingUser` (model `User`), `$isReminder` (boolean).

3. **`app/Jobs/NotifyAdminsPendingRegistrationJob.php`** (`ShouldQueue`)
   - Constructor: `public int $pendingUserId`.
   - `handle()`:
     - `User::find($pendingUserId)`; jika null, return.
     - Jika `registration_status !== pending`, return (idempoten jika dipanggil terlambat).
     - Ambil penerima: `User::query()->whereIn('role', ['admin', 'super_admin'])->whereNotNull('email')->pluck('email')->unique()->filter()->values()`.
     - Jika koleksi email kosong, log warning dan return.
     - Untuk setiap alamat (atau satu kiriman dengan BCC — pilih **satu** strategi di implementasi: disarankan **loop `Mail::to($email)->send(...)`** agar log per penerima jelas; hindari expose semua email admin satu sama lain kecuali produk meminta BCC).
     - Kirim `new PendingRegistrationAdminMail($pendingUser, false)`.

4. **`app/Jobs/NotifyAdminsPendingRegistrationReminderJob.php`** (`ShouldQueue`)
   - Constructor: `public int $pendingUserId` (sama).
   - `handle()`:
     - Load user; jika null, return.
     - Jika `registration_status !== User::REGISTRATION_PENDING`, return (sudah diproses — tidak kirim pengingat).
     - Ambil penerima dengan query **sama** seperti job pertama.
     - Kirim `new PendingRegistrationAdminMail($pendingUser, true)`.

### File diubah

5. **`app/Http/Controllers/Api/AuthController.php`**
   - Pada method `register`, setelah `$user = $this->service->register(...)` sukses (dan sebelum atau sesudah hapus OTP — urutan bebas, asal setelah user tersimpan):
     - `NotifyAdminsPendingRegistrationJob::dispatch($user->id);`
     - `NotifyAdminsPendingRegistrationReminderJob::dispatch($user->id)->delay(now()->addDays(3));`
   - Import class job di atas file.

### Queue & scheduler

- Pengingat H+3 memakai **`delay()`** pada job; **tidak** perlu `schedule()` harian untuk requirement ini.
- Prasyarat operasional: **queue worker** berjalan (`php artisan queue:work` atau supervisor); jika `QUEUE_CONNECTION=sync`, delay mungkin tidak berperilaku seperti yang diharapkan — catat di Risiko.

### Yang tidak diubah

- `UserApprovalController` (approve/reject) tidak wajib dispatch apa pun untuk membatalkan job — job pengingat **self-check** status `pending` di `handle()`.
- Template `account-activation.blade.php` dan `SendAccountActivationJob` tidak diubah isi perilaku utamanya.

## Implementation Checklist

1. Buat view `resources/views/emails/pending-registration-admin.blade.php` dengan layout mengikuti `account-activation.blade.php`, variabel `$pendingUser` dan `$isReminder`.
2. Buat `app/Mail/PendingRegistrationAdminMail.php` dengan subject dan view di atas.
3. Buat `app/Jobs/NotifyAdminsPendingRegistrationJob.php` sesuai spesifikasi `handle()`.
4. Buat `app/Jobs/NotifyAdminsPendingRegistrationReminderJob.php` sesuai spesifikasi `handle()`.
5. Edit `app/Http/Controllers/Api/AuthController.php` method `register`: dispatch job pertama dan job kedua dengan `delay(now()->addDays(3))`.
6. Jalankan `php artisan queue:work` (atau setara) di lingkungan dev; uji registrasi satu user pending — pastikan email masuk ke semua `admin`/`super_admin` (atau periksa log jika mail trap).
7. Uji: setelah approve user sebelum H+3, jalankan manual job reminder (atau tunggu) — harus **tidak** mengirim email (karena bukan `pending`).
8. Uji: biarkan user pending, simulasikan eksekusi reminder job setelah 3 hari (atau sementara `delay(1)` di dev lalu kembalikan) — harus kirim satu email pengingat dengan `$isReminder = true`.

## Risks / Catatan

- **Spam**: banyak pendaftar = banyak email ke semua admin; acceptable untuk skala kecil.
- **`super_admin` ikut**: jika organisasi tidak ingin super_admin dapat email ini, ubah query menjadi hanya `admin` saja.
- **Queue tidak jalan**: pengingat H+3 tidak terkirim; notifikasi langsung juga gagal jika bukan `sync` dan worker mati.
- **Timezone**: `addDays(3)` mengikuti timezone aplikasi Laravel; pastikan `config/app.php` timezone produksi sesuai ekspektasi “H+3”.

---

## IMPLEMENTATION CHECKLIST (urut eksekusi)

1. Tambah Blade `emails/pending-registration-admin.blade.php`.
2. Tambah Mailable `PendingRegistrationAdminMail`.
3. Tambah job `NotifyAdminsPendingRegistrationJob`.
4. Tambah job `NotifyAdminsPendingRegistrationReminderJob`.
5. Dispatch kedua job dari `AuthController::register` setelah user `pending` tercipta.
6. Verifikasi manual / dev: email pertama + reminder hanya saat masih pending.
7. Dokumentasikan kebutuhan queue worker di server produksi (bukan kode wajib dalam plan).
