# Activity Log API - User Guide

**Version:** 1.0  
**Last Updated:** 10 Januari 2026  
**Status:** ✅ Production Ready

---

## ⚡ Quick Start

**Frontend Developer: Ini Yang Perlu Kamu Tahu!**

### 📌 Apa itu Activity Log?
Fitur untuk menampilkan riwayat aktivitas CRUD user di halaman "Riwayat Aktivitas". Setiap kali user create/update/delete data, backend **OTOMATIS** mencatat ke database.

### 🔑 Yang Harus Dilakukan Frontend:

| Task | Endpoint | Method | Kapan? |
|------|----------|--------|--------|
| **Create/Update/Delete data** | `/api/anggota`, `/api/data-kunjungan`, dll | POST/PUT/DELETE | Seperti biasa (sudah ada) |
| **Tampilkan riwayat** | `/api/activity-logs` | GET | Di halaman Riwayat Aktivitas |
| **Tampilkan detail log** | `/api/activity-logs/{id}` | GET | Saat user tap item log |

### ❌ Yang TIDAK Perlu Dilakukan:

- ❌ POST ke `/api/activity-logs` setelah CRUD (backend auto-create!)
- ❌ Manual create log entries
- ❌ Handle IP address / user agent (backend auto-capture)
- ❌ Kirim `user_id` parameter (backend auto-filter)

### ✅ Complete Example:

```javascript
// 1. User create anggota (seperti biasa)
const response = await fetch('/api/anggota', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ NAMA: 'Siti', ID_KS: 'KS-001' })
});

// ✅ Activity log SUDAH OTOMATIS tercatat oleh backend!

// 2. Tampilkan di halaman Riwayat (optional)
const logs = await fetch('/api/activity-logs?per_page=10', {
  headers: { 'Authorization': `Bearer ${token}` }
});

// Response:
// {
//   data: [{
//     id: 1,
//     description: "Menambahkan anggota: Siti",
//     action_type: "create",
//     status: "success",
//     created_at: "2026-01-10T10:30:00Z"
//   }]
// }
```

### 📊 Available Endpoints:

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/activity-logs` | List semua log user yang login (dengan pagination & filter) |
| GET | `/api/activity-logs/{id}` | Detail 1 log berdasarkan ID |

**No POST/PUT/DELETE endpoints!** Activity log adalah read-only untuk frontend.

---

## 📋 Daftar Isi

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Endpoints](#endpoints)
4. [Query Parameters](#query-parameters)
5. [Response Format](#response-format)
6. [Use Cases](#use-cases)
7. [Error Handling](#error-handling)
8. [Best Practices](#best-practices)

---

## Overview

Activity Log API menyediakan fitur untuk **melihat riwayat aktivitas CRUD** yang dilakukan user. Setiap operasi create, update, dan delete pada resource akan **otomatis tercatat** dengan detail lengkap.

### Fitur Utama:
- ✅ **Auto-logging** - Semua CRUD operations tercatat otomatis
- ✅ **User Isolation** - User hanya bisa lihat aktivitas sendiri
- ✅ **Status Tracking** - Success/Failed dengan error message
- ✅ **Data Snapshot** - Old & new data dalam format JSON
- ✅ **Rich Filtering** - Filter by resource, action, status, date, search
- ✅ **Pagination** - Support untuk large dataset
- ✅ **Metadata** - IP address, user agent, timestamp

### Resources yang Di-track:
- `anggota` - Data Anggota
- `kel_sah` - Keluarga Sejahtera
- `data_kunjungan` - Data Kunjungan
- `data_lo` - Data LO
- `data_ao` - Data AO
- `ketua_ks` - Ketua KS
- `sekretaris_ks` - Sekretaris KS
- `data_penghasilan` - Data Penghasilan
- `data_pengelola` - Data Pengelola
- `data_jlh_keluarga` - Data Jumlah Keluarga

---

## How Activity Log Works

### 🔄 Auto-Logging Flow

**Frontend TIDAK perlu POST ke `/api/activity-logs`!**  
Activity log tercatat **100% otomatis** oleh backend saat frontend melakukan CRUD operations biasa.

#### Complete Flow Diagram:

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. FRONTEND: Create Anggota                                    │
│    POST /api/anggota                                            │
│    { NAMA: "Siti", ID_KS: "KS-001" }                           │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. BACKEND: Route → Controller                                  │
│    AnggotaController@store()                                    │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. BACKEND: Controller → Service                                │
│    AnggotaService->create($validatedData)                       │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. BACKEND: Service uses LogsActivity trait                     │
│    performWithLog('create', fn() => {...})                      │
│                                                                 │
│    try {                                                        │
│      $anggota = Anggota::create($data);  ← CREATE ANGGOTA      │
│                                                                 │
│      ActivityLog::create([               ← AUTO-LOG SUCCESS    │
│        'user_id' => auth()->id(),                               │
│        'resource_type' => 'anggota',                            │
│        'action_type' => 'create',                               │
│        'status' => 'success',                                   │
│        'new_data' => $data                                      │
│      ]);                                                        │
│                                                                 │
│      return $anggota;                                           │
│    }                                                            │
│    catch (Exception $e) {                                       │
│      ActivityLog::create([               ← AUTO-LOG FAILED     │
│        'status' => 'failed',                                    │
│        'error_message' => $e->getMessage()                      │
│      ]);                                                        │
│      throw $e;                                                  │
│    }                                                            │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5. FRONTEND: Receive Response                                   │
│    { id: 100, NAMA: "Siti", ... }                              │
│                                                                 │
│    Activity log SUDAH TERCATAT otomatis!                       │
└─────────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│ 6. FRONTEND: (Optional) Refresh Activity Log Display            │
│    GET /api/activity-logs?per_page=10                           │
│                                                                 │
│    Response: Log terbaru muncul di list                        │
└─────────────────────────────────────────────────────────────────┘
```

### 📝 What Frontend Should Do

**Step 1: Perform CRUD (seperti biasa)**
```javascript
// Create anggota
const response = await fetch('/api/anggota', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    NO_AGT: 'AGT-001',
    NAMA: 'Siti Nurhaliza',
    ID_KS: 'KS-001'
  })
});

// ✅ Activity log SUDAH OTOMATIS tercatat di backend!
// ❌ TIDAK perlu POST manual ke /api/activity-logs
```

**Step 2: (Optional) Refresh Activity Log Display**
```javascript
// Jika user sedang di halaman Riwayat, refresh untuk tampilkan log baru
if (isOnActivityLogPage) {
  await fetchActivityLogs();
}
```

### 🎯 Key Points

| Aspect | Detail |
|--------|--------|
| **Kapan log tercatat?** | **Otomatis** setiap POST/PUT/DELETE ke endpoint CRUD |
| **Siapa yang create log?** | **Backend** (via LogsActivity trait) |
| **Apa yang frontend lakukan?** | **Hanya GET** untuk menampilkan riwayat |
| **Apakah bisa manual POST log?** | **Tidak** - endpoint POST tidak tersedia |
| **Apakah validation error dicatat?** | **Ya** - semua error tercatat dengan status='failed' |
| **Apakah bisa edit/hapus log?** | **Tidak** - activity log immutable (read-only) |

### 💡 Real Example

