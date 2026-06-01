# Patokan field — Target, Realisasi, dan Data TRS

Dokumen ini mendefinisikan **field acuan (patokan)** untuk modul Target & Realisasi.  
Semua input target, perhitungan realisasi, dan agregasi dari **Data TRS** mengikuti **nama kolom yang sama** dengan patokan di bawah.

> Konteks lain: daftar lengkap semua kolom DB → `modul-target-realisasi-database-fields.md`  
> Konteks isian/API → `modul-target-realisasi-data-isian.md`

---

## 1. Daftar patokan — Data Target

Kolom berikut adalah **satu set field bisnis** yang dipakai konsisten di ketiga lapisan (target = input admin; realisasi = hasil capaian; TRS = sumber transaksi per anggota).

| No | Field | Label / arti |
|----|--------|----------------|
| — | `ID_KS` | ID Kelompok Sahabat |
| — | `TGL_TGT` | Tanggal Target (periode / tanggal acuan) |
| 1 | `JLH_AGT_BR` | Jumlah Anggota Baru |
| 2 | `STR_SP` | Setoran Simpanan Pokok |
| 3 | `STR_SW` | Setoran Simpanan Wajib |
| 4 | `STR_SHR` | Setoran Simpanan Hari Raya |
| 5 | `STR_SMD` | Setoran Simpanan Masa Depan |
| 6 | `STR_SPD` | Setoran Simpanan Pendidikan |
| 7 | `STR_SBJ` | Setoran Simpanan Berjangka |
| 8 | `STR_SRY` | Setoran Simpanan Raya |
| 9 | `STR_SKA` | Setoran Simpanan Khusus Anggota |
| 10 | `PCR_PJM` | Pencairan Pinjaman |
| 11 | `BNG_PJM` | Bunga Pinjaman |
| 12 | `ASR_PKK` | Asuransi Pokok |

**Kunci baris (bukan “nilai capaian”):** `ID_KS`, `TGL_TGT`.

**Nilai yang dibandingkan (target vs realisasi):** baris 1–12.

---

## 2. Field patokan target — yang **nama kolomnya sama** di `realisasi` dan `data_trs`

Bagian ini menjawab: dari daftar patokan §1, **kolom apa saja yang benar-benar ada dengan nama identik** di tabel `realisasi` dan `data_trs` (MySQL / Firebird `DATA_TRS`).

### 2.1 Semua field patokan di tabel `target`

Semua baris §1 disimpan di tabel **`target`** (kolom fisik sama nama):

| Kategori | Field |
|----------|--------|
| Kunci | `ID_KS`, `TGL_TGT` |
| Nilai capaian | `JLH_AGT_BR`, `STR_SP`, `STR_SW`, `STR_SHR`, `STR_SMD`, `STR_SPD`, `STR_SBJ`, `STR_SRY`, `STR_SKA`, `PCR_PJM`, `BNG_PJM`, `ASR_PKK` |

**Total: 14 field** (2 kunci + 12 nilai).

---

### 2.2 Di tabel `realisasi` — **nama kolom sama** dengan patokan target

Semua field patokan §1 **ada** di tabel `realisasi` dengan **nama kolom yang sama**:

| No | Field | Ada di `realisasi` | Label (sama dengan target) |
|----|--------|:--------------------:|----------------------------|
| — | `ID_KS` | ✅ | ID Kelompok Sahabat |
| — | `TGL_TGT` | ✅ | Tanggal Target |
| 1 | `JLH_AGT_BR` | ✅ | Jumlah Anggota Baru |
| 2 | `STR_SP` | ✅ | Setoran Simpanan Pokok |
| 3 | `STR_SW` | ✅ | Setoran Simpanan Wajib |
| 4 | `STR_SHR` | ✅ | Setoran Simpanan Hari Raya |
| 5 | `STR_SMD` | ✅ | Setoran Simpanan Masa Depan |
| 6 | `STR_SPD` | ✅ | Setoran Simpanan Pendidikan |
| 7 | `STR_SBJ` | ✅ | Setoran Simpanan Berjangka |
| 8 | `STR_SRY` | ✅ | Setoran Simpanan Raya |
| 9 | `STR_SKA` | ✅ | Setoran Simpanan Khusus Anggota |
| 10 | `PCR_PJM` | ✅ | Pencairan Pinjaman |
| 11 | `BNG_PJM` | ✅ | Bunga Pinjaman |
| 12 | `ASR_PKK` | ✅ | Asuransi Pokok |

**Ringkas:** **14 field** patokan target = **14 field** di `realisasi` (nama persis sama).

