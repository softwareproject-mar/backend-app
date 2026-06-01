# Plan: Perbaikan UX autocomplete (no anggota / field serupa) + AppDatePicker popup

## Context

- **Riset:** Beberapa form memakai `useEffect([searchState])` yang setelah fetch sukses memanggil `setShowAnggotaDropdown(true)` sementara setelah pilih item input diisi `"NO_AGT – NAMA"` sehingga effect jalan lagi dan dropdown terbuka ulang (Pola A).
- Layar **LO / AO / Kelompok Sahabat** memakai `onFocus={() => setShow…Dropdown(true)}` (+ load) sehingga setelah klik opsi fokus kembali ke input dan dropdown bisa langsung terbuka lagi (Pola B).
- **`DataKunjunganContent`** punya `onFocus` bersyarat (≥3 karakter + ada opsi); setelah pilih, opsi masih di memori sehingga syarat bisa terpenuhi dan dropdown ikut terbuka saat fokus kembali.
- **`AppDatePicker`** me-render panel di bawah trigger (`absolute` + `mt-2`), rentan terpotong oleh parent `overflow-y-auto` dan terasa “mendorong” konten; satu komponen dipakai di banyak layar.

## Goal

1. Satu kali pilih dari autocomplete harus **stabil** (dropdown tidak membuka kembali tanpa niat user mengetik/mencari lagi).
2. **`AppDatePicker`**: panel tanggal sebagai **popup overlay** (tidak mengembang ke bawah di dalam alur scroll form), satu implementasi untuk semua pemakai.

## Detailed Specifications

### A. Pola A — `useEffect` anggota + `setShowAnggotaDropdown(true)` setelah sukses

**File (path relatif ke root app `Kelompok Sahabat Obor Mas/Kelompok Sahabat Obor Mas/`):**

- `src/app/components/DataPenghasilanScreen.tsx`
- `src/app/components/JlhKeluargaDataScreen.tsx`
- `src/app/components/KetuaKSDataScreen.tsx`
- `src/app/components/SekretarisKSDataScreen.tsx`
- `src/app/components/DataPengelolaScreen.tsx`

**Perubahan per file (nama state sedikit beda antar file, pola sama):**

1. Di **`useEffect`** yang dependency-nya `anggotaSearch` (atau nama setara): pada cabang sukses fetch, **hapus** pemanggilan `setShowAnggotaDropdown(true)`. Pertahankan penanganan error (mis. `setShowAnggotaDropdown(false)` jika sudah ada).
2. Di **`onChange`** input nomor anggota (yang mengisi `anggotaSearch`): jika `e.target.value.trim().length >= 3` set **`setShowAnggotaDropdown(true)`**; jika di bawah 3 set **`setShowAnggotaDropdown(false)`** (dan biarkan effect yang mengosongkan opsi seperti sekarang).
3. Pastikan handler klik opsi tetap: set `formData.noAnggota` (atau setara), set teks tampilan `"NO_AGT – NAMA"`, **`setShowAnggotaDropdown(false)`** — tidak diubah kecuali perlu konsistensi.

### B. Pola B — hapus `onFocus` yang memaksa buka dropdown

**File:**

- `src/app/components/LODataScreen.tsx` — field **Nomor Anggota**: hapus prop **`onFocus={() => setShowNoAnggotaDropdown(true)}`** (baris di sekitar input `noAnggotaSearch`). Biarkan pembukaan melalui **`onChange`** + `loadAnggotaOptions` yang sudah ada.
- `src/app/components/AODataScreen.tsx` — sama untuk input `noAnggotaSearch`.
- `src/app/components/KelompokSahabatScreen.tsx` — hapus blok **`onFocus`** pada lima field autocomplete: **ID Ketua KS**, **ID Sekretaris KS**, **ID LO**, **ID AO**, **ID Pengelola** (masing-masing memanggil `setShow…Dropdown(true)` dan `load…Options`). **Jangan** menghapus logika **`onChange`** yang membuka dropdown dan memuat data.

### C. `DataKunjunganContent.tsx` — LO, Nomor Anggota, ID Kelompok

- Hapus **`onFocus`** pada ketiga input tersebut (ID LO, Nomor Anggota, ID Kelompok Sahabat) agar konsisten: dropdown hanya dari **ketik** (`onChange`), tidak membuka lagi hanya karena fokus kembali setelah pilih.
- **Tidak** mengubah pola fetch inline di `onChange` kecuali diperlukan untuk bug lain.

