# Plan: Web Admin – Integrasi Backend, Admin & Super Admin

## Context

- **Backend Laravel** (`backend-app`): Sudah punya API auth (login, register, logout, me), User model dengan `role` dan `is_active`, ActivityLog, Sanctum.
- **Web Admin** (`ui/Web Admin`): React + Vite, login hardcode (`admin@obormas.com` / `Admin@123`), localStorage auth, belum call API. Menu: Beranda, Riwayat, Persetujuan Akun (semua mock).

**Goal:**
1. Hubungkan Web Admin ke backend (login via API, token, auth client).
2. Dukung 2 role: **Admin** dan **Super Admin**.
3. Super Admin: identifikasi hardcode (email + password di env), redirect ke halaman Super Admin.
4. Admin: Persetujuan Akun → approve → email aktivasi, registrasi default `is_active = false`.
5. Super Admin menu: Dashboard, Manajemen Admin, Manajemen User, Riwayat Sistem.
6. Admin menu: Beranda (grid data), Riwayat Aktivitas, Persetujuan Akun.

---

## Detailed Specifications

### Phase 1: Backend – Auth & Super Admin

#### 1.1 Environment & Config
- **File:** `backend-app/.env.example`
  - Tambah:
    ```
    SUPER_ADMIN_EMAIL=superadmin@obormas.com
    SUPER_ADMIN_PASSWORD=SuperAdmin@123
    ```
- Config opsional: bisa buat `config/obormas.php` jika diperlukan, atau pakai env langsung di seeder.

#### 1.2 Super Admin – User di DB (Seeder)
- Super Admin = user di DB dengan `role = 'super_admin'`, dibuat oleh seeder. AuthService tidak diubah.
  - Credential dari env. Frontend cek `user.role === 'super_admin'` untuk redirect ke `/super-admin/dashboard`.

#### 1.3 Role `super_admin`
- **File:** `backend-app/app/Models/User.php` — Sudah ada `role` string, tidak perlu enum. Pastikan value `super_admin` valid.
- **File:** `backend-app/app/Http/Requests/RegisterWithOtpRequest.php` — Role validation: `in:admin,user` (jangan allow `super_admin` dari register).
- **File:** `backend-app/database/seeders/SuperAdminSeeder.php` (baru)
  - Buat user dengan `email` = env('SUPER_ADMIN_EMAIL'), `password` = Hash::make(env('SUPER_ADMIN_PASSWORD')), `role` = 'super_admin', `is_active` = true, `name` = 'Super Admin'.
  - Panggil di DatabaseSeeder.
- **File:** `backend-app/.env.example` — Tambah `SUPER_ADMIN_EMAIL`, `SUPER_ADMIN_PASSWORD`.

#### 1.4 Registrasi – Default `is_active = false`
- **File:** `backend-app/app/Services/AuthService.php` — Di `register()`, set `'is_active' => $data['is_active'] ?? false` (default false). Untuk OTP register flow, user yang daftar sendiri = false.

---

### Phase 2: Backend – Approval & Email Aktivasi

#### 2.1 Mail – Aktivasi Akun
- **File:** `backend-app/app/Mail/AccountActivationMail.php` (baru)
  - Mailable: subject "Aktivasi Akun - Kelompok Sahabat Obor Mas", body: notifikasi akun aktif, bisa login.
  - Constructor: `User $user`
  - View: `emails/account-activation.blade.php`
- **File:** `backend-app/resources/views/emails/account-activation.blade.php` (baru)
  - Teks: "Akun Anda telah diaktifkan. Anda dapat login dengan email dan password yang telah didaftarkan."

#### 2.2 Job – Kirim Email Aktivasi
- **File:** `backend-app/app/Jobs/SendAccountActivationJob.php` (baru)
  - Implements ShouldQueue. Constructor: `User $user`. Handle: `Mail::to($user->email)->send(new AccountActivationMail($user))`.

#### 2.3 Endpoint – Pending Users & Approve
- **File:** `backend-app/app/Http/Controllers/Api/UserApprovalController.php` (baru)
  - `index(Request $request)`: GET, list user dengan `is_active = false`, pagination. Hanya admin/super_admin.
  - `approve(int $id)`: POST/PATCH, set `is_active = true`, dispatch `SendAccountActivationJob`, return success.
- **File:** `backend-app/routes/api.php`
  - Tambah route dalam `auth:sanctum`:
    - `GET api/users/pending` → UserApprovalController@index
    - `POST api/users/{id}/approve` → UserApprovalController@approve
  - Middleware role: hanya admin dan super_admin boleh akses.
- **Middleware:** `backend-app/app/Http/Middleware/EnsureUserIsAdmin.php` (baru)
  - Cek `auth()->user()->role` in ['admin','super_admin']. Jika tidak, 403.

---