```javascript
// ============================================
// SCENARIO: User membuat anggota baru dari mobile app
// ============================================

// 1. User tap "Simpan" button
async function handleSaveAnggota(formData) {
  showLoading();
  
  try {
    // 2. POST ke /api/anggota (BUKAN ke /api/activity-logs!)
    const response = await fetch(`${API_URL}/anggota`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(formData)
    });
    
    if (response.ok) {
      const anggota = await response.json();
      
      // 3. Sukses!
      showSuccess('Anggota berhasil ditambahkan');
      
      // 4. Backend SUDAH AUTO-CREATE activity log dengan:
      //    - action_type: 'create'
      //    - resource_type: 'anggota'
      //    - status: 'success'
      //    - new_data: {formData}
      
      // 5. (Optional) Navigate ke halaman Riwayat
      navigateTo('/activity-logs');
      
      // 6. Fetch activity logs untuk display
      const logs = await fetch(`${API_URL}/activity-logs?per_page=10`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      
      // 7. User lihat log terbaru: "Menambahkan anggota: Siti Nurhaliza"
      displayLogs(logs.data);
      
    } else if (response.status === 422) {
      // Validation error
      const error = await response.json();
      showError(error.message);
      
      // Backend SUDAH AUTO-CREATE activity log dengan:
      //    - status: 'failed'
      //    - error_message: "The NAMA field is required"
      
      // Frontend bisa tampilkan "Failed Attempts" jika perlu:
      // GET /api/activity-logs?status=failed
    }
    
  } catch (error) {
    showError('Network error');
  } finally {
    hideLoading();
  }
}

// ============================================
// KESIMPULAN:
// - Frontend HANYA POST ke /api/anggota
// - Frontend HANYA GET dari /api/activity-logs
// - Backend handle sisanya otomatis!
// ============================================
```

---

## Authentication

Semua endpoint Activity Log memerlukan **authentication token**.

**Header Required:**
```http
Authorization: Bearer {your_access_token}
Accept: application/json
```

**Cara Mendapatkan Token:**
```bash
# Login untuk mendapatkan token
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}

# Response akan berisi token
{
  "token": "5|abc123xyz..."
}
```

---

## Endpoints

### 1. Get Activity Logs (List)

**Endpoint:**
```
GET /api/activity-logs
```

**Description:**  
Mendapatkan daftar activity logs milik user yang sedang login dengan pagination dan filtering.

**⚠️ Important Note:**  
Endpoint ini **otomatis memfilter** hanya log milik user yang sedang login. Backend menggunakan `auth()->id()` untuk filter, jadi frontend tidak perlu menambahkan parameter `user_id`. User **tidak dapat** melihat log user lain.

**Query Parameters:**  
Lihat [Query Parameters](#query-parameters) section.

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 5,
      "user_name": "Ahmad Rifai",
      "resource_type": "anggota",
      "resource_id": "AGT-001",
      "action_type": "create",
      "description": "Menambahkan anggota: Siti Nurhaliza",
      "status": "success",
      "error_message": null,
      "old_data": null,
      "new_data": {
        "NO_AGT": "AGT-001",
        "NAMA": "Siti Nurhaliza",
        "ID_KS": "KS-001"
      },
      "ip_address": "192.168.1.100",
      "created_at": "2026-01-10T10:30:00+00:00"
    }
  ],
  "links": {
    "first": "http://127.0.0.1:8000/api/activity-logs?page=1",
    "last": "http://127.0.0.1:8000/api/activity-logs?page=5",
    "prev": null,
    "next": "http://127.0.0.1:8000/api/activity-logs?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "http://127.0.0.1:8000/api/activity-logs",
    "per_page": 10,
    "to": 10,
    "total": 48
  }
}
```

**Example Request (cURL):**
```bash
curl -X GET "http://127.0.0.1:8000/api/activity-logs?per_page=10&page=1" \
  -H "Authorization: Bearer 5|abc123xyz..." \
  -H "Accept: application/json"
```

**Example Request (JavaScript):**
```javascript
const response = await fetch('http://127.0.0.1:8000/api/activity-logs?per_page=10', {
  headers: {
    'Authorization': 'Bearer 5|abc123xyz...',
    'Accept': 'application/json'
  }
});

const data = await response.json();
console.log(data);
```

---

### 2. Get Single Activity Log

**Endpoint:**
```
GET /api/activity-logs/{id}
```

**Description:**  
Mendapatkan detail satu activity log berdasarkan ID. User hanya bisa melihat log miliknya sendiri.

**Path Parameters:**
- `id` (integer, required) - ID activity log

**Success Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "user_id": 5,
    "user_name": "Ahmad Rifai",
    "resource_type": "anggota",
    "resource_id": "AGT-001",
    "action_type": "create",
    "description": "Menambahkan anggota: Siti Nurhaliza",
    "status": "success",
    "error_message": null,
    "old_data": null,
    "new_data": {
      "NO_AGT": "AGT-001",
      "NAMA": "Siti Nurhaliza",
      "ID_KS": "KS-001"
    },
    "ip_address": "192.168.1.100",
    "created_at": "2026-01-10T10:30:00+00:00"
  }
}
```

**Error Response (403 Forbidden):**
```json
{
  "message": "Unauthorized. You can only view your own activity logs."
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "No query results for model [App\\Models\\ActivityLog] 999"
}
```

**Example Request (cURL):**
```bash
curl -X GET "http://127.0.0.1:8000/api/activity-logs/1" \
  -H "Authorization: Bearer 5|abc123xyz..." \
  -H "Accept: application/json"
```

---

## Query Parameters

### Available Filters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `page` | integer | Nomor halaman (default: 1) | `?page=2` |
| `per_page` | integer | Jumlah item per halaman (default: 10, max: 50) | `?per_page=20` |
| `resource_type` | string | Filter by jenis resource | `?resource_type=anggota` |
| `action_type` | enum | Filter by jenis aksi: `create`, `update`, `delete` | `?action_type=create` |
| `status` | enum | Filter by status: `success`, `failed` | `?status=success` |
| `date_from` | date | Filter dari tanggal (format: YYYY-MM-DD) | `?date_from=2026-01-01` |
| `date_to` | date | Filter sampai tanggal (format: YYYY-MM-DD) | `?date_to=2026-01-31` |
| `search` | string | Search di description dan user_name | `?search=siti` |

### Example Queries

**1. Get logs halaman 2 dengan 20 items:**
```
GET /api/activity-logs?page=2&per_page=20
```

**2. Filter hanya create operations:**
```
GET /api/activity-logs?action_type=create
```

**3. Filter logs untuk resource anggota:**
```
GET /api/activity-logs?resource_type=anggota
```

**4. Filter logs yang gagal (failed):**
```
GET /api/activity-logs?status=failed
```

**5. Filter logs di range tanggal tertentu:**
```
GET /api/activity-logs?date_from=2026-01-01&date_to=2026-01-31
```

**6. Search logs yang mengandung kata "siti":**
```
GET /api/activity-logs?search=siti
```

**7. Kombinasi multiple filters:**
```
GET /api/activity-logs?resource_type=anggota&action_type=create&status=success&per_page=20
```

---

## Response Format

### Success Response Structure

```json
{
  "data": [
    {
      "id": 1,                              // ID log
      "user_id": 5,                         // ID user yang melakukan aksi
      "user_name": "Ahmad Rifai",           // Nama user
      "resource_type": "anggota",           // Jenis resource
      "resource_id": "AGT-001",             // ID resource
      "action_type": "create",              // Jenis aksi (create/update/delete)
      "description": "Menambahkan anggota: Siti", // Deskripsi
      "status": "success",                  // Status (success/failed)
      "error_message": null,                // Pesan error (jika failed)
      "old_data": {...},                    // Data sebelum perubahan (JSON)
      "new_data": {...},                    // Data setelah perubahan (JSON)
      "ip_address": "192.168.1.100",        // IP address user
      "created_at": "2026-01-10T10:30:00+00:00" // Timestamp (ISO 8601)
    }
  ],
  "links": {
    "first": "...",   // Link halaman pertama
    "last": "...",    // Link halaman terakhir
    "prev": null,     // Link halaman sebelumnya (null jika di halaman 1)
    "next": "..."     // Link halaman berikutnya (null jika di halaman terakhir)
  },
  "meta": {
    "current_page": 1,    // Halaman saat ini
    "from": 1,            // Nomor item pertama di halaman ini
    "last_page": 5,       // Total halaman
    "per_page": 10,       // Jumlah item per halaman
    "to": 10,             // Nomor item terakhir di halaman ini
    "total": 48           // Total seluruh item
  }
}
```

