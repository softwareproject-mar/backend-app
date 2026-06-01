# Field database — `data_trs`, `target`, `realisasi`

Dokumen ini **hanya** struktur kolom di database (konteks DB). Tidak membahas API, form UI, atau rumus monitoring.

**Sumber acuan:** migrasi Laravel di `database/migrations/`.  
**Catatan:** Aplikasi juga membaca **Firebird** (`DATA_TRS`, dll.) untuk beberapa fitur; nama kolom TRS di Firebird mengikuti pola yang sama dengan `data_trs` (lihat `FirebirdService`).

---

## 1. Tabel `data_trs` (MySQL)

### Primary key

| Versi skema | Primary key |
|-------------|-------------|
| Awal (`2025_12_13_145903`) | `NO_AGT` saja (satu baris per anggota) |
| Setelah `2026_04_08_100000` | **`ID`** (bigint auto-increment); banyak baris per `NO_AGT` |

### Kolom (skema terbaru setelah migrasi multi-row)

| Field | Tipe (MySQL) | Nullable | Keterangan |
|-------|----------------|----------|------------|
| `ID` | bigint unsigned, auto increment | tidak | PK surrogate |
| `NO_AGT` | string(15) | tidak | FK → `anggota.NO_AGT` |
| `STR_SP` | string(50) | ya | |
| `STR_SW` | string(50) | ya | |
| `STR_SKA` | string(50) | ya | |
| `STR_SRI` | string(50) | ya | |
| `STR_SDK` | string(50) | ya | |
| `STR_PJM` | string(50) | ya | |
| `STR_BNG` | string(50) | ya | |
| `PJM_BARU` | string(50) | ya | |
| `STR_SHR` | string(50) | ya | |
| `STR_SBJ` | string(50) | ya | |
| `STR_SJP` | string(50) | ya | |
| `STR_SPD` | string(50) | ya | |
| `STR_SRY` | string(50) | ya | |
| `STR_SMD` | string(50) | ya | |
| `TGL_LAP` | string(50) | ya | |
| `created_by` | unsigned bigint | ya | Ditambah migrasi `2026_04_03_000000` |

**Index / relasi:** index pada `NO_AGT`; foreign key `NO_AGT` → `anggota.NO_AGT`.

### Firebird (baca transaksi)

| Item | Nilai |
|------|--------|
| Nama tabel | `DATA_TRS` (env `FIREBIRD_DATA_TRS_TABLE`) |
| Kolom yang dibaca API (contoh) | `NO_AGT`, `STR_SP`, `STR_SW`, `STR_SKA`, `STR_SRI`, `STR_SDK`, `STR_PJM`, `STR_BNG`, `PJM_BARU`, `STR_SHR`, `STR_SBJ`, `STR_SJP`, `STR_SPD`, `STR_SRY`, `STR_SMD`, `TGL_LAP` |

Tidak ada kolom `ID` / `created_by` di query Firebird standar — itu khusus mirror MySQL.

---

## 2. Tabel `target` (MySQL)

### Primary key (komposit)

| Field | Tipe | Keterangan |
|-------|------|------------|
| `ID_KS` | string(12) | ID kelompok |
| `TGL_TGT` | string(50) | Periode / tanggal target (string legacy) |

### Semua kolom (migrasi `2025_12_13_145903_create_target_table`)

| Field | Tipe (MySQL) | Nullable |
|-------|----------------|----------|
| `ID_KS` | string(12) | tidak (bagian PK) |
| `TGL_TGT` | string(50) | tidak (bagian PK) |
| `JLH_AGT_BR` | integer | ya |
| `STR_SP` | double | ya |
| `SLD_SP` | string(50) | ya |
| `STR_SW` | double | ya |
| `SLD_SW` | string(50) | ya |
| `STR_SS` | string(50) | ya |
| `SLD_SS` | string(50) | ya |
| `STR_SHR` | integer | ya |
| `SLD_SHR` | string(50) | ya |
| `STR_SMD` | integer | ya |
| `SLD_SMD` | string(50) | ya |
| `STR_SPD` | double | ya |
| `SLD_SPD` | string(50) | ya |
| `STR_SBJ` | double | ya |
| `SLD_SBJ` | string(50) | ya |
| `STR_SJP` | string(50) | ya |
| `SLD_SJP` | string(50) | ya |
| `STR_SRY` | double | ya |
| `SLD_SRY` | string(50) | ya |
| `STR_SKA` | double | ya |
| `SLD_SKA` | string(50) | ya |
| `STR_SRI` | string(50) | ya |
| `SLD_SRI` | string(50) | ya |
| `STR_SSD` | string(50) | ya |
| `SLD_SSD` | string(50) | ya |
| `PCR_PJM` | integer | ya |
| `SLD_PJM` | string(50) | ya |
| `BNG_PJM` | integer | ya |
| `SLD_BNG` | string(50) | ya |
| `ASR_PKK` | integer | ya |
| `REK_SHR` | string(50) | ya |
| `REK_SPD` | string(50) | ya |
| `REK_SMD` | string(50) | ya |
| `REK_SRY` | string(50) | ya |
| `STF_SBJ` | string(50) | ya |
| `STF_SJP` | string(50) | ya |

