# Backend Requirement - Firebird Anggota List API

**Dibuat:** 17 Januari 2026  
**Untuk:** Backend Developer  
**Fitur:** Import Anggota dari Firebird dengan Dropdown Selection  
**Priority:** Medium-High  

---

## 📋 Konteks & Tujuan

### Latar Belakang

Saat ini aplikasi Flutter sudah memiliki fitur untuk **import data anggota dari database Firebird** ke MySQL menggunakan endpoint:
- `GET /import-anggota-firebird/{NO_AGT}` - Preview single record
- `POST /import-anggota-firebird` - Import & save to MySQL

Namun, untuk UX yang lebih baik, **frontend membutuhkan endpoint baru** untuk mendapatkan **list semua NO_AGT** yang tersedia di Firebird agar bisa ditampilkan dalam bentuk **dropdown/autocomplete**.

### Tujuan

User dapat:
1. **Melihat semua NO_AGT** yang tersedia di Firebird dalam dropdown
2. **Search/filter** berdasarkan NO_AGT atau NAMA
3. **Pilih anggota** dari dropdown (tidak perlu hafal NO_AGT)
4. **Auto-fill form** dengan data yang dipilih

---

## 🎯 Kebutuhan API Baru

### Endpoint yang Dibutuhkan

```
GET /api/import-anggota-firebird
```

**atau alternatif:**

```
GET /api/firebird-anggota
```

### Deskripsi

Endpoint ini untuk mendapatkan **list semua NO_AGT dan NAMA** dari database Firebird (bukan MySQL). Data ini akan digunakan untuk populate dropdown di form import.

---

## 📝 Spesifikasi Endpoint

### Request

**Method:** `GET`

**Endpoint:** `/api/import-anggota-firebird` (without ID parameter)

**Headers:**
```http
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters (Optional):**

| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| `search` | string | No | Search by NO_AGT atau NAMA | `?search=John` |
| `page` | integer | No | Nomor halaman (jika pakai pagination) | `?page=1` |
| `per_page` | integer | No | Jumlah data per halaman (default: 100) | `?per_page=50` |

**Example Request:**
```bash
GET /api/import-anggota-firebird
GET /api/import-anggota-firebird?search=160050
GET /api/import-anggota-firebird?page=1&per_page=100
```

---

### Response Success (200 OK)

#### Opsi 1: Simple List (Recommended untuk Dropdown)

```json
{
  "data": [
    {
      "NO_AGT": "160050000003",
      "NAMA": "John Doe"
    },
    {
      "NO_AGT": "160050000030",
      "NAMA": "Jane Smith"
    },
    {
      "NO_AGT": "160050000031",
      "NAMA": "Bob Johnson"
    }
  ],
  "meta": {
    "total": 3
  }
}
```

#### Opsi 2: With Pagination (Jika Data Banyak)

```json
{
  "data": [
    {
      "NO_AGT": "160050000003",
      "NAMA": "John Doe"
    },
    {
      "NO_AGT": "160050000030",
      "NAMA": "Jane Smith"
    }
  ],
  "links": {
    "first": "http://127.0.0.1:8000/api/import-anggota-firebird?page=1",
    "last": "http://127.0.0.1:8000/api/import-anggota-firebird?page=10",
    "prev": null,
    "next": "http://127.0.0.1:8000/api/import-anggota-firebird?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 100,
    "to": 100,
    "total": 950
  }
}
```

---

### Response Error

#### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

#### 500 Internal Server Error (Firebird Connection Error)
```json
{
  "message": "Failed to connect to Firebird database",
  "error": "Connection timeout"
}
```

---

## 🔧 Implementasi Backend

### Database Source

**Database:** Firebird (bukan MySQL)  
**Table:** `anggota` (atau sesuai nama tabel di Firebird)  
**Fields yang Dibutuhkan:**
- `NO_AGT` (string, 15 char) - Primary Key
- `NAMA` (string, 255 char) - Nama anggota

### Query Firebird (Contoh)

```sql
SELECT NO_AGT, NAMA 
FROM anggota 
WHERE NO_AGT IS NOT NULL
ORDER BY NO_AGT ASC
LIMIT 100 OFFSET 0
```

**Dengan Search:**
```sql
SELECT NO_AGT, NAMA 
FROM anggota 
WHERE (NO_AGT LIKE '%search%' OR NAMA LIKE '%search%')
  AND NO_AGT IS NOT NULL