### Field Descriptions

**`action_type` Values:**
- `create` - Operasi pembuatan data baru
- `update` - Operasi update/edit data
- `delete` - Operasi penghapusan data

**`status` Values:**
- `success` - Operasi berhasil
- `failed` - Operasi gagal (lihat `error_message`)

**`old_data` & `new_data`:**
- `old_data`: Data sebelum perubahan (untuk `update` dan `delete`)
- `new_data`: Data setelah perubahan (untuk `create` dan `update`)
- Format: JSON object berisi field-field resource

---

## Use Cases

### Use Case 1: Tampilkan Riwayat Aktivitas User

**Scenario:**  
Mobile app ingin menampilkan halaman "Riwayat Aktivitas" dengan pagination.

**Request:**
```bash
GET /api/activity-logs?per_page=10&page=1
Authorization: Bearer {token}
```

**Implementation (JavaScript):**
```javascript
async function loadActivityLogs(page = 1) {
  const response = await fetch(
    `${API_BASE_URL}/activity-logs?per_page=10&page=${page}`,
    {
      headers: {
        'Authorization': `Bearer ${userToken}`,
        'Accept': 'application/json'
      }
    }
  );
  
  const data = await response.json();
  
  // Display logs
  data.data.forEach(log => {
    console.log(`${log.created_at}: ${log.description} (${log.status})`);
  });
  
  // Handle pagination
  console.log(`Page ${data.meta.current_page} of ${data.meta.last_page}`);
  console.log(`Total: ${data.meta.total} logs`);
  
  return data;
}

// Load first page
loadActivityLogs(1);
```

---

### Use Case 2: Filter Logs yang Gagal

**Scenario:**  
User ingin melihat hanya transaksi yang gagal untuk di-retry.

**Request:**
```bash
GET /api/activity-logs?status=failed&per_page=20
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": [
    {
      "id": 5,
      "description": "Menghapus anggota: Budi Santoso",
      "status": "failed",
      "error_message": "SQLSTATE[23000]: Integrity constraint violation: 1451 Cannot delete or update a parent row",
      "action_type": "delete",
      "resource_type": "anggota"
    }
  ]
}
```

---

### Use Case 3: Track Aktivitas Spesifik Resource

**Scenario:**  
User ingin melihat semua perubahan pada resource "anggota" dalam 1 minggu terakhir.

**Request:**
```bash
GET /api/activity-logs?resource_type=anggota&date_from=2026-01-03&date_to=2026-01-10
Authorization: Bearer {token}
```

**Implementation (JavaScript):**
```javascript
async function getAnggotaActivityLogs() {
  const today = new Date();
  const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
  
  const dateFrom = weekAgo.toISOString().split('T')[0];
  const dateTo = today.toISOString().split('T')[0];
  
  const response = await fetch(
    `${API_BASE_URL}/activity-logs?resource_type=anggota&date_from=${dateFrom}&date_to=${dateTo}`,
    {
      headers: {
        'Authorization': `Bearer ${userToken}`,
        'Accept': 'application/json'
      }
    }
  );
  
  return await response.json();
}
```

---

### Use Case 4: Search Logs by Keyword

**Scenario:**  
User ingin mencari log yang mengandung nama "Siti".

**Request:**
```bash
GET /api/activity-logs?search=siti
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "description": "Menambahkan anggota: Siti Nurhaliza",
      "action_type": "create",
      "resource_type": "anggota"
    },
    {
      "id": 15,
      "description": "Mengupdate anggota: Siti Aminah",
      "action_type": "update",
      "resource_type": "anggota"
    }
  ]
}
```

---

### Use Case 5: Monitor Create Operations Only

**Scenario:**  
Dashboard admin ingin menampilkan statistik data baru yang dibuat hari ini.

**Request:**
```bash
GET /api/activity-logs?action_type=create&date_from=2026-01-10&date_to=2026-01-10
Authorization: Bearer {token}
```

**Implementation:**
```javascript
async function getTodayCreatedResources() {
  const today = new Date().toISOString().split('T')[0];
  
  const response = await fetch(
    `${API_BASE_URL}/activity-logs?action_type=create&date_from=${today}&date_to=${today}`,
    {
      headers: {
        'Authorization': `Bearer ${userToken}`,
        'Accept': 'application/json'
      }
    }
  );
  
  const data = await response.json();
  
  // Group by resource_type
  const stats = data.data.reduce((acc, log) => {
    acc[log.resource_type] = (acc[log.resource_type] || 0) + 1;
    return acc;
  }, {});
  
  console.log('Resources created today:', stats);
  // Output: { anggota: 5, data_kunjungan: 3, kel_sah: 2 }
  
  return stats;
}
```

---

### Use Case 6: Detail View - Show Old vs New Data

**Scenario:**  
User meng-click detail log untuk melihat perubahan data sebelum dan sesudah update.

**Request:**
```bash
GET /api/activity-logs/25
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "id": 25,
    "action_type": "update",
    "description": "Mengupdate anggota: Ahmad Rifai",
    "old_data": {
      "NAMA": "Ahmad Rifai",
      "ID_KS": "KS-001",
      "TGL_AKTIF": "2025-01-01"
    },
    "new_data": {
      "NAMA": "Ahmad Rifai",
      "ID_KS": "KS-002",
      "TGL_AKTIF": "2026-01-05"
    }
  }
}
```

**UI Display:**
```
Perubahan Data Anggota: Ahmad Rifai

Field Changed:
- ID_KS: KS-001 → KS-002
- TGL_AKTIF: 2025-01-01 → 2026-01-05

Field Unchanged:
- NAMA: Ahmad Rifai
```

---

## Complete Integration Guide

### 🚀 Tutorial: Implement Activity Log di Mobile App

#### Phase 1: Setup API Service

**File: `services/ActivityLogService.js`**
```javascript
import AsyncStorage from '@react-native-async-storage/async-storage';

const API_BASE_URL = 'https://your-api.com/api';

class ActivityLogService {
  
  // Get user token
  async getToken() {
    return await AsyncStorage.getItem('auth_token');
  }
  
  // Fetch activity logs dengan filtering
  async fetchLogs(params = {}) {
    const token = await this.getToken();
    
    // Build query string
    const queryParams = new URLSearchParams({
      per_page: params.perPage || 10,
      page: params.page || 1,
      ...(params.resourceType && { resource_type: params.resourceType }),
      ...(params.actionType && { action_type: params.actionType }),
      ...(params.status && { status: params.status }),
      ...(params.dateFrom && { date_from: params.dateFrom }),
      ...(params.dateTo && { date_to: params.dateTo }),
      ...(params.search && { search: params.search })
    });
    
    const response = await fetch(
      `${API_BASE_URL}/activity-logs?${queryParams}`,
      {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      }
    );
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    
    return await response.json();
  }
  
  // Get single log detail
  async getLogDetail(logId) {
    const token = await this.getToken();
    
    const response = await fetch(
      `${API_BASE_URL}/activity-logs/${logId}`,
      {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      }
    );
    
    if (!response.ok) {
      if (response.status === 403) {
        throw new Error('Unauthorized: Not your log');
      }
      throw new Error(`HTTP ${response.status}`);
    }
    
    return await response.json();
  }
  
  // Helper: Get logs after CRUD operation
  async getRecentLogs(limit = 5) {
    return await this.fetchLogs({ perPage: limit, page: 1 });
  }
  
  // Helper: Get failed logs for debugging
  async getFailedLogs() {
    return await this.fetchLogs({ status: 'failed', perPage: 20 });
  }
  
  // Helper: Get logs for specific resource
  async getLogsForResource(resourceType, resourceId) {
    const logs = await this.fetchLogs({
      resourceType,
      search: resourceId,
      perPage: 50
    });
    
    // Filter exact match
    return logs.data.filter(log => log.resource_id === resourceId);
  }
}

export default new ActivityLogService();
```