### Phase 3: Backend – Super Admin API

#### 3.1 Middleware Role
- **File:** `backend-app/app/Http/Middleware/EnsureUserIsAdmin.php` — role in ['admin','super_admin'].
- **File:** `backend-app/app/Http/Middleware/EnsureUserIsSuperAdmin.php` — role === 'super_admin'.
- Register di `bootstrap/app.php` atau `app/Http/Kernel.php`.

#### 3.2 User Management API
- **File:** `backend-app/app/Http/Controllers/Api/SuperAdmin/UserManagementController.php` (baru)
  - `index(Request $request)`: GET, list users (filter role: admin/user), pagination. Hanya super_admin.
  - `store(Request $request)`: POST, create user (admin). Validasi: name, email, password, role. Hanya super_admin.
  - `update(Request $request, int $id)`: PATCH, update role dan/atau is_active. Hanya super_admin.
  - `destroy(int $id)`: DELETE, hapus user. Hanya super_admin.
- **File:** `backend-app/app/Http/Controllers/Api/SuperAdmin/DashboardController.php` (baru)
  - `stats()`: GET, return `{ total_users, total_admins, total_active, total_inactive }`.
  - `recentActivities()`: GET, return 10 aktivitas terbaru.
  - `chartData(Request $request)`: GET, query params `period=day|week`, return `{ labels: [], registrations: [], activities: [] }` untuk grafik.
- **File:** `backend-app/app/Http/Controllers/Api/SuperAdmin/AdminManagementController.php` (baru)
  - `index(Request $request)`: GET, list users where role = 'admin'. Pagination.
  - Create/Update/Delete admin — bisa pakai UserManagementController yang sama, dengan filter role.
- **Routes:** Grup prefix `api/super-admin`, middleware `auth:sanctum`, `EnsureUserIsSuperAdmin`:
  - `GET dashboard/stats`
  - `GET dashboard/recent-activities`
  - `GET dashboard/chart`
  - `GET users` (semua user)
  - `GET admins` (hanya admin)
  - `POST users` (create admin/user)
  - `PATCH users/{id}` (update role, is_active)
  - `DELETE users/{id}`

#### 3.3 Riwayat Sistem API
- **File:** `backend-app/app/Http/Controllers/Api/SuperAdmin/SystemActivityController.php` (baru)
  - `index(Request $request)`: GET, list activity_logs SEMUA user (tanpa filter user_id), pagination, filter search, date_from, date_to, action_type. Hanya super_admin.
- **Migration:** Activity log `user_id` — saat ini required. Untuk event "User registrasi" bisa pakai user_id = newly created user. Untuk konsistensi, biarkan required. Tambah action_type jika perlu. Migration ubah `action_type` dari enum ke string(50) agar bisa 'register','login','approve','activate','deactivate', dll.
- **File:** `backend-app/database/migrations/2026_03_15_000000_change_activity_logs_action_type_to_string.php` (baru)
  - Ubah kolom `action_type` dari enum ke `string('action_type', 50)`.

#### 3.4 Activity Logging untuk Event Baru
- Approval: di UserApprovalController, setelah approve, create ActivityLog (user_id = admin, action_type = 'approve', resource_type = 'user', resource_id = approved user id).
- Registrasi: di AuthController register, setelah User::create, create ActivityLog (user_id = new user id, action_type = 'register', ...). Butuh helper yang bisa log dengan user_id explicit (bukan auth).
- Buat helper/service `ActivityLogService::log($userId, $userName, $actionType, $resourceType, $resourceId, $description, ...)`.
- Login: log di AuthController login (user_id = logged in user, action_type = 'login').
- Admin create/update/delete user: log di UserManagementController.

---

### Phase 4: Web Admin – Auth & API Client

#### 4.1 Config – API Base URL
- **File:** `backend-app/.env.example` — N/A (backend)
- **File:** `ui/Web Admin/.env.example` (baru)
  - `VITE_API_BASE_URL=http://localhost:8000`
- **File:** `ui/Web Admin/src/config/api.ts` (baru)
  - `export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';`

#### 4.2 Auth Service
- **File:** `ui/Web Admin/src/services/authService.ts` (baru)
  - `login(email: string, password: string): Promise<{ user, token }>`
  - `logout(): void` — clear storage
  - `getToken(): string | null`
  - `getUser(): User | null` (dari localStorage)
  - `setAuth(user, token): void` — save to localStorage
  - Types: `User { id, name, email, role, is_active }`

#### 4.3 API Client
- **File:** `ui/Web Admin/src/services/api.ts` (baru)
  - `apiClient`: fetch wrapper, base URL dari config, tambah header `Authorization: Bearer ${token}` dari authService.getToken()
  - `get`, `post`, `patch`, `delete` methods

---

### Phase 5: Web Admin – Login & Routing

