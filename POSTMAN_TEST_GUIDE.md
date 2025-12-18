# 📮 Postman Testing Guide - Backend API

Base URL: `http://localhost/backend-app/public/api`

---

## 🔐 Authentication Endpoints

### 1. Request OTP (Email Verification)
**POST** `/auth/request-otp`
```json
{
  "email": "john@example.com"
}
```
**Response:**
```json
{
  "message": "OTP has been sent to your email",
  "email": "john@example.com",
  "expires_in": 300
}
```
**Note:** Check your email inbox for the 6-digit OTP code. The code expires in 5 minutes.

### 2. Register (with OTP)
**POST** `/auth/register`
```json
{
  "email": "john@example.com",
  "otp": "123456",
  "name": "John Doe",
  "password": "password123",
  "password_confirmation": "password123"
}
```
**Response:** Returns user object with `email_verified_at` set and authentication token.

**Note:** You must request OTP first (endpoint #1) before registering.

### 3. Login
**POST** `/auth/login`
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```
**Response:** Save the `token` for Authorization header

### 4. Logout
**POST** `/auth/logout`
**Headers:** `Authorization: Bearer {token}`

### 5. Get User Profile
**GET** `/auth/me`
**Headers:** `Authorization: Bearer {token}`

---

## 📋 CRUD Endpoints (All require Authentication)

**Note:** Add `Authorization: Bearer {token}` to all requests below

---

## 👥 KETUA KS (Ketua Kelompok Swadaya)

### Create
**POST** `/ketua-ks`
```json
{
  "ID_KET": "KET001",
  "NO_AGT": "AGT001",
  "NAMA": "Ahmad Suryanto",
  "STAT": "AKTIF",
  "TGL_STAT": "2025-01-01",
  "NO_SK": 123
}
```

### Get All (with filters)
**GET** `/ketua-ks?per_page=15&ID_KET=KET001&NO_AGT=AGT001`

### Get One
**GET** `/ketua-ks/KET001`

### Update
**PUT** `/ketua-ks/KET001`
```json
{
  "NO_AGT": "AGT002",
  "NAMA": "Ahmad Suryanto Updated",
  "STAT": "TIDAK_AKTIF",
  "TGL_STAT": "2025-02-01",
  "NO_SK": 124
}
```

### Delete
**DELETE** `/ketua-ks/KET001`

---

## 📝 SEKRETARIS KS (Sekretaris Kelompok Swadaya)

### Create
**POST** `/sekretaris-ks`
```json
{
  "ID_SEKRE": "SEK001",
  "NO_AGT": "AGT002",
  "NAMA": "Siti Aminah",
  "STAT": "AKTIF",
  "TGL_STAT": "2025-01-01",
  "NO_SK": 125
}
```

### Get All
**GET** `/sekretaris-ks?per_page=15`

### Get One
**GET** `/sekretaris-ks/SEK001`

### Update
**PUT** `/sekretaris-ks/SEK001`
```json
{
  "NAMA": "Siti Aminah Updated",
  "STAT": "TIDAK_AKTIF"
}
```

### Delete
**DELETE** `/sekretaris-ks/SEK001`

---

## 👤 ANGGOTA (Member)

### Create
**POST** `/anggota`
```json
{
  "NO_AGT": "AGT12345",
  "NAMA": "Budi Santoso",
  "ID_KS": "KS001",
  "ID_LO": "LO001",
  "ID_AO": "AO001",
  "ID_KS_ASL": "KS001",
  "TGL_MTS": "2025-01-15",
  "TGL_AKTIF": "2025-01-20",
  "TGL_JA": "2025-02-01"
}
```

### Get All (with filters)
**GET** `/anggota?per_page=15&NO_AGT=AGT12345&ID_KS=KS001`

### Get One
**GET** `/anggota/AGT12345`

### Update
**PUT** `/anggota/AGT12345`
```json
{
  "NAMA": "Budi Santoso Updated",
  "ID_KS": "KS002",
  "TGL_AKTIF": "2025-02-01"
}
```

### Delete
**DELETE** `/anggota/AGT12345`

---

## 🏠 KEL SAH (Kelompok Sahaja)

### Create
**POST** `/kel-sah`
```json
{
  "ID_KEL": "KEL001",
  "NAMA_KEL": "Kelompok Makmur",
  "ID_KETUA": "KET001",
  "ID_SEK": "SEK001",
  "ID_LO": "LO001",
  "ID_AO": "AO001",
  "ALAMAT": "Jl. Merdeka No. 123",
  "STAT": "AKTIF",
  "TGL_STAT": "2025-01-01",
  "ID_PENGELOLA": "PNG001"
}
```

### Get All
**GET** `/kel-sah?per_page=15&ID_KEL=KEL001`

### Get One
**GET** `/kel-sah/KEL001`

### Update
**PUT** `/kel-sah/KEL001`
```json
{
  "NAMA_KEL": "Kelompok Makmur Jaya",
  "ALAMAT": "Jl. Merdeka No. 125",
  "STAT": "TIDAK_AKTIF"
}
```

### Delete
**DELETE** `/kel-sah/KEL001`

---

## 🏢 DATA LO (Liaison Officer)

### Create
**POST** `/data-lo`
```json
{
  "ID_LO": "LO001",
  "NO_AGT": "AGT001",
  "ID_TP": "TP001",
  "NAMA": "Rina Wijaya",
  "STAT": "AKTIF",
  "TGL_STAT": "2025-01-01"
}
```

### Get All
**GET** `/data-lo?per_page=15&ID_LO=LO001`

### Get One
**GET** `/data-lo/LO001`

### Update
**PUT** `/data-lo/LO001`
```json
{
  "NAMA": "Rina Wijaya Updated",
  "STAT": "TIDAK_AKTIF"
}
```

### Delete
**DELETE** `/data-lo/LO001`

---

## 💼 DATA AO (Account Officer)

### Create
**POST** `/data-ao`
```json
{
  "ID_AO": "AO001",
  "NO_AGT": "AGT003",
  "NAMA": "Dedi Kurniawan",
  "STAT": "AKTIF",
  "TGL_STAT": "2025-01-01"
}
```

### Get All
**GET** `/data-ao?per_page=15&ID_AO=AO001`

### Get One
**GET** `/data-ao/AO001`

### Update
**PUT** `/data-ao/AO001`
```json
{
  "NAMA": "Dedi Kurniawan Updated",
  "STAT": "TIDAK_AKTIF"
}
```

### Delete
**DELETE** `/data-ao/AO001`

---

## 👨‍👩‍👧‍👦 DATA JUMLAH KELUARGA

### Create
**POST** `/data-jlh-keluarga`
```json
{
  "NO_AGT": "AGT12345",
  "JLH_AGT_KEL": 5,
  "TGL": "2025-01-15"
}
```

### Get All
**GET** `/data-jlh-keluarga?per_page=15&NO_AGT=AGT12345`

### Get One
**GET** `/data-jlh-keluarga/AGT12345`

### Update
**PUT** `/data-jlh-keluarga/AGT12345`
```json
{
  "JLH_AGT_KEL": 6,
  "TGL": "2025-02-01"
}
```

### Delete
**DELETE** `/data-jlh-keluarga/AGT12345`

---

## 💰 DATA PENGHASILAN

### Create
**POST** `/data-penghasilan`
```json
{
  "NO_AGT": "AGT12345",
  "PENGHASILAN": "5000000",
  "PENGELUARAN": "3000000",
  "TGL_DATA": "2025-01-15"
}
```

### Get All
**GET** `/data-penghasilan?per_page=15&NO_AGT=AGT12345`

### Get One
**GET** `/data-penghasilan/AGT12345`

### Update
**PUT** `/data-penghasilan/AGT12345`
```json
{
  "PENGHASILAN": "5500000",
  "PENGELUARAN": "3200000",
  "TGL_DATA": "2025-02-01"
}
```

### Delete
**DELETE** `/data-penghasilan/AGT12345`

---

## 🎯 TARGET (Planning Data - Composite Key)

### Create
**POST** `/target`
```json
{
  "ID_KS": "KS001",
  "TGL_TGT": "2025-01",
  "JLH_AGT_BR": 100,
  "STR_SP": "50000000",
  "SLD_SP": "45000000",
  "STR_SW": "30000000",
  "SLD_SW": "28000000",
  "STR_SS": "20000000",
  "SLD_SS": "19000000",
  "STR_SHR": "15000000",
  "SLD_SHR": "14500000",
  "STR_SMD": "10000000",
  "SLD_SMD": "9500000",
  "STR_SPD": "8000000",
  "SLD_SPD": "7800000",
  "STR_SBJ": "5000000",
  "SLD_SBJ": "4900000",
  "STR_SJP": "3000000",
  "SLD_SJP": "2950000",
  "STR_SRY": "2000000",
  "SLD_SRY": "1950000",
  "STR_SKA": "1000000",
  "SLD_SKA": "980000",
  "STR_SRI": "500000",
  "SLD_SRI": "490000",
  "STR_SSD": "300000",
  "SLD_SSD": "295000",
  "PCR_PJM": "2000000",
  "SLD_PJM": "1900000",
  "BNG_PJM": "100000",
  "SLD_BNG": "95000",
  "ASR_PKK": "500000",
  "REK_SHR": 50,
  "REK_SPD": 30,
  "REK_SMD": 20,
  "REK_SRY": 10,
  "STF_SBJ": 5,
  "STF_SJP": 3,
  "JLH_REK": 100,
  "JLH_TAB": 80,
  "TBN_PK": 10,
  "PRC_SHR": "10",
  "JLH_TAR_SHR": 500000,
  "SLD_T_SHR": "490000",
  "PRC_SMD": "8",
  "JLH_TAR_SMD": 300000,
  "SLD_T_SMD": "290000",
  "PRC_SPD": "5",
  "JLH_TAR_SPD": 200000,
  "SLD_T_SPD": "195000",
  "PRC_SRY": "3",
  "JLH_TAR_SRY": 100000,
  "SLD_T_SRY": "98000"
}
```

### Get All
**GET** `/target?per_page=15&ID_KS=KS001&TGL_TGT=2025-01`

### Get One
**GET** `/target/KS001/2025-01`

### Update
**PUT** `/target/KS001/2025-01`
```json
{
  "JLH_AGT_BR": 105,
  "STR_SP": "52000000",
  "SLD_SP": "47000000"
}
```

### Delete
**DELETE** `/target/KS001/2025-01`

---

## 📊 REALISASI (Read Only - Composite Key)

### Get All
**GET** `/realisasi?per_page=15&ID_KS=KS001&TGL_TGT=2025-01`

### Get One
**GET** `/realisasi/KS001/2025-01`

---

## 📈 DASHBOARD (Read Only - JOIN Target + Realisasi)

### Get Dashboard Data
**GET** `/dashboard?ID_KS=KS001&TGL_TGT=2025-01`

**Response Example:**
```json
{
  "data": [
    {
      "ID_KS": "KS001",
      "TGL_TGT": "2025-01",
      "target_jlh_agt_br": 100,
      "target_str_sp": "50000000",
      "target_sld_sp": "45000000",
      "realisasi_jlh_agt_br": 95,
      "realisasi_str_sp": "48000000",
      "realisasi_str_sw": "29000000"
    }
  ],
  "summary": {
    "total_records": 1,
    "total_target_anggota": 100,
    "total_realisasi_anggota": 95,
    "total_target_rekening_shr": 50,
    "total_realisasi_rekening_shr": 48
  }
}
```

---

## 🔍 DATA KUNJUNGAN (Full CRUD)

### Create
**POST** `/data-kunjungan`
```json
{
  "ID_LO": "LO001",
  "NO_AGT": "AGT12345",
  "ID_KEL_SAH": "KEL001",
  "TGL_KUN": "2025-01-20",
  "KEGIATAN": "Pembinaan Kelompok",
  "ID_PIC": "PIC001",
  "JLH_PESERTA": 25
}
```

### Get All
**GET** `/data-kunjungan?per_page=15&ID_LO=LO001`

### Get One
**GET** `/data-kunjungan/1`

### Update
**PUT** `/data-kunjungan/1`
```json
{
  "KEGIATAN": "Pembinaan Kelompok Updated",
  "JLH_PESERTA": 30
}
```

### Delete
**DELETE** `/data-kunjungan/1`

---

## 📖 DATA TRS (Read Only)

### Get All
**GET** `/data-trs?per_page=15`

### Get One
**GET** `/data-trs/{id}`

---

## 🔧 Common Query Parameters

All `GET` endpoints support:
- `per_page` - Number of items per page (default: 15)
- Filter parameters specific to each resource

**Example:**
```
GET /anggota?per_page=20&ID_KS=KS001&ID_LO=LO001
```

---

## ✅ Testing Workflow Recommendation

1. **Setup Gmail SMTP (First Time Only)**
   - Generate App Password from Google Account
   - Update `.env` with Gmail SMTP credentials
   - Start queue worker: `php artisan queue:work`

2. **OTP Registration Flow**
   - Request OTP → `POST /auth/request-otp` with email
   - Check email inbox for 6-digit OTP code
   - Register → `POST /auth/register` with email, OTP, name, password
   - Get auth token from response

3. **Login** → Use registered credentials to get auth token

4. **Create Master Data:**
   - Ketua KS
   - Sekretaris KS
   - Data LO
   - Data AO
   - Anggota

5. **Create Transactional Data:**
   - Kel Sah
   - Data Jumlah Keluarga
   - Data Penghasilan
   - Target

6. **Test Read Operations:**
   - Get all resources with filters
   - Get specific resource by ID

7. **Test Dashboard:**
   - View joined Target + Realisasi data

8. **Test Update & Delete:**
   - Update specific records
   - Delete records

---

## 📝 Notes

### OTP System
- **OTP expires in 5 minutes** - Request new OTP if expired
- **Rate limiting:** Max 3 OTP requests per email per 10 minutes
- **Max attempts:** 5 verification attempts per OTP code
- **Email must be unique** - Cannot request OTP for already registered email
- **Queue worker required:** Run `php artisan queue:work` for email sending

### General
- All fields marked as `nullable` in validation can be omitted
- Date fields use string format (YYYY-MM-DD or YYYY-MM)
- Composite key resources (Target, Realisasi) use two parameters: `idKs` and `tglTgt`
- Dashboard endpoint provides summary statistics for business intelligence

---

## 🚨 Common HTTP Status Codes

- `200 OK` - Success (GET, PUT)
- `201 Created` - Resource created (POST)
- `204 No Content` - Success with no body (DELETE)
- `401 Unauthorized` - Missing or invalid token
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation error

---

**Happy Testing! 🚀**
