# 🚀 Backend API Documentation - Complete Reference

**Last Updated:** December 19, 2025  
**API Version:** 1.0  
**Framework:** Laravel 12.0 + PHP 8.2  
**Database:** MySQL 8.0+ (firebird)

---

## 📋 Quick Navigation

- [Base Information](#base-information)
- [Authentication Endpoints](#-authentication-endpoints)
- [Protected Resources (CRUD)](#-protected-resources-crud)
- [Response Format & Errors](#-response-format--errors)
- [Frontend Integration Guide](#-frontend-integration-guide)

---

## Base Information

### Base URL
```
http://127.0.0.1:8000/api
```

### Authentication Method
**Laravel Sanctum** - Token-based authentication

### Required Headers
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}  # untuk protected endpoints
```

### Database Field Naming
**UPPERCASE** convention (contoh: ID_KS, NO_AGT, TGL_KUN, dll)

---

## 🔐 Authentication Endpoints

### 1. Request OTP

```http
POST /auth/request-otp
```

**Purpose:** Request OTP untuk verifikasi email sebelum registrasi

**Request:**
```json
{
  "email": "user@example.com"
}
```

**Response (200):**
```json
{
  "message": "OTP has been sent to your email",
  "email": "user@example.com",
  "expires_in": 300
}
```

**Validation:**
- `email`: required | email | unique:users

**Notes:**
- OTP expired dalam 5 menit
- Saat ini `MAIL_MAILER=log`, cek OTP di `storage/logs/laravel.log`
- Rate limit per email berlaku

---

### 2. Register with OTP

```http
POST /auth/register
```

**Purpose:** Register user baru dengan OTP verification

**Request:**
```json
{
  "name": "John Doe",
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "otp": "123456",
  "role": "user"
}
```

**Response (201):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "role": "user",
    "is_active": true,
    "email_verified_at": "2025-12-19T10:30:00.000000Z",
    "last_login_at": null
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz"
}
```

**Validation:**
- `name`: required | string | max:255
- `email`: required | email
- `password`: required | min:8 | confirmed
- `otp`: required | string | size:6
- `role`: nullable | string (default: "user")

---

### 3. Login

```http
POST /auth/login
```

**Purpose:** Login dengan email & password

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "role": "user",
    "is_active": true,
    "last_login_at": "2025-12-19T10:35:00.000000Z"
  },
  "token": "2|xyz9876543210abcdefgh"
}
```

**Validation:**
- `email`: required | email
- `password`: required

**Error Cases:**
- Invalid credentials → 422
- Account deactivated (`is_active = false`) → 422

---

### 4. Get Current User

```http
GET /auth/me
```

**Purpose:** Get informasi user yang sedang login

**Headers Required:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "role": "user",
    "is_active": true,
    "email_verified_at": "2025-12-19T10:30:00.000000Z",
    "last_login_at": "2025-12-19T10:35:00.000000Z"
  }
}
```

---

### 5. Logout

```http
POST /auth/logout
```

**Purpose:** Logout dan revoke current token

**Headers Required:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

---

## 🔒 Protected Resources (CRUD)

**⚠️ Semua endpoint di bawah memerlukan header:**
```
Authorization: Bearer {token}
```

---

## 1. Data Kunjungan

**Table:** `data_kunjungan`  
**Primary Key:** `NO_URT` (auto-increment)

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/data-kunjungan` | List (paginated) |
| POST | `/data-kunjungan` | Create new |
| GET | `/data-kunjungan/{NO_URT}` | Show detail |
| PUT | `/data-kunjungan/{NO_URT}` | Update |
| DELETE | `/data-kunjungan/{NO_URT}` | Delete |

### Fields

```javascript
{
  NO_URT: integer (auto, primary key),
  ID_LO: string(12) | nullable,
  NO_AGT: string(15) | nullable,
  ID_KEL_SAH: string(12) | nullable,
  TGL_KUN: string(50) | nullable,  // tanggal kunjungan
  KEGIATAN: string(255) | nullable,
  ID_PIC: string(12) | nullable,
  JLH_PESERTA: integer | nullable
}
```

### Filter Parameters (GET)
```
?ID_LO=001&NO_AGT=AGT001&TGL_KUN=2025-12-19&page=1&per_page=20
```

---

## 2. Anggota (Members)

**Table:** `anggota`  
**Primary Key:** `NO_AGT` (string, not auto-increment)

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/anggota` | List (paginated) |
| POST | `/anggota` | Create new |
| GET | `/anggota/{NO_AGT}` | Show detail |
| PUT | `/anggota/{NO_AGT}` | Update |
| DELETE | `/anggota/{NO_AGT}` | Delete |

### Fields

```javascript
{
  NO_AGT: string(15) | required | primary key,
  NAMA: string(255) | nullable,
  ID_KS: string(12) | nullable,
  ID_LO: string(12) | nullable,
  ID_AO: string(12) | nullable,
  ID_KS_ASL: string(12) | nullable,
  TGL_MTS: string(50) | nullable,
  TGL_AKTIF: string(50) | nullable,
  TGL_JA: string(50) | nullable
}
```

---

## 3. Kel Sah (Keluarga Sejahtera)

**Table:** `kel_sah`  
**Primary Key:** `ID_KS`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/kel-sah` | List (paginated) |
| POST | `/kel-sah` | Create new |
| GET | `/kel-sah/{ID_KS}` | Show detail |
| PUT | `/kel-sah/{ID_KS}` | Update |
| DELETE | `/kel-sah/{ID_KS}` | Delete |

### Fields

```javascript
{
  ID_KS: string(12) | required | primary key,
  NAMA_KS: string(255) | nullable,
  // ... fields sesuai table
}
```

---

## 4. Data LO (Lembaga Operasional)

**Table:** `data_lo`  
**Primary Key:** `ID_LO`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/data-lo` | List (paginated) |
| POST | `/data-lo` | Create new |
| GET | `/data-lo/{ID_LO}` | Show detail |
| PUT | `/data-lo/{ID_LO}` | Update |
| DELETE | `/data-lo/{ID_LO}` | Delete |

### Fields

```javascript
{
  ID_LO: string(12) | required | primary key,
  NAMA_LO: string(255) | nullable,
  // ... fields sesuai table
}
```

---

## 5. Data AO (Anggota Operasional)

**Table:** `data_ao`  
**Primary Key:** `NO_AO`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/data-ao` | List (paginated) |
| POST | `/data-ao` | Create new |
| GET | `/data-ao/{NO_AO}` | Show detail |
| PUT | `/data-ao/{NO_AO}` | Update |
| DELETE | `/data-ao/{NO_AO}` | Delete |

---

## 6. Data Jlh Keluarga

**Table:** `data_jlh_keluarga`  
**Primary Key:** `ID_JLH_KEL`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/data-jlh-keluarga` | List (paginated) |
| POST | `/data-jlh-keluarga` | Create new |
| GET | `/data-jlh-keluarga/{ID_JLH_KEL}` | Show detail |
| PUT | `/data-jlh-keluarga/{ID_JLH_KEL}` | Update |
| DELETE | `/data-jlh-keluarga/{ID_JLH_KEL}` | Delete |

---

## 7. Ketua KS

**Table:** `ketua_ks`  
**Primary Key:** `ID_KET`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/ketua-ks` | List (paginated) |
| POST | `/ketua-ks` | Create new |
| GET | `/ketua-ks/{ID_KET}` | Show detail |
| PUT | `/ketua-ks/{ID_KET}` | Update |
| DELETE | `/ketua-ks/{ID_KET}` | Delete |

### Fields

```javascript
{
  ID_KET: string(12) | required | primary key,
  NO_AGT: string(15) | required,
  NAMA: string(50) | nullable,
  STAT: string(50) | nullable,
  TGL_STAT: string(50) | nullable,
  NO_SK: integer | nullable
}
```

---

## 8. Sekretaris KS

**Table:** `sekre_ks`  
**Primary Key:** `ID_SEKRE`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/sekretaris-ks` | List (paginated) |
| POST | `/sekretaris-ks` | Create new |
| GET | `/sekretaris-ks/{ID_SEKRE}` | Show detail |
| PUT | `/sekretaris-ks/{ID_SEKRE}` | Update |
| DELETE | `/sekretaris-ks/{ID_SEKRE}` | Delete |

### Fields

```javascript
{
  ID_SEKRE: string(12) | required | primary key,
  NO_AGT: string(15) | required,
  NAMA: string(50) | nullable,
  STAT: string(50) | nullable,
  TGL_STAT: string(50) | nullable,
  NO_SK: integer | nullable
}
```

---

## 9. Data Penghasilan

**Table:** `data_penghasilan`  
**Primary Key:** `ID_PENGHASILAN`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/data-penghasilan` | List (paginated) |
| POST | `/data-penghasilan` | Create new |
| GET | `/data-penghasilan/{ID_PENGHASILAN}` | Show detail |
| PUT | `/data-penghasilan/{ID_PENGHASILAN}` | Update |
| DELETE | `/data-penghasilan/{ID_PENGHASILAN}` | Delete |

---

## 10. Target (Master/Planning)

**Table:** `target`  
**Composite Primary Key:** `ID_KS` + `TGL_TGT`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/target` | List (paginated) |
| POST | `/target` | Create new |
| GET | `/target/{ID_KS}/{TGL_TGT}` | Show detail |
| PUT | `/target/{ID_KS}/{TGL_TGT}` | Update |
| DELETE | `/target/{ID_KS}/{TGL_TGT}` | Delete |

### Fields (Excerpt - banyak fields)

```javascript
{
  ID_KS: string(12) | required | PK,
  TGL_TGT: string(50) | required | PK,
  JLH_AGT_BR: integer | nullable,
  STR_SP: string(50) | nullable,
  SLD_SP: string(50) | nullable,
  STR_SW: string(50) | nullable,
  SLD_SW: string(50) | nullable,
  // ... 40+ fields lainnya (simpanan, pinjaman, dll)
}
```

**⚠️ Note:** Table target memiliki 50+ fields untuk berbagai jenis simpanan dan pinjaman

---

## 11. Realisasi (Transactional) - READ ONLY

**Table:** `realisasi`  
**Composite Primary Key:** `ID_KS` + `TGL_TGT`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/realisasi` | List (paginated) |
| GET | `/realisasi/{ID_KS}/{TGL_TGT}` | Show detail |

**⚠️ READ ONLY** - Tidak ada POST/PUT/DELETE

---

## 12. Data TRS (Transaksi) - READ ONLY

**Table:** `data_trs`  
**Primary Key:** `NO_TRS`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/data-trs` | List (paginated) |
| GET | `/data-trs/{NO_TRS}` | Show detail |

**⚠️ READ ONLY** - Tidak ada POST/PUT/DELETE

---

## 13. Dashboard (JOIN Target + Realisasi) - READ ONLY

### Endpoint

```http
GET /dashboard
```

**Purpose:** Get data dashboard dengan JOIN antara Target dan Realisasi

**Query Parameters:**
```
?ID_KS=001&TGL_TGT=2025-12-19
```

**Response (200):**
```json
[
  {
    // Combined data dari Target + Realisasi
    "ID_KS": "001",
    "TGL_TGT": "2025-12-19",
    // ... all fields from both tables
  }
]
```

**⚠️ READ ONLY** - Hanya GET

---

## 📊 Response Format & Errors

### Standard Pagination Response

```json
{
  "data": [ /* array of resources */ ],
  "links": {
    "first": "http://127.0.0.1:8000/api/resource?page=1",
    "last": "http://127.0.0.1:8000/api/resource?page=10",
    "prev": null,
    "next": "http://127.0.0.1:8000/api/resource?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "path": "http://127.0.0.1:8000/api/resource",
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

### Pagination & Filter Parameters

```
GET /resource?page=2&per_page=25&FIELD_NAME=value
```

- `page`: Nomor halaman (default: 1)
- `per_page`: Item per halaman (default: 15, max: 100)
- Filter by field: `?ID_KS=001&TGL_KUN=2025-12-19`

---

### HTTP Status Codes

| Code | Meaning | When |
|------|---------|------|
| 200 | OK | Success GET/PUT |
| 201 | Created | Success POST |
| 204 | No Content | Success DELETE |
| 401 | Unauthorized | Token invalid/expired |
| 404 | Not Found | Resource tidak ditemukan |
| 422 | Unprocessable Entity | Validation error |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Server Error | Internal error |

---

### Error Response Format

**Validation Error (422):**
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password confirmation does not match."]
  }
}
```

**Not Found (404):**
```json
{
  "message": "Resource not found"
}
```

**Unauthorized (401):**
```json
{
  "message": "Unauthenticated."
}
```

---

## 🎯 Frontend Integration Guide

### 1. Token Management

```javascript
// Store token
localStorage.setItem('auth_token', token);

// Get token
const token = localStorage.getItem('auth_token');

// Remove token
localStorage.removeItem('auth_token');
```

---

### 2. Axios Setup (Recommended)

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Add token to every request
api.interceptors.request.use(config => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle 401 globally
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

---

### 3. Usage Examples

#### Authentication Flow

```javascript
// 1. Request OTP
const requestOtp = async (email) => {
  try {
    const { data } = await api.post('/auth/request-otp', { email });
    console.log('OTP sent to:', data.email);
    return data;
  } catch (error) {
    console.error('Error:', error.response?.data);
  }
};

// 2. Register
const register = async (userData) => {
  try {
    const { data } = await api.post('/auth/register', userData);
    localStorage.setItem('auth_token', data.token);
    return data.user;
  } catch (error) {
    console.error('Registration failed:', error.response?.data);
  }
};

// 3. Login
const login = async (email, password) => {
  try {
    const { data } = await api.post('/auth/login', { email, password });
    localStorage.setItem('auth_token', data.token);
    return data.user;
  } catch (error) {
    console.error('Login failed:', error.response?.data);
  }
};

// 4. Logout
const logout = async () => {
  try {
    await api.post('/auth/logout');
    localStorage.removeItem('auth_token');
  } catch (error) {
    console.error('Logout failed:', error.response?.data);
  }
};
```

#### CRUD Operations

```javascript
// Get list with pagination
const getAnggota = async (page = 1, filters = {}) => {
  try {
    const { data } = await api.get('/anggota', {
      params: { page, per_page: 20, ...filters }
    });
    return data; // { data: [...], meta: {...}, links: {...} }
  } catch (error) {
    console.error('Error:', error.response?.data);
  }
};

// Get single item
const getAnggotaDetail = async (noAgt) => {
  try {
    const { data } = await api.get(`/anggota/${noAgt}`);
    return data.data; // single item
  } catch (error) {
    console.error('Error:', error.response?.data);
  }
};

// Create
const createAnggota = async (anggotaData) => {
  try {
    const { data } = await api.post('/anggota', anggotaData);
    return data.data;
  } catch (error) {
    console.error('Error:', error.response?.data);
    throw error;
  }
};

// Update
const updateAnggota = async (noAgt, updates) => {
  try {
    const { data } = await api.put(`/anggota/${noAgt}`, updates);
    return data.data;
  } catch (error) {
    console.error('Error:', error.response?.data);
    throw error;
  }
};

// Delete
const deleteAnggota = async (noAgt) => {
  try {
    await api.delete(`/anggota/${noAgt}`);
    return true;
  } catch (error) {
    console.error('Error:', error.response?.data);
    throw error;
  }
};
```

#### Composite Key Example (Target)

```javascript
// Get target with composite key
const getTarget = async (idKs, tglTgt) => {
  try {
    const { data } = await api.get(`/target/${idKs}/${tglTgt}`);
    return data.data;
  } catch (error) {
    console.error('Error:', error.response?.data);
  }
};

// Update target
const updateTarget = async (idKs, tglTgt, updates) => {
  try {
    const { data } = await api.put(`/target/${idKs}/${tglTgt}`, updates);
    return data.data;
  } catch (error) {
    console.error('Error:', error.response?.data);
  }
};
```

---

### 4. React Hooks Example

```javascript
import { useState, useEffect } from 'react';
import api from './api';

// Custom hook untuk fetch list
export const useAnggotaList = (page = 1, filters = {}) => {
  const [data, setData] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        const response = await api.get('/anggota', {
          params: { page, per_page: 15, ...filters }
        });
        setData(response.data.data);
        setMeta(response.data.meta);
        setError(null);
      } catch (err) {
        setError(err.response?.data?.message || 'Failed to fetch data');
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [page, JSON.stringify(filters)]);

  return { data, meta, loading, error };
};

// Usage in component
function AnggotaList() {
  const [page, setPage] = useState(1);
  const { data, meta, loading, error } = useAnggotaList(page);

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div>
      {data.map(anggota => (
        <div key={anggota.NO_AGT}>{anggota.NAMA}</div>
      ))}
      
      <button 
        onClick={() => setPage(p => p - 1)} 
        disabled={page === 1}
      >
        Previous
      </button>
      
      <span>Page {meta.current_page} of {meta.last_page}</span>
      
      <button 
        onClick={() => setPage(p => p + 1)} 
        disabled={page === meta.last_page}
      >
        Next
      </button>
    </div>
  );
}
```

---

### 5. Form Validation Helper

```javascript
// Extract Laravel validation errors
const getFieldError = (errors, fieldName) => {
  return errors?.[fieldName]?.[0] || null;
};

// Example usage in form
const handleSubmit = async (formData) => {
  try {
    await api.post('/anggota', formData);
    // Success
  } catch (error) {
    if (error.response?.status === 422) {
      const errors = error.response.data.errors;
      
      // Display errors per field
      Object.keys(errors).forEach(field => {
        const errorMessage = errors[field][0];
        // Show error in UI for each field
        console.log(`${field}: ${errorMessage}`);
      });
    }
  }
};
```

---

## 📝 Testing dengan Postman/cURL

### cURL Examples

**Request OTP:**
```bash
curl -X POST http://127.0.0.1:8000/api/auth/request-otp \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"test@example.com"}'
```

**Register:**
```bash
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name":"Test User",
    "email":"test@example.com",
    "password":"password123",
    "password_confirmation":"password123",
    "otp":"123456"
  }'
```

**Login:**
```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
```

**Get Protected Resource:**
```bash
curl -X GET http://127.0.0.1:8000/api/anggota?page=1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

**Create Resource:**
```bash
curl -X POST http://127.0.0.1:8000/api/data-kunjungan \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "ID_LO":"001",
    "NO_AGT":"AGT001",
    "TGL_KUN":"2025-12-19",
    "KEGIATAN":"Pertemuan Rutin"
  }'
```

---

## 📊 Complete Endpoint Summary

### Public Endpoints (2)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/request-otp` | Request OTP |
| POST | `/auth/register` | Register with OTP |
| POST | `/auth/login` | Login |

### Protected Endpoints (Require Token)

#### Auth (3)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/auth/me` | Current user info |
| POST | `/auth/logout` | Logout |

#### Full CRUD Resources (9)
| Resource | Endpoints | PK |
|----------|-----------|-----|
| data-kunjungan | 5 endpoints (GET, POST, GET/:id, PUT/:id, DELETE/:id) | NO_URT |
| anggota | 5 endpoints | NO_AGT |
| kel-sah | 5 endpoints | ID_KS |
| data-lo | 5 endpoints | ID_LO |
| data-ao | 5 endpoints | NO_AO |
| data-jlh-keluarga | 5 endpoints | ID_JLH_KEL |
| ketua-ks | 5 endpoints | ID_KET |
| sekretaris-ks | 5 endpoints | ID_SEKRE |
| data-penghasilan | 5 endpoints | ID_PENGHASILAN |

#### Composite Key CRUD (1)
| Resource | Endpoints | PK |
|----------|-----------|-----|
| target | 5 endpoints | ID_KS + TGL_TGT |

#### Read-Only Resources (3)
| Resource | Endpoints | PK |
|----------|-----------|-----|
| realisasi | 2 endpoints (GET, GET/:id1/:id2) | ID_KS + TGL_TGT |
| data-trs | 2 endpoints (GET, GET/:id) | NO_TRS |
| dashboard | 1 endpoint (GET with filters) | - |

**Total: 63 endpoints** (3 public auth + 2 protected auth + 45 CRUD + 5 composite + 5 read-only + 1 dashboard + 2 debug)

---

## ⚠️ Important Notes

### 1. Debug Routes
Ada debug route di `/debug-ketua-ks` yang sebaiknya **dihapus di production**.

### 2. Email Configuration
Saat ini `MAIL_MAILER=log` - email OTP hanya ditulis ke log file.  
Untuk production, ubah ke SMTP provider (Gmail, SendGrid, dll).

### 3. No Authorization Layer
Semua authenticated user dapat CRUD semua data.  
Tidak ada role-based access control atau policy.

### 4. No Soft Deletes
Semua delete adalah hard delete (permanent).

### 5. Timestamp
Mayoritas table tidak memiliki `created_at` / `updated_at`.

### 6. Field Naming
Database menggunakan UPPERCASE naming (legacy), berbeda dengan Laravel convention (snake_case).

---

## 🔧 Environment Setup

### Required `.env` Variables

```env
# App
APP_URL=http://localhost
APP_ENV=local
APP_DEBUG=true

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=firebird
DB_USERNAME=root
DB_PASSWORD=your_password

# Queue & Cache
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

# Mail (untuk production)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 📞 Support & Contact

Untuk pertanyaan atau issue terkait API, hubungi tim development.

---

**Happy Coding! 🚀**