---

#### Phase 2: Create Activity Log Screen

**File: `screens/ActivityLogScreen.js`**
```javascript
import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  FlatList,
  RefreshControl,
  ActivityIndicator,
  TouchableOpacity,
  StyleSheet
} from 'react-native';
import ActivityLogService from '../services/ActivityLogService';
import { formatRelativeTime } from '../utils/dateFormatter';

const ActivityLogScreen = ({ navigation }) => {
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [filters, setFilters] = useState({});
  
  // Load logs
  const loadLogs = async (page = 1, isRefresh = false) => {
    try {
      if (!isRefresh) setLoading(true);
      
      const response = await ActivityLogService.fetchLogs({
        page,
        perPage: 10,
        ...filters
      });
      
      if (isRefresh || page === 1) {
        setLogs(response.data);
      } else {
        setLogs(prev => [...prev, ...response.data]);
      }
      
      setCurrentPage(response.meta.current_page);
      setTotalPages(response.meta.last_page);
      
    } catch (error) {
      console.error('Failed to load logs:', error);
      Alert.alert('Error', 'Gagal memuat riwayat aktivitas');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };
  
  // Pull to refresh
  const onRefresh = () => {
    setRefreshing(true);
    loadLogs(1, true);
  };
  
  // Load more (pagination)
  const loadMore = () => {
    if (currentPage < totalPages && !loading) {
      loadLogs(currentPage + 1);
    }
  };
  
  // Initial load
  useEffect(() => {
    loadLogs();
  }, [filters]);
  
  // Render single log item
  const renderLogItem = ({ item }) => {
    const getStatusColor = () => {
      return item.status === 'success' ? '#4CAF50' : '#F44336';
    };
    
    const getActionIcon = () => {
      switch (item.action_type) {
        case 'create': return '➕';
        case 'update': return '✏️';
        case 'delete': return '🗑️';
        default: return '📝';
      }
    };
    
    return (
      <TouchableOpacity
        style={styles.logItem}
        onPress={() => navigation.navigate('ActivityLogDetail', { logId: item.id })}
      >
        <View style={styles.logHeader}>
          <Text style={styles.logIcon}>{getActionIcon()}</Text>
          <View style={styles.logInfo}>
            <Text style={styles.logDescription}>{item.description}</Text>
            <Text style={styles.logTime}>
              {formatRelativeTime(item.created_at)}
            </Text>
          </View>
          <View
            style={[
              styles.statusBadge,
              { backgroundColor: getStatusColor() }
            ]}
          >
            <Text style={styles.statusText}>
              {item.status === 'success' ? '✓' : '✗'}
            </Text>
          </View>
        </View>
        
        {item.error_message && (
          <Text style={styles.errorMessage}>{item.error_message}</Text>
        )}
        
        <View style={styles.logMeta}>
          <Text style={styles.metaText}>{item.resource_type}</Text>
          <Text style={styles.metaText}>•</Text>
          <Text style={styles.metaText}>{item.action_type}</Text>
        </View>
      </TouchableOpacity>
    );
  };
  
  // Render footer (loading more indicator)
  const renderFooter = () => {
    if (!loading || currentPage === 1) return null;
    return (
      <View style={styles.footer}>
        <ActivityIndicator size="small" color="#2196F3" />
      </View>
    );
  };
  
  // Render empty state
  const renderEmpty = () => {
    if (loading) return null;
    return (
      <View style={styles.emptyState}>
        <Text style={styles.emptyText}>Belum ada aktivitas</Text>
      </View>
    );
  };
  
  return (
    <View style={styles.container}>
      {/* Filter Bar */}
      <View style={styles.filterBar}>
        <TouchableOpacity
          style={styles.filterButton}
          onPress={() => setFilters({ ...filters, status: 'success' })}
        >
          <Text>Sukses</Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={styles.filterButton}
          onPress={() => setFilters({ ...filters, status: 'failed' })}
        >
          <Text>Gagal</Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={styles.filterButton}
          onPress={() => setFilters({})}
        >
          <Text>Semua</Text>
        </TouchableOpacity>
      </View>
      
      {/* Activity List */}
      <FlatList
        data={logs}
        renderItem={renderLogItem}
        keyExtractor={item => item.id.toString()}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
        onEndReached={loadMore}
        onEndReachedThreshold={0.5}
        ListFooterComponent={renderFooter}
        ListEmptyComponent={renderEmpty}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5'
  },
  filterBar: {
    flexDirection: 'row',
    padding: 10,
    backgroundColor: 'white',
    borderBottomWidth: 1,
    borderBottomColor: '#e0e0e0'
  },
  filterButton: {
    paddingHorizontal: 15,
    paddingVertical: 8,
    marginRight: 10,
    backgroundColor: '#e3f2fd',
    borderRadius: 20
  },
  logItem: {
    backgroundColor: 'white',
    padding: 15,
    marginVertical: 5,
    marginHorizontal: 10,
    borderRadius: 8,
    elevation: 2
  },
  logHeader: {
    flexDirection: 'row',
    alignItems: 'center'
  },
  logIcon: {
    fontSize: 24,
    marginRight: 10
  },
  logInfo: {
    flex: 1
  },
  logDescription: {
    fontSize: 16,
    fontWeight: '500',
    color: '#333'
  },
  logTime: {
    fontSize: 12,
    color: '#999',
    marginTop: 4
  },
  statusBadge: {
    width: 24,
    height: 24,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center'
  },
  statusText: {
    color: 'white',
    fontSize: 14,
    fontWeight: 'bold'
  },
  errorMessage: {
    fontSize: 12,
    color: '#F44336',
    marginTop: 8,
    fontStyle: 'italic'
  },
  logMeta: {
    flexDirection: 'row',
    marginTop: 8
  },
  metaText: {
    fontSize: 12,
    color: '#666',
    marginRight: 8
  },
  footer: {
    padding: 20,
    alignItems: 'center'
  },
  emptyState: {
    padding: 50,
    alignItems: 'center'
  },
  emptyText: {
    fontSize: 16,
    color: '#999'
  }
});

export default ActivityLogScreen;
```

---

#### Phase 3: Auto-Refresh After CRUD

**File: `services/AnggotaService.js` (example)**
```javascript
import ActivityLogService from './ActivityLogService';

class AnggotaService {
  
  async createAnggota(data) {
    const token = await AsyncStorage.getItem('auth_token');
    
    const response = await fetch(`${API_BASE_URL}/anggota`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    });
    
    if (!response.ok) {
      throw new Error('Failed to create anggota');
    }
    
    const result = await response.json();
    
    // ✅ Activity log SUDAH OTOMATIS tercatat di backend!
    // Kita hanya perlu refresh UI jika user sedang di halaman Riwayat
    
    // Optional: Emit event untuk refresh activity log
    EventEmitter.emit('activity-log-updated');
    
    return result;
  }
  
  async updateAnggota(id, data) {
    // Similar pattern...
    const result = await this.performUpdate(id, data);
    EventEmitter.emit('activity-log-updated');
    return result;
  }
  
  async deleteAnggota(id) {
    // Similar pattern...
    const result = await this.performDelete(id);
    EventEmitter.emit('activity-log-updated');
    return result;
  }
}

export default new AnggotaService();
```

