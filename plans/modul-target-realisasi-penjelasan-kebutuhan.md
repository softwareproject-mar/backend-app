# Modul Target & Realisasi — Penjelasan Kebutuhan

Dokumen ini merangkum **maksud kebutuhan** modul Target & Realisasi (Modul 3) dalam bahasa yang mudah dipahami, selaras dengan spesifikasi stakeholder dan implementasi di codebase.

---

## Gambaran umum

Modul ini adalah **alat pemantauan (monitoring)**: seberapa dekat setiap kelompok dengan **target simpanan** yang ditetapkan.

- **Target** → angka yang **ditetapkan admin** per kelompok (contoh: Kelompok A = Rp1.000.000).
- **Realisasi** → **bukan** input manual admin; dihitung dari **penjumlahan transaksi/simpanan** seluruh anggota dalam kelompok yang sama (sumber: tabel `data_trs` / agregasi TRS).

Admin **mengatur target** dan **memantau semua kelompok**. User (anggota) **hanya memantau** kelompoknya sendiri, **tanpa** mengubah target.

---

## A. Target

| Aspek | Keterangan |
|--------|------------|
| Siapa yang input | **Admin** (Web Admin / Admin Mobile) |
| Apa yang diinput | **Nominal target** per kelompok |
| Contoh | Target Kelompok A = Rp1.000.000 |
| Penyimpanan (implementasi) | Baris khusus di tabel `target` (monitoring), bukan tabel terpisah `simpanan_target_kelompok` |

---

## B. Realisasi

| Aspek | Keterangan |
|--------|------------|
| Cara diperoleh | **Penjumlahan** seluruh transaksi/simpanan anggota dalam **satu kelompok** |
| Input manual? | **Tidak** — realisasi mengikuti data transaksi terbaru |
| Contoh | Anggota A Rp200.000 + B Rp300.000 + C Rp300.000 → **Total realisasi** = Rp800.000 |

---

## C. Persentase pencapaian

### Rumus

```
Persentase = (Realisasi / Target) × 100%
```

### Contoh

| Target | Realisasi | Persentase |
|--------|-----------|------------|
| Rp1.000.000 | Rp800.000 | **80%** |

### Catatan teknis

- Jika **belum ada target** (nol/kosong), persentase tidak bermakna — UI/backend biasanya menampilkan `null`, `0%`, atau strip kosong (bukan error).
- Persentase **dihitung di backend**; frontend menampilkan hasil (dengan fallback hitung ulang di progress bar jika perlu).

---

## Status target

| Status | Arti (definisi bisnis umum) |
|--------|------------------------------|
| **On Target** | Realisasi **sudah mencapai** target → praktiknya **realisasi ≥ target** |
| **Belum On Target** | Realisasi **masih di bawah** target |

> Jika bisnis memerlukan **On Target hanya saat sama persis** (bukan ≥), perlu penegasan terpisah di spesifikasi produk.

**Implementasi API:** nilai `status_target` → `on_target` | `below_target` | `no_target` (belum ada target).

---

## Informasi yang ditampilkan pada modul

Setiap baris / kartu monitoring minimal memuat:

1. **Nama kelompok**
2. **Nominal target**
3. **Total realisasi**
4. **Persentase pencapaian (%)**
5. **Status target**
6. **Jumlah anggota** dalam kelompok

### Perbedaan peran

| Peran | Input target | Scope data |
|--------|--------------|------------|
| **Admin** | Ya (ubah nominal per kelompok) | Umumnya **semua kelompok** (ringkasan) |
| **User (anggota)** | Tidak | **Satu kelompok** milik akun (`/target-realisasi/me`) |

---

## Visualisasi (jika memungkinkan)

| Jenis | Fungsi |
|--------|--------|
| **Progress bar** | Membandingkan realisasi vs target secara visual (mis. bar ~80% penuh) |
| **Bar chart** | Perbandingan antar kelompok atau kategori (opsional / fase lanjut) |
| **Line chart** | Tren pencapaian dari waktu ke waktu (opsional / fase lanjut) |

**Prioritas:** tabel angka + progress bar = inti modul; chart = peningkatan UX, tidak wajib di rilis pertama.

---

## Tujuan modul

- **Admin** dan **user** dapat **memonitor** perkembangan pencapaian target **lebih mudah**.
- **Realtime** dalam requirements berarti data realisasi mengikuti transaksi terbaru (refresh halaman / tombol refresh / polling), kecuali produk memutuskan websocket khusus.

---

## Yang *bukan* maksud kebutuhan

- **Bukan** dua form input manual di layar yang sama: “isi target” dan “isi realisasi”.
- **Bukan** “dipisah per modul” dalam arti banyak sub-menu terpisah di UI; yang dimaksud spesifikasi adalah **dua konsep data** (A = target, B = realisasi), bukan dua halaman input ganda.
- **Bukan** badge angka di kartu Beranda Web Admin untuk modul ini — kartu lain memakai `/dashboard/counts`; kartu Target & Realisasi sengaja tanpa `countKey` (badge titik `•`).

---

## Kesesuaian dengan implementasi saat ini

| Kebutuhan | Status implementasi (ringkas) |
|-----------|-------------------------------|
| Admin input target per kelompok | ✅ `PUT /api/admin/target-realisasi/kelompok/{id_kel}` |
| Realisasi dari agregasi TRS | ✅ via `FirebirdService` + kolom config `target_realisasi_sum_columns` |
| Persentase & status | ✅ di `TargetRealisasiMonitoringService` + resource API |
| Enam informasi di tabel | ✅ Web Admin + Mobile |
| Progress bar | ✅ Web Admin + Mobile (member) |
| Bar / line chart | ⏳ Opsional — belum / fase berikutnya |
| Data kosong di tabel | Periksa master `kel_sah`, koneksi Firebird, dan env `TARGET_MONITORING_TGL_TGT` |

---

## Referensi terkait

- Spesifikasi + research codebase: `plans/modul-target-realisasi-research.md`
- Plan implementasi: `plans/modul-target-realisasi.md`
- API: `API_DOCUMENTATION.md` → bagian **Target & Realisasi (monitoring)**
- Config: `config/obormas.php` → `target_monitoring_tgl_tgt`, `target_realisasi_sum_columns`
