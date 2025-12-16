# Backend API Documentation

## Informasi Umum

### Base URL
```
http://127.0.0.1:8000/api
```

### Format Response
Semua response menggunakan format JSON dengan struktur yang konsisten menggunakan Laravel API Resources.

### Authentication
API menggunakan **Laravel Sanctum** dengan token-based authentication. Token akan expired setelah **120 menit** (2 jam) sejak dibuat.

---

## Authentication Flow

### 1. Register User Baru

**Endpoint:** `POST /auth/register`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "user"
}
```

**Field Validation:**
- `name`: required, string, max 255 karakter
- `email`: required, email valid, unique di database
- `password`: required, min 8 karakter, harus sama dengan password_confirmation
- `password_confirmation`: required
- `role`: optional, string (default: "user")

**Success Response (201 Created):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "user",
    "is_active": true,
    "last_login_at": null,
    "created_at": "2025-12-16T10:30:00.000000Z",
    "updated_at": "2025-12-16T10:30:00.000000Z"
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz1234567890"
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": [
      "The email has already been taken."
    ]
  }
}
```

---

### 2. Login

**Endpoint:** `POST /auth/login`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Field Validation:**
- `email`: required, email valid
- `password`: required

**Success Response (200 OK):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "user",
    "is_active": true,
    "last_login_at": "2025-12-16T10:35:00.000000Z",
    "created_at": "2025-12-16T10:30:00.000000Z",
    "updated_at": "2025-12-16T10:35:00.000000Z"
  },
  "token": "2|xyz9876543210abcdefghijklmnopqrstuvw"
}
```

**Error Response (401 Unauthorized):**
```json
{
  "message": "Invalid credentials"
}
```

---

### 3. Get Current User Info

**Endpoint:** `GET /auth/me`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Success Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "user",
    "is_active": true,
    "last_login_at": "2025-12-16T10:35:00.000000Z",
    "created_at": "2025-12-16T10:30:00.000000Z",
    "updated_at": "2025-12-16T10:35:00.000000Z"
  }
}
```

