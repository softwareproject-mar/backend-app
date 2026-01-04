# Dokumentasi Final Backend (Untuk Frontend)

## Base Info
- Base URL: `http://127.0.0.1:8000/api`
- Headers:
  - `Content-Type: application/json`
  - `Accept: application/json`
  - `Authorization: Bearer {token}` untuk endpoint yang dilindungi `auth:sanctum`

## Public Endpoints (tanpa token)
- `POST /auth/request-otp` — minta OTP verifikasi email  
  Body: `email` (required, email, unik di users)
- `POST /auth/register` — registrasi dengan OTP  
  Body: `email` (required, email), `otp` (required, string, size 6), `name` (required, string max 255), `password` (required, min 8, confirmed), `password_confirmation`, `role` (optional, in:admin,user)
- `POST /auth/login` — login email + password  
  Body: `email` (required, email), `password` (required, string)
- `POST /auth/forgot-password` — minta OTP reset password  
  Body: `email` (required, email, exists:users)
- `POST /auth/verify-reset-otp` — verifikasi OTP reset  
  Body: `email` (required, email, exists:users), `otp` (required, string, size 6)
- `POST /auth/reset-password` — setel ulang password  
  Body: `email` (required, email, exists:users), `reset_token` (required, string), `password` (required, min 8, confirmed), `password_confirmation`

## Protected Endpoints (butuh Bearer token)

### Auth
- `POST /auth/logout`
- `GET /auth/me`

### Data Kunjungan (CRUD)
- Endpoints: `GET /data-kunjungan`, `POST /data-kunjungan`, `GET /data-kunjungan/{NO_URT}`, `PUT /data-kunjungan/{NO_URT}`, `DELETE /data-kunjungan/{NO_URT}`
- Primary key: `NO_URT` (auto increment int)
- Filters (list): `ID_LO`, `NO_AGT`, `ID_KEL_SAH`, `TGL_KUN`, `KEGIATAN`, `ID_PIC`; `per_page` untuk pagination
- Fields (create/update):
  - `ID_LO` string max 12 (optional)
  - `NO_AGT` string max 15 (optional)
  - `ID_KEL_SAH` string max 12 (optional)
  - `TGL_KUN` string max 50 (optional)
  - `KEGIATAN` string max 50 (optional)
  - `ID_PIC` string max 50 (optional)
  - `JLH_PESERTA` integer (optional)

### Anggota (CRUD)
- Endpoints: `GET /anggota`, `POST /anggota`, `GET /anggota/{NO_AGT}`, `PUT /anggota/{NO_AGT}`, `DELETE /anggota/{NO_AGT}`
- Primary key: `NO_AGT` (string)
- Filters (list): `NO_AGT`, `ID_KS`, `ID_LO`; `per_page` untuk pagination
- Fields (create):
  - `NO_AGT` string max 15 (required)
  - `NAMA` string max 255 (optional)
  - `ID_KS` string max 12 (optional)
  - `ID_LO` string max 12 (optional)
  - `ID_AO` string max 12 (optional)
  - `ID_KS_ASL` string max 12 (optional)
  - `TGL_MTS` string max 50 (optional)
  - `TGL_AKTIF` string max 50 (optional)
  - `TGL_JA` string max 50 (optional)
- Fields (update): sama seperti create kecuali `NO_AGT` tidak diubah

### Kel Sah (CRUD)
- Endpoints: `GET /kel-sah`, `POST /kel-sah`, `GET /kel-sah/{ID_KEL}`, `PUT /kel-sah/{ID_KEL}`, `DELETE /kel-sah/{ID_KEL}`
- Primary key: `ID_KEL` (string)
- Filters (list): `ID_KEL`, `ID_LO`, `ID_AO`; `per_page` untuk pagination
- Fields (create):
  - `ID_KEL` string max 12 (required)
  - `NAMA_KEL` string max 255 (optional)
  - `ID_KETUA` string max 12 (optional)
  - `ID_SEK` string max 12 (optional)
  - `ID_LO` string max 12 (optional)
  - `ID_AO` string max 12 (optional)
  - `ALAMAT` string (optional)
  - `STAT` string max 50 (optional)
  - `TGL_STAT` string max 50 (optional)
  - `ID_PENGELOLA` string max 50 (optional)
- Fields (update): sama seperti create tanpa `ID_KEL`

### Data LO (CRUD)
- Endpoints: `GET /data-lo`, `POST /data-lo`, `GET /data-lo/{ID_LO}`, `PUT /data-lo/{ID_LO}`, `DELETE /data-lo/{ID_LO}`
- Primary key: `ID_LO` (string)
- Filters (list): `ID_LO`, `NO_AGT`; `per_page` untuk pagination
- Fields (create):
  - `ID_LO` string max 12 (required)
  - `NO_AGT` string max 15 (optional)
  - `ID_TP` string max 12 (optional)
  - `NAMA` string max 255 (optional)
  - `STAT` string max 50 (optional)
  - `TGL_STAT` string max 50 (optional)
- Fields (update): `NO_AGT`, `ID_TP`, `NAMA`, `STAT`, `TGL_STAT`

