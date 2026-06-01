# Plan: Android Export Stability (Excel/PDF)

## Context
- Export Excel/PDF sudah terhubung ke backend di banyak modul.
- Di Android, saat klik export aplikasi force close (kemungkinan besar di jalur binary handling di WebView/native bridge).
- Prioritas saat ini: fitur export **jalan dulu** secara stabil, bukan perfect in-app file processing.

## Goal
- Excel/PDF bisa dipakai di Android tanpa force close.
- Web flow tetap berjalan normal.
- Perubahan minimal, cepat rollout, dan mudah diverifikasi.

## Detailed Specifications
- **File:** `c:\Users\galih\Documents\ui\Kelompok Sahabat Obor Mas\Kelompok Sahabat Obor Mas\src\lib\apiClient.ts`
  - Tambah helper pembuat URL absolut export (`buildApiUrl(path)`).
  - Tambah helper untuk native export fallback yang membuka URL export via browser/download handler eksternal.
  - Gunakan token auth (`auth_token`) via query parameter sementara khusus native external open agar backend tetap authorize.
  - Pertahankan flow existing untuk web.

- **File:** `c:\Users\galih\Documents\ui\Kelompok Sahabat Obor Mas\Kelompok Sahabat Obor Mas\src\services\exportService.ts`
  - Tambah fungsi `triggerNativeExternalExport(url: string): Promise<void>` (gunakan plugin App/Browser sesuai ketersediaan saat implementasi).
  - Pada platform native, hentikan alur `blob -> base64 -> filesystem` untuk export backend; ganti ke external-open flow.
  - Web tetap memakai download blob seperti sekarang.

- **File (opsional refactor kecil):** `c:\Users\galih\Documents\ui\Kelompok Sahabat Obor Mas\Kelompok Sahabat Obor Mas\src/services/*Service.ts`
  - Pastikan semua fungsi export service mengembalikan data yang cukup untuk external-open mode (path endpoint + query), atau tetap `apiFetchBinary` namun expose URL builder dari `apiClient`.
  - Tidak mengubah kontrak fitur selain kebutuhan stabilisasi Android.

- **File (screen layer):**
  - `src/app/components/LODataScreen.tsx`
  - `src/app/components/AODataScreen.tsx`
  - `src/app/components/KetuaKSDataScreen.tsx`
  - `src/app/components/DataPenghasilanScreen.tsx`
  - `src/app/components/JlhKeluargaDataScreen.tsx`
  - `src/app/components/DataKunjunganContent.tsx`
  - `src/app/components/KelompokSahabatScreen.tsx`
  - `src/app/components/DataTrsScreen.tsx`
  - Sesuaikan pemanggilan export agar di native route ke external-open flow, web tetap save blob.
  - Pertahankan UX tombol (disable saat exporting + toast sukses/gagal).

- **File backend (jika diperlukan auth query token):**
  - `c:\laragon\www\backend-app\app\Http\Middleware\ForceJsonResponse.php` (hanya jika perlu bypass kecil untuk route export + query token flow).
  - `c:\laragon\www\backend-app\routes\api.php` (tidak ada perubahan endpoint; hanya verifikasi route export sudah benar).

## Implementation Checklist
1. Tambahkan helper URL builder export di `apiClient.ts` untuk menghasilkan URL absolut endpoint export.
2. Tambahkan helper native external-open export di `exportService.ts`.
3. Ubah `saveBackendExportBlob` agar di native tidak lagi menjalankan pipeline blob/base64/filesystem untuk export backend.
4. Implement query-token strategy pada URL export native external-open agar request tetap authorized.
5. Update layer service export agar bisa memberikan URL/path export yang kompatibel dengan flow external-open.
6. Update semua screen modul export yang sudah di-wire agar memakai flow native external-open + fallback web existing.
7. Tambahkan handling error message konsisten (native open gagal, token tidak ada, URL invalid).
8. Jalankan lint pada seluruh file frontend yang diubah.
9. Build frontend + sync Capacitor (`npm run build` dan `npx cap sync android`) untuk validasi integrasi.
10. Uji manual di Android: LO, AO, Ketua KS, Penghasilan, Jumlah Keluarga, Kunjungan, Kelompok Sahabat, Transaksi (PDF & Excel) dan catat hasil.

## Risks / Catatan
- Menaruh token di query parameter punya trade-off security (log URL/browser history). Ini acceptable untuk stabilization sementara dan perlu hardening lanjutan.
- Perilaku download bergantung browser/download manager device.
- Jika backend tidak menerima auth dari query token, perlu fallback signed temporary export URL dari backend.
