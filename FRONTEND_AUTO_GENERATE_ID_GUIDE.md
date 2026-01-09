# Frontend Integration Guide - Auto-Generate ID Feature

## 📋 Overview

Backend sekarang mendukung **auto-generate ID** untuk 6 master data entities. Frontend **tidak perlu lagi** menyediakan ID saat create record baru - backend akan otomatis generate ID dengan format yang konsisten.

### ✨ Key Benefits
- ✅ Tidak perlu handle ID generation di frontend
- ✅ ID format konsisten dan tervalidasi
- ✅ Thread-safe (tidak ada duplicate ID)
- ✅ Sequential numbering otomatis
- ✅ Masih bisa manual input ID jika diperlukan (optional)

---

## 🎯 Affected Endpoints

### Entities dengan Auto-Generate ID

| Entity | Endpoint | ID Field | Auto-Generate Prefix |
|--------|----------|----------|---------------------|
| **Ketua KS** | `POST /api/ketua-ks` | `ID_KET` | `0160051xxxxx` |
| **Kelompok Sahabat** | `POST /api/kel-sah` | `ID_KEL` | `0160052xxxxx` |
| **Data LO** | `POST /api/data-lo` | `ID_LO` | `0160053xxxxx` |
| **Sekretaris KS** | `POST /api/sekre-ks` | `ID_SEKRE` | `0160054xxxxx` |
| **Data AO** | `POST /api/data-ao` | `ID_AO` | `0160055xxxxx` |
| **Data Pengelola** | `POST /api/data-pengelola` | `ID_PENG` | `0160056xxxxx` |

---

## 📐 ID Format Specification

### Format Structure
```
[KODE_OBORMAS][KODE_ROLE][RUNNING_NUMBER]
     016005        X          NNNNN

Total: 12 digits
```

### Format Details
- **Total Length:** 12 characters (numeric only)
- **KODE_OBORMAS:** `016005` (fixed, 6 digits)
- **KODE_ROLE:** 1 digit, specific per entity type
- **RUNNING_NUMBER:** 5 digits, sequential (00001-99999)

### Examples
```
016005100001  →  Ketua KS pertama
016005200001  →  Kelompok Sahabat pertama
016005300001  →  Data LO pertama
016005400001  →  Sekretaris KS pertama
016005500001  →  Data AO pertama
016005600001  →  Data Pengelola pertama
```

---

## 🔄 API Changes

### Before (Old Behavior)
```json
// ❌ ID field REQUIRED
POST /api/data-ao
{
  "ID_AO": "016005500001",     // WAJIB diisi manual
  "NO_AGT": "123456",
  "NAMA": "John Doe",
  "STAT": "Active"
}
```

### After (New Behavior)
```json
// ✅ ID field OPTIONAL (auto-generate if not provided)
POST /api/data-ao
{
  // ID_AO tidak perlu dikirim - backend auto-generate
  "NO_AGT": "123456",
  "NAMA": "John Doe",
  "STAT": "Active"
}

// Response
{
  "data": {
    "ID_AO": "016005500001",   // ← Auto-generated
    "NO_AGT": "123456",
    "NAMA": "John Doe",
    "STAT": "Active",
    "TGL_STAT": null
  }
}
```

---

## 📝 Usage Examples

### 1. Ketua KS - Auto Generate

**Request:**
```http
POST /api/ketua-ks
Authorization: Bearer {token}
Content-Type: application/json

{
  "NO_AGT": "123456",
  "NAMA": "Ahmad Ketua",
  "STAT": "Active"
}
```

**Response (201 Created):**
```json
{
  "data": {
    "ID_KET": "016005100001",
    "NO_AGT": "123456",
    "NAMA": "Ahmad Ketua",
    "STAT": "Active",
    "TGL_STAT": null
  }
}
```

---

### 2. Kelompok Sahabat - Auto Generate

**Request:**
```http
POST /api/kel-sah
Authorization: Bearer {token}
Content-Type: application/json

{
  "NAMA_KEL": "Kelompok Mawar",
  "ID_KETUA": "016005100001",
  "ID_LO": "016005300001",
  "ID_AO": "016005500001",
  "ALAMAT": "Jl. Merdeka No. 123"
}
```

