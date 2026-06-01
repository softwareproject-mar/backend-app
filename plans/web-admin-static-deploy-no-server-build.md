# Plan: Web Admin — deploy statis tanpa `npm run build` di server

## Context

- Web Admin adalah SPA Vite + React; artefak produksi berupa isi folder `dist/` (HTML + `assets/`).
- VPS produksi memiliki **CPU/RAM terbatas**; menjalankan `npm install` / `npm run build` di server sering gagal (OOM, proses `Killed`, atau sangat lambat).
- Aplikasi diakses di subpath **`/kelsah-obor-mas/`**; `base` Vite dan `basename` router mengikuti `VITE_BASE_PATH` saat build.
- Backend API terpisah (URL di-set lewat `VITE_API_BASE_URL`); `src/services/api.ts` menambahkan `/api` setelah base URL — **jangan** menyertakan `/api` di akhir `VITE_API_BASE_URL`.
- Cuplikan Nginx subpath (simpan di `server { }` aktif): `location = /kelsah-obor-mas { return 301 /kelsah-obor-mas/; }` dan `location ^~ /kelsah-obor-mas/ { root /var/www/html; try_files $uri $uri/ /kelsah-obor-mas/index.html; }` (file fisik di `/var/www/html/kelsah-obor-mas/`).

## Goal

- **Tidak** menjalankan `npm run build` (dan idealnya tidak `npm install` untuk frontend) di VPS produksi.
- Alur resmi: **build di mesin lokal (atau CI)** → unggah **hanya isi `dist/`** → Nginx melayani file statis di `/kelsah-obor-mas/`.
- Dokumentasi satu tempat agar tim mengulang deploy tanpa menebak env atau struktur folder.

## Detailed Specifications

### File / artefak

| Lokasi | Peran |
|--------|--------|
| `Web Admin/.env` | `VITE_BASE_PATH`, `VITE_API_BASE_URL` (dipakai juga saat `npm run build`) |
| `Web Admin/dist/` | Output build; yang di-deploy ke server hanya **isi** folder ini |

### Variabel build (wajib benar sebelum `npm run build`)

- `VITE_BASE_PATH`: harus cocok dengan URL publik, mis. `/kelsah-obor-mas/` (dengan trailing slash setelah normalisasi Vite).
- `VITE_API_BASE_URL`: origin backend **tanpa** suffix `/api`, mis. `http://IP/obormas` atau `https://domain.com/obormas`.

### Struktur di server (contoh standar)

- `root` Nginx: `/var/www/html`
- File statis: `/var/www/html/kelsah-obor-mas/index.html` dan `/var/www/html/kelsah-obor-mas/assets/...`
- **Bukan** menyalin seluruh repo `Web Admin` ke server jika tidak diperlukan; cukup sinkronisasi isi `dist/`.

### Verifikasi cepat setelah deploy

- Buka `http://IP/kelsah-obor-mas/` — halaman load, tidak layar putih.
- Di DevTools → Network: request JS/CSS ke path `/kelsah-obor-mas/assets/...` status 200.
- Login atau endpoint sederhana: request ke `{VITE_API_BASE_URL}/api/...` tidak double `/api/api`.

### Opsional fase 2 (di luar scope wajib checklist ini)

- Pipeline CI (mis. GitHub Actions) yang menjalankan `npm ci` + `npm run build` dengan secrets env, mengunggah artefak `dist/` ke VPS via SSH/rsync. Spesifik workflow bisa ditambahkan rencana terpisah jika disetujui.

## Implementation Checklist

1. Pastikan `Web Admin/.env` berisi `VITE_BASE_PATH=/kelsah-obor-mas/` dan `VITE_API_BASE_URL` **tanpa** `/api` di akhir, sesuai URL backend produksi.
2. Di mesin build (lokal): `cd` ke folder `Web Admin`, jalankan `npm install` jika belum, lalu `npm run build`.
3. Verifikasi lokal: buka `Web Admin/dist/index.html` dan pastikan tag script/stylesheet memakai prefix `/kelsah-obor-mas/assets/...` (bukan `/assets/...` saja).
4. Pada server: buat direktori tujuan jika belum ada, mis. `/var/www/html/kelsah-obor-mas/`, dengan permission yang bisa dibaca user Nginx.
5. Unggah **isi** `dist/` ke server (bukan folder induk proyek): minimal `index.html` dan seluruh isi folder `assets/` ke bawah `/var/www/html/kelsah-obor-mas/`.
6. Pastikan blok Nginx untuk `/kelsah-obor-mas/` terpasang (lihat cuplikan di bagian Context atas), dengan `root /var/www/html` dan `try_files` mengarah ke `/kelsah-obor-mas/index.html` untuk fallback SPA.
7. Jalankan `sudo nginx -t`; jika sukses, `sudo systemctl reload nginx` (atau setara).
8. Uji di browser: URL dengan trailing slash `http://IP/kelsah-obor-mas/` dan navigasi dalam aplikasi; jika error, kumpulkan baris relevan dari log Nginx (`error.log`) untuk diagnosis.
9. Dokumentasikan alur langkah 1–8 di satu file README di folder `Web Admin` (mis. `DEPLOY.md`) agar deploy berikutnya tidak bergantung pada chat — isi ringkas: env, perintah build, path server, cuplink ke snippet Nginx.
10. (Opsional) Set kebijakan tim: **larang** menjalankan `npm run build` di VPS kecuali untuk eksperimen; produksi hanya terima artefak `dist/`.

## Risks / Catatan

- Mengganti `VITE_API_BASE_URL` atau `VITE_BASE_PATH` **wajib** diikuti **build ulang**; mengganti file di server tanpa rebuild tidak mengubah nilai yang sudah ter-bundle.
- Jika backend atau CORS berubah domain, periksa juga konfigurasi Laravel `config/cors.php` dan env backend — di luar build frontend tetapi sering dikira masalah “deploy”.
- Folder proyek frontend boleh tetap bernama **`Web Admin`** (spasi diizinkan di Windows). Yang menentukan URL publik adalah **`VITE_BASE_PATH` + Nginx** (`/kelsah-obor-mas/`), bukan nama folder di disk. Path deploy di Linux umumnya tanpa spasi; itu path server, bukan instruksi mengganti nama folder kerja.
