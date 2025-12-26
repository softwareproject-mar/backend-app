# 🎯 Quick Start for Frontend Developers

## API Documentation Files

📚 **Pilih dokumentasi sesuai kebutuhan:**

### 1. [`API_DOCUMENTATION_COMPLETE.md`](./API_DOCUMENTATION_COMPLETE.md) ⭐ **RECOMMENDED**

**Dokumentasi lengkap dan terstruktur untuk frontend development.**

**Berisi:**
- ✅ **63 endpoints** dengan detail lengkap
- ✅ **Authentication flow** (OTP-based registration + login)
- ✅ **Request/Response examples** untuk setiap endpoint
- ✅ **Validation rules** semua field
- ✅ **Frontend integration guide:**
  - Axios setup & interceptors
  - React Hooks examples
  - Error handling patterns
  - Token management
- ✅ **Testing examples** (Postman, cURL)
- ✅ **Complete field definitions** untuk 13+ tables

**👉 Mulai dari sini jika baru pertama kali!**

---

### 2. [`documentation.md`](./documentation.md)

Dokumentasi legacy yang masih dipertahankan untuk reference.

---

## 🚀 Quick Integration (5 Minutes)

### 1. Setup Axios

```javascript
// api.js
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Auto-attach token
api.interceptors.request.use(config => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Auto-handle 401
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

### 2. Authentication Flow

```javascript
import api from './api';

// Step 1: Request OTP
await api.post('/auth/request-otp', { 
  email: 'user@example.com' 
});

// Step 2: Register with OTP
const { data } = await api.post('/auth/register', {
  name: 'John Doe',
  email: 'user@example.com',
  password: 'password123',
  password_confirmation: 'password123',
  otp: '123456'  // dari email
});

// Save token
localStorage.setItem('auth_token', data.token);

// Step 3: Use protected endpoints
const anggota = await api.get('/anggota');
```

### 3. CRUD Example

```javascript
// Get list dengan pagination
const { data } = await api.get('/anggota', {
  params: { page: 1, per_page: 20 }
});
// data.data = array items
// data.meta = pagination info

// Get detail
const { data } = await api.get('/anggota/AGT001');
// data.data = single item

// Create
const { data } = await api.post('/anggota', {
  NO_AGT: 'AGT002',
  NAMA: 'John Doe',
  // ... fields lainnya
});

// Update
const { data } = await api.put('/anggota/AGT002', {
  NAMA: 'Jane Doe'
});

// Delete
await api.delete('/anggota/AGT002');
```

---

## 📊 Available Resources

| Resource | Endpoints | Access |
|----------|-----------|--------|
| **Authentication** | 5 endpoints | Public + Protected |
| **Data Kunjungan** | Full CRUD | Protected |
| **Anggota** | Full CRUD | Protected |
| **Kel Sah** | Full CRUD | Protected |
| **Data LO** | Full CRUD | Protected |
| **Data AO** | Full CRUD | Protected |
| **Data Jlh Keluarga** | Full CRUD | Protected |
| **Ketua KS** | Full CRUD | Protected |
| **Sekretaris KS** | Full CRUD | Protected |
| **Data Penghasilan** | Full CRUD | Protected |
| **Target** | Full CRUD (composite key) | Protected |
| **Realisasi** | Read Only | Protected |
| **Data TRS** | Read Only | Protected |
| **Dashboard** | Read Only (JOIN) | Protected |

**Total: 63 endpoints**

---

## ⚠️ Important Notes

### Field Naming Convention
Database menggunakan **UPPERCASE** naming:
```javascript
{
  NO_AGT: "AGT001",      // bukan no_agt
  NAMA: "John Doe",      // bukan nama
  TGL_KUN: "2025-12-19", // bukan tgl_kun
  ID_KS: "001"           // bukan id_ks
}
```

### Composite Keys
Beberapa endpoint menggunakan **2 parameter** di URL:
```javascript
// Target & Realisasi
GET /target/{ID_KS}/{TGL_TGT}
GET /realisasi/{ID_KS}/{TGL_TGT}

// Example
GET /target/001/2025-12-19
```

### OTP Email
Saat ini backend menggunakan `MAIL_MAILER=log`.  
**OTP code ada di:** `storage/logs/laravel.log`

### No Authorization Layer
Semua authenticated user dapat CRUD semua data (tidak ada role-based access).

---

## 🔗 Helpful Links

- [Complete API Documentation](./API_DOCUMENTATION_COMPLETE.md) - **Start here!**
- [Legacy Documentation](./documentation.md)
- [Postman Collection](./postman_collection.json)
- [Project Overview](./PROJECT_OVERVIEW.md)
- [Project Rules](./PROJECT_RULES.md)

---

## 📞 Need Help?

Baca dokumentasi lengkap di [`API_DOCUMENTATION_COMPLETE.md`](./API_DOCUMENTATION_COMPLETE.md).

**Happy Coding! 🚀**