```text
ID_KS, TGL_TGT,
JLH_AGT_BR, STR_SP, STR_SW, STR_SHR, STR_SMD, STR_SPD, STR_SBJ, STR_SRY, STR_SKA,
PCR_PJM, BNG_PJM, ASR_PKK
```

---

### 2.3 Di tabel `data_trs` / Firebird `DATA_TRS` — **nama kolom sama** dengan patokan target

Hanya kolom patokan yang **namanya persis sama** di TRS:

| No | Field patokan | Ada di `data_trs` | Label |
|----|---------------|:-----------------:|-------|
| — | `ID_KS` | ❌ | (lewat join `anggota.ID_KS`, bukan kolom TRS) |
| — | `TGL_TGT` | ❌ | (TRS pakai `TGL_LAP`, bukan `TGL_TGT`) |
| 1 | `JLH_AGT_BR` | ❌ | Tidak ada kolom ini di TRS |
| 2 | `STR_SP` | ✅ | Setoran Simpanan Pokok |
| 3 | `STR_SW` | ✅ | Setoran Simpanan Wajib |
| 4 | `STR_SHR` | ✅ | Setoran Simpanan Hari Raya |
| 5 | `STR_SMD` | ✅ | Setoran Simpanan Masa Depan |
| 6 | `STR_SPD` | ✅ | Setoran Simpanan Pendidikan |
| 7 | `STR_SBJ` | ✅ | Setoran Simpanan Berjangka |
| 8 | `STR_SRY` | ✅ | Setoran Simpanan Raya |
| 9 | `STR_SKA` | ✅ | Setoran Simpanan Khusus Anggota |
| 10 | `PCR_PJM` | ❌ | Di TRS kolomnya **`STR_PJM`** (nama beda) |
| 11 | `BNG_PJM` | ❌ | Di TRS kolomnya **`STR_BNG`** (nama beda) |
| 12 | `ASR_PKK` | ❌ | Belum ada kolom sama nama di TRS |

**Ringkas — nama sama persis di TRS (8 field):**

```text
STR_SP, STR_SW, STR_SHR, STR_SMD, STR_SPD, STR_SBJ, STR_SRY, STR_SKA
```

**Patokan target yang tidak punya kolom sama nama di TRS (6 field + 2 kunci):**

```text
ID_KS, TGL_TGT, JLH_AGT_BR, PCR_PJM, BNG_PJM, ASR_PKK
```

---

### 2.4 Perbandingan satu pandangan

| Kelompok field | `target` | `realisasi` (nama sama) | `data_trs` (nama sama) |
|----------------|:--------:|:-----------------------:|:----------------------:|
| Kunci | `ID_KS`, `TGL_TGT` | `ID_KS`, `TGL_TGT` | — (join / field lain) |
| Anggota baru | `JLH_AGT_BR` | `JLH_AGT_BR` | — |
| Setoran (8 jenis) | `STR_SP` … `STR_SKA` | `STR_SP` … `STR_SKA` | `STR_SP` … `STR_SKA` |
| Pinjaman & asuransi | `PCR_PJM`, `BNG_PJM`, `ASR_PKK` | `PCR_PJM`, `BNG_PJM`, `ASR_PKK` | — (lihat alias `STR_PJM` / `STR_BNG` di §6) |

**Irisan ketiga tabel (target + realisasi + TRS, nama kolom identik):** hanya **8 field setoran**:

```text
STR_SP, STR_SW, STR_SHR, STR_SMD, STR_SPD, STR_SBJ, STR_SRY, STR_SKA
```

**Irisan target + realisasi (semua patokan, nama identik):** **14 field** (seluruh §1).

---

## 3. Aturan keselarasan antar tabel

| Lapisan | Tabel / sumber | Peran field patokan |
|---------|----------------|---------------------|
| **Data Target** | `target` | **Input admin** — mengisi nilai target per `ID_KS` + `TGL_TGT` pada kolom patokan (1–12). |
| **Data Realisasi** | `realisasi` **atau** hasil agregat TRS | **Baca/hitung** — nilai realisasi memakai **nama kolom yang sama** dengan target, per kelompok + periode. |
| **Data TRS** | `data_trs` / Firebird `DATA_TRS` | **Sumber transaksi** — per `NO_AGT`; kolom yang **namanya sama** dengan patokan dijumlahkan per kelompok (`anggota.ID_KS`) menjadi komponen realisasi. |

### Prinsip

1. **Nama field harus identik** antara target, realisasi, dan TRS agar masuk patokan (contoh: `STR_SP` di ketiga konteks).
2. **Realisasi** untuk setiap field patokan = **SUM** nilai field tersebut dari semua baris TRS anggota dalam kelompok yang sama (kecuali `JLH_AGT_BR` — lihat §5).
3. **Target** disimpan per kelompok + `TGL_TGT`, bukan per anggota.
4. Field di database yang **tidak** ada di daftar patokan §1 **tidak** dipakai dalam modul ini (kecuali ditambahkan ke patokan secara eksplisit).