**Response (201 Created):**
```json
{
  "data": {
    "ID_KEL": "016005200001",
    "NAMA_KEL": "Kelompok Mawar",
    "ID_KETUA": "016005100001",
    "ID_SEK": null,
    "ID_LO": "016005300001",
    "ID_AO": "016005500001",
    "ALAMAT": "Jl. Merdeka No. 123",
    "STAT": null,
    "TGL_STAT": null,
    "ID_PENGELOLA": null
  }
}
```

---

### 3. Data AO - Auto Generate

**Request:**
```http
POST /api/data-ao
Authorization: Bearer {token}
Content-Type: application/json

{
  "NO_AGT": "654321",
  "NAMA": "Siti AO",
  "STAT": "Active"
}
```

**Response (201 Created):**
```json
{
  "data": {
    "ID_AO": "016005500001",
    "NO_AGT": "654321",
    "NAMA": "Siti AO",
    "STAT": "Active",
    "TGL_STAT": null
  }
}
```

---

### 4. Manual ID Input (Optional)

Jika ada kebutuhan khusus untuk specify ID secara manual, masih bisa dilakukan:

**Request:**
```http
POST /api/data-ao
Authorization: Bearer {token}
Content-Type: application/json

{
  "ID_AO": "016005599999",     // Manual ID
  "NO_AGT": "111111",
  "NAMA": "Manual Entry",
  "STAT": "Active"
}
```

**Response (201 Created):**
```json
{
  "data": {
    "ID_AO": "016005599999",   // ← Uses provided ID
    "NO_AGT": "111111",
    "NAMA": "Manual Entry",
    "STAT": "Active",
    "TGL_STAT": null
  }
}
```

---

## ⚠️ Error Handling

### 1. Invalid ID Format

**Request:**
```json
{
  "ID_AO": "123",  // ❌ Invalid format (too short)
  "NAMA": "Test"
}
```

**Response (422 Unprocessable Entity):**
```json
{
  "message": "The ID AO field format is invalid. (and 3 more errors)",
  "errors": {
    "ID_AO": [
      "The ID AO field format is invalid.",
      "The ID AO field must be 12 characters.",
      "The ID AO must be a number.",
      "The ID AO must start with 016005."
    ]
  }
}
```

### 2. Duplicate ID

**Request:**
```json
{
  "ID_AO": "016005500001",  // ❌ Already exists
  "NAMA": "Test"
}
```

**Response (422 Unprocessable Entity):**
```json
{
  "message": "The ID AO has already been taken.",
  "errors": {
    "ID_AO": [
      "The ID AO has already been taken."
    ]
  }
}
```

### 3. Wrong Kode Role

**Request:**
```json
{
  "ID_AO": "016005699999",  // ❌ Kode role 6 salah (harusnya 5 untuk AO)
  "NAMA": "Test"
}
```

**Response (422 Unprocessable Entity):**
```json
{
  "message": "The ID AO field format is invalid.",
  "errors": {
    "ID_AO": [
      "The ID AO field format is invalid."
    ]
  }
}
```

### 4. Maximum Limit Reached (99999)

Jika sequential number sudah mencapai maksimum:

**Response (422 Unprocessable Entity):**
```json
{
  "message": "Maximum ID limit reached for entity type data-ao. Please contact system administrator.",
  "error": "MaximumIdLimitException"
}
```

---

## 🎨 Frontend Implementation Guide

### Recommended Approach

**1. Form Fields - HIDE ID Input**
```javascript
// ✅ Recommended: Jangan tampilkan ID field di create form
const CreateDataAoForm = () => {
  return (
    <form onSubmit={handleSubmit}>
      {/* NO ID field - backend auto-generate */}
      <input name="NO_AGT" placeholder="No. Anggota" required />
      <input name="NAMA" placeholder="Nama" required />
      <select name="STAT">
        <option value="Active">Active</option>
        <option value="Inactive">Inactive</option>
      </select>
      <button type="submit">Simpan</button>
    </form>
  );
};
```