**Listen to events in ActivityLogScreen:**
```javascript
useEffect(() => {
  const subscription = EventEmitter.addListener(
    'activity-log-updated',
    () => {
      // Refresh logs when CRUD operation happens
      loadLogs(1, true);
    }
  );
  
  return () => subscription.remove();
}, []);
```

---

#### Phase 4: Utility Functions

**File: `utils/dateFormatter.js`**
```javascript
export function formatRelativeTime(isoString) {
  const date = new Date(isoString);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);
  
  if (diffMins < 1) return 'Baru saja';
  if (diffMins < 60) return `${diffMins} menit yang lalu`;
  if (diffHours < 24) return `${diffHours} jam yang lalu`;
  if (diffDays < 7) return `${diffDays} hari yang lalu`;
  
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

export function formatFullDate(isoString) {
  const date = new Date(isoString);
  return date.toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
}
```

---

#### Phase 5: Detail Screen

**File: `screens/ActivityLogDetailScreen.js`**
```javascript
import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  ActivityIndicator
} from 'react-native';
import ActivityLogService from '../services/ActivityLogService';
import { formatFullDate } from '../utils/dateFormatter';

const ActivityLogDetailScreen = ({ route }) => {
  const { logId } = route.params;
  const [log, setLog] = useState(null);
  const [loading, setLoading] = useState(true);
  
  useEffect(() => {
    loadLogDetail();
  }, [logId]);
  
  const loadLogDetail = async () => {
    try {
      const response = await ActivityLogService.getLogDetail(logId);
      setLog(response.data);
    } catch (error) {
      Alert.alert('Error', 'Gagal memuat detail log');
    } finally {
      setLoading(false);
    }
  };
  
  if (loading) {
    return (
      <View style={styles.loader}>
        <ActivityIndicator size="large" color="#2196F3" />
      </View>
    );
  }
  
  if (!log) return null;
  
  const renderDataComparison = () => {
    if (!log.old_data && !log.new_data) return null;
    
    if (log.action_type === 'create') {
      return (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Data Baru:</Text>
          <View style={styles.dataBox}>
            <Text style={styles.dataText}>
              {JSON.stringify(log.new_data, null, 2)}
            </Text>
          </View>
        </View>
      );
    }
    
    if (log.action_type === 'delete') {
      return (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Data yang Dihapus:</Text>
          <View style={styles.dataBox}>
            <Text style={styles.dataText}>
              {JSON.stringify(log.old_data, null, 2)}
            </Text>
          </View>
        </View>
      );
    }
    
    // Update - show comparison
    return (
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Perubahan Data:</Text>
        <View style={styles.dataBox}>
          <Text style={styles.dataLabel}>Sebelum:</Text>
          <Text style={styles.dataText}>
            {JSON.stringify(log.old_data, null, 2)}
          </Text>
        </View>
        <View style={[styles.dataBox, { marginTop: 10 }]}>
          <Text style={styles.dataLabel}>Sesudah:</Text>
          <Text style={styles.dataText}>
            {JSON.stringify(log.new_data, null, 2)}
          </Text>
        </View>
      </View>
    );
  };
  
  return (
    <ScrollView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>{log.description}</Text>
        <View
          style={[
            styles.statusBadge,
            { backgroundColor: log.status === 'success' ? '#4CAF50' : '#F44336' }
          ]}
        >
          <Text style={styles.statusText}>
            {log.status === 'success' ? 'SUKSES' : 'GAGAL'}
          </Text>
        </View>
      </View>
      
      <View style={styles.section}>
        <InfoRow label="Waktu" value={formatFullDate(log.created_at)} />
        <InfoRow label="Resource" value={log.resource_type} />
        <InfoRow label="Resource ID" value={log.resource_id} />
        <InfoRow label="Aksi" value={log.action_type} />
        <InfoRow label="IP Address" value={log.ip_address} />
      </View>
      
      {log.error_message && (
        <View style={[styles.section, styles.errorSection]}>
          <Text style={styles.sectionTitle}>Error Message:</Text>
          <Text style={styles.errorText}>{log.error_message}</Text>
        </View>
      )}
      
      {renderDataComparison()}
    </ScrollView>
  );
};

const InfoRow = ({ label, value }) => (
  <View style={styles.infoRow}>
    <Text style={styles.infoLabel}>{label}:</Text>
    <Text style={styles.infoValue}>{value}</Text>
  </View>
);

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5'
  },
  loader: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center'
  },
  header: {
    backgroundColor: 'white',
    padding: 20,
    borderBottomWidth: 1,
    borderBottomColor: '#e0e0e0'
  },
  title: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 10
  },
  statusBadge: {
    alignSelf: 'flex-start',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 4
  },
  statusText: {
    color: 'white',
    fontSize: 12,
    fontWeight: 'bold'
  },
  section: {
    backgroundColor: 'white',
    padding: 15,
    marginTop: 10
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 10,
    color: '#333'
  },
  infoRow: {
    flexDirection: 'row',
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#f0f0f0'
  },
  infoLabel: {
    flex: 1,
    fontSize: 14,
    color: '#666'
  },
  infoValue: {
    flex: 2,
    fontSize: 14,
    color: '#333',
    fontWeight: '500'
  },
  errorSection: {
    backgroundColor: '#ffebee'
  },
  errorText: {
    fontSize: 14,
    color: '#c62828',
    fontStyle: 'italic'
  },
  dataBox: {
    backgroundColor: '#f5f5f5',
    padding: 12,
    borderRadius: 4,
    borderLeftWidth: 3,
    borderLeftColor: '#2196F3'
  },
  dataLabel: {
    fontSize: 12,
    fontWeight: 'bold',
    color: '#666',
    marginBottom: 5
  },
  dataText: {
    fontSize: 12,
    fontFamily: 'monospace',
    color: '#333'
  }
});

export default ActivityLogDetailScreen;
```

---

### 📊 Testing Your Implementation

**Test Checklist:**

1. **✅ Test Create Operation**
   - Create anggota baru
   - Navigate ke Activity Log screen
   - Verify log "Menambahkan anggota: [nama]" muncul
   - Check status = success
   - Check new_data contains form data

2. **✅ Test Update Operation**
   - Update anggota
   - Refresh activity log
   - Verify log "Mengupdate anggota: [nama]" muncul
   - Check old_data vs new_data

3. **✅ Test Delete Operation**
   - Delete anggota
   - Check log "Menghapus anggota: [nama]"
   - Verify old_data contains deleted data

4. **✅ Test Validation Error**
   - Submit form dengan data invalid (e.g., nama kosong)
   - Check log with status = failed
   - Verify error_message berisi pesan validasi

5. **✅ Test Filtering**
   - Filter by status (success/failed)
   - Filter by resource_type
   - Filter by date range
   - Search by keyword

6. **✅ Test Pagination**
   - Scroll to bottom
   - Verify "Load More" works
   - Check page numbers

7. **✅ Test Pull-to-Refresh**
   - Pull down list
   - Verify fresh data loaded

8. **✅ Test Detail Screen**
   - Tap on log item
   - Verify detail screen shows complete info
   - Check data comparison for update actions

---

## Error Handling

### Common Error Responses

**1. Unauthenticated (401)**
```json
{
  "message": "Unauthenticated."
}
```
**Cause:** Token tidak ada atau sudah expired  
**Solution:** Login ulang untuk mendapatkan token baru

---

**2. Forbidden (403)**
```json
{
  "message": "Unauthorized. You can only view your own activity logs."
}
```
**Cause:** User mencoba akses log milik user lain  
**Solution:** Hanya akses log dengan ID milik sendiri

---

