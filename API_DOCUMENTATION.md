# API Documentation — Kelompok Sahabat Obor Mas

**Base URL:** `http://localhost/api`  
**Auth:** Laravel Sanctum (Bearer Token)  
**Database:** Firebird (`OBORMAS.FDB`)

---

## Daftar Isi

- [Autentikasi](#autentikasi)
- [Dashboard](#dashboard)
- [Anggota](#anggota)
- [Kelompok Sahabat (Kel Sah)](#kelompok-sahabat-kel-sah)
- [Data LO](#data-lo)
- [Data AO](#data-ao)
- [Ketua KS](#ketua-ks)
- [Sekretaris KS](#sekretaris-ks)
- [Data Pengelola](#data-pengelola)
- [Data Penghasilan](#data-penghasilan)
- [Data Transaksi (Data TRS)](#data-transaksi-data-trs)
- [Data Jumlah Keluarga](#data-jumlah-keluarga)
- [Data Kunjungan](#data-kunjungan)
- [Target & Realisasi (monitoring)](#target--realisasi-monitoring)
- [Activity Log](#activity-log)
- [Member Kelompok](#member-kelompok)
- [Import dari Firebird](#import-dari-firebird)
- [User Approval (Admin)](#user-approval-admin)
- [Super Admin](#super-admin)

---

## Konvensi

### Authentication Header
```
Authorization: Bearer {token}
```

### Role & Middleware

| Role | Middleware | Akses |
|---|---|---|
| `user` | `auth:sanctum` + `member_approved` | Data sesuai kelompok sendiri |
| `admin` | `auth:sanctum` + `admin` | Data semua kelompok + User Approval |
| `super_admin` | `auth:sanctum` + `super_admin` | Akses penuh termasuk manajemen user |

### Format Response Sukses
```json
{
  "data": { ... }
}
```

### Format Response Error
```json
{
  "message": "Pesan error",
  "errors": {
    "field": ["Deskripsi error"]
  }
}
```

### Format Pagination
```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

---

## Autentikasi

### 1. Request OTP (Registrasi)

```
POST /api/auth/request-otp
```

Mengirimkan OTP ke email untuk verifikasi sebelum registrasi.

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

**Response 200:**
```json
{
  "message": "OTP has been sent to your email",
  "email": "user@example.com",
  "expires_in": 300
}
```

**Response 429 (Rate Limit):**
```json
{
  "message": "Too many requests. Please try again later."
}
```

---

### 2. Register

```
POST /api/auth/register
```

Registrasi akun baru dengan verifikasi OTP. Status akun akan menjadi `pending` sampai disetujui admin.

**Request Body:**
```json
{
  "name": "Nama Lengkap",
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "otp": "123456"
}
```

**Response 201:**
```json
{
  "user": {
    "id": 1,
    "name": "Nama Lengkap",
    "email": "user@example.com",
    "role": "user",
    "is_active": false,
    "registration_status": "pending"
  },
  "token": "1|abc123..."
}
```

**Response 422:**
```json
{
  "message": "Invalid or expired OTP code",
  "errors": {
    "otp": ["The OTP code is invalid or has expired."]
  }
}
```

---

### 3. Login

```
POST /api/auth/login
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "device_id": "unique-device-id-optional"
}
```

> `device_id` wajib untuk role `user` (mobile app). Opsional untuk `admin`/`super_admin`.

**Response 200:**
```json
{
  "user": {
    "id": 1,
    "name": "Nama Lengkap",
    "email": "user@example.com",
    "role": "user",
    "is_active": true,
    "registration_status": "approved",
    "no_agt": "016005001",
    "id_kel": "KS000000001"
  },
  "token": "1|abc123..."
}
```

---

### 4. Logout

```
POST /api/auth/logout
```

**Auth:** Required

**Response 200:**
```json
{
  "message": "Successfully logged out"
}
```

---

### 5. Get Current User

```
GET /api/auth/me
```

**Auth:** Required

**Response 200:**
```json
{
  "id": 1,
  "name": "Nama Lengkap",
  "email": "user@example.com",
  "role": "user",
  "is_active": true,
  "registration_status": "approved",
  "no_agt": "016005001",
  "id_kel": "KS000000001"
}
```

---

### 6. Forgot Password

```
POST /api/auth/forgot-password
```

Mengirimkan OTP reset password ke email.

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

**Response 200:**
```json
{
  "message": "OTP has been sent to your email",
  "email": "user@example.com",
  "expires_in": 300
}
```

---

### 7. Verify Reset OTP

```
POST /api/auth/verify-reset-otp
```

Verifikasi OTP dan mendapatkan `reset_token` untuk reset password.

**Request Body:**
```json
{
  "email": "user@example.com",
  "otp": "123456"
}
```

**Response 200:**
```json
{
  "message": "OTP verified. Use the reset token to update your password.",
  "reset_token": "abc123..."
}
```

---

### 8. Reset Password

```
POST /api/auth/reset-password
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "reset_token": "abc123...",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**Response 200:**
```json
{
  "message": "Password has been reset successfully."
}
```

---

## Dashboard

### Dashboard Counts

```
GET /api/dashboard/counts
```

**Auth:** Required (`member_approved`)

Mengembalikan jumlah record untuk semua entitas. Untuk role `user`, data dibatasi sesuai kelompok. Field `data_kunjungan` adalah jumlah baris tabel data kunjungan (untuk member dibalas `0`). Field `target_realisasi` untuk admin/super_admin adalah jumlah kelompok yang ID-nya ada di tabel **target** dan **realisasi** sekaligus (sama dengan baris ringkasan modul Target & Realisasi); untuk member bernilai `1` jika kelompoknya termasuk irisan itu, selain itu `0`.

**Response 200:**
```json
{
  "data": {
    "anggota": 120,
    "data_lo": 5,
    "data_ao": 3,
    "kelompok_sahabat": 10,
    "ketua_ks": 10,
    "sekretaris_ks": 8,
    "pengelola": 3,
    "data_kunjungan": 42,
    "target_realisasi": 7
  }
}
```

---

## Anggota

**Auth:** Required (`member_approved`)  
**Base URL:** `/api/anggota`

### List Anggota

```
GET /api/anggota
```

**Query Parameters:**

| Parameter | Tipe | Keterangan |
|---|---|---|
| `per_page` | integer | Jumlah per halaman (default: 15) |
| `NO_AGT` | string | Filter nomor anggota |
| `ID_KS` | string | Filter ID kelompok |
| `search` | string | Pencarian nama/nomor (substring, **tidak peka huruf besar/kecil**; mendukung nama lengkap atau sebagian) |
| `page` | integer | Halaman |

**Response 200:**
```json
{
  "data": [
    {
      "NO_AGT": "016005001",
      "NAMA": "Nama Anggota",
      "ID_KS": "KS000000001",
      "ID_LO": "LO000000001",
      "ID_AO": "AO000000001",
      "ID_KS_ASL": null,
      "TGL_MTS": "2024-01-01",
      "TGL_AKTIF": "2024-01-15",
      "TGL_JA": null
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

---

### Create Anggota

```
POST /api/anggota
```

> Role `user` (member) tidak dapat menambah data anggota.

**Request Body:**
```json
{
  "NO_AGT": "016005001",
  "NAMA": "Nama Anggota",
  "ID_KS": "KS000000001",
  "ID_KS_ASL": null,
  "TGL_MTS": "2024-01-01",
  "TGL_AKTIF": "2024-01-15",
  "TGL_JA": null
}
```

**Validasi:**
- `NO_AGT`: required, string, max 15 karakter, unique
- `NAMA`: nullable, string, max 255
- `ID_KS`: nullable, string, max 12
- `TGL_MTS`, `TGL_AKTIF`, `TGL_JA`: nullable, string, max 50

**Response 201:**
```json
{
  "data": { ... }
}
```

---

### Get Anggota

```
GET /api/anggota/{NO_AGT}
```

**Response 200:**
```json
{
  "data": {
    "NO_AGT": "016005001",
    "NAMA": "Nama Anggota",
    ...
  }
}
```

---

### Update Anggota

```
PUT /api/anggota/{NO_AGT}
PATCH /api/anggota/{NO_AGT}
```

> Role `user` tidak dapat mengubah data anggota.

**Request Body:** (sama seperti Create, semua field opsional)

**Response 200:**
```json
{
  "data": { ... }
}
```

---

### Delete Anggota

```
DELETE /api/anggota/{NO_AGT}
```

> Role `user` tidak dapat menghapus data anggota.

**Response 204:** No Content

---

### Export Excel

```
GET /api/anggota/export/excel
```

**Query Parameters:** sama seperti List Anggota

**Response:** File `.xlsx`

---

### Export PDF

```
GET /api/anggota/export/pdf
```

**Response:** File `.pdf`

---

## Kelompok Sahabat (Kel Sah)

**Auth:** Required (`member_approved`)  
**Base URL:** `/api/kel-sah`

### List Kelompok Sahabat

```
GET /api/kel-sah
```

**Response 200:**
```json
{
  "data": [
    {
      "ID_KEL": "KS000000001",
      "NAMA_KEL": "Kelompok Mawar",
      "ID_KETUA": "KT000000001",
      "ID_SEK": "SK000000001",
      "ID_LO": "LO000000001",
      "ID_AO": "AO000000001",
      "ALAMAT": "Jl. Contoh No. 1",
      "STAT": "aktif",
      "TGL_STAT": "2024-01-01",
      "ID_PENGELOLA": null
    }
  ]
}
```

---

### Create Kelompok Sahabat

```
POST /api/kel-sah
```

**Request Body:**
```json
{
  "NAMA_KEL": "Kelompok Mawar",
  "ID_KETUA": "KT000000001",
  "ID_SEK": "SK000000001",
  "ID_LO": "LO000000001",
  "ID_AO": "AO000000001",
  "ALAMAT": "Jl. Contoh No. 1",
  "STAT": "aktif",
  "TGL_STAT": "2024-01-01"
}
```

**Validasi:**
- `NAMA_KEL`: nullable, string, unique
- `ID_KETUA`: nullable, string, max 12, unique
- `ID_SEK`: nullable, string, max 12, unique
- `ID_LO`: nullable, string, max 12, unique
- `ID_AO`: nullable, string, max 12, unique

**Response 201**

---

### Get / Update / Delete Kelompok Sahabat

```
GET    /api/kel-sah/{ID_KEL}
PUT    /api/kel-sah/{ID_KEL}
DELETE /api/kel-sah/{ID_KEL}
```

---

### Export

```
GET /api/kel-sah/export/excel
GET /api/kel-sah/export/pdf
```

---

## Data LO

**Auth:** Required (`member_approved`)  
**Base URL:** `/api/data-lo`

### List Data LO

```
GET /api/data-lo
```

**Response 200:**
```json
{
  "data": [
    {
      "ID_LO": "LO000000001",
      "NO_AGT": "016005001",
      "ID_TP": null,
      "NAMA": "Nama LO",
      "STAT": "aktif",
      "TGL_STAT": "2024-01-01"
    }
  ]
}
```

---

### Create Data LO

```
POST /api/data-lo
```

**Request Body:**
```json
{
  "NO_AGT": "016005001",
  "NAMA": "Nama LO",
  "STAT": "aktif",
  "TGL_STAT": "2024-01-01"
}
```

---

### Get / Update / Delete

```
GET    /api/data-lo/{ID_LO}
PUT    /api/data-lo/{ID_LO}
DELETE /api/data-lo/{ID_LO}
```

---

### Export

```
GET /api/data-lo/export/excel
GET /api/data-lo/export/pdf
```

---

## Data AO

**Auth:** Required (`member_approved`)  
**Base URL:** `/api/data-ao`

### List Data AO

```
GET /api/data-ao
```

**Response 200:**
```json
{
  "data": [
    {
      "ID_AO": "AO000000001",
      "NO_AGT": "016005001",
      "NAMA": "Nama AO",
      "STAT": "aktif",
      "TGL_STAT": "2024-01-01"
    }
  ]
}
```

---

### Create Data AO

```
POST /api/data-ao
```

**Request Body:**
```json
{
  "NO_AGT": "016005001",
  "NAMA": "Nama AO",
  "STAT": "aktif",
  "TGL_STAT": "2024-01-01"
}
```

---

### Get / Update / Delete

```
GET    /api/data-ao/{ID_AO}
PUT    /api/data-ao/{ID_AO}
DELETE /api/data-ao/{ID_AO}
```

---

### Export

```
GET /api/data-ao/export/excel
GET /api/data-ao/export/pdf
```

---

## Ketua KS

**Auth:** Required (`member_approved`)  
**Base URL:** `/api/ketua-ks`

### List Ketua KS

```
GET /api/ketua-ks
```

**Response 200:**
```json
{
  "data": [
    {
      "ID_KET": "KT000000001",
      "NO_AGT": "016005001",
      "NAMA": "Nama Ketua",
      "STAT": "aktif",
      "TGL_STAT": "2024-01-01",
      "NO_SK": 123
    }
  ]
}
```

---

### Create Ketua KS

```
POST /api/ketua-ks
```

**Request Body:**
```json
{
  "NO_AGT": "016005001",
  "NAMA": "Nama Ketua",
  "STAT": "aktif",
  "TGL_STAT": "2024-01-01",
  "NO_SK": 123
}
```

---

### Get / Update / Delete

```
GET    /api/ketua-ks/{ID_KET}
PUT    /api/ketua-ks/{ID_KET}
DELETE /api/ketua-ks/{ID_KET}
```

---

### Export

```
GET /api/ketua-ks/export/excel
GET /api/ketua-ks/export/pdf
```

---

## Sekretaris KS

**Auth:** Required (`member_approved`)  
**Base URL:** `/api/sekretaris-ks`

### List Sekretaris KS

```
GET /api/sekretaris-ks
```

**Response 200:**
```json
{
  "data": [
    {
      "ID_SEKRE": "SK000000001",
      "NO_AGT": "016005001",
      "NAMA": "Nama Sekretaris",
      "STAT": "aktif",
      "TGL_STAT": "2024-01-01",
      "NO_SK": 456
    }
  ]
}
```

---

### Create / Get / Update / Delete

```
POST   /api/sekretaris-ks
GET    /api/sekretaris-ks/{ID_SEKRE}
PUT    /api/sekretaris-ks/{ID_SEKRE}
DELETE /api/sekretaris-ks/{ID_SEKRE}
```

---

### Export

```
GET /api/sekretaris-ks/export/excel
GET /api/sekretaris-ks/export/pdf
```

---

## Data Pengelola

**Auth:** Required (`member_approved`)  
**Base URL:** `/api/data-pengelola`

### List Data Pengelola

```
GET /api/data-pengelola
```

**Response 200:**
```json
{
  "data": [
    {
      "ID_PENG": "PG000000001",
      "NO_AGT": "016005001",
      "NO_SK": 789
    }
  ]
}
```

---

### Create / Get / Update / Delete

```
POST   /api/data-pengelola
GET    /api/data-pengelola/{ID_PENG}
PUT    /api/data-pengelola/{ID_PENG}
DELETE /api/data-pengelola/{ID_PENG}
```

---

### Export

```
GET /api/data-pengelola/export/excel
GET /api/data-pengelola/export/pdf
```

---

## Data Penghasilan

**Auth:** Required (`member_approved`)  
**Base URL:** `/api/data-penghasilan`

### List Data Penghasilan

```
GET /api/data-penghasilan
```

**Response 200:**
```json
{
  "data": [
    {
      "NO_AGT": "016005001",
      "PENGHASILAN": "5000000",
      "PENGELUARAN": "3000000",
      "TGL_DATA": "2024-01-01"
    }
  ]
}
```

---

### Create Data Penghasilan

```
POST /api/data-penghasilan
```

**Request Body:**
```json
{
  "NO_AGT": "016005001",
  "PENGHASILAN": "5000000",
  "PENGELUARAN": "3000000",
  "TGL_DATA": "2024-01-01"
}
```

**Validasi:**
- `NO_AGT`: required, string, max 15, must exist di tabel `anggota`, harus milik kelompok user
- `PENGHASILAN`: nullable, string, max 50
- `PENGELUARAN`: nullable, string, max 50
- `TGL_DATA`: nullable, string, max 50

---

### Get / Update / Delete

```
GET    /api/data-penghasilan/{NO_AGT}
PUT    /api/data-penghasilan/{NO_AGT}
DELETE /api/data-penghasilan/{NO_AGT}
```

---

### Export

```
GET /api/data-penghasilan/export/excel
GET /api/data-penghasilan/export/pdf
```

---

## Data Transaksi (Data TRS)

**Auth:** Required (`member_approved`)  
**Base URL:** `/api/data-trs`

### List Data TRS

```
GET /api/data-trs
```

**Query Parameters:**

| Parameter | Keterangan |
|---|---|
| `NO_AGT` | Filter nomor anggota |
| `per_page` | Jumlah per halaman |
| `page` | Halaman |

**Response 200:**
```json
{
  "data": [
    {
      "NO_AGT": "016005001",
      "STR_SP": "500000",
      "STR_SW": "200000",
      "STR_SKA": null,
      "STR_SRI": null,
      "STR_SDK": null,
      "STR_PJM": null,
      "STR_BNG": null,
      "PJM_BARU": null,
      "STR_SHR": null,
      "STR_SBJ": null,
      "STR_SJP": null,
      "STR_SPD": null,
      "STR_SRY": null,
      "STR_SMD": null,
      "TGL_LAP": "2024-01-01"
    }
  ]
}
```

---

### Create Data TRS

```
POST /api/data-trs
```

**Request Body:**
```json
{
  "NO_AGT": "016005001",
  "STR_SP": "500000",
  "STR_SW": "200000",
  "STR_SKA": null,
  "STR_SRI": null,
  "STR_SDK": null,
  "STR_PJM": null,
  "STR_BNG": null,
  "PJM_BARU": null,
  "STR_SHR": null,
  "STR_SBJ": null,
  "STR_SJP": null,
  "STR_SPD": null,
  "STR_SRY": null,
  "STR_SMD": null,
  "TGL_LAP": "2024-01-01"
}
```

**Validasi:**
- `NO_AGT`: required, string, max 15, harus milik kelompok user
- Semua field `STR_*`, `PJM_BARU`, `TGL_LAP`: nullable, string, max 50

---

### Get / Update / Delete

```
GET    /api/data-trs/{id}
PUT    /api/data-trs/{id}
DELETE /api/data-trs/{id}
```

---

### Export

```
GET /api/data-trs/export/excel
GET /api/data-trs/export/pdf
```

---

## Data Jumlah Keluarga

**Auth:** Required (`member_approved`)  
**Base URL:** `/api/data-jlh-keluarga`

### List Data Jumlah Keluarga

```
GET /api/data-jlh-keluarga
```

**Response 200:**
```json
{
  "data": [
    {
      "NO_AGT": "016005001",
      "JLH_AGT_KEL": "5",
      "TGL": "2024-01-01"
    }
  ]
}
```

---

### Create / Get / Update / Delete

```
POST   /api/data-jlh-keluarga
GET    /api/data-jlh-keluarga/{NO_AGT}
PUT    /api/data-jlh-keluarga/{NO_AGT}
DELETE /api/data-jlh-keluarga/{NO_AGT}
```

---

### Export

```
GET /api/data-jlh-keluarga/export/excel
GET /api/data-jlh-keluarga/export/pdf
```

---

## Data Kunjungan

**Auth:** Required (`member_approved`)  
**Base URL:** `/api/data-kunjungan`

### List Data Kunjungan

```
GET /api/data-kunjungan
```

**Response 200:**
```json
{
  "data": [
    {
      "NO_URT": 1,
      "NO_AGT": "016005001",
      "ID_LO": "LO000000001",
      "ID_KEL_SAH": "KS000000001",
      "nama_kelompok": "Kelompok Sahabat A",
      "nama_anggota": "Budi",
      "TGL_KUN": "2024-01-15",
      "KEGIATAN": "Kunjungan rutin",
      "ID_PIC": "PIC001",
      "JLH_PESERTA": 20,
      "foto_path": null,
      "foto_url": null,
      "foto_api_url": null,
      "latitude": null,
      "longitude": null
    }
  ]
}
```

### Laporan admin (ringkasan per kelompok & per anggota)

**Auth:** Required (`auth:sanctum`) + **`admin`** (role `admin` atau `super_admin`).

```
GET /api/admin/data-kunjungan/report/group-summary
```

**Query (opsional):** `search` (substring nama kelompok atau nama anggota).

**Response 200:** `{ "data": [ { "id_kel_sah": "…", "nama_kelompok": "…", "frekuensi": 0 } ] }`  
Hanya baris dengan `ID_KEL_SAH` terisi; diurutkan menaik menurut nama kelompok.

```
GET /api/admin/data-kunjungan/report/by-kelompok/{id_kel_sah}/anggota-summary
```

Query opsional sama seperti `group-summary` (`search`).

**Response 200:** `{ "data": [ { "no_agt": "…", "nama_anggota": "…", "frekuensi": 0, "tanggal_terakhir": "…" } ] }`

**Catatan mutasi:** `POST` / `PUT` / `DELETE` pada `/api/data-kunjungan` hanya untuk pengguna anggota (`role` `user` dengan aturan kepemilikan yang berlaku). `admin` / `super_admin` mendapat **403** pada mutasi tersebut.

---

## Target & Realisasi (monitoring)

Target per field disimpan di tabel **`target`** (baris dengan `TGL_TGT` = konfigurasi `TARGET_MONITORING_TGL_TGT`, default **`2099-01-01`**). Realisasi untuk setoran (`STR_SP` … `STR_SKA`) diisi otomatis dari **`REALISASI`** (sinkron trigger `DATA_TRS` di Firebird). Realisasi **`JLH_AGT_BR`** = jumlah anggota aktif per `ID_KS`.

Field fase 1: `JLH_AGT_BR`, `STR_SP`, `STR_SW`, `STR_SHR`, `STR_SMD`, `STR_SPD`, `STR_SBJ`, `STR_SRY`, `STR_SKA`. Untuk **`JLH_AGT_BR`**, realisasi = jumlah anggota per `ID_KS`; banding target/realisasi/persentase di UI utama hanya dipakai untuk **delapan setoran** (bukan anggota baru).

### Ringkasan (admin)

**Auth:** `auth:sanctum` + middleware **`admin`** (`admin` atau `super_admin`).

```
GET /api/admin/target-realisasi/summary
```

**Response 200:** Satu elemen `data[]` per **periode bulanan** `(id_kel, tahun, bulan)` — bukan satu baris per kelompok saja. Periode diambil dari **union** baris `target` + `realisasi` (baris sentinel `2099-01-01` diabaikan). Kelompok dengan dua bulan berbeda di DB muncul **dua baris** (mis. Jan 2026 dan Feb 2026).

Setiap baris berisi:

| Field | Keterangan |
|-------|------------|
| `tgl_tgt` | **Akhir bulan** periode baris (`Y-m-d`), dipakai untuk deep link GET detail |
| `period_year`, `period_month` | Kalender periode (1–12) |
| `tgl_baris_target` | Opsional: tanggal fisik baris `target` jika beda dari akhir bulan |
| `fields` | Array 9 baris field fase 1 |
| `nominal_target`, `total_realisasi`, `persentase_pencapaian`, `status_target`, `has_target` | Ringkasan **`STR_SP`** untuk periode itu |
| `jumlah_anggota` | Metadata |

Pairing target & realisasi per baris memakai **tahun + bulan** (`EXTRACT` / `whereYear` + `whereMonth`), bukan equality ke tanggal operasional realisasi (mis. `TGL_TGT` realisasi bisa `2026-02-20`, target `2026-02-28` — tetap satu periode Feb 2026).

`status_target`: `on_target` | `below_target` | `no_target`. `persentase_pencapaian` boleh `null` jika tanpa target SP.

### Detail per field (admin)

```
GET /api/admin/target-realisasi/kelompok/{id_kel}?tgl_tgt=YYYY-MM-DD
PUT /api/admin/target-realisasi/kelompok/{id_kel}
```

**Query `tgl_tgt` (GET, opsional):** **akhir bulan** periode yang dimuat (`Y-m-d`, mis. `2026-02-28`). Wajib akhir bulan — selain itu **422**. Jika diabaikan, dipakai **akhir bulan kalender berjalan** (bukan sentinel legacy `2099-01-01`).

**GET Response 200:**

```json
{
  "data": {
    "id_kel": "KS000000001",
    "nama_kelompok": "…",
    "jumlah_anggota": 12,
    "tgl_tgt": "2026-02-28",
    "period_year": 2026,
    "period_month": 2,
    "fields": [
      {
        "key": "STR_SP",
        "label": "Simpanan Pokok",
        "kind": "currency",
        "target": "1000000",
        "realisasi": "800000",
        "persentase": 80,
        "status": "below_target"
      }
    ]
  }
}
```

`kind`: `count` | `currency`. `status` per field: `on_target` | `below_target` | `no_target`.

**PUT body (JSON) — disarankan:**

```json
{
  "tgl_tgt": "2026-03-31",
  "targets": {
    "JLH_AGT_BR": 15,
    "STR_SP": 1000000,
    "STR_SW": 500000
  }
}
```

**`tgl_tgt`** (opsional): **akhir bulan** periode baris `target` di database (mis. Maret 2026 → `2026-03-31`). Jika tidak dikirim, dipakai akhir bulan berjalan. Harus akhir bulan — selain itu **422**. Hanya key dalam `targets` yang dikirim yang di-update.

```json
{ "nominal_target": 1000000 }
```

(setara `targets.STR_SP`).

**Response 200:** sama bentuk dengan **GET** detail.  
**404:** kelompok tidak ada di master `kel_sah`.

### Akun anggota (user)

**Auth:** `auth:sanctum` + **`member_approved`**.

```
GET /api/target-realisasi/me
```

Mengembalikan **ringkasan** (bukan `fields[]`) untuk satu kelompok milik anggota (`MemberScope`). **404** jika akun tidak memiliki kelompok terpetakan.

---

### Create Data Kunjungan

```
POST /api/data-kunjungan
```

**Request Body:**
```json
{
  "ID_LO": "LO000000001",
  "NO_AGT": "016005001",
  "ID_KEL_SAH": "KS000000001",
  "TGL_KUN": "2024-01-15",
  "KEGIATAN": "Kunjungan rutin",
  "ID_PIC": "PIC001",
  "JLH_PESERTA": 20
}
```

---

### Get / Update / Delete

```
GET    /api/data-kunjungan/{NO_URT}
PUT    /api/data-kunjungan/{NO_URT}
DELETE /api/data-kunjungan/{NO_URT}
```

---

### Export

```
GET /api/data-kunjungan/export/excel
GET /api/data-kunjungan/export/pdf
```

---

## Activity Log

**Auth:** Required (`member_approved`)

### List Activity Log

```
GET /api/activity-logs
```

> Admin/Super Admin: semua log. User biasa: hanya log milik sendiri.

**Query Parameters:**

| Parameter | Keterangan |
|---|---|
| `resource_type` | Filter jenis resource (mis. `anggota`, `data_trs`) |
| `action_type` | Filter jenis aksi (`create`, `update`, `delete`) |
| `status` | Filter status log |
| `date_from` | Filter tanggal mulai (`YYYY-MM-DD`) |
| `date_to` | Filter tanggal akhir (`YYYY-MM-DD`) |
| `search` | Pencarian deskripsi atau nama user |
| `per_page` | Jumlah per halaman |

**Response 200:**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "user_name": "Nama User",
      "action_type": "create",
      "resource_type": "anggota",
      "description": "Menambahkan anggota: 016005001",
      "old_data": null,
      "new_data": { ... },
      "status": "success",
      "created_at": "2024-01-15T10:00:00Z"
    }
  ]
}
```

---

### Get Activity Log

```
GET /api/activity-logs/{id}
```

> User hanya bisa lihat log milik sendiri.

**Response 403** jika bukan log milik user.

---

## Member Kelompok

**Auth:** Required (`member_approved`)

### Get Data Kelompok Member

```
GET /api/me/kelompok
```

Mengambil snapshot data kelompok yang terkait dengan akun user yang sedang login.

**Query Parameters:**

| Parameter | Keterangan |
|---|---|
| `per_page` | Jumlah anggota per halaman (default: 15) |
| `page` | Halaman |

**Response 200:**
```json
{
  "data": {
    "kelompok": {
      "ID_KEL": "KS000000001",
      "NAMA_KEL": "Kelompok Mawar",
      "ALAMAT": "Jl. Contoh No. 1",
      "STAT": "aktif"
    },
    "ketua": { ... },
    "sekretaris": { ... },
    "lo": { ... },
    "ao": { ... },
    "anggota": {
      "data": [ ... ],
      "meta": { ... }
    }
  }
}
```

**Response 404:**
```json
{
  "message": "Kelompok tidak ditemukan. Pastikan akun memiliki kelompok sahabat (disetujui admin) atau nomor anggota yang terhubung ke data kelompok."
}
```

---

## Import dari Firebird

**Auth:** Required (`member_approved`)

### Import Anggota

#### List Anggota di Firebird

```
GET /api/import-anggota-firebird
```

**Query Parameters:**

| Parameter | Keterangan |
|---|---|
| `search` | Pencarian nama/nomor anggota |
| `page` | Halaman (jika pakai pagination) |
| `per_page` | Jumlah per halaman |

**Response 200:**
```json
{
  "data": [
    { "NO_AGT": "016005001", "NAMA": "Nama Anggota" }
  ],
  "meta": {
    "total": 500,
    "showing": 50,
    "has_more": true
  }
}
```

---

#### Preview Anggota dari Firebird

```
GET /api/import-anggota-firebird/{noAgt}
```

Cek apakah anggota sudah ada di sistem atau hanya ada di Firebird legacy.

**Response 200:**
```json
{
  "message": "Preview data retrieved successfully.",
  "data": {
    "exists_in_mysql": false,
    "mysql_data": null,
    "firebird_data": {
      "NO_AGT": "016005001",
      "NAMA": "Nama Anggota",
      ...
    }
  }
}
```

---

#### Import Anggota dari Firebird

```
POST /api/import-anggota-firebird
```

**Request Body:**
```json
{
  "no_agt": "016005001",
  "confirm_import": true
}
```

**Response 201:**
```json
{
  "message": "Anggota imported successfully.",
  "data": { ... }
}
```

---

### Import Data TRS

#### List DATA_TRS di Firebird

```
GET /api/import-data-trs-firebird
```

**Query Parameters:** sama seperti Import Anggota

---

#### Preview DATA_TRS dari Firebird

```
GET /api/import-data-trs-firebird/{noAgt}
```

**Response 200:**
```json
{
  "message": "Data pratinjau berhasil diambil.",
  "data": {
    "firebird_rows": [
      {
        "NO_AGT": "016005001",
        "STR_SP": "500000",
        ...
      }
    ]
  }
}
```

---

#### Import DATA_TRS dari Firebird

```
POST /api/import-data-trs-firebird
```

**Request Body:**
```json
{
  "no_agt": "016005001",
  "confirm_import": true
}
```

> Anggota harus sudah di-import terlebih dahulu sebelum import DATA_TRS.

**Response 201:**
```json
{
  "message": "3 baris DATA_TRS berhasil diimpor.",
  "data": [ ... ]
}
```

---

## User Approval (Admin)

**Auth:** Required (`admin` atau `super_admin`)

### Statistik Pendaftaran

```
GET /api/users/approval-stats
```

**Response 200:**
```json
{
  "pending": 5,
  "rejected": 2,
  "approved": 48
}
```

---

### Antrian Pendaftaran

```
GET /api/users/registration-queue
```

Alias: `GET /api/users/registrations`

**Query Parameters:**

| Parameter | Nilai | Keterangan |
|---|---|---|
| `status` | `all`, `pending`, `approved`, `rejected` | Filter status (default: `pending`) |
| `search` | string | Cari nama atau email |
| `per_page` | integer | Default: 20, max: 100 |
| `page` | integer | Halaman |

**Response 200:**
```json
{
  "data": [
    {
      "id": 10,
      "name": "Nama User",
      "email": "user@example.com",
      "role": "user",
      "is_active": false,
      "registration_status": "pending",
      "registration_reviewed_at": null,
      "registration_reviewed_by": null,
      "created_at": "2024-01-15T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 5
  }
}
```

---

### Daftar Pending

```
GET /api/users/pending
```

Alias: `GET /api/users/member-pending`

---

### Daftar Rejected

```
GET /api/users/rejected
```

---

### Daftar Approved

```
GET /api/users/member-approved
```

---

### Approve Pendaftaran

```
POST /api/users/{id}/approve
```

**Request Body:**
```json
{
  "id_kel": "KS000000001"
}
```

> `id_kel`: ID Kelompok Sahabat yang dikaitkan ke akun user. Bisa null jika belum ditentukan.

**Response 200:**
```json
{
  "message": "Akun berhasil diaktifkan. Email aktivasi telah dikirim.",
  "data": { ... }
}
```

**Response 422** jika pendaftaran sudah diproses sebelumnya.

---

### Reject Pendaftaran

```
POST /api/users/{id}/reject
```

Tidak perlu request body.

**Response 200:**
```json
{
  "message": "Pendaftaran ditolak. Akun tidak dapat login.",
  "data": { ... }
}
```

---

### Reset Perangkat (Admin)

```
POST /api/users/{id}/reset-device
```

Menghapus `device_id` akun anggota agar bisa login dari perangkat baru.

> Hanya untuk akun dengan status `approved`.

**Response 200:**
```json
{
  "message": "Perangkat akun berhasil direset. Pengguna dapat login ulang dari perangkat baru.",
  "data": { ... }
}
```

---

## Super Admin

**Auth:** Required (`super_admin`)  
**Base URL:** `/api/super-admin`

### Dashboard Statistik

```
GET /api/super-admin/dashboard/stats
```

**Response 200:**
```json
{
  "data": {
    "total_users": 100,
    "total_admins": 5,
    "total_active": 85,
    "total_inactive": 15
  }
}
```

---

### Recent Activities

```
GET /api/super-admin/dashboard/recent-activities
```

**Response 200:**
```json
{
  "data": [
    {
      "id": 1,
      "user_name": "Nama Admin",
      "action_type": "create",
      "resource_type": "anggota",
      "description": "...",
      "created_at": "..."
    }
  ]
}
```

---

### Chart Data

```
GET /api/super-admin/dashboard/chart
```

**Query Parameters:**

| Parameter | Nilai | Keterangan |
|---|---|---|
| `period` | `week`, `month` | Default: `week` |

**Response 200:**
```json
{
  "data": {
    "labels": ["2024-01-01", "2024-01-02", "..."],
    "registrations": [2, 5, 1, 0, 3, 2, 4],
    "activities": [10, 25, 8, 15, 30, 12, 20]
  }
}
```

---

### List Semua User

```
GET /api/super-admin/users
```

**Query Parameters:**

| Parameter | Keterangan |
|---|---|
| `role` | Filter role (`admin`, `user`, `super_admin`) |
| `search` | Cari nama atau email |
| `per_page` | Default: 10000, max: 50000 |

**Response 200:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Nama",
      "email": "email@example.com",
      "role": "admin",
      "is_active": true,
      "no_agt": null,
      "jabatan": "ketua",
      "created_at": "..."
    }
  ],
  "meta": { ... }
}
```

---

### List Admins

```
GET /api/super-admin/admins
```

Sama seperti List Semua User.

---

### Create User

```
POST /api/super-admin/users
```

**Request Body:**
```json
{
  "name": "Nama Admin",
  "email": "admin@example.com",
  "password": "Password@123",
  "role": "admin",
  "no_agt": "016005001"
}
```

**Validasi:**
- `name`: required, string, max 255
- `email`: required, email, unique
- `password`: required, min 8, mixed case, angka, simbol
- `role`: required, `admin` atau `user`
- `no_agt`: opsional, harus exist di `anggota`, unique di `users`

**Response 201:**
```json
{
  "message": "User berhasil dibuat.",
  "data": { ... }
}
```

---

### Update User

```
PATCH /api/super-admin/users/{id}
```

**Request Body:** (semua opsional)
```json
{
  "role": "admin",
  "is_active": true,
  "no_agt": "016005001",
  "id_kel": "KS000000001"
}
```

**Response 200:**
```json
{
  "message": "User berhasil diubah.",
  "data": { ... }
}
```

---

### Delete User

```
DELETE /api/super-admin/users/{id}
```

> Tidak dapat menghapus akun super_admin atau akun yang sedang login.

**Response 200:**
```json
{
  "message": "User berhasil dihapus."
}
```

---

### Reset Perangkat (Super Admin)

```
POST /api/super-admin/users/{id}/reset-device
```

**Response 200:**
```json
{
  "message": "Perangkat user berhasil direset.",
  "data": { ... }
}
```

---

### System Activity Log

```
GET /api/super-admin/system-activity
```

**Query Parameters:**

| Parameter | Keterangan |
|---|---|
| `search` | Cari deskripsi, nama user, atau resource type |
| `action_type` | Filter jenis aksi |
| `date_from` | Filter tanggal mulai (`YYYY-MM-DD`) |
| `date_to` | Filter tanggal akhir (`YYYY-MM-DD`) |
| `per_page` | Default: 10000, max: 50000 |

**Response 200:**
```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10000,
    "total": 250
  }
}
```

---

## HTTP Status Codes

| Code | Keterangan |
|---|---|
| `200` | OK — Request berhasil |
| `201` | Created — Data berhasil dibuat |
| `204` | No Content — Delete berhasil |
| `401` | Unauthorized — Token tidak valid atau tidak ada |
| `403` | Forbidden — Tidak punya izin |
| `404` | Not Found — Data tidak ditemukan |
| `422` | Unprocessable Entity — Validasi gagal |
| `429` | Too Many Requests — Rate limit tercapai |
| `500` | Internal Server Error — Error server |

---

*Dokumentasi ini di-generate berdasarkan source code `backend-app` per 6 Mei 2026.*
