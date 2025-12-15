# Project Overview – Backend Aplikasi Manajemen Data Kunjungan

Backend ini adalah layanan API untuk sistem **Manajemen Data Kunjungan**.  
Tujuannya adalah mengelola data kunjungan melalui operasi CRUD (Create, Read, Update, Delete) pada table `data_kunjungan`.

Sistem backend ini dibangun dengan:

- **Laravel 10/11 + PHP 8.2+**
- **MySQL 8.0+**
- Middleware pendukung: Sanctum untuk authentication, CORS, dll.
- Digunakan oleh frontend (misalnya React/Next.js) untuk konsumsi API.

---

## 1. Tujuan Sistem

Sistem ini dibuat untuk:

1. Mengelola **Data Kunjungan** melalui API RESTful.
2. Mendukung operasi CRUD pada table `data_kunjungan`.
3. Menyediakan autentikasi dasar menggunakan Laravel Sanctum.
4. Siap untuk ekstensi fitur lain di masa depan (seperti approval, notifikasi, dll.).

---

## 2. Fitur Utama Backend (High-Level)

### 2.1 Data Kunjungan Management

- **Create**: Membuat data kunjungan baru.
- **Read**: Mengambil list atau detail data kunjungan.
- **Update**: Mengupdate data kunjungan existing.
- **Delete**: Menghapus data kunjungan.
- Validasi input menggunakan Form Request.
- Response dalam format JSON standar.

### 2.2 Authentication (Opsional untuk V1)

- Login/logout menggunakan Sanctum tokens.
- Middleware `auth:sanctum` untuk protect routes.

### 2.3 Integrasi Eksternal (Future)

- Jika diperlukan, integrasi dengan API eksternal (misalnya HRIS atau lainnya).

---

## 3. Lingkup Backend

Backend meliputi file-file berikut (struktur Laravel standar):

```
app/
├─ Http/
│  ├─ Controllers/
│  │  ├─ Api/
│  │  │  └─ DataKunjunganController.php  → CRUD untuk data_kunjungan
│  │  └─ AuthController.php              → Login/logout (jika ada)
│  ├─ Requests/
│  │  └─ StoreDataKunjunganRequest.php   → Validasi create
│  │  └─ UpdateDataKunjunganRequest.php  → Validasi update
│  └─ Resources/
│     └─ DataKunjunganResource.php       → Shape response JSON
├─ Models/
│  └─ DataKunjungan.php                  → Model Eloquent
├─ Services/                             → Business logic (opsional)
│  └─ DataKunjunganService.php
└─ Policies/                             → Authorization (opsional)
    └─ DataKunjunganPolicy.php

routes/
└─ api.php                               → API routes

database/
├─ migrations/
│  └─ 2023_xx_xx_create_data_kunjungans_table.php
└─ seeders/                              → Seeders jika perlu

config/
├─ app.php
├─ auth.php                              → Sanctum config
└─ database.php

storage/
└─ app/public/                           → Jika ada file upload

tests/
├─ Feature/
│  └─ DataKunjunganTest.php
└─ Unit/
    └─ DataKunjunganServiceTest.php
```

---

## 4. Struktur Database

Table utama: `data_kunjungans`

Kolom contoh (sesuaikan kebutuhan):
- `id` (primary key, auto increment)
- `nama_pengunjung` (string)
- `tanggal_kunjungan` (date)
- `tujuan_kunjungan` (text)
- `created_at`, `updated_at` (timestamps)

Migration: `php artisan make:migration create_data_kunjungans_table`

---

## 5. API Endpoints (V1)

Base path: `/api/v1`

- `GET /api/v1/data-kunjungan` → List data kunjungan (dengan pagination)
- `POST /api/v1/data-kunjungan` → Create data kunjungan baru
- `GET /api/v1/data-kunjungan/{id}` → Detail data kunjungan
- `PUT /api/v1/data-kunjungan/{id}` → Update data kunjungan
- `DELETE /api/v1/data-kunjungan/{id}` → Delete data kunjungan

Response format:
- Success: `{ "data": {...}, "meta": {...} }`
- Error: `{ "error": "message", "errors": {...} }`

---

## 6. Teknologi & Dependencies

- **Laravel Framework**: 10.x atau 11.x
- **PHP**: 8.2+
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Sanctum v3.x
- **Testing**: PHPUnit
- **Code Quality**: Laravel Pint

---

## 7. Development Setup

1. Install dependencies: `composer install`
2. Copy `.env.example` to `.env`
3. Configure DB di `.env`
4. Run migrations: `php artisan migrate`
5. Serve: `php artisan serve` (http://localhost:8000)

---

## 8. Future Extensions

- Notifikasi via email/queue.
- Integrasi dengan sistem eksternal.
- Upload dokumen terkait kunjungan.

---

**Catatan**: Sistem ini dimulai dengan CRUD sederhana untuk `data_kunjungan`. Fitur lain akan ditambahkan secara bertahap sesuai kebutuhan.



## 2. Fitur Utama Backend (High-Level)

### 2.1 Project Management

- Membuat project baru (new project).
- Menyimpan informasi dasar project (nama, lokasi, dsb).
- Menandai project sebagai:
  - Draft
  - Dalam proses approval
  - Approved/Rejected

### 2.2 Version & Document Management

- Menambahkan **versi baru** untuk project, dengan kumpulan dokumen:
  - Layout (PDF) → **wajib ada untuk memulai**
  - 3D (PDF) → boleh kosong saat awal, bisa di-upload kemudian
  - FS (PDF) → boleh kosong saat awal, bisa di-upload kemudian
- Mengizinkan update versi (misalnya layout diperbarui atau 3D/FS ditambahkan belakangan).
- Menghubungkan setiap versi dengan status approval-nya.

### 2.3 Approval Workflow (V1)

Backend mengatur alur approval sebagai berikut:

1. **Project dibuat** + **versi dengan minimal layout** di-submit.
2. Sistem memulai approval V1 dengan urutan:

   1. Manager Business Development
   2. Approver divisi Product
   3. Approver divisi Sales
   4. Direktur Utama (Dirut)
   5. BOD dari masing-masing divisi yang relevan

3. Setiap step bisa:
   - Approve → lanjut ke step berikutnya.
   - Reject → flow berhenti, status versi/project menjadi Rejected (butuh revisi).
   - Opsional: memberi komentar/catatan.

Urutan ini adalah flow **V1** yang akan dijelaskan lebih detail di `REQUIREMENTS.md`.

### 2.4 Integrasi HRIS

- `src/services/hrisService.ts` digunakan untuk mengambil data eksternal, misalnya:
  - informasi organisasi/divisi
  - mapping user ke divisi tertentu
- Data ini digunakan untuk:
  - Menentukan approver dinamis (Product, Sales, BOD per divisi)
  - Validasi role/user

---

## 3. Lingkup Backend

Backend meliputi file-file berikut:

```text
src/
├─ app.ts        → setup Express, middleware, router, /health
├─ server.ts     → entry point server
├─ lib/          → env, backend helpers, notifications, prisma, session, storage
├─ middleware/   → requireAuth, middleware lain
├─ routes/       → auth, projects, comments, tasks, notifications, locations
├─ services/     → hrisService (integrasi eksternal)
├─ types/        → express.d.ts (type augmentation)
└─ prisma/       → schema.prisma + migration