### D. `AppDatePicker.tsx` — overlay popup

**File:** `src/app/components/ui/AppDatePicker.tsx`

1. Import **`createPortal`** dari **`react-dom`**.
2. Saat `open && !disabled`, render ke **`document.body`** via portal:
   - **Backdrop:** `fixed inset-0`, background semi-transparan (mis. `bg-black/40`), **`z-index` tinggi** (mis. `z-[10000]` atau setara) agar di atas modal form yang memakai `z-[70]`.
   - **Panel kalender:** `fixed` **terpusat** (mis. kombinasi `left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2` atau `inset` + flex center), `max-w-xs`, tetap rounded/shadow seperti sekarang, **`max-h-[min(90vh,...)]`** + `overflow-y-auto` jika perlu agar tidak keluar layar kecil.
3. Ganti penutup klik luar: pada mode terbuka, **`mousedown`**/`pointerdown` di backdrop menutup; klik di dalam panel tidak menutup (`stopPropagation` pada panel jika perlu).
4. **Hapus** wrapper panel lama yang **`absolute … mt-2 left-0 right-0`** di bawah tombol trigger (trigger tetap `relative` boleh, tapi isi kalender hanya lewat portal).
5. Opsional kecil (satu commit yang sama boleh): sesuaikan **label hari** di header grid agar selaras dengan **`startOfWeek(..., { locale: localeId })`** (hindari array statis `M,S,S,…` yang bisa tidak cocok dengan urutan kolom).

### E. Out of scope

- Refactor besar menjadi satu komponen `AutocompleteField` bersama (boleh fase berikutnya).
- Perubahan backend / API.
- Field autocomplete di **`MemberDataScreen`** yang tidak memakai Pola A/B di atas (tidak wajib di checklist kecuali pengujian regresi menemukan masalah serupa).

## Implementation Checklist

1. **DataPenghasilanScreen.tsx:** Hapus `setShowAnggotaDropdown(true)` dari sukses `useEffect` anggota; di `onChange` input `anggotaSearch` set show dropdown true/false berdasarkan panjang trim ≥ 3.
2. **JlhKeluargaDataScreen.tsx:** Sama seperti item 1.
3. **KetuaKSDataScreen.tsx:** Sama seperti item 1.
4. **SekretarisKSDataScreen.tsx:** Sama seperti item 1.
5. **DataPengelolaScreen.tsx:** Sama seperti item 1.
6. **LODataScreen.tsx:** Hapus `onFocus` pada input Nomor Anggota yang memaksa `setShowNoAnggotaDropdown(true)`.
7. **AODataScreen.tsx:** Sama seperti item 6.
8. **KelompokSahabatScreen.tsx:** Hapus kelima handler `onFocus` autocomplete (ketua, sekretaris, LO, AO, pengelola).
9. **DataKunjunganContent.tsx:** Hapus `onFocus` pada input ID LO, Nomor Anggota, dan ID Kelompok Sahabat.
10. **AppDatePicker.tsx:** Implementasi portal + backdrop + panel `fixed` terpusat; perbarui penutup klik luar; hilangkan panel `absolute` di bawah trigger.
11. **AppDatePicker.tsx (opsional):** Perbaiki label hari minggu agar konsisten dengan locale / urutan kolom.
12. Jalankan **`npm run build`** di root app mobile; uji manual: form tambah (Penghasilan, Jlh Keluarga, LO, Kunjungan, Kelompok Sahabat) — pilih autocomplete sekali, pastikan dropdown tidak membuka sendiri; buka date picker di dalam modal, pastikan kalender tampil di atas overlay dan tidak terpotong scroll.

## Risks / Catatan

- Tanpa `onFocus`, user yang ingin melihat ulang daftar tanpa mengubah teks harus **mengetik ulang** atau menghapus/menambah karakter; trade-off disengaja demi stabilitas seleksi.
- `z-index` kalender harus lebih tinggi dari modal tertinggi di app; jika ada layar dengan z lebih besar dari 10000, sesuaikan satu angka di `AppDatePicker`.
- **Capacitor / WebView:** portal ke `document.body` tetap standar; uji cepat di Android jika memungkinkan.
