
# Requirements – Backend Aplikasi Manajemen Data Kunjungan

Dokumen ini menjelaskan kebutuhan bisnis dan alur proses untuk backend Manajemen Data Kunjungan.

---

## 1. Glossary & Entities

### 1.1 Entities

- **Data Kunjungan**  
  Representasi satu record kunjungan, berisi informasi pengunjung, tanggal, tujuan, dll.

### 1.2 Roles

- **Admin/User**  
  User yang dapat melakukan CRUD pada data kunjungan (dengan autentikasi Sanctum).

---

## 2. High-Level Flow

### 2.1 Flow Utama

1. User (authenticated) dapat **membuat data kunjungan baru**.
2. User dapat **melihat list data kunjungan** (dengan pagination).
3. User dapat **melihat detail data kunjungan**.
4. User dapat **mengupdate data kunjungan existing**.
5. User dapat **menghapus data kunjungan**.

Tidak ada approval atau workflow kompleks untuk V1; hanya CRUD sederhana.

---

## 3. Detail Proses

### 3.1 Pembuatan Data Kunjungan Baru

**Endpoint backend:**

- `POST /api/v1/data-kunjungan`
- Body:
  - `nama_pengunjung` (string, required)
  - `tanggal_kunjungan` (date, required)
  - `tujuan_kunjungan` (text, required)
  - Field lain sesuai kebutuhan (opsional)

**Aturan:**

- Validasi: Semua field required, format date valid.
- Response: JSON dengan data yang dibuat.

### 3.2 Melihat List Data Kunjungan

**Endpoint:**

- `GET /api/v1/data-kunjungan`
- Query params: `page` (pagination), `per_page` (default 15)

**Aturan:**

- Return list data dengan meta pagination.

### 3.3 Melihat Detail Data Kunjungan

**Endpoint:**

- `GET /api/v1/data-kunjungan/{id}`

**Aturan:**

- Jika tidak ditemukan, return 404.

### 3.4 Update Data Kunjungan

**Endpoint:**

- `PUT /api/v1/data-kunjungan/{id}`
- Body: Sama seperti create, partial update allowed.

**Aturan:**

- Validasi field yang dikirim.
- Jika tidak ditemukan, return 404.

### 3.5 Delete Data Kunjungan

**Endpoint:**

- `DELETE /api/v1/data-kunjungan/{id}`

**Aturan:**

- Soft delete jika diperlukan (opsional).

---

## 4. Aturan Data & Validasi

- Field wajib: `nama_pengunjung`, `tanggal_kunjungan`, `tujuan_kunjungan`.
- Validasi: String max length, date format.
- Tidak ada file upload untuk V1.

---

## 5. Status & State

Tidak ada status khusus; hanya created/updated/deleted timestamps.

---

## 6. Catatan

- Sistem ini dimulai dengan CRUD dasar.