---

## 4. Matriks: field patokan × tabel database

| Field patokan | `target` | `realisasi` | `data_trs` | Catatan |
|---------------|:--------:|:-----------:|:----------:|---------|
| `ID_KS` | ✅ PK | ✅ PK | — | Di TRS lewat `anggota.ID_KS` (join), bukan kolom TRS. |
| `TGL_TGT` | ✅ PK | ✅ PK | — | Periode target/realisasi; TRS pakai `TGL_LAP` terpisah (bukan patokan). |
| `JLH_AGT_BR` | ✅ | ✅ | ❌ | Level kelompok; realisasi = hitungan/aturan bisnis anggota baru, **bukan** SUM dari TRS. |
| `STR_SP` | ✅ | ✅ | ✅ | Setoran per transaksi → dijumlah per kelompok. |
| `STR_SW` | ✅ | ✅ | ✅ | Idem. |
| `STR_SHR` | ✅ | ✅ | ✅ | Idem. |
| `STR_SMD` | ✅ | ✅ | ✅ | Idem. |
| `STR_SPD` | ✅ | ✅ | ✅ | Idem. |
| `STR_SBJ` | ✅ | ✅ | ✅ | Idem. |
| `STR_SRY` | ✅ | ✅ | ✅ | Idem. |
| `STR_SKA` | ✅ | ✅ | ✅ | Idem. |
| `PCR_PJM` | ✅ | ✅ | ❌ * | Ada di target/realisasi; di TRS nama kolom **`STR_PJM`** (bukan `PCR_PJM`) — lihat §6. |
| `BNG_PJM` | ✅ | ✅ | ❌ * | Di TRS nama kolom **`STR_BNG`** — lihat §6. |
| `ASR_PKK` | ✅ | ✅ | ❌ | Tidak ada kolom sama di `data_trs`; realisasi dari sumber lain atau kolom belum ada di TRS. |

**Legenda:** ✅ = kolom ada di tabel; ❌ = tidak ada nama sama; ❌ * = perlu pemetaan nama (§6).

---

## 5. Perilaku per jenis field

### 5.1 Kunci — `ID_KS`, `TGL_TGT`

| Tabel | Cara pakai |
|-------|------------|
| `target` | Admin memilih kelompok + periode saat input. |
| `realisasi` | Satu baris per pasangan `ID_KS` + `TGL_TGT` (snapshot atau dihitung). |
| `data_trs` | Tidak disimpan; agregasi memakai `WHERE anggota.ID_KS = :id_ks`. |

**Modul monitoring saat ini (implementasi):** `TGL_TGT` tetap = nilai sentinel `TARGET_MONITORING_TGL_TGT` (`__OBORMAS_MONITORING__`) untuk satu baris target per kelompok tanpa periode kalender — jika bisnis memakai `TGL_TGT` riil, sesuaikan input form admin.

### 5.2 Target numerik setoran — `STR_SP` … `STR_SKA` (8 kolom)

| Data Target | Data Realisasi | Data TRS |
|-------------|----------------|----------|
| Admin **input** nominal target per kolom | **SUM**(`STR_*`) semua baris TRS anggota kelompok | Admin/user **input** per transaksi per `NO_AGT` |

**Contoh (satu kelompok, satu field):**

```text
Target.STR_SP     = 1.000.000   ← input admin
Realisasi.STR_SP  = SUM(trs.STR_SP) untuk semua NO_AGT dengan ID_KS tersebut
```

### 5.3 Pinjaman & asuransi — `PCR_PJM`, `BNG_PJM`, `ASR_PKK`

| Field | Target | Realisasi (patokan) | TRS |
|-------|--------|---------------------|-----|
| `PCR_PJM` | Input admin | SUM atau isi ke `realisasi.PCR_PJM` | Lihat pemetaan §6 |
| `BNG_PJM` | Input admin | Idem | Lihat pemetaan §6 |
| `ASR_PKK` | Input admin | Idem | Belum ada kolom TRS sama nama |

### 5.4 `JLH_AGT_BR`

| Lapisan | Perilaku yang disepakati (patokan dokumen) |
|---------|---------------------------------------------|
| Target | Admin mengisi target jumlah anggota baru. |
| Realisasi | Nilai capaian = **jumlah anggota baru aktual** (bukan SUM kolom TRS). |
| TRS | Tidak ada kolom `JLH_AGT_BR`; hitung dari master `anggota` / aturan bisnis yang disepakati. |

---

## 6. Kolom TRS yang mirip tetapi nama berbeda (belum masuk patokan otomatis)

