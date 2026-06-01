# Data TRS, Realisasi, dan Target — Ringkasan Isian

Dokumen ini menjelaskan **apa saja yang diisi**, **di mana disimpan**, dan **bagaimana ketiganya berhubungan** dalam modul **Target & Realisasi** (implementasi saat ini di `backend-app`).

---

## Gambaran singkat

| Konsep | Diisi manual? | Sumber utama | Dipakai modul monitoring untuk |
|--------|---------------|--------------|--------------------------------|
| **Data TRS** | Ya (per transaksi/anggota, modul Data TRS) | Firebird `DATA_TRS` (+ mirror MySQL `data_trs`) | Menghitung **realisasi** |
| **Data realisasi** (monitoring) | **Tidak** | Agregasi dari TRS per kelompok | Tampilan **Total realisasi** |
| **Data target** (monitoring) | Ya (**admin** saja) | Tabel `target` (baris khusus monitoring) | Tampilan **Nominal target** |

```text
[Admin input]  →  target (STR_SP, baris TGL_TGT monitoring)
                        ↓
                  dibandingkan dengan
                        ↓
[Otomatis]     ←  SUM kolom TRS (default STR_SP) semua anggota kelompok
                  dari DATA_TRS via join ANGGOTA.ID_KS
```

---

## 1. Data TRS (`DATA_TRS` / `data_trs`)

### Fungsi

Menyimpan **transaksi / simpanan per anggota** (bisa lebih dari satu baris per `NO_AGT` setelah migrasi multi-row).

### Lokasi penyimpanan

| Lapisan | Nama |
|---------|------|
| Firebird (sumber baca API) | `DATA_TRS` — env `FIREBIRD_DATA_TRS_TABLE` |
| MySQL (legacy mirror) | `data_trs` |

### Kunci & relasi

| Field | Keterangan |
|-------|------------|
| `ID` | Primary key (surrogate, integer) — skema terbaru |
| `NO_AGT` | Nomor anggota — menghubungkan ke `anggota.NO_AGT` |
| `anggota.ID_KS` | ID kelompok anggota — dipakai saat **menjumlahkan realisasi per kelompok** |

### Kolom isian (nilai transaksi)

Kolom berikut ada di model/API (`DataTrs` / `DataTrsResource`). Isian berupa **nominal/string per jenis simpanan** pada satu baris transaksi:

| Kolom | Keterangan (umum) |
|-------|-------------------|
| `NO_AGT` | **Wajib** — anggota pemilik transaksi |
| `STR_SP` | Simpanan pokok (sering dipakai untuk agregasi realisasi default) |
| `STR_SW` | Simpanan wajib |
| `STR_SKA` | Simpanan khusus (SKA) |
| `STR_SRI` | Simpanan (SRI) |
| `STR_SDK` | Simpanan (SDK) |
| `STR_PJM` | Pinjaman / PJM |
| `STR_BNG` | Bunga |
| `PJM_BARU` | PJM baru |
| `STR_SHR` | Simpanan harian |
| `STR_SBJ` | Simpanan berjangka |
| `STR_SJP` | Simpanan JPS |
| `STR_SPD` | Simpanan produk (SPD) |
| `STR_SRY` | Simpanan (SRY) |
| `STR_SMD` | Simpanan (SMD) |
| `TGL_LAP` | Tanggal laporan (string di skema legacy) |
| `created_by` | Audit (jika diisi dari aplikasi) |

### Siapa mengisi

- Diisi lewat **modul Data TRS** (anggota/admin sesuai kebijakan mutasi yang berlaku di API).
- **Bukan** diisi di halaman Target & Realisasi.

### Contoh isian (satu baris)

| NO_AGT | STR_SP | STR_SW | … |
|--------|--------|--------|---|
| `AGT001` | `200000` | `0` | … |

### Catatan untuk realisasi

- Modul monitoring **menjumlahkan** kolom yang dikonfigurasi di env **`TARGET_REALISASI_SUM_COLUMNS`** (default: **`STR_SP`** saja).
- Contoh: `TARGET_REALISASI_SUM_COLUMNS=STR_SP,STR_SW` → setiap baris TRS, nilai `STR_SP` + `STR_SW` dijumlahkan; lalu semua baris anggota dalam kelompok yang sama dijumlahkan lagi.

---

## 2. Data realisasi (modul Target & Realisasi)

### Fungsi

**Total capaian** kelompok: penjumlahan transaksi/simpanan seluruh anggota dalam **satu kelompok** (`ID_KEL` / `ID_KS`).

### Apakah ada form “isi realisasi”?

**Tidak.** Realisasi **tidak** punya form input terpisah di Web Admin / Mobile untuk modul monitoring ini.

### Cara dihitung (backend)

```
Total realisasi kelompok =
  SUM (kolom TRS terpilih dari setiap baris DATA_TRS)
  untuk semua NO_AGT yang ANGGOTA.ID_KS = ID kelompok
```

- Implementasi: `FirebirdService::sumRealisasiNominalForKelompok()`
- Query: `DATA_TRS` ⋈ `ANGGOTA` filter `TRIM(A.ID_KS) = ?`

### Bentuk data di API (bukan tabel input)

Response modul monitoring (`TargetRealisasiSummaryResource`):

| Field API | Tipe | Contoh | Keterangan |
|-----------|------|--------|------------|
| `total_realisasi` | string desimal | `"800000.00"` | Hasil agregasi TRS |
| `persentase_pencapaian` | number \| null | `80` | `(realisasi / target) × 100` |
| `status_target` | enum | `on_target` \| `below_target` \| `no_target` | Turunan perbandingan |

