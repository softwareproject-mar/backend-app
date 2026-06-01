# Plan: Middleware API — user biasa wajib aktif & approved

## Context

- Setelah register OTP, backend mengeluarkan token Sanctum sementara `is_active = false` dan `registration_status = pending`.
- Grup route `auth:sanctum` saat ini membuka seluruh resource (anggota, kunjungan, dll.) untuk bearer token apa pun, selama user terautentikasi.
- `AuthService::login` sudah menolak login jika `! is_active`, tetapi token dari **register** tetap valid untuk endpoint lain sampai dicabut.
- Plan induk: `plans/persetujuan-akun-alur-pending-approve.md` — bagian **opsional** item 8–11 belum diimplementasikan.

## Goal

1. Untuk pengguna dengan **`role === user`**, blokir akses ke endpoint API bisnis (anggota, data master, activity log, impor, tes firebird) jika **`is_active`** bukan true atau **`registration_status`** bukan **`approved`**.
2. Tetap izinkan **`GET /api/auth/me`** dan **`POST /api/auth/logout`** agar klien bisa menampilkan status pendaftaran dan logout.
3. **`admin`** dan **`super_admin`** tidak dikenai aturan ini (lewat middleware tanpa cek tambahan).
4. Grup **`admin`** dan **`super-admin`** tetap seperti sekarang (tidak perlu middleware baru di dalamnya).

## Detailed Specifications

### File baru

- Path: **`app/Http/Middleware/EnsureMemberApprovedForApi.php`**
- Class name: **`EnsureMemberApprovedForApi`**
- Behavior:
  - Ambil `$request->user()`. Jika null, biarkan (tidak terjadi setelah `auth:sanctum` kecuali edge case) atau return next — praktis `auth:sanctum` sudah menangani.
  - Jika `$user->role` **bukan** `'user'` (mis. `admin`, `super_admin`), panggil `$next($request)` tanpa cek lanjutan.
  - Jika `$user->role === 'user'`, izinkan hanya jika **`$user->is_active`** truthy **dan** **`$user->registration_status === User::REGISTRATION_APPROVED`** (gunakan konstanta model `User`, bukan string magic di banyak tempat).
  - Jika gagal, return **`JsonResponse`** status **403** dengan body minimal: **`message`** (string bahasa Indonesia), mis. bahwa akun menunggu persetujuan atau ditolak / nonaktif — boleh satu pesan generik untuk `pending` vs `rejected` atau dua variasi berdasarkan `registration_status` (spesifikasi bebas asalkan konsisten dan tidak membocorkan data sensitif).
- Dependency: `App\Models\User`, `Closure`, `Illuminate\Http\Request`, `Symfony\Component\HttpFoundation\Response`.

### Registrasi alias middleware

- File: **`bootstrap/app.php`**
- Di dalam `->withMiddleware(function (Middleware $middleware) { ... })`, pada array **`$middleware->alias([...])`**, tambahkan satu entri:
  - Key alias string: **`member_approved`**
  - Value: **`\App\Http\Middleware\EnsureMemberApprovedForApi::class`**

### Restrukturisasi route

- File: **`routes/api.php`**
- Di dalam grup **`Route::middleware('auth:sanctum')->group(function () { ... })`**:
  1. Pertama, definisikan **hanya** dua route berikut **tanpa** middleware `member_approved`:
     - **`POST /auth/logout`** → `AuthController@logout`
     - **`GET /auth/me`** → `AuthController@me`
  2. Buka grup **`Route::middleware('member_approved')->group(function () { ... });`** yang membungkus **seluruh** route yang saat ini ada di bawah sanctum **kecuali** dua route di atas, **kecuali** grup `admin`, **kecuali** grup `prefix('super-admin')`.
  3. Isi grup `member_approved` harus berisi persis blok route yang dipindahkan, urutan relatif antar-route di dalam blok **tetap sama** seperti sekarang (penting untuk precedence `export` sebelum `{anggota}`, dll.).
  4. Grup **`Route::middleware('admin')->group(...)`** (UserApprovalController) tetap **di luar** grup `member_approved` tetapi **tetap di dalam** `auth:sanctum` (admin/super_admin mengakses persetujuan tanpa cek `member_approved`, atau jika secara tidak sengaja masuk, role bukan `user` sehingga lolos).
  5. Grup **`Route::prefix('super-admin')->middleware('super_admin')`** tetap di dalam `auth:sanctum`, di luar `member_approved`.

### Yang tidak diubah dalam plan ini

- **`UserManagementController::update`** — tidak diubah kecuali plan terpisah disetujui (bypass aktivasi dari Manajemen User tetap ada).
- **`AuthController::register`** — tetap mengembalikan token (perubahan “tidak kirim token sampai approved” = scope produk terpisah).
- Hapus route debug `debug-ketua-ks` / `test-firebird-connection` — **bukan** bagian plan ini (jangan sentuh kecuali task lain).

## Implementation Checklist (atomik)

1. Buat file `app/Http/Middleware/EnsureMemberApprovedForApi.php` dengan class `EnsureMemberApprovedForApi` dan method `handle(Request $request, Closure $next): Response` sesuai behavior di atas.
2. Tambahkan alias `member_approved` → `EnsureMemberApprovedForApi::class` di `bootstrap/app.php`.
3. Edit `routes/api.php`: pindahkan route `logout` dan `me` ke atas grup sanctum (tetap di dalam sanctum), lalu bungkus sisa route (activity-logs sampai data-trs) dalam `Route::middleware('member_approved')->group(...)`.
4. Pastikan grup `admin` (user approval) dan `super-admin` tetap sibling di dalam `auth:sanctum`, tidak berada di dalam `member_approved`.
5. Jalankan `php artisan route:list --path=api` (atau setara) dan verifikasi tidak ada duplikasi path dan urutan resource tetap benar.
6. Uji manual atau feature test ringkas: token user `pending` memanggil `GET /api/auth/me` → 200; memanggil `GET /api/anggota` (atau satu resource representatif) → 403; token user `approved`+aktif → 200 pada resource yang sama; token `admin` → resource tetap dapat diakses sesuai aturan existing controller.

## Risks / Catatan

- **Aplikasi mobile** yang setelah register langsung memanggil API data akan mendapat **403** — UI harus menampilkan menunggu persetujuan (cek `registration_status` dari `auth/me`).
- User **`rejected`** tetap nonaktif; pesan 403 sebaiknya membedakan atau mengarahkan ke kontak admin (opsional copywriting).
- **`activity-logs`** ikut diblokir untuk `pending` — selaras dengan “belum disetujui”; jika bisnis ingin log tetap terbaca, itu deviasi dan perlu keputusan terpisah.

---

## IMPLEMENTATION CHECKLIST (urut eksekusi — salinan untuk eksekusi)

1. Implementasikan `EnsureMemberApprovedForApi` di `app/Http/Middleware/EnsureMemberApprovedForApi.php`.
2. Daftarkan alias `member_approved` di `bootstrap/app.php`.
3. Restrukturisasi `routes/api.php` sesuai spesifikasi (sanctum → me/logout → member_approved group → admin group → super-admin group).
4. Verifikasi daftar route dan precedence export vs parameter dinamis.
5. Uji token untuk role `user` pending / approved / admin.
