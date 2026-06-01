# Panduan: Trigger Firebird — sync `REALISASI` dari `DATA_TRS` (DBeaver)

Dokumen ini untuk dijalankan manual di **DBeaver** pada database **PROD** (`OBORMAS_PROD.FDB` — koneksi `DB_*` di `.env`, **bukan** `OBORMAS.FDB` legacy).

**Tidak membuat tabel baru.** Yang sudah ada di database:

| Tabel | Peran |
|-------|--------|
| **`DATA_TRS`** | Transaksi per anggota (banyak baris per `NO_AGT`) |
| **`ANGGOTA`** | Master anggota — **`ID_KS`** = kelompok mana anggota itu |
| **`REALISASI`** | Capaian **per kelompok** (satu baris per `ID_KS` + `TGL_TGT`) |

Trigger hanya **mengisi/menyegarkan kolom di `REALISASI`** dari agregasi **`DATA_TRS`**, dengan kelompok diambil lewat **`ANGGOTA`**.

---

## Relasi tabel (yang Anda maksud)

```text
DATA_TRS                         ANGGOTA                    REALISASI
────────                         ───────                    ─────────
NO_AGT  ──join──►  NO_AGT                           PK: ID_KS
STR_SP, STR_SW, …       ID_KS  ──kelompok mana──►         TGL_TGT
TGL_LAP, …              NAMA, …                        STR_SP, STR_SW, …
(banyak baris           (satu baris per                 (satu baris per
 per anggota)            NO_AGT)                         kelompok + periode)
```

**Cara tahu “kelompok mana”:**

1. Dari baris TRS yang berubah → ambil **`DATA_TRS.NO_AGT`**
2. Cari di **`ANGGOTA`** baris dengan **`ANGGOTA.NO_AGT`** yang sama
3. Baca **`ANGGOTA.ID_KS`** → itulah ID kelompok untuk update **`REALISASI`**

Contoh:

```text
DATA_TRS: NO_AGT = '016005001', STR_SP = 100000
     ↓ join NO_AGT
ANGGOTA:  NO_AGT = '016005001', ID_KS = '016005'
     ↓ agregasi semua TRS anggota dengan ID_KS = '016005'
REALISASI: ID_KS = '016005', TGL_TGT = '__OBORMAS_MONITORING__', STR_SP = SUM(...)
```

---

## Kolom yang sudah ada (referensi)

### `DATA_TRS` — sumber transaksi (sudah ada)

| Kolom | Dipakai trigger |
|-------|-----------------|
| `ID` | PK baris (tidak disalin ke realisasi) |
| `NO_AGT` | Join ke `ANGGOTA` |
| `STR_SP`, `STR_SW`, `STR_SHR`, `STR_SMD`, `STR_SPD`, `STR_SBJ`, `STR_SRY`, `STR_SKA` | Di-**SUM** → kolom **nama sama** di `REALISASI` |
| `STR_PJM` | Di-**SUM** → `REALISASI.PCR_PJM` |
| `STR_BNG` | Di-**SUM** → `REALISASI.BNG_PJM` |
| `STR_SRI`, `STR_SDK`, `STR_SJP`, `PJM_BARU`, `TGL_LAP`, `created_by` | Tidak masuk agregasi patokan ini |

### `ANGGOTA` — cek kelompok (sudah ada)

| Kolom | Dipakai trigger |
|-------|-----------------|
| `NO_AGT` | PK / join dengan `DATA_TRS.NO_AGT` |
| **`ID_KS`** | **ID kelompok** → kunci `REALISASI.ID_KS` |
| `NAMA`, `ID_LO`, `ID_AO`, … | Tidak dipakai trigger |

### `REALISASI` — target isian trigger (sudah ada)

| Kolom | Cara terisi |
|-------|-------------|
| **`ID_KS`** | Dari `ANGGOTA.ID_KS` (bukan dari TRS) |
| **`TGL_TGT`** | Tetap `__OBORMAS_MONITORING__` (periode monitoring modul) |
| `STR_SP` … `STR_SKA` (8 kolom) | **SUM** dari `DATA_TRS` anggota kelompok itu |
| `PCR_PJM`, `BNG_PJM` | **SUM** `STR_PJM` / `STR_BNG` dari TRS |
| `JLH_AGT_BR`, `ASR_PKK`, `STR_SS`, `REK_*`, … | **Tidak** diisi trigger TRS |