Agar patokan **“nama sama persis”** tetap konsisten, kolom TRS berikut **tidak** dianggap sama sampai bisnis menetapkan alias resmi:

| Field patokan (target/realisasi) | Kolom di `data_trs` saat ini | Rekomendasi |
|----------------------------------|------------------------------|-------------|
| `PCR_PJM` | `STR_PJM` (+ ada `PJM_BARU`) | Tetapkan: apakah realisasi `PCR_PJM` = SUM(`STR_PJM`) atau kombinasi. |
| `BNG_PJM` | `STR_BNG` | Tetapkan: realisasi `BNG_PJM` = SUM(`STR_BNG`). |
| `ASR_PKK` | — | Tambah kolom `ASR_PKK` di TRS **atau** kecualikan dari agregasi TRS. |

**Opsi implementasi nanti (salah satu):**

- Rename / tambah kolom di TRS agar = patokan, **atau**
- Mapping di service: `PCR_PJM` ← SUM(`STR_PJM`), `BNG_PJM` ← SUM(`STR_BNG`) (dokumentasikan di kode sebagai alias, bukan nama DB sama).

---

## 7. Kolom TRS di database yang di luar patokan §1

Tidak dipakai dalam perbandingan target–realisasi patokan ini (kecuali nanti ditambah ke §1):

| Kolom `data_trs` | Keterangan |
|------------------|------------|
| `ID` | PK surrogate |
| `NO_AGT` | Kunci ke anggota |
| `STR_SRI` | Bukan di daftar patokan |
| `STR_SDK` | Bukan di daftar patokan |
| `STR_SJP` | Bukan di daftar patokan |
| `STR_PJM` | Lihat §6 (bukan nama `PCR_PJM`) |
| `STR_BNG` | Lihat §6 (bukan nama `BNG_PJM`) |
| `PJM_BARU` | Bukan di daftar patokan |
| `TGL_LAP` | Tanggal laporan transaksi, bukan `TGL_TGT` |
| `created_by` | Audit |

---

## 8. Kolom `target` / `realisasi` di DB yang di luar patokan §1

Kolom legacy (saldo `SLD_*`, `STR_SS`, `STR_SRI`, `REK_*`, `STF_*`, dll.) **tidak** masuk modul patokan ini.

Hanya field §1 yang relevan untuk:

- form input target admin, dan  
- perbandingan capaian per komponen (progress / % per field atau agregat — keputusan UI terpisah).

---

## 9. Ringkasan set field untuk pengembangan

### Yang harus bisa di-input admin (target) — per `ID_KS` + `TGL_TGT`

```text
JLH_AGT_BR, STR_SP, STR_SW, STR_SHR, STR_SMD, STR_SPD, STR_SBJ, STR_SRY, STR_SKA,
PCR_PJM, BNG_PJM, ASR_PKK
```

### Yang harus dihitung/dibaca sebagai realisasi (nama kolom sama)

```text
JLH_AGT_BR  → aturan khusus (bukan SUM TRS)
STR_SP … STR_SKA, PCR_PJM, BNG_PJM, ASR_PKK
  → SUM dari TRS per kelompok untuk kolom yang namanya sama
  → atau alias §6 untuk PCR_PJM / BNG_PJM
```

### Yang diisi di Data TRS (per transaksi / per `NO_AGT`)

Minimal kolom patokan yang **nama sama**:

```text
STR_SP, STR_SW, STR_SHR, STR_SMD, STR_SPD, STR_SBJ, STR_SRY, STR_SKA
```

Plus penyelesaian §6 untuk pinjaman/asuransi.

---

## 10. Gap implementasi saat ini (referensi)

| Aspek | Patokan dokumen ini | Kode saat ini |
|-------|---------------------|---------------|
| Kolom target di-input | 12 field §1 | Hanya `STR_SP` (monitoring sentinel) |
| Agregasi realisasi TRS | Semua `STR_*` patokan | Env `TARGET_REALISASI_SUM_COLUMNS` default `STR_SP` saja |
| Tabel `realisasi` | Bisa dipakai snapshot | Modul monitoring baca agregat TRS, bukan CRUD `realisasi` |

Dokumen ini adalah **spesifikasi patokan**; penyesuaian backend/UI mengikuti dokumen ini pada fase implementasi berikutnya.

---

## 11. Referensi

| File | Isi |
|------|-----|
| `plans/modul-target-realisasi-database-fields.md` | Semua kolom fisik di MySQL |
| `plans/modul-target-realisasi-penjelasan-kebutuhan.md` | Kebutuhan bisnis modul |
| `config/obormas.php` | `target_monitoring_tgl_tgt`, `target_realisasi_sum_columns` |