### Catatan model `Target.php`

Model Eloquent memuat **fillable tambahan** (`JLH_REK`, `JLH_TAB`, `TBN_PK`, `PRC_*`, `JLH_TAR_*`, `SLD_T_*`, dll.) yang **tidak** muncul di migrasi `create_target_table` di atas. Itu biasanya mencerminkan skema **Firebird/legacy** penuh; di **MySQL murni dari migrasi**, daftar kolom efektif mengikuti tabel di atas kecuali ada migrasi alter lain (saat ini tidak ada `Schema::table('target', ...)` tambahan di repo).

---

## 3. Tabel `realisasi` (MySQL)

### Primary key (komposit)

| Field | Tipe | Keterangan |
|-------|------|------------|
| `ID_KS` | string(12) | ID kelompok |
| `TGL_TGT` | string(50) | Periode (sama konsep dengan `target`) |

### Semua kolom (migrasi `2025_12_13_145903_create_realisasi_table`)

| Field | Tipe (MySQL) | Nullable |
|-------|----------------|----------|
| `ID_KS` | string(12) | tidak (bagian PK) |
| `TGL_TGT` | string(50) | tidak (bagian PK) |
| `JLH_AGT_BR` | string(50) | ya |
| `STR_SP` | string(50) | ya |
| `STR_SW` | string(50) | ya |
| `STR_SS` | string(50) | ya |
| `STR_SHR` | string(50) | ya |
| `STR_SMD` | string(50) | ya |
| `STR_SPD` | string(50) | ya |
| `STR_SBJ` | string(50) | ya |
| `STR_SJP` | string(50) | ya |
| `STR_SRY` | string(50) | ya |
| `STR_SKA` | string(50) | ya |
| `STR_SRI` | string(50) | ya |
| `STR_SSD` | string(50) | ya |
| `PCR_PJM` | string(50) | ya |
| `BNG_PJM` | string(50) | ya |
| `ASR_PKK` | string(50) | ya |
| `REK_SHR` | string(50) | ya |
| `REK_SPD` | string(50) | ya |
| `REK_SMD` | string(50) | ya |
| `REK_SRY` | string(50) | ya |
| `STF_SBJ` | string(50) | ya |
| `STF_SJP` | string(50) | ya |

**Index:** `ID_KS` (index `fk_realisasi_1`).

**Perbedaan dengan `target`:** di `realisasi` hampir semua nilai bertipe **string(50)**; di `target` banyak pasangan `STR_*` (angka) + `SLD_*` (string), plus kolom `SLD_*` / `REK_*` yang tidak ada di `realisasi`.

---

## 4. Tabel terkait (bukan TRS/target/realisasi, tapi dipakai join)

| Tabel | PK | Field penting untuk hubungan |
|-------|-----|------------------------------|
| `anggota` | `NO_AGT` | `ID_KS` → kelompok anggota |
| `kel_sah` | `ID_KEL` | `NAMA_KEL`, dll. |

Modul monitoring memetakan **`ID_KS`** (target/realisasi) ≈ **`ID_KEL`** (kelompok) dan **`anggota.ID_KS`**.

---

## 5. Ringkasan jumlah kolom

| Tabel | PK | Perkiraan jumlah kolom data (tanpa PK) |
|-------|-----|----------------------------------------|
| `data_trs` | `ID` + `NO_AGT` | 15 kolom transaksi + `TGL_LAP` + `created_by` |
| `target` | `ID_KS`, `TGL_TGT` | 38 kolom (setelah PK) |
| `realisasi` | `ID_KS`, `TGL_TGT` | 22 kolom (setelah PK) |

---

## 6. Dokumen lain (konteks berbeda)

| Dokumen | Isi |
|---------|-----|
| `plans/modul-target-realisasi-data-isian.md` | Siapa mengisi apa + API monitoring |
| `plans/modul-target-realisasi-penjelasan-kebutuhan.md` | Kebutuhan bisnis modul |
| `plans/modul-target-realisasi-field-patokan.md` | Field acuan target = realisasi = TRS (nama sama) |