---

## Ringkasan fungsi trigger

Setiap **INSERT / UPDATE / DELETE** pada **`DATA_TRS`**:

1. Ambil **`NO_AGT`** (baris baru/lama).
2. Dari **`ANGGOTA`**: `SELECT ID_KS WHERE NO_AGT = …` → tahu **kelompok mana**.
3. **SUM** kolom TRS semua anggota yang **`ANGGOTA.ID_KS`**-nya sama.
4. **UPDATE atau INSERT** satu baris di **`REALISASI`** untuk `(ID_KS, TGL_TGT)`.

---

## Prasyarat

- [ ] Koneksi DBeaver ke **`OBORMAS_PROD.FDB`** (host/port/user dari `DB_*`, bukan `FIREBIRD_*`).
- [ ] Tabel **`DATA_TRS`**, **`ANGGOTA`**, **`REALISASI`** sudah ada dan berisi struktur di atas.
- [ ] **Backup** file `.FDB` sebelum DDL.
- [ ] Firebird **3.0+** disarankan. Untuk **2.5** lihat [Alternatif Firebird 2.5](#alternatif-firebird-25).

---

## Step 1 — Cek tabel & relasi NO_AGT → ID_KS

Daftar tabel:

```sql
SELECT TRIM(RDB$RELATION_NAME) AS TBL
FROM RDB$RELATIONS
WHERE RDB$SYSTEM_FLAG = 0
  AND TRIM(RDB$RELATION_NAME) IN ('DATA_TRS', 'ANGGOTA', 'REALISASI')
ORDER BY 1;
```

Cek **kelompok dari TRS** (ini yang dipakai trigger):

```sql
SELECT FIRST 20
  t.NO_AGT,
  a.ID_KS AS KELOMPOK_ID_KS,
  a.NAMA,
  t.STR_SP,
  t.TGL_LAP
FROM DATA_TRS t
LEFT JOIN ANGGOTA a ON TRIM(a.NO_AGT) = TRIM(t.NO_AGT)
ORDER BY a.ID_KS, t.NO_AGT;
```

- Jika **`ID_KS` NULL** → anggota belum punya kelompok; baris TRS itu **tidak** mengisi `REALISASI` (trigger skip).

Cek baris realisasi kelompok (setelah trigger/backfill):

```sql
SELECT r.ID_KS, r.TGL_TGT, r.STR_SP, r.STR_SW, r.PCR_PJM, r.BNG_PJM
FROM REALISASI r
WHERE r.TGL_TGT = '__OBORMAS_MONITORING__'
ORDER BY r.ID_KS;
```

---

## Step 2 — Hapus objek lama (jika pernah dicoba)

Jalankan **hanya** jika sebelumnya sudah membuat trigger/procedure dengan nama sama:

```sql
DROP TRIGGER TRG_DATA_TRS_SYNC_REALISASI;
DROP PROCEDURE SP_SYNC_REALISASI_KS;
```

Jika error “not found”, abaikan.

---

## Step 3 — Buat procedure sync (inti logika)

Procedure ini dipanggil trigger **dan** backfill.

**Input:** `P_ID_KS` = nilai **`ANGGOTA.ID_KS`** (kelompok).  
**Baca:** semua baris **`DATA_TRS`** yang `NO_AGT`-nya punya `ID_KS` sama di **`ANGGOTA`**.  
**Tulis:** baris **`REALISASI`** untuk `(ID_KS, TGL_TGT)`.

### Cek tipe kolom `TGL_TGT` (jika backfill error conversion)

```sql
SELECT FIRST 5 ID_KS, TGL_TGT FROM REALISASI;
SELECT FIRST 5 ID_KS, TGL_TGT FROM TARGET;
```

- Jika `TGL_TGT` tampil sebagai **tanggal** (bukan teks `__OBORMAS_MONITORING__`) → pakai procedure **versi DATE** di bawah (PROD Firebird umumnya begini).
- Error `conversion error from string "__OBORMAS_MONITORING__"` = kolom `REALISASI.TGL_TGT` bertipe **DATE**, bukan VARCHAR.

**Sentinel monitoring (versi DATE):** `2099-01-01` — nanti selaraskan dengan baris `TARGET` / `.env` Laravel jika perlu.

```sql
SET TERM ^ ;

CREATE OR ALTER PROCEDURE SP_SYNC_REALISASI_KS (
    P_ID_KS VARCHAR(12)
)
AS
    DECLARE VARIABLE V_TGL_TGT DATE;
    DECLARE VARIABLE V_STR_SP DECIMAL(18,2);
    DECLARE VARIABLE V_STR_SW DECIMAL(18,2);
    DECLARE VARIABLE V_STR_SHR DECIMAL(18,2);
    DECLARE VARIABLE V_STR_SMD DECIMAL(18,2);
    DECLARE VARIABLE V_STR_SPD DECIMAL(18,2);
    DECLARE VARIABLE V_STR_SBJ DECIMAL(18,2);
    DECLARE VARIABLE V_STR_SRY DECIMAL(18,2);
    DECLARE VARIABLE V_STR_SKA DECIMAL(18,2);
    DECLARE VARIABLE V_PCR_PJM DECIMAL(18,2);
    DECLARE VARIABLE V_BNG_PJM DECIMAL(18,2);
BEGIN
    IF (P_ID_KS IS NULL OR TRIM(P_ID_KS) = '') THEN
        EXIT;

    /* Sentinel periode monitoring — HARUS sama tipe/konsep dengan kolom REALISASI.TGL_TGT di PROD */
    V_TGL_TGT = DATE '2099-01-01';

    SELECT
        COALESCE(SUM(
            CASE WHEN TRIM(COALESCE(t.STR_SP, '')) = '' THEN 0
                 ELSE CAST(TRIM(t.STR_SP) AS DECIMAL(18,2)) END
        ), 0),
        COALESCE(SUM(
            CASE WHEN TRIM(COALESCE(t.STR_SW, '')) = '' THEN 0
                 ELSE CAST(TRIM(t.STR_SW) AS DECIMAL(18,2)) END
        ), 0),
        COALESCE(SUM(
            CASE WHEN TRIM(COALESCE(t.STR_SHR, '')) = '' THEN 0
                 ELSE CAST(TRIM(t.STR_SHR) AS DECIMAL(18,2)) END
        ), 0),
        COALESCE(SUM(
            CASE WHEN TRIM(COALESCE(t.STR_SMD, '')) = '' THEN 0
                 ELSE CAST(TRIM(t.STR_SMD) AS DECIMAL(18,2)) END
        ), 0),
        COALESCE(SUM(
            CASE WHEN TRIM(COALESCE(t.STR_SPD, '')) = '' THEN 0
                 ELSE CAST(TRIM(t.STR_SPD) AS DECIMAL(18,2)) END
        ), 0),
        COALESCE(SUM(
            CASE WHEN TRIM(COALESCE(t.STR_SBJ, '')) = '' THEN 0
                 ELSE CAST(TRIM(t.STR_SBJ) AS DECIMAL(18,2)) END
        ), 0),
        COALESCE(SUM(
            CASE WHEN TRIM(COALESCE(t.STR_SRY, '')) = '' THEN 0
                 ELSE CAST(TRIM(t.STR_SRY) AS DECIMAL(18,2)) END
        ), 0),
        COALESCE(SUM(
            CASE WHEN TRIM(COALESCE(t.STR_SKA, '')) = '' THEN 0
                 ELSE CAST(TRIM(t.STR_SKA) AS DECIMAL(18,2)) END
        ), 0),
        COALESCE(SUM(
            CASE WHEN TRIM(COALESCE(t.STR_PJM, '')) = '' THEN 0
                 ELSE CAST(TRIM(t.STR_PJM) AS DECIMAL(18,2)) END
        ), 0),
        COALESCE(SUM(
            CASE WHEN TRIM(COALESCE(t.STR_BNG, '')) = '' THEN 0
                 ELSE CAST(TRIM(t.STR_BNG) AS DECIMAL(18,2)) END
        ), 0)
    FROM DATA_TRS t
    INNER JOIN ANGGOTA a ON TRIM(a.NO_AGT) = TRIM(t.NO_AGT)
    WHERE TRIM(a.ID_KS) = TRIM(:P_ID_KS)
    INTO
        :V_STR_SP, :V_STR_SW, :V_STR_SHR, :V_STR_SMD, :V_STR_SPD,
        :V_STR_SBJ, :V_STR_SRY, :V_STR_SKA, :V_PCR_PJM, :V_BNG_PJM;

    UPDATE OR INSERT INTO REALISASI (
        ID_KS,
        TGL_TGT,
        STR_SP,
        STR_SW,
        STR_SHR,
        STR_SMD,
        STR_SPD,
        STR_SBJ,
        STR_SRY,
        STR_SKA,
        PCR_PJM,
        BNG_PJM
    ) VALUES (
        TRIM(:P_ID_KS),
        :V_TGL_TGT,
        CAST(:V_STR_SP AS VARCHAR(50)),
        CAST(:V_STR_SW AS VARCHAR(50)),
        CAST(:V_STR_SHR AS VARCHAR(50)),
        CAST(:V_STR_SMD AS VARCHAR(50)),
        CAST(:V_STR_SPD AS VARCHAR(50)),
        CAST(:V_STR_SBJ AS VARCHAR(50)),
        CAST(:V_STR_SRY AS VARCHAR(50)),
        CAST(:V_STR_SKA AS VARCHAR(50)),
        CAST(:V_PCR_PJM AS VARCHAR(50)),
        CAST(:V_BNG_PJM AS VARCHAR(50))
    )
    MATCHING (ID_KS, TGL_TGT);
END
^

SET TERM ; ^
```

**Commit** di DBeaver (Ctrl+Enter pada seluruh script, atau Execute SQL Script).

---

## Step 4 — Buat trigger pada `DATA_TRS`

```sql
SET TERM ^ ;

CREATE OR ALTER TRIGGER TRG_DATA_TRS_SYNC_REALISASI
FOR DATA_TRS
ACTIVE AFTER INSERT OR UPDATE OR DELETE
POSITION 0
AS
    DECLARE VARIABLE V_NO_AGT VARCHAR(15);
    DECLARE VARIABLE V_ID_KS VARCHAR(12);
    DECLARE VARIABLE V_OLD_ID_KS VARCHAR(12);
BEGIN
    IF (INSERTING OR UPDATING) THEN
        V_NO_AGT = NEW.NO_AGT;
    ELSE
        V_NO_AGT = OLD.NO_AGT;

    IF (V_NO_AGT IS NULL OR TRIM(V_NO_AGT) = '') THEN
        EXIT;

    SELECT FIRST 1 TRIM(a.ID_KS)
    FROM ANGGOTA a
    WHERE TRIM(a.NO_AGT) = TRIM(:V_NO_AGT)
    INTO :V_ID_KS;

    IF (V_ID_KS IS NOT NULL AND TRIM(V_ID_KS) <> '') THEN
        EXECUTE PROCEDURE SP_SYNC_REALISASI_KS(V_ID_KS);

  /* Jika NO_AGT berubah, sync juga kelompok lama */
    IF (UPDATING AND TRIM(COALESCE(OLD.NO_AGT, '')) <> TRIM(COALESCE(NEW.NO_AGT, ''))) THEN
    BEGIN
        V_OLD_ID_KS = NULL;
        SELECT FIRST 1 TRIM(a.ID_KS)
        FROM ANGGOTA a
        WHERE TRIM(a.NO_AGT) = TRIM(OLD.NO_AGT)
        INTO :V_OLD_ID_KS;

        IF (V_OLD_ID_KS IS NOT NULL AND TRIM(V_OLD_ID_KS) <> ''
            AND V_OLD_ID_KS <> V_ID_KS) THEN
            EXECUTE PROCEDURE SP_SYNC_REALISASI_KS(V_OLD_ID_KS);
    END
END
^

SET TERM ; ^
```

---

## Step 5 — Backfill (data TRS yang sudah ada)

Sekali jalan setelah trigger aktif:

```sql
EXECUTE BLOCK AS
    DECLARE VARIABLE V_ID_KS VARCHAR(12);
BEGIN
    FOR
        SELECT DISTINCT TRIM(a.ID_KS)
        FROM ANGGOTA a
        INNER JOIN DATA_TRS t ON TRIM(t.NO_AGT) = TRIM(a.NO_AGT)
        WHERE a.ID_KS IS NOT NULL AND TRIM(a.ID_KS) <> ''
        INTO :V_ID_KS
    DO
        EXECUTE PROCEDURE SP_SYNC_REALISASI_KS(:V_ID_KS);
END
```

Untuk **satu kelompok** saja (uji):

```sql
EXECUTE PROCEDURE SP_SYNC_REALISASI_KS('GANTI_DENGAN_ID_KS');
```

---

## Step 6 — Verifikasi

### 6.1 Trigger terdaftar

```sql
SELECT TRIM(RDB$TRIGGER_NAME) AS TRIGGER_NAME,
       TRIM(RDB$RELATION_NAME) AS TABLE_NAME,
       RDB$TRIGGER_TYPE,
       RDB$TRIGGER_INACTIVE
FROM RDB$TRIGGERS
WHERE TRIM(RDB$TRIGGER_NAME) = 'TRG_DATA_TRS_SYNC_REALISASI';
```

`RDB$TRIGGER_INACTIVE` harus **0** (aktif).

### 6.2 Bandingkan SUM manual vs realisasi

Ganti `ID_KS` contoh:

```sql
SELECT
  TRIM(a.ID_KS) AS ID_KS,
  SUM(CASE WHEN TRIM(COALESCE(t.STR_SP, '')) = '' THEN 0
           ELSE CAST(TRIM(t.STR_SP) AS DECIMAL(18,2)) END) AS SUM_STR_SP
FROM DATA_TRS t
JOIN ANGGOTA a ON TRIM(a.NO_AGT) = TRIM(t.NO_AGT)
WHERE TRIM(a.ID_KS) = 'GANTI_ID_KS'
GROUP BY TRIM(a.ID_KS);
```

```sql
SELECT ID_KS, TGL_TGT, STR_SP, STR_SW, PCR_PJM, BNG_PJM
FROM REALISASI
WHERE TRIM(ID_KS) = 'GANTI_ID_KS'
  AND TGL_TGT = '__OBORMAS_MONITORING__';
```

Angka **`STR_SP`** (dan kolom lain) harus selaras.

### 6.3 Tes insert (opsional di staging)

Setelah INSERT satu baris ke `DATA_TRS`, jalankan lagi query 6.2 — `REALISASI` harus berubah tanpa perintah manual.

---

## Step 7 — Rollback (jika perlu)

```sql
DROP TRIGGER TRG_DATA_TRS_SYNC_REALISASI;
DROP PROCEDURE SP_SYNC_REALISASI_KS;
```

Baris `REALISASI` yang sudah terisi **tidak** terhapus otomatis.

---

## Pemetaan kolom (referensi)

| `REALISASI` | Sumber agregasi dari `DATA_TRS` |
|-------------|----------------------------------|
| `STR_SP` … `STR_SKA` | `SUM` kolom sama nama |
| `PCR_PJM` | `SUM(STR_PJM)` |
| `BNG_PJM` | `SUM(STR_BNG)` |
| `JLH_AGT_BR`, `ASR_PKK` | **Tidak** diisi trigger ini |

Kolom legacy lain di `REALISASI` (`STR_SS`, `REK_*`, dll.) **tidak** diubah trigger.

---

## Alternatif Firebird 2.5

Jika `UPDATE OR INSERT` atau `AFTER INSERT OR UPDATE OR DELETE` error:

1. Buat **3 trigger** terpisah: `..._AI`, `..._AU`, `..._AD` dengan body yang sama (panggil `SP_SYNC_REALISASI_KS`).
2. Di procedure, ganti `UPDATE OR INSERT` dengan:

```sql
UPDATE REALISASI SET
  STR_SP = CAST(:V_STR_SP AS VARCHAR(50)),
  /* ... kolom lain ... */
WHERE TRIM(ID_KS) = TRIM(:P_ID_KS)
  AND TGL_TGT = :V_TGL_TGT;

IF (ROW_COUNT = 0) THEN
  INSERT INTO REALISASI (ID_KS, TGL_TGT, STR_SP, /* ... */)
  VALUES (TRIM(:P_ID_KS), :V_TGL_TGT, CAST(:V_STR_SP AS VARCHAR(50)), /* ... */);
```

---

## Setelah trigger jalan

Langkah berikutnya di aplikasi Laravel (belum otomatis dari trigger):

- Ubah `TargetRealisasiMonitoringService` agar membaca **`REALISASI`** di PROD, bukan SUM dari Firebird legacy.

---

## Checklist cepat

- [ ] Backup DB
- [ ] Step 1 — cek tabel
- [ ] Step 3 — procedure `SP_SYNC_REALISASI_KS`
- [ ] Step 4 — trigger `TRG_DATA_TRS_SYNC_REALISASI`
- [ ] Step 5 — backfill
- [ ] Step 6 — verifikasi SUM vs `REALISASI`