**3. Not Found (404)**
```json
{
  "message": "No query results for model [App\\Models\\ActivityLog] 999"
}
```
**Cause:** Activity log dengan ID tersebut tidak ditemukan  
**Solution:** Cek ID yang benar atau pastikan log belum dihapus

---

**4. Validation Error (422)**
```json
{
  "message": "The per_page must not be greater than 50.",
  "errors": {
    "per_page": ["The per_page must not be greater than 50."]
  }
}
```
**Cause:** Parameter query tidak valid (contoh: per_page > 50)  
**Solution:** Gunakan nilai yang valid sesuai dokumentasi

---

**5. Server Error (500)**
```json
{
  "message": "Server Error"
}
```
**Cause:** Error di backend server  
**Solution:** Hubungi administrator atau cek log server

---

### Validation Errors in Activity Logs

**Important:** Validation errors (422) juga **tercatat** di activity log dengan status `failed`.

**Example Scenario:**  
User mencoba create anggota dengan data invalid (nama kosong).

**Request:**
```bash
POST /api/anggota
{
  "NO_AGT": "AGT-001",
  "NAMA": "",  // Empty name - validation error!
  "ID_KS": "KS-001"
}
```

**Response (422):**
```json
{
  "message": "The NAMA field is required.",
  "errors": {
    "NAMA": ["The NAMA field is required."]
  }
}
```

**Activity Log Created:**
```json
{
  "id": 25,
  "action_type": "create",
  "resource_type": "anggota",
  "status": "failed",
  "error_message": "The NAMA field is required.",
  "description": "Menambahkan anggota: Unknown",
  "new_data": {
    "NO_AGT": "AGT-001",
    "NAMA": "",
    "ID_KS": "KS-001"
  }
}
```

**Frontend Usage:**  
Bisa digunakan untuk menampilkan "Failed Attempts" atau "Error History" untuk debugging.

```javascript
// Get all validation errors
const failedLogs = await fetch(
  `${API_BASE_URL}/activity-logs?status=failed`,
  { headers: { Authorization: `Bearer ${token}` } }
);

// Show to user: "You have 3 failed attempts today"
```

---

### Error Handling Implementation

**JavaScript Example:**
```javascript
async function fetchActivityLogs() {
  try {
    const response = await fetch(`${API_BASE_URL}/activity-logs`, {
      headers: {
        'Authorization': `Bearer ${userToken}`,
        'Accept': 'application/json'
      }
    });
    
    // Check response status
    if (!response.ok) {
      const error = await response.json();
      
      switch (response.status) {
        case 401:
          // Token expired, redirect to login
          redirectToLogin();
          break;
        
        case 403:
          showError('Anda tidak memiliki akses ke log ini');
          break;
        
        case 404:
          showError('Log tidak ditemukan');
          break;
        
        case 422:
          showError(error.message || 'Data tidak valid');
          break;
        
        default:
          showError('Terjadi kesalahan, silakan coba lagi');
      }
      
      return null;
    }
    
    return await response.json();
    
  } catch (error) {
    console.error('Network error:', error);
    showError('Tidak dapat terhubung ke server');
    return null;
  }
}
```

---

## Best Practices

### 1. Pagination Strategy

**✅ DO:**
```javascript
// Start with small page size
const logs = await fetchActivityLogs({ per_page: 10, page: 1 });

// Implement infinite scroll or "Load More" button
if (logs.meta.current_page < logs.meta.last_page) {
  showLoadMoreButton();
}
```

**❌ DON'T:**
```javascript
// Don't request all data at once
const allLogs = await fetchActivityLogs({ per_page: 1000 }); // ❌ Bad!
```

---

### 2. Caching Strategy

**✅ DO:**
```javascript
// Cache logs with timestamp
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

function getCachedLogs() {
  const cached = localStorage.getItem('activity_logs');
  if (!cached) return null;
  
  const { data, timestamp } = JSON.parse(cached);
  
  if (Date.now() - timestamp > CACHE_DURATION) {
    localStorage.removeItem('activity_logs');
    return null;
  }
  
  return data;
}

async function loadLogs() {
  const cached = getCachedLogs();
  if (cached) return cached;
  
  const fresh = await fetchActivityLogs();
  localStorage.setItem('activity_logs', JSON.stringify({
    data: fresh,
    timestamp: Date.now()
  }));
  
  return fresh;
}
```

---

### 3. Filter Combination

**✅ DO:**
```javascript
// Build query params dynamically
function buildQuery(filters) {
  const params = new URLSearchParams();
  
  if (filters.resource_type) params.append('resource_type', filters.resource_type);
  if (filters.action_type) params.append('action_type', filters.action_type);
  if (filters.status) params.append('status', filters.status);
  if (filters.date_from) params.append('date_from', filters.date_from);
  if (filters.date_to) params.append('date_to', filters.date_to);
  if (filters.search) params.append('search', filters.search);
  
  params.append('per_page', filters.per_page || 10);
  params.append('page', filters.page || 1);
  
  return params.toString();
}

// Usage
const query = buildQuery({
  resource_type: 'anggota',
  status: 'success',
  per_page: 20
});

fetch(`${API_BASE_URL}/activity-logs?${query}`);
```

---

### 4. Real-time Updates

**Pull-to-Refresh vs Auto-Refresh:**

Mobile app sebaiknya menggunakan **pull-to-refresh** daripada auto-refresh untuk menghemat bandwidth dan battery.

**✅ DO - Pull-to-Refresh (Recommended):**
```javascript
// Implement pull-to-refresh gesture
async function onPullToRefresh() {
  showLoadingIndicator();
  
  const freshLogs = await fetchActivityLogs({ page: 1, per_page: 10 });
  
  // Update UI with fresh data
  replaceLogsInUI(freshLogs);
  
  hideLoadingIndicator();
  showSuccessToast('Riwayat diperbarui');
}

// Refresh after successful CRUD
async function afterCreateAnggota() {
  // Wait for log to be created (backend processing time)
  await new Promise(resolve => setTimeout(resolve, 500));
  
  // Trigger pull-to-refresh programmatically
  await onPullToRefresh();
}
```

**❌ DON'T - Auto-Refresh dengan Polling:**
```javascript
// DON'T: Auto-refresh every few seconds (battery drain!)
setInterval(async () => {
  await fetchActivityLogs();  // ❌ Wasteful!
}, 5000); // Every 5 seconds
```

**Alternative: Event-Based Refresh**
```javascript
// Refresh only when app comes to foreground
document.addEventListener('visibilitychange', async () => {
  if (!document.hidden) {
    // App became visible, refresh logs
    await onPullToRefresh();
  }
});

// Or use React Native's AppState
import { AppState } from 'react-native';

AppState.addEventListener('change', async (nextAppState) => {
  if (nextAppState === 'active') {
    await onPullToRefresh();
  }
});
```

**Best Practice for Mobile:**
1. **Pull-to-Refresh** - User manual refresh (primary)
2. **After CRUD Success** - Auto-refresh after create/update/delete
3. **App Resume** - Refresh when app comes from background
4. **Never** - Auto-polling atau setInterval

---

### 5. Display Formatting

**✅ DO:**
```javascript
// Format timestamps for better UX
function formatLogTime(isoString) {
  const date = new Date(isoString);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);
  
  if (diffMins < 1) return 'Baru saja';
  if (diffMins < 60) return `${diffMins} menit yang lalu`;
  if (diffHours < 24) return `${diffHours} jam yang lalu`;
  if (diffDays < 7) return `${diffDays} hari yang lalu`;
  
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// Usage
console.log(formatLogTime('2026-01-10T10:30:00+00:00'));
// Output: "2 jam yang lalu" or "10 Januari 2026, 10:30"
```

---

### 6. Performance Tips

