# Data Jumlah Keluarga (Data Jlh Keluarga)

## Overview

Fitur **Data Jumlah Keluarga** digunakan untuk mencatat informasi jumlah anggota keluarga dari setiap anggota yang terdaftar di sistem. Data ini terkait dengan tabel anggota melalui relasi foreign key `NO_AGT`.

---

## Tabel Database

**Nama Tabel:** `data_jlh_keluarga`

**Primary Key:** `NO_AGT`

**Foreign Key:**
- `NO_AGT` → `anggota.NO_AGT` (cascade on update, no action on delete)

---

## Field / Kolom

| Nama Field | Tipe Data | Nullable | Keterangan |
|------------|-----------|----------|------------|
| `NO_AGT` | string (15) | NO | **Primary Key** - Nomor anggota (relasi ke tabel `anggota`) |
| `JLH_AGT_KEL` | string (50) | YES | Jumlah anggota keluarga |
| `TGL` | string (50) | YES | Tanggal pencatatan data |

---

## Isian Field (Create/Update)

### 1. Create (POST) - Field yang Diperlukan

```json
{
  "NO_AGT": "string (required, max: 15)",
  "JLH_AGT_KEL": "integer (optional)",
  "TGL": "string (optional, max: 50)"
}
```

**Validasi:**
- `NO_AGT`: **required**, string, maksimal 15 karakter
- `JLH_AGT_KEL`: **optional**, integer
- `TGL`: **optional**, string, maksimal 50 karakter

**Contoh Request (Create):**
```json
{
  "NO_AGT": "AGT001",
  "JLH_AGT_KEL": 5,
  "TGL": "2026-01-08"
}
```

---

### 2. Update (PUT) - Field yang Bisa Diubah

```json
{
  "JLH_AGT_KEL": "integer (optional)",
  "TGL": "string (optional, max: 50)"
}
```

**Validasi:**
- `JLH_AGT_KEL`: **optional**, integer
- `TGL`: **optional**, string, maksimal 50 karakter

**Contoh Request (Update):**
```json
{
  "JLH_AGT_KEL": 6,
  "TGL": "2026-01-10"
}
```

**Catatan:** Field `NO_AGT` adalah **primary key** dan **tidak bisa diubah** saat update.

---

## Route / Endpoint API

### Base URL
```
/api/data-jlh-keluarga
```

### Daftar Endpoint

| Method | Endpoint | Keterangan | Auth Required |
|--------|----------|------------|---------------|
| GET | `/api/data-jlh-keluarga` | List semua data (dengan pagination) | ✅ Yes |
| POST | `/api/data-jlh-keluarga` | Create data baru | ✅ Yes |
| GET | `/api/data-jlh-keluarga/{NO_AGT}` | Show detail data by NO_AGT | ✅ Yes |
| PUT | `/api/data-jlh-keluarga/{NO_AGT}` | Update data by NO_AGT | ✅ Yes |
| DELETE | `/api/data-jlh-keluarga/{NO_AGT}` | Delete data by NO_AGT | ✅ Yes |

---

## Detail Endpoint

### 1. GET - List Data (Paginated)

**Endpoint:** `GET /api/data-jlh-keluarga`

**Query Parameters:**
- `per_page` (optional, default: 15) - Jumlah data per halaman
- `NO_AGT` (optional) - Filter by nomor anggota

**Header:**
```
Authorization: Bearer {token}
```

**Contoh Request:**
```bash
GET /api/data-jlh-keluarga?per_page=10&NO_AGT=AGT001
```

**Response Success (200):**
```json
{
  "data": [
    {
      "NO_AGT": "AGT001",
      "JLH_AGT_KEL": "5",
      "TGL": "2026-01-08"
    }
  ],
  "links": {
    "first": "http://localhost/api/data-jlh-keluarga?page=1",
    "last": "http://localhost/api/data-jlh-keluarga?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://localhost/api/data-jlh-keluarga",
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

---

### 2. POST - Create Data Baru

**Endpoint:** `POST /api/data-jlh-keluarga`

**Header:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "NO_AGT": "AGT001",
  "JLH_AGT_KEL": 5,
  "TGL": "2026-01-08"
}
```

**Response Success (201):**
```json
{
  "data": {
    "NO_AGT": "AGT001",
    "JLH_AGT_KEL": "5",
    "TGL": "2026-01-08"
  }
}
```