**2. API Call - Don't Include ID**
```javascript
// ✅ Best Practice
const createDataAo = async (formData) => {
  const response = await fetch('/api/data-ao', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      // No ID field
      NO_AGT: formData.noAgt,
      NAMA: formData.nama,
      STAT: formData.stat
    })
  });
  
  const result = await response.json();
  
  if (response.ok) {
    console.log('Created with ID:', result.data.ID_AO);
    return result.data;
  } else {
    throw new Error(result.message);
  }
};
```

**3. Handle Response - Extract Generated ID**
```javascript
const handleCreate = async () => {
  try {
    const newRecord = await createDataAo(formData);
    
    // ID sudah di-generate
    console.log('New ID:', newRecord.ID_AO); // "016005500001"
    
    // Redirect atau update UI
    navigate(`/data-ao/${newRecord.ID_AO}`);
  } catch (error) {
    console.error('Error:', error.message);
  }
};
```

---

### Optional: Advanced Use Case (Manual ID)

Jika ada kebutuhan untuk "Import Data" atau "Manual Entry":

```javascript
const CreateDataAoForm = ({ allowManualId = false }) => {
  return (
    <form onSubmit={handleSubmit}>
      {allowManualId && (
        <div>
          <label>ID AO (Optional - auto-generate if empty)</label>
          <input 
            name="ID_AO" 
            placeholder="016005500001"
            pattern="[0-9]{12}"
            title="12 digit number starting with 0160055"
          />
          <small>Leave empty for auto-generation</small>
        </div>
      )}
      
      <input name="NO_AGT" placeholder="No. Anggota" required />
      <input name="NAMA" placeholder="Nama" required />
      <button type="submit">Simpan</button>
    </form>
  );
};
```

---

## 🔍 Validation Rules (Frontend)

Jika frontend ingin validasi sebelum kirim ke backend:

```javascript
const validateManualId = (idValue, entityType) => {
  const kodeRoles = {
    'ketua-ks': '1',
    'kel-sah': '2',
    'data-lo': '3',
    'sekre-ks': '4',
    'data-ao': '5',
    'data-pengelola': '6'
  };
  
  // Must be 12 digits
  if (!/^\d{12}$/.test(idValue)) {
    return 'ID must be 12 digits';
  }
  
  // Must start with 016005
  if (!idValue.startsWith('016005')) {
    return 'ID must start with 016005';
  }
  
  // Check correct kode role (7th digit)
  const expectedKodeRole = kodeRoles[entityType];
  if (idValue[6] !== expectedKodeRole) {
    return `ID must have kode role ${expectedKodeRole} at position 7`;
  }
  
  // Running number must be 00001-99999
  const runningNumber = parseInt(idValue.slice(7));
  if (runningNumber < 1 || runningNumber > 99999) {
    return 'Running number must be between 00001-99999';
  }
  
  return null; // Valid
};

// Usage
const error = validateManualId('016005500001', 'data-ao');
if (error) {
  console.error(error);
}
```

---

## 📊 Entity-Specific Details

### 1. Ketua KS (`/api/ketua-ks`)
- **ID Field:** `ID_KET`
- **Prefix:** `0160051`
- **Required Fields:** `NO_AGT`, `NAMA`
- **Optional Fields:** `STAT`, `TGL_STAT`

### 2. Kelompok Sahabat (`/api/kel-sah`)
- **ID Field:** `ID_KEL`
- **Prefix:** `0160052`
- **Required Fields:** `NAMA_KEL`, `ID_KETUA`, `ID_LO`, `ID_AO`
- **Optional Fields:** `ID_SEK`, `ALAMAT`, `STAT`, `TGL_STAT`, `ID_PENGELOLA`
- **Note:** Harus create Ketua, LO, AO dulu sebelum create Kelompok Sahabat

### 3. Data LO (`/api/data-lo`)
- **ID Field:** `ID_LO`
- **Prefix:** `0160053`
- **Required Fields:** `NO_AGT`, `NAMA`
- **Optional Fields:** `STAT`, `TGL_STAT`

### 4. Sekretaris KS (`/api/sekre-ks`)
- **ID Field:** `ID_SEKRE`
- **Prefix:** `0160054`
- **Required Fields:** `NO_AGT`, `NAMA`
- **Optional Fields:** `STAT`, `TGL_STAT`