**✅ DO:**
- Gunakan `per_page` yang reasonable (10-20 items)
- Implement debounce untuk search input
- Cache hasil query yang sering diakses
- Load detail log hanya saat diperlukan (lazy loading)

**❌ DON'T:**
- Jangan request `per_page=50` jika tidak perlu
- Jangan fetch ulang setiap kali user scroll sedikit
- Jangan simpan semua logs di memory (gunakan pagination)

---

## Response Examples

### Example 1: Success Response dengan Multiple Logs

```json
{
  "data": [
    {
      "id": 10,
      "user_id": 3,
      "user_name": "Budi Santoso",
      "resource_type": "data_kunjungan",
      "resource_id": "150",
      "action_type": "create",
      "description": "Menambahkan data kunjungan: KS-045",
      "status": "success",
      "error_message": null,
      "old_data": null,
      "new_data": {
        "NO_URT": 150,
        "ID_LO": "LO-001",
        "NO_AGT": "AGT-100",
        "ID_KEL_SAH": "KS-045",
        "TGL_KUN": "2026-01-10",
        "KEGIATAN": "Kunjungan rutin"
      },
      "ip_address": "192.168.1.105",
      "created_at": "2026-01-10T14:25:30+00:00"
    },
    {
      "id": 9,
      "user_id": 3,
      "user_name": "Budi Santoso",
      "resource_type": "anggota",
      "resource_id": "AGT-100",
      "action_type": "update",
      "description": "Mengupdate anggota: Dewi Lestari",
      "status": "success",
      "error_message": null,
      "old_data": {
        "NO_AGT": "AGT-100",
        "NAMA": "Dewi Lestari",
        "ID_KS": "KS-001",
        "TGL_AKTIF": "2025-12-01"
      },
      "new_data": {
        "TGL_AKTIF": "2026-01-01"
      },
      "ip_address": "192.168.1.105",
      "created_at": "2026-01-10T13:15:00+00:00"
    }
  ],
  "links": {
    "first": "http://127.0.0.1:8000/api/activity-logs?page=1",
    "last": "http://127.0.0.1:8000/api/activity-logs?page=3",
    "prev": null,
    "next": "http://127.0.0.1:8000/api/activity-logs?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "path": "http://127.0.0.1:8000/api/activity-logs",
    "per_page": 10,
    "to": 10,
    "total": 25
  }
}
```

### Example 2: Failed Operations

```json
{
  "data": [
    {
      "id": 8,
**Q: Apakah validation error (422) juga tercatat di log?**  
A: **Ya**. Semua error termasuk validation error tercatat dengan `status: 'failed'` dan `error_message` berisi pesan error lengkap.

**Q: Bagaimana cara auto-refresh logs di mobile app?**  
A: **Gunakan pull-to-refresh**, jangan auto-polling. Refresh logs setelah sukses CRUD atau saat app resume dari background. Lihat [Best Practices - Real-time Updates](#4-real-time-updates).

**Q: Apakah backend auto-filter by user_id?**  
A: **Ya**. Endpoint `/api/activity-logs` otomatis filter `WHERE user_id = auth()->id()`. Frontend tidak perlu (dan tidak bisa) menambahkan parameter `user_id`. Setiap user **hanya** bisa lihat log miliknya sendiri.

**Q: Apakah saya perlu POST ke /api/activity-logs setelah CRUD?**  
A: **TIDAK!** Activity log **100% otomatis** tercatat oleh backend. Frontend hanya perlu:
1. POST/PUT/DELETE ke endpoint CRUD biasa (`/api/anggota`, dll)
2. GET dari `/api/activity-logs` untuk menampilkan riwayat
3. **Tidak ada** endpoint POST/PUT/DELETE untuk activity-logs

**Q: Kenapa log saya tidak muncul setelah create anggota?**  
A: Kemungkinan:
1. **Belum refresh** - Pull-to-refresh atau fetch ulang
2. **Delay backend** - Tunggu 500ms lalu fetch
3. **Token expired** - Cek response 401, login ulang
4. **CRUD operation failed** - Cek response dari endpoint CRUD, jika error maka log tetap tercatat dengan status='failed'

**Q: Bisakah admin melihat log semua user?**  
A: **Tidak** dengan endpoint ini. Endpoint `/api/activity-logs` hanya return log user yang login. Jika perlu admin dashboard, butuh endpoint terpisah (belum tersedia).

**Q: Berapa lama cache logs sebaiknya?**  
A: Recommended **5 menit** untuk mobile app. Gunakan pull-to-refresh untuk manual refresh, dan auto-refresh saat app resume dari background.

**Q: Apakah activity log bisa dihapus?**  
A: **Tidak**. Activity log bersifat immutable (read-only). Data tersimpan permanen untuk audit trail.

---

## Troubleshooting

### Issue 1: "Logs Tidak Muncul Setelah CRUD"

**Symptoms:**
- User create/update/delete data
- Navigate ke Activity Log screen
- Log terbaru tidak muncul

**Diagnosis Steps:**

1. **Check CRUD Response**
   ```javascript
   const response = await createAnggota(data);
   console.log('CRUD Response:', response.status);
   // Expected: 200 atau 201
   ```
   - Jika **200/201**: CRUD sukses, log tercatat
   - Jika **422**: Validation error, log tercatat dengan status='failed'
   - Jika **500**: Server error, cek backend logs

2. **Check Timing**
   ```javascript
   // Add delay before fetching logs
   await createAnggota(data);
   await new Promise(r => setTimeout(r, 500)); // Wait 500ms
   const logs = await fetchActivityLogs();
   ```

3. **Check Pagination**
   ```javascript
   // Make sure fetching page 1
   const logs = await fetchActivityLogs({ page: 1, perPage: 10 });
   console.log('Total logs:', logs.meta.total);
   ```

4. **Check Token**
   ```javascript
   const token = await AsyncStorage.getItem('auth_token');
   console.log('Token:', token ? 'Valid' : 'Missing');
   ```

**Solutions:**
- ✅ Implement pull-to-refresh
- ✅ Add 500ms delay before auto-refresh
- ✅ Always fetch page=1 after CRUD
- ✅ Handle 401 errors (token expired)

---

### Issue 2: "403 Forbidden saat GET log detail"

**Symptoms:**
```json
{
  "message": "Unauthorized. You can only view your own activity logs."
}
```

**Cause:**
User mencoba akses log milik user lain.

**Diagnosis:**
```javascript
const log = await fetchLogDetail(999);
// Check: Apakah log ID 999 milik user yang login?
```

**Solutions:**
- ✅ Hanya tampilkan logs dari endpoint `GET /api/activity-logs` (sudah auto-filter)
- ✅ Jangan hardcode log IDs
- ✅ Validate log ownership sebelum navigate ke detail

---

### Issue 3: "Empty Response atau []"

**Symptoms:**
```json
{
  "data": [],
  "meta": { "total": 0 }
}
```

**Possible Causes:**

1. **User belum pernah melakukan CRUD**
   - Solution: Lakukan create/update/delete dulu

2. **Filter terlalu ketat**
   ```javascript
   // Check applied filters
   const logs = await fetchActivityLogs({
     resource_type: 'anggota',
     status: 'failed',
     date_from: '2026-01-01',
     date_to: '2026-01-01'
   });
   // Mungkin tidak ada log yang match semua filter
   ```
   - Solution: Reset filters atau perluas range

3. **Wrong token / different user**
   - Solution: Verify login credentials

---

### Issue 4: "Validation Error Log Tidak Tercatat"

**Symptoms:**
- Submit form dengan data invalid
- Dapat response 422
- Tapi log tidak muncul di activity logs

**Diagnosis:**
```javascript
try {
  await createAnggota({ NAMA: '' }); // Invalid!
} catch (error) {
  console.log('Error status:', error.status); // Should be 422
  
  // Wait then check logs
  await new Promise(r => setTimeout(r, 1000));
  
  const logs = await fetchActivityLogs({ status: 'failed' });
  console.log('Failed logs:', logs.data);
}
```

**Expected Behavior:**
- Backend **HARUS** create log dengan `status='failed'`
- Check `error_message` berisi pesan validasi

**If Still Not Working:**
- Kemungkinan bug di backend
- Check backend logs: `tail -f storage/logs/laravel.log`
- Verify trait LogsActivity di-use di Service

---

### Issue 5: "Performance Issues / Slow Loading"

**Symptoms:**
- Activity log screen takes > 3 seconds to load
- App freezes saat scroll

**Diagnosis:**

1. **Check per_page value**
   ```javascript
   // Too many items?
   const logs = await fetchActivityLogs({ perPage: 100 }); // ❌ Too much!
   ```
   - Solution: Use `perPage: 10-20`

2. **Check network latency**
   ```javascript
   const start = Date.now();
   await fetchActivityLogs();
   const duration = Date.now() - start;
   console.log('Fetch duration:', duration, 'ms');
   // Expected: < 1000ms
   ```

3. **Check caching**
   ```javascript
   // Are you fetching too often?
   // Implement cache with 5-minute TTL
   ```

**Solutions:**
- ✅ Use `perPage: 10`
- ✅ Implement caching (5 min TTL)
- ✅ Use FlatList with proper `keyExtractor`
- ✅ Debounce search input (300ms)
- ✅ Lazy load detail screen

---

### Issue 6: "Old Data vs New Data Empty"

**Symptoms:**
```json
{
  "action_type": "update",
  "old_data": null,
  "new_data": null
}
```

**Cause:**
Backend issue - Service tidak passing data dengan benar.

**Expected Behavior:**
- **Create**: `old_data: null`, `new_data: {data}`
- **Update**: `old_data: {before}`, `new_data: {after}`
- **Delete**: `old_data: {data}`, `new_data: null`

**If Happens:**
- Report ke backend team
- Cek implementation di Service layer

---

### Debug Checklist

Sebelum report bug, pastikan:

- [ ] Token valid (cek AsyncStorage)
- [ ] CRUD operation sukses (response 200/201)
- [ ] Fetch logs dengan `page=1` setelah CRUD
- [ ] Pull-to-refresh implemented
- [ ] No filter yang terlalu ketat
- [ ] `perPage` tidak terlalu besar (< 50)
- [ ] Network connection stable
- [ ] Backend server running
- [ ] Check console logs untuk error message

---

## Backend Implementation Details

### Auto-Filter Mechanism

**Controller Level (Security Layer):**
```php
// app/Http/Controllers/Api/ActivityLogController.php