#### 5.1 Login Page
- **File:** `ui/Web Admin/src/app/pages/Login.tsx`
  - Ganti hardcode dengan `authService.login(email, password)`. On success: `authService.setAuth(user, token)`. Cek `user.role`: jika `super_admin` → `navigate('/super-admin/dashboard')`, else → `navigate('/')`.
  - On error: tampilkan pesan dari API.

#### 5.2 Protected Route & Role Guard
- **File:** `ui/Web Admin/src/app/components/ProtectedRoute.tsx`
  - Cek token dan user dari authService (atau localStorage). Jika tidak ada → Navigate to /login.
  - Props: `allowedRoles?: string[]`. Jika ada, cek user.role in allowedRoles. Jika tidak match → redirect.
- **File:** `ui/Web Admin/src/app/components/SuperAdminRoute.tsx` (baru)
  - Cek role === 'super_admin'. Jika tidak → Navigate to '/'.

#### 5.3 Routes
- **File:** `ui/Web Admin/src/app/routes.tsx`
  - `/login` → Login
  - `/` → ProtectedRoute (allowedRoles: ['admin','super_admin']) → Layout → Beranda
  - `/super-admin` → SuperAdminRoute → SuperAdminLayout
  - `/super-admin/dashboard` → SuperAdminDashboard
  - `/super-admin/manajemen-admin` → ManajemenAdminPage
  - `/super-admin/manajemen-user` → ManajemenUserPage
  - `/super-admin/riwayat-sistem` → RiwayatSistemPage
  - Children dari Layout (admin): anggota, data-lo, data-ao, kelompok-sahabat, ketua-ks, sekretaris-ks, pengelola, riwayat-aktivitas, persetujuan-akun
  - `*` → Navigate to /login

---

### Phase 6: Web Admin – Layout & Menu

#### 6.1 Layout Dinamis
- **File:** `ui/Web Admin/src/app/components/Layout.tsx`
  - Ambil user dari authService. Jika role === 'super_admin' → render SuperAdminLayout atau redirect ke /super-admin/dashboard. Jika admin → render sidebar Admin (Beranda, Riwayat, Persetujuan Akun) + content.
  - Atau: Layout cek path. Jika path.startsWith('/super-admin') → render SuperAdminLayout. Else render Admin layout.
- **Keputusan:** Pisah Layout. Route `/` dan children memakai `Layout` (Admin). Route `/super-admin/*` memakai `SuperAdminLayout`.
- **File:** `ui/Web Admin/src/app/components/SuperAdminLayout.tsx` (baru)
  - Sidebar: Dashboard, Manajemen Admin, Manajemen User, Riwayat Sistem. Tambah link "Ke Admin" atau "Beranda Admin" jika Super Admin juga akses Beranda.
  - Sesuai requirement: Super Admin punya menu terpisah. Beranda Admin (Anggota, LO, dll) — apakah Super Admin punya akses? Dari diskusi: "super admin bisa lebih di sisi akses". Kita asumsikan Super Admin TIDAK perlu akses Beranda data (Anggota, LO, dll), fokus ke Dashboard, Manajemen Admin, Manajemen User, Riwayat Sistem.
  - Header: tampilkan nama/email user, logout.

---

### Phase 7: Web Admin – Super Admin Pages

#### 7.1 Dashboard
- **File:** `ui/Web Admin/src/app/pages/super-admin/DashboardPage.tsx` (baru)
  - Fetch stats: GET /api/super-admin/dashboard/stats
  - Fetch recent activities: GET /api/super-admin/dashboard/recent-activities
  - Fetch chart: GET /api/super-admin/dashboard/chart?period=week
  - Tampilkan: 4 card stat (Total User, Total Admin, Akun Aktif, Akun Nonaktif), tabel aktivitas terbaru, grafik (Recharts).

#### 7.2 Manajemen Admin
- **File:** `ui/Web Admin/src/app/pages/super-admin/ManajemenAdminPage.tsx` (baru)
  - Tabel: No, Nama, Email, Role, Status, Tanggal Dibuat, Aksi.
  - Tombol: Tambah Admin. Modal form: Nama, Email, Role (admin), Password.
  - Aksi per baris: Aktifkan/Nonaktifkan, Hapus (dengan konfirmasi).
  - API: GET /api/super-admin/admins, POST /api/super-admin/users, PATCH /api/super-admin/users/{id}, DELETE /api/super-admin/users/{id}.

#### 7.3 Manajemen User
- **File:** `ui/Web Admin/src/app/pages/super-admin/ManajemenUserPage.tsx` (baru)
  - Tabel: No, Nama, Email, Role, Status, Tanggal Registrasi, Aksi.
  - Filter: role (user/admin). Aksi: Ubah role (dropdown Admin/User), Aktifkan/Nonaktifkan.
  - API: GET /api/super-admin/users?role=user, PATCH /api/super-admin/users/{id}.