**Error Response (401 Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

---

### 4. Logout

**Endpoint:** `POST /auth/logout`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Success Response (200 OK):**
```json
{
  "message": "Logged out successfully"
}
```

**Error Response (401 Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

---

## Protected Resources

Semua endpoint di bawah ini **memerlukan authentication**. Sertakan token di header:

```
Authorization: Bearer {token}
```

---

## 1. Anggota (Members)

### Get All Anggota (Paginated)

**Endpoint:** `GET /anggota`

**Query Parameters:**
- `page`: (optional) nomor halaman, default = 1
- `per_page`: (optional) jumlah item per halaman, default = 15
- Semua field dari tabel `anggota` bisa digunakan sebagai filter

**Example Request:**
```
GET /anggota?page=1&per_page=20&ID_KS=001
```

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "NO_AGT": "A001",
      "NAMA": "John Doe",
      "ID_KS": "001",
      "JENIS_KELAMIN": "L",
      "TEMPAT_LAHIR": "Jakarta",
      "TANGGAL_LAHIR": "1990-01-15",
      "HUBUNGAN_KELUARGA": "Kepala Keluarga",
      "STATUS_KAWIN": "Kawin",
      "AGAMA": "Islam",
      "KEBANGSAAN": "Indonesia",
      "PENDIDIKAN": "S1",
      "PEKERJAAN": "Karyawan Swasta"
    }
  ],
  "links": {
    "first": "http://127.0.0.1:8000/api/anggota?page=1",
    "last": "http://127.0.0.1:8000/api/anggota?page=5",
    "prev": null,
    "next": "http://127.0.0.1:8000/api/anggota?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "http://127.0.0.1:8000/api/anggota",
    "per_page": 20,
    "to": 20,
    "total": 95
  }
}
```

---

### Get Single Anggota by NO_AGT

**Endpoint:** `GET /anggota/{NO_AGT}`

**Example Request:**
```
GET /anggota/A001
```

**Success Response (200 OK):**
```json
{
  "data": {
    "NO_AGT": "A001",
    "NAMA": "John Doe",
    "ID_KS": "001",
    "JENIS_KELAMIN": "L",
    "TEMPAT_LAHIR": "Jakarta",
    "TANGGAL_LAHIR": "1990-01-15",
    "HUBUNGAN_KELUARGA": "Kepala Keluarga",
    "STATUS_KAWIN": "Kawin",
    "AGAMA": "Islam",
    "KEBANGSAAN": "Indonesia",
    "PENDIDIKAN": "S1",
    "PEKERJAAN": "Karyawan Swasta"
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "Anggota not found"
}
```

---

## 2. Kel Sah (Keluarga Sejahtera)

### Get All Kel Sah (Paginated)

**Endpoint:** `GET /kel-sah`

**Query Parameters:**
- `page`: (optional) nomor halaman, default = 1
- `per_page`: (optional) jumlah item per halaman, default = 15
- Semua field dari tabel `kel_sah` bisa digunakan sebagai filter

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "ID_KS": "001",
      "KEPALA_KELUARGA": "John Doe",
      "NO_RT": "001",
      "NO_RW": "01",
      "KODE_DESA": "3201012001",
      "ALAMAT": "Jl. Merdeka No. 123"
    }
  ],
  "links": { "..." },
  "meta": { "..." }
}
```

---

### Get Single Kel Sah by ID_KS

**Endpoint:** `GET /kel-sah/{ID_KS}`

**Example Request:**
```
GET /kel-sah/001
```

**Success Response (200 OK):**
```json
{
  "data": {
    "ID_KS": "001",
    "KEPALA_KELUARGA": "John Doe",
    "NO_RT": "001",
    "NO_RW": "01",
    "KODE_DESA": "3201012001",
    "ALAMAT": "Jl. Merdeka No. 123"
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "KelSah not found"
}
```

---

## 3. Data LO (Lembaga Operasional)

### Get All Data LO (Paginated)

**Endpoint:** `GET /data-lo`

**Query Parameters:**
- `page`: (optional) nomor halaman, default = 1
- `per_page`: (optional) jumlah item per halaman, default = 15
- Semua field dari tabel `data_lo` bisa digunakan sebagai filter

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "ID_LO": "LO001",
      "NAMA_LO": "Kelompok Bina Keluarga Balita",
      "JENIS_LO": "BKB",
      "STATUS": "Aktif",
      "TAHUN_PEMBENTUKAN": 2020
    }
  ],
  "links": { "..." },
  "meta": { "..." }
}
```

---

### Get Single Data LO by ID_LO

**Endpoint:** `GET /data-lo/{ID_LO}`

**Example Request:**
```
GET /data-lo/LO001
```

**Success Response (200 OK):**
```json
{
  "data": {
    "ID_LO": "LO001",
    "NAMA_LO": "Kelompok Bina Keluarga Balita",
    "JENIS_LO": "BKB",
    "STATUS": "Aktif",
    "TAHUN_PEMBENTUKAN": 2020
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "DataLo not found"
}
```

---

## 4. Data AO (Anggota Operasional)

### Get All Data AO (Paginated)

**Endpoint:** `GET /data-ao`

**Query Parameters:**
- `page`: (optional) nomor halaman, default = 1
- `per_page`: (optional) jumlah item per halaman, default = 15
- Semua field dari tabel `data_ao` bisa digunakan sebagai filter

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "NO_AO": "AO001",
      "ID_LO": "LO001",
      "NO_AGT": "A001",
      "JABATAN": "Ketua",
      "STATUS_KEANGGOTAAN": "Aktif"
    }
  ],
  "links": { "..." },
  "meta": { "..." }
}
```

---

### Get Single Data AO by NO_AO

**Endpoint:** `GET /data-ao/{NO_AO}`

**Example Request:**
```
GET /data-ao/AO001
```

**Success Response (200 OK):**
```json
{
  "data": {
    "NO_AO": "AO001",
    "ID_LO": "LO001",
    "NO_AGT": "A001",
    "JABATAN": "Ketua",
    "STATUS_KEANGGOTAAN": "Aktif"
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "DataAo not found"
}
```

---

## 5. Realisasi

**Note:** Realisasi menggunakan **composite primary key** (`ID_KS` + `TGL_TGT`).

### Get All Realisasi (Paginated)

**Endpoint:** `GET /realisasi`

**Query Parameters:**
- `page`: (optional) nomor halaman, default = 1
- `per_page`: (optional) jumlah item per halaman, default = 15
- Semua field dari tabel `realisasi` bisa digunakan sebagai filter

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "ID_KS": "001",
      "TGL_TGT": "2025-01-01",
      "NILAI_REALISASI": 1500000,
      "KETERANGAN": "Realisasi bulan Januari"
    }
  ],
  "links": { "..." },
  "meta": { "..." }
}
```

---

### Get Single Realisasi by ID_KS and TGL_TGT

**Endpoint:** `GET /realisasi/{ID_KS}/{TGL_TGT}`

**Example Request:**
```
GET /realisasi/001/2025-01-01
```

**Success Response (200 OK):**
```json
{
  "data": {
    "ID_KS": "001",
    "TGL_TGT": "2025-01-01",
    "NILAI_REALISASI": 1500000,
    "KETERANGAN": "Realisasi bulan Januari"
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "Realisasi not found"
}
```

---

## 6. Data TRS (Transaksi)

### Get All Data TRS (Paginated)

**Endpoint:** `GET /data-trs`

**Query Parameters:**
- `page`: (optional) nomor halaman, default = 1
- `per_page`: (optional) jumlah item per halaman, default = 15
- Semua field dari tabel `data_trs` bisa digunakan sebagai filter

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "NO_TRS": "TRS001",
      "ID_KS": "001",
      "TGL_TRS": "2025-12-15",
      "JENIS_TRS": "Simpanan",
      "NOMINAL": 500000,
      "KETERANGAN": "Simpanan wajib"
    }
  ],
  "links": { "..." },
  "meta": { "..." }
}
```

---

### Get Single Data TRS by NO_TRS

**Endpoint:** `GET /data-trs/{NO_TRS}`

**Example Request:**
```
GET /data-trs/TRS001
```

**Success Response (200 OK):**
```json
{
  "data": {
    "NO_TRS": "TRS001",
    "ID_KS": "001",
    "TGL_TRS": "2025-12-15",
    "JENIS_TRS": "Simpanan",
    "NOMINAL": 500000,
    "KETERANGAN": "Simpanan wajib"
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "DataTrs not found"
}
```

---

## 7. Target

**Note:** Target menggunakan **composite primary key** (`ID_KS` + `TGL_TGT`).

### Get All Target (Paginated)

**Endpoint:** `GET /target`

**Query Parameters:**
- `page`: (optional) nomor halaman, default = 1
- `per_page`: (optional) jumlah item per halaman, default = 15
- Semua field dari tabel `target` bisa digunakan sebagai filter

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "ID_KS": "001",
      "TGL_TGT": "2025-01-01",
      "NILAI_TARGET": 2000000,
      "KETERANGAN": "Target bulan Januari"
    }
  ],
  "links": { "..." },
  "meta": { "..." }
}
```

---

### Get Single Target by ID_KS and TGL_TGT

**Endpoint:** `GET /target/{ID_KS}/{TGL_TGT}`

**Example Request:**
```
GET /target/001/2025-01-01
```

**Success Response (200 OK):**
```json
{
  "data": {
    "ID_KS": "001",
    "TGL_TGT": "2025-01-01",
    "NILAI_TARGET": 2000000,
    "KETERANGAN": "Target bulan Januari"
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "Target not found"
}
```

---

## 8. Data Jumlah Keluarga

### Get All Data Jumlah Keluarga (Paginated)

**Endpoint:** `GET /data-jlh-keluarga`

**Query Parameters:**
- `page`: (optional) nomor halaman, default = 1
- `per_page`: (optional) jumlah item per halaman, default = 15
- Semua field dari tabel `data_jlh_keluarga` bisa digunakan sebagai filter

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "ID_JLH_KEL": "JK001",
      "ID_KS": "001",
      "JUMLAH_ANGGOTA": 5,
      "TAHUN": 2025
    }
  ],
  "links": { "..." },
  "meta": { "..." }
}
```

---

### Get Single Data Jumlah Keluarga by ID_JLH_KEL

**Endpoint:** `GET /data-jlh-keluarga/{ID_JLH_KEL}`

**Example Request:**
```
GET /data-jlh-keluarga/JK001
```

**Success Response (200 OK):**
```json
{
  "data": {
    "ID_JLH_KEL": "JK001",
    "ID_KS": "001",
    "JUMLAH_ANGGOTA": 5,
    "TAHUN": 2025
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "DataJlhKeluarga not found"
}
```

---

## 9. Data Kunjungan

### Get All Data Kunjungan (Paginated)

**Endpoint:** `GET /data-kunjungan`

**Query Parameters:**
- `page`: (optional) nomor halaman, default = 1
- `per_page`: (optional) jumlah item per halaman, default = 15
- Semua field dari tabel `data_kunjungan` bisa digunakan sebagai filter

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "NO_URT": 1,
      "ID_KS": "001",
      "TANGGAL": "2025-12-15",
      "KETERANGAN": "Kunjungan rumah bulanan",
      "STATUS": "Selesai"
    }
  ],
  "links": { "..." },
  "meta": { "..." }
}
```

---

### Get Single Data Kunjungan by NO_URT

**Endpoint:** `GET /data-kunjungan/{NO_URT}`

**Example Request:**
```
GET /data-kunjungan/1
```

**Success Response (200 OK):**
```json
{
  "data": {
    "NO_URT": 1,
    "ID_KS": "001",
    "TANGGAL": "2025-12-15",
    "KETERANGAN": "Kunjungan rumah bulanan",
    "STATUS": "Selesai"
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "DataKunjungan not found"
}
```

---

## Error Handling

### Error Response Format

Semua error response mengikuti format standar Laravel:

**Validation Error (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": [
      "Error message 1",
      "Error message 2"
    ]
  }
}
```

**Authentication Error (401):**
```json
{
  "message": "Unauthenticated."
}
```

**Not Found Error (404):**
```json
{
  "message": "Resource not found"
}
```

**Server Error (500):**
```json
{
  "message": "Server Error"
}
```

---

## HTTP Status Codes

| Status Code | Description |
|-------------|-------------|
| 200 | OK - Request berhasil |
| 201 | Created - Resource berhasil dibuat (register) |
| 401 | Unauthorized - Token invalid/expired atau credentials salah |
| 404 | Not Found - Resource tidak ditemukan |
| 422 | Unprocessable Entity - Validation error |
| 500 | Internal Server Error - Error di server |

---

## Pagination

Semua endpoint list/index menggunakan Laravel pagination dengan struktur response:

```json
{
  "data": [ /* array of resources */ ],
  "links": {
    "first": "http://127.0.0.1:8000/api/resource?page=1",
    "last": "http://127.0.0.1:8000/api/resource?page=10",
    "prev": "http://127.0.0.1:8000/api/resource?page=1",
    "next": "http://127.0.0.1:8000/api/resource?page=3"
  },
  "meta": {
    "current_page": 2,
    "from": 16,
    "last_page": 10,
    "path": "http://127.0.0.1:8000/api/resource",
    "per_page": 15,
    "to": 30,
    "total": 150
  }
}
```

### Pagination Parameters

- `page`: Nomor halaman yang ingin diakses (default: 1)
- `per_page`: Jumlah item per halaman (default: 15, max: 100)

**Example:**
```
GET /anggota?page=2&per_page=25
```

---

## Filtering

Semua endpoint list/index mendukung filtering berdasarkan field yang ada di tabel.

**Example:**
```
GET /anggota?ID_KS=001&JENIS_KELAMIN=L
GET /data-trs?TGL_TRS=2025-12-15&JENIS_TRS=Simpanan
```

Filter akan melakukan exact match pada nilai yang diberikan.

---

## Tips untuk Frontend Developer

### 1. Token Management
- Simpan token di localStorage atau secure cookie
- Set token di header setiap request ke protected endpoint
- Handle token expiration (401 response) dengan redirect ke login
- Token expired setelah 120 menit, pertimbangkan refresh mechanism

### 2. Request Headers
Selalu sertakan header ini:
```javascript
{
  'Content-Type': 'application/json',
  'Accept': 'application/json',
  'Authorization': 'Bearer ' + token // untuk protected endpoint
}
```

### 3. Error Handling
```javascript
// Example error handling
try {
  const response = await fetch(url, options);
  const data = await response.json();
  
  if (!response.ok) {
    if (response.status === 401) {
      // Token expired, redirect to login
      redirectToLogin();
    } else if (response.status === 422) {
      // Validation errors
      showValidationErrors(data.errors);
    } else {
      // Other errors
      showErrorMessage(data.message);
    }
  }
  
  return data;
} catch (error) {
  showErrorMessage('Network error');
}
```

### 4. Pagination Handling
```javascript
// Akses pagination info dari response
const { data, meta } = response;
const currentPage = meta.current_page;
const totalPages = meta.last_page;
const totalItems = meta.total;

// Build pagination UI dari meta
```

### 5. Date Format
Semua tanggal menggunakan format ISO 8601: `YYYY-MM-DD` atau `YYYY-MM-DDTHH:mm:ss.000000Z`

---

## Testing Examples

### Using cURL

**Register:**
```bash
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Login:**
```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

**Get Anggota (with token):**
```bash
curl -X GET http://127.0.0.1:8000/api/anggota \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Using JavaScript Fetch

**Register:**
```javascript
const register = async (userData) => {
  const response = await fetch('http://127.0.0.1:8000/api/auth/register', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(userData)
  });
  
  return await response.json();
};
```

**Get Protected Resource:**
```javascript
const getAnggota = async (token, page = 1) => {
  const response = await fetch(`http://127.0.0.1:8000/api/anggota?page=${page}`, {
    method: 'GET',
    headers: {
      'Accept': 'application/json',
      'Authorization': `Bearer ${token}`
    }
  });
  
  return await response.json();
};
```

---

## Summary Endpoints

### Public Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Register user baru |
| POST | `/auth/login` | Login dan dapatkan token |

### Protected Endpoints (Require Token)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/auth/me` | Get info user yang login |
| POST | `/auth/logout` | Logout dan revoke token |
| GET | `/anggota` | Get list anggota (paginated) |
| GET | `/anggota/{NO_AGT}` | Get detail anggota |
| GET | `/kel-sah` | Get list keluarga sejahtera (paginated) |
| GET | `/kel-sah/{ID_KS}` | Get detail keluarga sejahtera |
| GET | `/data-lo` | Get list lembaga operasional (paginated) |
| GET | `/data-lo/{ID_LO}` | Get detail lembaga operasional |
| GET | `/data-ao` | Get list anggota operasional (paginated) |
| GET | `/data-ao/{NO_AO}` | Get detail anggota operasional |
| GET | `/realisasi` | Get list realisasi (paginated) |
| GET | `/realisasi/{ID_KS}/{TGL_TGT}` | Get detail realisasi (composite key) |
| GET | `/data-trs` | Get list transaksi (paginated) |
| GET | `/data-trs/{NO_TRS}` | Get detail transaksi |
| GET | `/target` | Get list target (paginated) |
| GET | `/target/{ID_KS}/{TGL_TGT}` | Get detail target (composite key) |
| GET | `/data-jlh-keluarga` | Get list jumlah keluarga (paginated) |
| GET | `/data-jlh-keluarga/{ID_JLH_KEL}` | Get detail jumlah keluarga |
| GET | `/data-kunjungan` | Get list kunjungan (paginated) |
| GET | `/data-kunjungan/{NO_URT}` | Get detail kunjungan |

**Total: 25 endpoints** (2 public + 23 protected)

---

## Contact & Support

Untuk pertanyaan atau issue terkait API, silakan hubungi tim development.

---

**Last Updated:** December 16, 2025  
**API Version:** 1.0  
**Framework:** Laravel 12.0
