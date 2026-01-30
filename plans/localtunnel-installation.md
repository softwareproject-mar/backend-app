# Plan: Install Localtunnel untuk Laravel (local dev)

## Context
- Project Laravel berada di workspace (Windows). Pengguna ingin mengekspos server lokal untuk testing webhook atau akses publik sementara.
- Tool yang dipilih: localtunnel (npm) — cepat, gratis, cocok untuk pengujian lokal.

## Goal
- Menyediakan langkah terperinci untuk menginstal dan menjalankan `localtunnel`.
- Verifikasi bahwa URL publik mengarah ke aplikasi Laravel lokal.
- Dokumentasi singkat untuk tim.

## Detailed Specifications
- File yang akan dibuat: `plans/localtunnel-installation.md` (dokumen ini).
- Perubahan kode: tidak ada perubahan pada kode Laravel.
- Opsional: menambahkan `npm` script di `package.json` (opsional, hanya dokumentasi):
  - `"tunnel": "lt --port 8000 --subdomain namaAnda"`

## Implementation Checklist
1. Verifikasi environment
   - Jalankan `node -v` dan `npm -v` di PowerShell.
   - Jika belum ada, instal Node.js dari https://nodejs.org/en/download/ (Windows installer).
2. Jalankan Laravel dev server
   - Perintah: `php artisan serve --host=127.0.0.1 --port=8000`.
3. Instalasi localtunnel
   - Global: `npm i -g localtunnel` atau gunakan tanpa install: `npx localtunnel --port 8000`.
4. Jalankan localtunnel
   - Tanpa subdomain: `lt --port 8000`.
   - Dengan subdomain (opsional, jika tersedia): `lt --port 8000 --subdomain namaAnda`.
5. Verifikasi
   - Salin URL publik (mis. `https://abc123.loca.lt`) dan buka di browser.
   - Pastikan halaman utama Laravel dan endpoint API (mis. `/api/...`) responsif.
   - Jika untuk webhook, kirim tes dari penyedia webhook ke URL publik.
6. (Opsional) Tambah npm script
   - Edit `package.json` untuk menambahkan script `tunnel` (opsional, commit jika disetujui tim).
7. Dokumentasi dan pengalihan
   - Simpan instruksi ini di `plans/localtunnel-installation.md`.
   - Tandai task di todo list setelah verifikasi selesai.

## Risks / Catatan
- Subdomain `--subdomain` bersifat availability-dependent; bisa saja sudah dipakai orang lain.
- Localtunnel tidak untuk production; gunakan hanya untuk testing/debug.
- Pastikan firewall Windows tidak memblokir koneksi keluar.
- Jika `php artisan serve` bind ke host berbeda, sesuaikan host/port saat menjalankan tunnel.

---

Jika Anda setuju, saya bisa lanjut ke langkah berikutnya: memeriksa `node`/`npm` di environment Anda dan menjalankan instalasi `localtunnel` sekarang.