#### 7.4 Riwayat Sistem
- **File:** `ui/Web Admin/src/app/pages/super-admin/RiwayatSistemPage.tsx` (baru)
  - Tabel: No, Nama Pengguna, Aktivitas, Resource, Waktu, Status.
  - Filter: search, date range, action_type. Pagination.
  - API: GET /api/super-admin/system-activity atau GET /api/activity-logs?scope=all (dengan middleware super_admin).

---

### Phase 8: Web Admin – Admin Pages (Integrasi API)

#### 8.1 Persetujuan Akun
- **File:** `ui/Web Admin/src/app/pages/PersetujuanAkun.tsx`
  - Fetch: GET /api/users/pending. Replace mock data.
  - Approve: POST /api/users/{id}/approve. On success, refresh list.
  - Reject: (opsional) PATCH /api/users/{id}/reject atau set status. Jika belum ada, bisa skip dulu, fokus approve.

#### 8.2 Riwayat Aktivitas
- **File:** `ui/Web Admin/src/app/pages/RiwayatAktivitas.tsx`
  - Fetch: GET /api/activity-logs (user's own logs). Replace mock.
  - Tetap filter user_id di backend (sudah ada).

#### 8.3 Layout – User Info
- **File:** `ui/Web Admin/src/app/components/Layout.tsx`
  - User name/email dari authService.getUser(), bukan hardcode.

---

## Implementation Checklist

### Backend (Laravel)

1. Buat `config/obormas.php` dengan key `super_admin_email`, `super_admin_password`.
2. Update `.env.example`: `SUPER_ADMIN_EMAIL`, `SUPER_ADMIN_PASSWORD`.
3. Buat `SuperAdminSeeder` — create user super_admin dari env. Register di DatabaseSeeder.
4. Update `AuthService::register()` — `is_active => false` default.
5. Buat migration `change_activity_logs_action_type_to_string` — ubah action_type ke string(50).
6. Buat `AccountActivationMail` + view `emails/account-activation.blade.php`.
7. Buat `SendAccountActivationJob`.
8. Buat `UserApprovalController` — index (pending), approve (id).
9. Buat middleware `EnsureUserIsAdmin` dan `EnsureUserIsSuperAdmin`.
10. Daftarkan middleware di bootstrap.
11. Tambah routes: GET users/pending, POST users/{id}/approve, dengan middleware Admin.
12. Buat `ActivityLogService` helper untuk log dengan user_id explicit.
13. Di AuthController register: log activity 'register'. Di login: log 'login'.
14. Di UserApprovalController approve: log 'approve', dispatch job.
15. Buat `SuperAdmin/UserManagementController` — index, store, update, destroy.
16. Buat `SuperAdmin/DashboardController` — stats, recentActivities, chartData.
17. Buat `SuperAdmin/SystemActivityController` — index (all logs).
18. Tambah routes grup super-admin.
19. Update `ActivityLogController` atau buat endpoint terpisah untuk system logs (super admin only).
20. Seed admin default `admin@obormas.com` / `Admin@123` jika belum ada (untuk testing).

### Web Admin (React)

21. Buat `.env.example` dengan `VITE_API_BASE_URL`.
22. Buat `src/config/api.ts`.
23. Buat `src/services/authService.ts`.
24. Buat `src/services/api.ts`.
25. Update `Login.tsx` — call authService.login, redirect by role.
26. Update `ProtectedRoute.tsx` — cek token, optional allowedRoles.
27. Buat `SuperAdminRoute.tsx`.
28. Buat `SuperAdminLayout.tsx`.
29. Update `routes.tsx` — add super-admin routes.
30. Buat `DashboardPage.tsx` (Super Admin).
31. Buat `ManajemenAdminPage.tsx`.
32. Buat `ManajemenUserPage.tsx`.
33. Buat `RiwayatSistemPage.tsx`.
34. Update `PersetujuanAkun.tsx` — fetch & approve via API.
35. Update `RiwayatAktivitas.tsx` — fetch via API.
36. Update `Layout.tsx` — user info dari authService.

---

## Risks / Catatan

- **CORS:** Pastikan `config/cors.php` allowed_origins include Web Admin dev URL (e.g. localhost:5173).
- ** Sanctum:** Stateful domains include Web Admin origin.
- **Reject flow:** Persetujuan Akun — reject (hapus/tolak pendaftaran) belum di-spec. Bisa Phase 2.
- **Admin seeder:** User `admin@obormas.com` perlu ada untuk testing. Tambah di seeder.
- **Activity log user_id nullable:** Jika event "sistem" tanpa user (e.g. cron), perlu nullable. Untuk registrasi kita pakai new user's id. Tetap required dulu.