### Data AO (CRUD)
- Endpoints: `GET /data-ao`, `POST /data-ao`, `GET /data-ao/{ID_AO}`, `PUT /data-ao/{ID_AO}`, `DELETE /data-ao/{ID_AO}`
- Primary key: `ID_AO` (string)
- Filters (list): `ID_AO`, `NO_AGT`; `per_page` untuk pagination
- Fields (create):
  - `ID_AO` string max 12 (required)
  - `NO_AGT` string max 15 (optional)
  - `NAMA` string max 255 (optional)
  - `STAT` string max 50 (optional)
  - `TGL_STAT` string max 50 (optional)
- Fields (update): `NO_AGT`, `NAMA`, `STAT`, `TGL_STAT`

### Data Jumlah Keluarga (CRUD)
- Endpoints: `GET /data-jlh-keluarga`, `POST /data-jlh-keluarga`, `GET /data-jlh-keluarga/{NO_AGT}`, `PUT /data-jlh-keluarga/{NO_AGT}`, `DELETE /data-jlh-keluarga/{NO_AGT}`
- Primary key: `NO_AGT` (string)
- Filters (list): `NO_AGT`; `per_page` untuk pagination
- Fields (create):
  - `NO_AGT` string max 15 (required)
  - `JLH_AGT_KEL` integer (optional)
  - `TGL` string max 50 (optional)
- Fields (update): `JLH_AGT_KEL`, `TGL`

### Data Pengelola (CRUD)
- Endpoints: `GET /data-pengelola`, `POST /data-pengelola`, `GET /data-pengelola/{ID_PENG}`, `PUT /data-pengelola/{ID_PENG}`, `DELETE /data-pengelola/{ID_PENG}`
- Primary key: `ID_PENG` (string)
- Filters (list): `ID_PENG`, `NO_AGT`; `per_page` untuk pagination
- Fields (create):
  - `ID_PENG` string max 12 (required)
  - `NO_AGT` string max 15 (optional)
  - `NO_SK` integer (optional)
- Fields (update): `NO_AGT`, `NO_SK`

### Ketua KS (CRUD)
- Endpoints: `GET /ketua-ks`, `POST /ketua-ks`, `GET /ketua-ks/{ID_KET}`, `PUT /ketua-ks/{ID_KET}`, `DELETE /ketua-ks/{ID_KET}`
- Primary key: `ID_KET` (string)
- Filters (list): `ID_KET`, `NO_AGT`, `NAMA`, `STAT`; `per_page` untuk pagination
- Fields (create):
  - `ID_KET` string max 12 (required)
  - `NO_AGT` string max 15 (required)
  - `NAMA` string max 50 (optional)
  - `STAT` string max 50 (optional)
  - `TGL_STAT` string max 50 (optional)
  - `NO_SK` integer (optional)
- Fields (update): `NO_AGT` (optional), `NAMA`, `STAT`, `TGL_STAT`, `NO_SK`

### Sekretaris KS (CRUD)
- Endpoints: `GET /sekretaris-ks`, `POST /sekretaris-ks`, `GET /sekretaris-ks/{ID_SEKRE}`, `PUT /sekretaris-ks/{ID_SEKRE}`, `DELETE /sekretaris-ks/{ID_SEKRE}`
- Primary key: `ID_SEKRE` (string)
- Filters (list): `ID_SEKRE`, `NO_AGT`, `NAMA`, `STAT`; `per_page` untuk pagination
- Fields (create):
  - `ID_SEKRE` string max 12 (required)
  - `NO_AGT` string max 15 (required)
  - `NAMA` string max 50 (optional)
  - `STAT` string max 50 (optional)
  - `TGL_STAT` string max 50 (optional)
  - `NO_SK` integer (optional)
- Fields (update): `NO_AGT` (optional), `NAMA`, `STAT`, `TGL_STAT`, `NO_SK`

### Data Penghasilan (CRUD)
- Endpoints: `GET /data-penghasilan`, `POST /data-penghasilan`, `GET /data-penghasilan/{NO_AGT}`, `PUT /data-penghasilan/{NO_AGT}`, `DELETE /data-penghasilan/{NO_AGT}`
- Primary key: `NO_AGT` (string)
- Filters (list): `NO_AGT`; `per_page` untuk pagination
- Fields (create):
  - `NO_AGT` string max 15 (required)
  - `PENGHASILAN` string max 50 (optional)
  - `PENGELUARAN` string max 50 (optional)
  - `TGL_DATA` string max 50 (optional)
- Fields (update): `PENGHASILAN`, `PENGELUARAN`, `TGL_DATA`

### Data TRS (READ ONLY)
- Endpoints: `GET /data-trs`, `GET /data-trs/{NO_AGT}`
- Primary key: `NO_AGT` (string)
- Filters (list): `NO_AGT`; `per_page` untuk pagination
- Fields (response):
  - `NO_AGT`
  - `STR_SP`, `STR_SW`, `STR_SKA`, `STR_SRI`, `STR_SDK`, `STR_PJM`, `STR_BNG`, `PJM_BARU`
  - `STR_SHR`, `STR_SBJ`, `STR_SJP`, `STR_SPD`, `STR_SRY`, `STR_SMD`
  - `TGL_LAP`

## Status Nonaktif
- Endpoint Target, Realisasi, Dashboard: dinonaktifkan (routes dihapus).
