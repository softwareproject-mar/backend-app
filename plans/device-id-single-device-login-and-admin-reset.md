# Plan: Device ID Single-Device Login + Admin Reset

## Context
- Saat ini backend auth (`/auth/register`, `/auth/login`) belum menyimpan dan memvalidasi `device_id`.
- Frontend aplikasi user (Capacitor + React) belum mengirim `device_id` pada register/login.
- Web Admin sudah punya halaman pengajuan akun (`PersetujuanAkun`) untuk alur approve/reject, tetapi belum ada aksi reset device untuk akun user yang sudah approved.
- Kebutuhan bisnis: role `user` harus terkunci pada satu device; login dari device lain ditolak kecuali admin reset device binding. Role `admin`/`super_admin` tidak terkena device lock.

## Goal
- Menyimpan `device_id` akun role `user` saat registrasi dan enforce saat login.
- Menyediakan aksi admin untuk reset binding device agar user bisa login dari HP baru.
- Menjaga alur sederhana: setelah reset oleh admin, login pertama user dari device baru otomatis merekam `device_id` baru.

## Detailed Specifications
- **Backend database**
  - Tambah migration baru di `database/migrations/` untuk menambah kolom nullable `device_id` (string, indexed) pada tabel `users`.
  - Kolom `device_id` dipakai khusus role `user`; untuk role lain boleh null dan tidak dipakai validasi lock.

- **Backend model/resource**
  - Update `app/Models/User.php`:
    - Tambah `device_id` ke `$fillable`.
  - Update `app/Http/Resources/UserResource.php`:
    - Ekspos `device_id` dalam response JSON agar Web Admin bisa menampilkan status binding (tanpa menampilkan detail sensitif lain).

- **Backend request validation**
  - Update `app/Http/Requests/RegisterWithOtpRequest.php`:
    - Tambah rule wajib `device_id` (string, max length yang aman untuk platform mobile).
  - Update `app/Http/Requests/LoginRequest.php`:
    - Tambah rule wajib `device_id` (string).

- **Backend auth service logic**
  - Update `app/Services/AuthService.php` method `register(array $data, bool $otpVerified = false)`:
    - Saat role `user`, simpan `device_id` dari request.
    - Saat role non-`user`, abaikan `device_id` (set null atau tidak diisi).
  - Update `app/Services/AuthService.php` method `login(array $credentials)`:
    - Ambil `device_id` dari request.
    - Jika role `user`:
      - Jika `users.device_id` null/kosong, bind otomatis ke `device_id` request (khusus skenario setelah admin reset).
      - Jika `users.device_id` ada dan sama, login lanjut.
      - Jika berbeda, tolak login dengan `ValidationException` message khusus mismatch device.
    - Jika role `admin` / `super_admin`: skip validasi device lock.
  - Pertahankan update `last_login_at` dan pembuatan Sanctum token seperti alur sekarang.

- **Backend admin reset endpoint**
  - Tambah endpoint baru di `routes/api.php` dalam group middleware `admin`:
    - `POST /users/{id}/reset-device` (atau `PATCH`, konsisten dengan style route saat ini).
  - Tambah method baru di `app/Http/Controllers/Api/UserApprovalController.php`:
    - Validasi target user ada.
    - Batasi hanya untuk target role `user`.
    - Batasi hanya untuk akun yang sudah `registration_status = approved`.
    - Set `device_id = null`.
    - Opsional revoke token aktif user tersebut (`tokens()->delete()`) agar sesi lama langsung invalid.
    - Isi metadata reviewer (`registration_reviewed_by` / timestamp) hanya jika memang disepakati dipakai ulang untuk audit; jika tidak, cukup log activity.
  - Jika project sudah memiliki service aktivitas (`app/Services/ActivityLogService.php`), catat aksi reset device oleh admin.