### Contoh bisnis

| Anggota | Kontribusi TRS (STR_SP) |
|---------|-------------------------|
| A | Rp200.000 |
| B | Rp300.000 |
| C | Rp300.000 |
| **Total realisasi kelompok** | **Rp800.000** |

### Tabel `realisasi` (legacy)

Di database masih ada tabel **`realisasi`** (PK `ID_KS` + `TGL_TGT`, banyak kolom snapshot). Itu dipakai pola **dashboard lama** (join `target` + `realisasi`), **bukan** sumber utama modul monitoring baru.

Modul **Target & Realisasi** saat ini mengambil realisasi dari **`DATA_TRS`**, bukan dari CRUD tabel `realisasi`.

---

## 3. Data target (modul Target & Realisasi)

### Fungsi

**Nominal target** yang ditetapkan **admin per kelompok** untuk pemantauan capaian.

### Lokasi penyimpanan

| Item | Nilai |
|------|--------|
| Tabel | `target` |
| Primary key komposit | `ID_KS` + `TGL_TGT` |
| `ID_KS` | = **ID kelompok** (`ID_KEL` di `kel_sah`) |
| `TGL_TGT` (monitoring) | Nilai sentinel — env **`TARGET_MONITORING_TGL_TGT`**, default **`__OBORMAS_MONITORING__`** |
| Kolom nilai target monitoring | **`STR_SP`** (disimpan sebagai angka/desimal) |

Baris lain di tabel `target` (dengan `TGL_TGT` berbeda) bisa dipakai impor/legacy; modul monitoring **hanya** mengelola baris dengan `TGL_TGT` sentinel di atas.

### Isian yang diinput admin (form Web / Mobile)

Hanya **satu nilai bisnis** per kelompok di UI:

| Isian form | Field API request | Disimpan ke DB |
|------------|-------------------|----------------|
| Pilih kelompok | — (path URL) | `target.ID_KS` |
| Nominal target (Rp) | `nominal_target` (number) | `target.STR_SP` |

**Endpoint:** `PUT /api/admin/target-realisasi/kelompok/{id_kel}`

**Body contoh:**

```json
{
  "nominal_target": 1000000
}
```

### Kolom lain di tabel `target` (legacy / impor)

Tabel `target` memiliki banyak kolom (`JLH_AGT_BR`, `STR_SW`, `SLD_*`, `REK_*`, `PRC_*`, dll.). **Modul monitoring saat ini tidak mengisi kolom-kolom itu** — hanya **`STR_SP`** pada baris `TGL_TGT` monitoring.

Referensi lengkap kolom: migrasi `database/migrations/2025_12_13_145903_create_target_table.php`, model `app/Models/Target.php`.

### Contoh isian

| ID_KS (kelompok) | TGL_TGT | STR_SP (nominal target) |
|------------------|---------|-------------------------|
| `KEL001` | `__OBORMAS_MONITORING__` | `1000000` |

### Response API (ringkasan per kelompok)

| Field API | Contoh | Keterangan |
|-----------|--------|------------|
| `id_kel` | `KEL001` | ID kelompok |
| `nama_kelompok` | `Kelompok A` | Dari `kel_sah.NAMA_KEL` |
| `jumlah_anggota` | `12` | Count `anggota` where `ID_KS` |
| `nominal_target` | `"1000000.00"` | Dari `target.STR_SP` baris monitoring |
| `total_realisasi` | `"800000.00"` | Dari agregasi TRS |
| `persentase_pencapaian` | `80` | 800000 / 1000000 × 100 |
| `status_target` | `below_target` | `on_target` jika realisasi ≥ target |

---

## 4. Perbandingan: apa yang diisi di layar

### Modul Data TRS

| Diisi | Tidak diisi di modul ini |
|-------|---------------------------|
| `NO_AGT`, kolom `STR_*`, `TGL_LAP`, … | Target kelompok, realisasi agregat |

### Modul Target & Realisasi (monitoring)

| Diisi (admin) | Hanya tampil (otomatis) |
|---------------|-------------------------|
| `nominal_target` per kelompok | `total_realisasi`, `%`, `status`, jumlah anggota, nama kelompok |

### User (anggota)

| Diisi | Hanya lihat |
|-------|-------------|
| — | Semua field ringkasan kelompoknya (`GET /api/target-realisasi/me`) |

---

## 5. Konfigurasi environment terkait

| Env | Default | Pengaruh |
|-----|---------|----------|
| `FIREBIRD_DATA_TRS_TABLE` | `DATA_TRS` | Nama tabel TRS di Firebird |
| `TARGET_MONITORING_TGL_TGT` | `__OBORMAS_MONITORING__` | `TGL_TGT` baris target monitoring |
| `TARGET_REALISASI_SUM_COLUMNS` | `STR_SP` | Kolom TRS yang dijumlahkan untuk realisasi |

File config: `config/obormas.php`.

---

## 6. Referensi kode

| Topik | Path |
|-------|------|
| Model TRS | `app/Models/DataTrs.php` |
| Model target | `app/Models/Target.php` |
| Agregasi realisasi | `app/Services/FirebirdService.php` → `sumRealisasiNominalForKelompok()` |
| Simpan target monitoring | `app/Services/TargetRealisasiMonitoringService.php` → `setNominalTarget()` |
| API admin | `app/Http/Controllers/Api/Admin/TargetRealisasiController.php` |
| Penjelasan kebutuhan | `plans/modul-target-realisasi-penjelasan-kebutuhan.md` |
| API HTTP | `API_DOCUMENTATION.md` → Target & Realisasi (monitoring) |