### 5. Data AO (`/api/data-ao`)
- **ID Field:** `ID_AO`
- **Prefix:** `0160055`
- **Required Fields:** `NO_AGT`, `NAMA`
- **Optional Fields:** `STAT`, `TGL_STAT`

### 6. Data Pengelola (`/api/data-pengelola`)
- **ID Field:** `ID_PENG`
- **Prefix:** `0160056`
- **Required Fields:** `NO_AGT`, `NAMA`
- **Optional Fields:** `STAT`, `TGL_STAT`

---

## 🚀 Migration Guide (Frontend)

### Step 1: Update Create Forms
```diff
// Before
<form>
-  <input name="ID_AO" required />  // ❌ Remove
  <input name="NO_AGT" required />
  <input name="NAMA" required />
</form>

// After
<form>
+  {/* ID auto-generated by backend */}
  <input name="NO_AGT" required />
  <input name="NAMA" required />
</form>
```

### Step 2: Update API Calls
```diff
// Before
const data = {
-  ID_AO: generateIdManually(), // ❌ Remove
  NO_AGT: formData.noAgt,
  NAMA: formData.nama
};

// After
const data = {
+  // ID_AO tidak perlu - backend auto-generate
  NO_AGT: formData.noAgt,
  NAMA: formData.nama
};
```

### Step 3: Handle Response
```diff
fetch('/api/data-ao', { ... })
  .then(res => res.json())
  .then(result => {
-    const id = data.ID_AO; // ❌ Old way
+    const id = result.data.ID_AO; // ✅ Get from response
    console.log('Created ID:', id);
  });
```

---

## ✅ Testing Checklist

**Frontend Developer Testing:**

- [ ] Create record tanpa ID field → Should succeed with auto-generated ID
- [ ] Create record dengan valid manual ID → Should succeed with provided ID
- [ ] Create record dengan invalid ID format → Should get 422 validation error
- [ ] Create record dengan duplicate ID → Should get 422 duplicate error
- [ ] Create record dengan wrong kode role → Should get 422 validation error
- [ ] Check sequential numbering → IDs should increment (00001, 00002, 00003...)
- [ ] Check response structure → ID should be in `data.ID_*` field

---

## 🆘 Troubleshooting

### Issue: "ID field is required"
**Problem:** Backend masih expect ID field
**Solution:** Pastikan backend sudah di-update. Check migration status:
```bash
php artisan migrate:status | grep id_sequences
```

### Issue: "422 Validation Error on ID"
**Problem:** Manual ID yang dikirim invalid
**Solution:** 
- Pastikan 12 digit numeric
- Pastikan starts with 016005
- Pastikan kode role benar (digit ke-7)
- Pastikan running number 00001-99999

### Issue: "Duplicate ID"
**Problem:** ID yang di-specify manual sudah exist
**Solution:**
- Jangan specify ID, biar backend auto-generate
- Atau check database dulu sebelum specify manual ID

### Issue: "Sequential number not incrementing"
**Problem:** Multiple records created tapi ID sama
**Solution:** 
- Ini bug di backend, report ke backend team
- Check `id_sequences` table untuk debug

---

## 📞 Support

**Backend Team Contact:**
- Developer: Backend Team
- Documentation: `BACKEND_REQUIREMENT_AUTO_GENERATE_ID.md`
- Test Coverage: 41 tests, 124 assertions

**Related Endpoints:**
- Get List: `GET /api/{entity}`
- Get Detail: `GET /api/{entity}/{id}`
- Update: `PUT/PATCH /api/{entity}/{id}`
- Delete: `DELETE /api/{entity}/{id}`

---

## 📚 Additional Resources

- **API Documentation:** `/POSTMAN_TEST_GUIDE.md`
- **Backend Requirements:** `/BACKEND_REQUIREMENT_AUTO_GENERATE_ID.md`
- **Postman Collection:** `/postman_collection.json`
- **Testing Guide:** `/TESTING_POWERSHELL.md`

---

**Last Updated:** January 4, 2026  
**Version:** 1.0.0  
**Status:** ✅ Production Ready