- **Frontend aplikasi user (Kelompok Sahabat Obor Mas)**
  - Tambah utility baru, contoh `src/services/deviceService.ts`:
    - Ambil unique device identifier menggunakan plugin Capacitor Device.
    - Fallback untuk web/dev (mis. local generated installation id di localStorage) agar dev flow tetap jalan.
  - Update dependency `@capacitor/device` di `package.json` aplikasi user jika belum ada.
  - Update `src/services/authService.ts`:
    - `login(payload)` kirim `device_id`.
    - `register(payload)` kirim `device_id`.
  - Update caller terkait:
    - `src/state/authStore.tsx` untuk login.
    - `src/app/components/RegisterScreen.tsx` untuk register.
  - Tambah handling error message khusus device mismatch agar user mendapat pesan jelas (“akun terdaftar di perangkat lain, hubungi admin untuk reset perangkat”).

- **Frontend Web Admin**
  - Update `c:/Users/galih/Documents/ui/Web Admin/src/app/pages/PersetujuanAkun.tsx`:
    - Pada baris status `approved`, tampilkan tombol aksi baru `Reset Device` di kolom Aksi.
    - Tampilkan tombol hanya untuk role `user`.
    - Tambah dialog konfirmasi sebelum memanggil endpoint reset.
    - Setelah sukses: refresh queue + stats.
  - Alternatif/opsional tambahan:
    - Tambah aksi yang sama di `ManajemenUserPage.tsx` agar super admin bisa reset dari modul manajemen user.
  - Update tipe data row user agar memuat `device_id` jika ditampilkan di UI.

- **Testing**
  - Backend feature tests baru (di `tests/Feature/`), minimal:
    - Register user menyimpan `device_id`.
    - Login user ditolak jika `device_id` mismatch.
    - Login user sukses jika `device_id` match.
    - Login user auto-bind jika `device_id` null (setelah reset).
    - Login admin tidak terkena lock device.
    - Admin dapat reset device untuk user approved.
    - Admin tidak dapat reset device untuk role non-user / user belum approved.
  - Frontend manual test checklist:
    - Register dari HP A -> login HP A sukses.
    - Login HP B ditolak.
    - Admin reset di web admin -> login HP B sukses dan tersimpan sebagai device baru.

## Implementation Checklist
1. Buat migration `users.device_id` nullable + index pada backend.
2. Update model `User` untuk menerima `device_id` di mass assignment.
3. Update `UserResource` untuk expose `device_id` ke client admin.
4. Tambah rule validasi `device_id` pada `RegisterWithOtpRequest`.
5. Tambah rule validasi `device_id` pada `LoginRequest`.
6. Ubah `AuthService::register()` agar menyimpan `device_id` untuk role `user` saja.
7. Ubah `AuthService::login()` agar enforce single-device untuk role `user` (match / auto-bind when null / reject when mismatch).
8. Tambah endpoint protected admin untuk reset device binding user.
9. Implement method reset device di `UserApprovalController` dengan guard role/status + clear `device_id`.
10. (Jika disepakati) revoke semua token user saat reset device agar sesi lama langsung putus.
11. (Jika tersedia) catat aksi reset device ke activity log.
12. Tambah util device identifier di aplikasi user (`deviceService`) dan integrasikan dengan Capacitor Device.
13. Update request register di app user agar selalu menyertakan `device_id`.
14. Update request login di app user agar selalu menyertakan `device_id`.
15. Tambah mapping pesan error device mismatch di UI login user.
16. Update halaman `PersetujuanAkun` Web Admin dengan tombol + dialog `Reset Device` untuk akun approved.
17. Hubungkan aksi `Reset Device` Web Admin ke endpoint backend baru lalu refresh data tabel.
18. Tambah/ubah tipe data web admin agar `device_id` tersedia untuk kebutuhan tampilan/status.
19. Tambah feature tests backend untuk skenario lock device + reset oleh admin.
20. Jalankan verifikasi lint/test yang relevan di backend dan frontend sebelum finalisasi.

## Risks / Catatan
- Device identifier native bisa berbeda saat reinstall/reset perangkat; ini sesuai kebutuhan lock ketat, namun akan meningkatkan kebutuhan reset oleh admin.
- Perlu fallback yang konsisten saat berjalan di web/dev agar tim dev tidak terblokir saat testing non-mobile.
- Jika token lama tidak dicabut saat reset, sesi device lama bisa tetap aktif sampai token expired/logout; keputusan revoke token perlu ditegaskan saat eksekusi.
- UI web admin perlu wording jelas agar operator paham dampak aksi reset device.