ORDER BY NO_AGT ASC
LIMIT 100 OFFSET 0
```

### Laravel Controller Example (Reference)

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FirebirdAnggotaController extends Controller
{
    /**
     * Get list of anggota from Firebird database
     */
    public function index(Request $request)
    {
        try {
            // Get Firebird connection
            $firebird = DB::connection('firebird');
            
            // Build query
            $query = $firebird->table('anggota')
                ->select('NO_AGT', 'NAMA')
                ->whereNotNull('NO_AGT');
            
            // Apply search if provided
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('NO_AGT', 'LIKE', "%{$search}%")
                      ->orWhere('NAMA', 'LIKE', "%{$search}%");
                });
            }
            
            // Order by NO_AGT
            $query->orderBy('NO_AGT', 'asc');
            
            // Pagination (optional)
            $perPage = $request->get('per_page', 100);
            if ($request->has('page')) {
                $result = $query->paginate($perPage);
                
                return response()->json([
                    'data' => $result->items(),
                    'links' => [
                        'first' => $result->url(1),
                        'last' => $result->url($result->lastPage()),
                        'prev' => $result->previousPageUrl(),
                        'next' => $result->nextPageUrl(),
                    ],
                    'meta' => [
                        'current_page' => $result->currentPage(),
                        'from' => $result->firstItem(),
                        'last_page' => $result->lastPage(),
                        'per_page' => $result->perPage(),
                        'to' => $result->lastItem(),
                        'total' => $result->total(),
                    ]
                ]);
            }
            
            // Simple list (no pagination)
            $data = $query->limit(1000)->get();
            
            return response()->json([
                'data' => $data,
                'meta' => [
                    'total' => $data->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch data from Firebird',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

### Route Definition (routes/api.php)

```php
// Firebird Anggota endpoints
Route::middleware('auth:sanctum')->group(function () {
    // List all anggota from Firebird (for dropdown)
    Route::get('/import-anggota-firebird', [FirebirdAnggotaController::class, 'index']);
    
    // Preview single anggota (existing)
    Route::get('/import-anggota-firebird/{noAgt}', [FirebirdAnggotaController::class, 'preview']);
    
    // Import anggota to MySQL (existing)
    Route::post('/import-anggota-firebird', [FirebirdAnggotaController::class, 'import']);
});
```

---

## ✅ Validation & Business Rules

### Validation Rules

- ✅ User harus **authenticated** (pakai Sanctum token)
- ✅ Return **hanya data dengan NO_AGT yang valid** (not null, not empty)
- ✅ **NAMA boleh null** (handle di frontend)
- ✅ Data di-order berdasarkan **NO_AGT ascending**

### Business Rules

1. **Data Source:** Harus dari **Firebird database**, bukan MySQL
2. **Fields Minimal:** NO_AGT dan NAMA (fields lain tidak perlu)
3. **Performance:** 
   - Jika data < 1000 records → return all (no pagination)
   - Jika data > 1000 records → pakai pagination (per_page: 100)
4. **Search:** Support search di NO_AGT dan NAMA (LIKE query)
5. **No Duplicate:** NO_AGT harus unique di response

---

## 🎯 Use Cases

### Use Case 1: Load Dropdown (All Data)

**Frontend:**
```dart
GET /api/import-anggota-firebird
```

**Expected Response:**
```json
{
  "data": [
    {"NO_AGT": "160050000003", "NAMA": "John Doe"},
    {"NO_AGT": "160050000030", "NAMA": "Jane Smith"},
    ...
  ],
  "meta": {"total": 150}
}
```

**Frontend Action:**
- Populate dropdown dengan format: `"160050000003 - John Doe"`
- User pilih dari dropdown
- Hit preview API untuk detail lengkap

---

### Use Case 2: Search Anggota

**Frontend:**
```dart
GET /api/import-anggota-firebird?search=John
```

**Expected Response:**
```json
{
  "data": [
    {"NO_AGT": "160050000003", "NAMA": "John Doe"},
    {"NO_AGT": "160050000045", "NAMA": "Johnny Walker"}
  ],
  "meta": {"total": 2}
}
```

**Frontend Action:**
- Filter dropdown berdasarkan search
- Menampilkan hasil yang match

---

### Use Case 3: Pagination (Jika Data Banyak)

**Frontend:**
```dart
GET /api/import-anggota-firebird?page=2&per_page=100
```

**Expected Response:**
```json
{
  "data": [...],
  "links": {
    "first": "...?page=1",
    "prev": "...?page=1",
    "next": "...?page=3",
    "last": "...?page=10"
  },
  "meta": {
    "current_page": 2,
    "total": 950
  }
}
```

**Frontend Action:**
- Load more saat scroll
- Lazy loading untuk performance

---

## 🔍 Testing Checklist

### Backend Developer Testing

- [ ] Endpoint accessible dengan auth token
- [ ] Return data dari **Firebird** (bukan MySQL)
- [ ] Response format sesuai spesifikasi
- [ ] Query parameter `search` berfungsi (NO_AGT dan NAMA)
- [ ] Pagination berfungsi (jika diimplementasikan)
- [ ] Handle Firebird connection error dengan proper error message
- [ ] Performance test dengan data > 1000 records
- [ ] No duplicate NO_AGT di response
- [ ] Handle NAMA yang null tidak error

### Integration Testing

- [ ] Frontend bisa hit endpoint dan dapat data
- [ ] Dropdown ter-populate dengan benar
- [ ] Search/filter berfungsi
- [ ] Format display: "NO_AGT - NAMA" tampil dengan benar
- [ ] Setelah pilih, preview API (`GET /{NO_AGT}`) return data lengkap

---

## 📌 Notes & Considerations

### Performance

- Jika Firebird database **lambat**, pertimbangkan:
  - Caching di backend (Redis)
  - Limit default per_page: 100
  - Indexing di field NO_AGT dan NAMA

### Data Consistency

- Data di Firebird bisa **berbeda** dengan MySQL
- Pastikan endpoint ini **tidak modify** data di Firebird
- Read-only access ke Firebird

### Error Handling

- Handle **Firebird connection timeout**
- Handle **query timeout** (jika data sangat besar)
- Return proper HTTP status codes

---

## 🚀 Priority & Timeline

**Priority:** Medium-High

**Estimated Backend Work:**
- Setup endpoint & routing: **0.5 jam**
- Query Firebird logic: **1 jam**
- Search & pagination: **1 jam**
- Testing & error handling: **1 jam**

**Total:** ~3-4 jam

**Dependencies:**
- Firebird database connection sudah configured
- Table `anggota` accessible dari Laravel

---

## 📞 Contact

Jika ada pertanyaan atau klarifikasi terkait requirement ini:
- **Frontend Team:** [Your Name/Team]
- **Issue Tracker:** [Link jika ada]

---

## 🔄 Updates Log

| Date | Version | Changes |
|------|---------|---------|
| 2026-01-17 | 1.0 | Initial requirement document |

---

**Approved by:** _________________  
**Date:** _________________