public function index(Request $request)
{
    $query = ActivityLog::query()
        ->where('user_id', auth()->id())  // ⚠️ AUTO-FILTER HERE
        ->orderBy('created_at', 'desc');
    
    // ... apply other filters
    
    return ActivityLogResource::collection($query->paginate($perPage));
}

public function show(int $id)
{
    $log = ActivityLog::findOrFail($id);
    
    // Authorization check
    if ($log->user_id !== auth()->id()) {  // ⚠️ OWNERSHIP CHECK
        return response()->json([
            'message' => 'Unauthorized. You can only view your own activity logs.',
        ], Response::HTTP_FORBIDDEN);
    }
    
    return new ActivityLogResource($log);
}
```

**Service Level (Auto-Logging):**
```php
// app/Traits/LogsActivity.php

protected function performWithLog(string $action, callable $operation, array $context)
{
    try {
        $result = $operation();
        
        // Log success
        $this->createActivityLog($action, $context, 'success');
        
        return $result;
    } catch (\Exception $e) {
        // Log failure (includes validation errors!)
        $this->createActivityLog($action, $context, 'failed', $e->getMessage());
        
        throw $e;  // Re-throw for proper error response
    }
}

private function createActivityLog(...)
{
    ActivityLog::create([
        'user_id' => auth()->id(),           // ⚠️ AUTO-CAPTURE USER
        'user_name' => auth()->user()->name,
        'status' => $status,                  // 'success' or 'failed'
        'error_message' => $errorMessage,     // Null if success
        'ip_address' => request()->ip(),      // ⚠️ AUTO-CAPTURE IP
        'user_agent' => request()->userAgent(), // ⚠️ AUTO-CAPTURE DEVICE
        // ...
    ]);
}
```

### What Gets Logged Automatically

✅ **Success Operations:**
- Create anggota → `status: 'success'`, `new_data: {...}`
- Update data → `status: 'success'`, `old_data: {...}`, `new_data: {...}`
- Delete record → `status: 'success'`, `old_data: {...}`

✅ **Failed Operations:**
- Validation errors (422) → `status: 'failed'`, `error_message: "The field is required"`
- Database constraint errors → `status: 'failed'`, `error_message: "Foreign key constraint"`
- Authorization errors → `status: 'failed'`, `error_message: "Access denied"`
- Any exception → `status: 'failed'`, `error_message: {exception message}`

**Frontend Tidak Perlu:**
- ❌ Manual create log
- ❌ Pass user_id parameter
- ❌ Handle IP address / user agent
- ❌ Differentiate success vs failed (backend auto-detect)

**Frontend Hanya Perlu:**
- ✅ Consume endpoint untuk display
- ✅ Handle pagination
- ✅ Apply filters sesuai kebutuhan UI

      "user_id": 3,
      "user_name": "Budi Santoso",
      "resource_type": "anggota",
      "resource_id": "AGT-050",
      "action_type": "delete",
      "description": "Menghapus anggota: Ahmad Yani",
      "status": "failed",
      "error_message": "SQLSTATE[23000]: Integrity constraint violation: 1451 Cannot delete or update a parent row: a foreign key constraint fails",
      "old_data": {
        "NO_AGT": "AGT-050",
        "NAMA": "Ahmad Yani",
        "ID_KS": "KS-010"
      },
      "new_data": null,
      "ip_address": "192.168.1.105",
      "created_at": "2026-01-10T12:00:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 1
  }
}
```

---

## FAQ

**Q: Apakah saya perlu manual create activity log?**  
A: Tidak. Activity log **otomatis tercatat** setiap kali ada operasi CRUD. Frontend hanya perlu consume endpoint untuk menampilkan.

**Q: Berapa lama activity log disimpan?**  
A: **Unlimited**. Data tidak pernah dihapus otomatis dan tersimpan permanen di database.

**Q: Apakah saya bisa lihat activity log user lain?**  
A: **Tidak**. User hanya bisa melihat activity log milik sendiri. Endpoint sudah auto-filter berdasarkan `auth()->id()`.

**Q: Apa bedanya `old_data` dan `new_data`?**  
A: 
- `old_data`: Data sebelum perubahan (untuk update & delete)
- `new_data`: Data setelah perubahan (untuk create & update)
- Keduanya dalam format JSON object

**Q: Kenapa `error_message` null padahal status success?**  
A: Field `error_message` hanya terisi jika `status = 'failed'`. Untuk success, field ini selalu `null`.

**Q: Bagaimana cara retry failed operation?**  
A: Activity log hanya mencatat, tidak bisa di-retry dari sini. User harus manual ulangi operasi CRUD dari halaman yang sesuai.

---

## Support

**Dokumentasi:**
- [Backend Requirement](BACKEND_REQUIREMENT_ACTIVITY_LOG.md)
- [Implementation Plan](plans/activity-log-implementation.md)

**Testing:**
- Run test script: `php test-activity-log.php`
- Check routes: `php artisan route:list --path=activity-logs`

**Contact:**
- Developer: Backend Team
- Last Updated: 10 Januari 2026