**Response Error (422 - Validation Error):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "NO_AGT": [
      "The NO_AGT field is required."
    ]
  }
}
```

---

### 3. GET - Show Detail by NO_AGT

**Endpoint:** `GET /api/data-jlh-keluarga/{NO_AGT}`

**Header:**
```
Authorization: Bearer {token}
```

**Contoh Request:**
```bash
GET /api/data-jlh-keluarga/AGT001
```

**Response Success (200):**
```json
{
  "data": {
    "NO_AGT": "AGT001",
    "JLH_AGT_KEL": "5",
    "TGL": "2026-01-08"
  }
}
```

**Response Error (404):**
```json
{
  "message": "No query results for model [App\\Models\\DataJlhKeluarga] AGT001"
}
```

---

### 4. PUT - Update Data

**Endpoint:** `PUT /api/data-jlh-keluarga/{NO_AGT}`

**Header:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "JLH_AGT_KEL": 6,
  "TGL": "2026-01-10"
}
```

**Response Success (200):**
```json
{
  "data": {
    "NO_AGT": "AGT001",
    "JLH_AGT_KEL": "6",
    "TGL": "2026-01-10"
  }
}
```

**Response Error (404):**
```json
{
  "message": "No query results for model [App\\Models\\DataJlhKeluarga] AGT001"
}
```

---

### 5. DELETE - Hapus Data

**Endpoint:** `DELETE /api/data-jlh-keluarga/{NO_AGT}`

**Header:**
```
Authorization: Bearer {token}
```

**Contoh Request:**
```bash
DELETE /api/data-jlh-keluarga/AGT001
```

**Response Success (204 No Content):**
```
(No response body)
```

**Response Error (404):**
```json
{
  "message": "No query results for model [App\\Models\\DataJlhKeluarga] AGT001"
}
```

---

## File-file Terkait

### Model
- **File:** `app/Models/DataJlhKeluarga.php`
- Primary Key: `NO_AGT`
- Timestamps: Disabled

### Controller
- **File:** `app/Http/Controllers/Api/DataJlhKeluargaController.php`
- Methods: `index`, `store`, `show`, `update`, `destroy`

### Service Layer
- **File:** `app/Services/DataJlhKeluargaService.php`
- Business logic untuk CRUD operations

### Form Requests (Validation)
- **Create:** `app/Http/Requests/StoreDataJlhKeluargaRequest.php`
- **Update:** `app/Http/Requests/UpdateDataJlhKeluargaRequest.php`

### Resource (Response Formatting)
- **File:** `app/Http/Resources/DataJlhKeluargaResource.php`

### Migration
- **Create Table:** `database/migrations/2025_12_13_145903_create_data_jlh_keluarga_table.php`
- **Foreign Keys:** `database/migrations/2025_12_13_145906_add_foreign_keys_to_data_jlh_keluarga_table.php`

---

## Catatan Penting

1. **Authentication Required**: Semua endpoint memerlukan autentikasi menggunakan Laravel Sanctum (Bearer token).

2. **Primary Key**: `NO_AGT` adalah primary key dan tidak bisa diubah setelah data dibuat.

3. **Foreign Key Constraint**: Field `NO_AGT` harus ada di tabel `anggota` terlebih dahulu sebelum bisa membuat data jumlah keluarga.

4. **Data Type**: Meskipun `JLH_AGT_KEL` disimpan sebagai string di database, validasi menerima input sebagai integer.

5. **Soft Delete**: Model ini **tidak menggunakan** soft delete.

6. **Timestamps**: Model ini **tidak menggunakan** timestamps (`created_at`, `updated_at`).

---

## Contoh Flow Penggunaan

1. **Pastikan anggota sudah terdaftar** dengan `NO_AGT` tertentu
2. **Create** data jumlah keluarga dengan endpoint POST
3. **List/Filter** data dengan endpoint GET (list)
4. **View Detail** dengan endpoint GET (show)
5. **Update** jika ada perubahan jumlah keluarga
6. **Delete** jika data tidak diperlukan

---

## Testing dengan Postman

1. Login terlebih dahulu untuk mendapatkan token
2. Set Authorization header: `Bearer {token}`
3. Test create → show → update → delete
4. Verifikasi validasi field (coba kirim tanpa `NO_AGT`)
5. Test pagination dengan parameter `per_page`

---

**Dokumen dibuat:** 2026-01-08  
**Versi API:** 1.0
