# Plan: Import Anggota dari Firebird

## Context
- Sistem Laravel saat ini hanya menggunakan MySQL sebagai database utama
- Ada kebutuhan untuk import data anggota dari database Firebird 2.5 eksternal
- Struktur tabel `anggota` identik antara Firebird dan MySQL
- UI frontend perlu fitur: pilih NO_AGT, preview data sebelum import, dan save ke database MySQL
- Tujuan akhir: memungkinkan user import data anggota individual dari Firebird dengan kontrol penuh

## Goal
- Menambahkan koneksi database Firebird ke sistem Laravel
- Membuat endpoint API untuk fetch data anggota dari Firebird berdasarkan NO_AGT
- Mengimplementasi UI preview data sebelum import
- Memastikan import aman dengan validasi dan error handling yang proper
- Menjaga konsistensi data antara Firebird (source) dan MySQL (target)

## Detailed Specifications

### File yang akan diubah/dibuat
- `config/database.php`: Tambah connection configuration untuk Firebird
- `.env`: Tambah environment variables FIREBIRD_*
- `composer.json`: Tambah dependency `jacquestvanzuydam/laravel-firebird`
- `app/Services/FirebirdService.php`: Service class untuk handle koneksi dan query Firebird
- `app/Http/Controllers/Api/AnggotaImportController.php`: Controller untuk handle import logic
- `routes/api.php`: Tambah route `/api/import-anggota-firebird/{noAgt}`
- `app/Http/Requests/ImportAnggotaRequest.php`: Form request untuk validasi import
- `resources/js/components/ImportAnggota.vue`: Frontend component untuk UI import (asumsi Vue.js)

### Function/Method Names
- `FirebirdService::connect()`: Establish connection to Firebird
- `FirebirdService::fetchAnggota(string $noAgt)`: Fetch single anggota from Firebird
- `AnggotaImportController::preview(string $noAgt)`: Get preview data
- `AnggotaImportController::import(ImportAnggotaRequest $request)`: Execute import
- `Anggota::createFromFirebird(array $data)`: Create anggota from Firebird data

### Props, Types, Interfaces
- `NO_AGT`: string(15), required, primary key
- `anggotaData`: array dengan fields: NO_AGT, NAMA, ID_KS, ID_LO, ID_AO, ID_KS_ASL, TGL_MTS, TGL_AKTIF, TGL_JA
- `ImportAnggotaRequest`: extends FormRequest dengan validation rules
- Response format: standard Laravel API response dengan data, message, errors

## Implementation Checklist

1. **Install PHP Extension**: Enable `php_pdo_firebird.dll` di Laragon atau install `php8.2-interbase` di server
2. **Install Composer Package**: `composer require jacquestvanzuydam/laravel-firebird`
3. **Setup Environment Variables**: Tambah FIREBIRD_HOST, FIREBIRD_PORT, FIREBIRD_DATABASE, FIREBIRD_USERNAME, FIREBIRD_PASSWORD di .env
4. **Configure Database Connection**: Tambah 'firebird' connection array di `config/database.php`
5. **Create FirebirdService**: Buat service class untuk handle semua interaksi dengan Firebird database
6. **Create Import Controller**: Buat AnggotaImportController dengan methods preview dan import
7. **Add API Routes**: Tambah protected routes untuk import functionality
8. **Create Form Request**: Buat ImportAnggotaRequest untuk validasi input
9. **Test Firebird Connection**: Buat test route untuk verify koneksi berhasil
10. **Implement Fetch Logic**: Method untuk fetch data dari Firebird berdasarkan NO_AGT
11. **Implement Preview Logic**: Method untuk return data preview tanpa save
12. **Implement Import Logic**: Method untuk validate dan save data ke MySQL
13. **Add Error Handling**: Comprehensive error handling untuk connection failures, validation errors, dll
14. **Create Frontend Component**: Vue component untuk UI import dengan preview
15. **Add Frontend Integration**: Axios calls dan state management untuk import flow
16. **Testing End-to-End**: Unit tests, feature tests, dan manual testing
17. **Documentation Update**: Update API_DOCUMENTATION_COMPLETE.md dengan endpoint baru

## Risks / Catatan

- **Security Risk**: Koneksi ke database eksternal perlu di-secure (VPN, encrypted connection)
- **Performance Impact**: Query ke Firebird jangan mengganggu performance main application
- **Connection Reliability**: Error handling jika Firebird server down atau unreachable
- **Data Consistency**: Validasi bahwa struktur tabel benar-benar identik
- **Duplicate Prevention**: Logic untuk handle jika NO_AGT sudah ada di MySQL
- **Transaction Safety**: Import harus atomic - jika gagal, tidak ada partial data
- **Rate Limiting**: Prevent abuse dengan rate limiting pada import endpoint
- **Logging**: Comprehensive logging untuk audit trail import activities

## Dependencies & Prerequisites

- Firebird 2.5 server accessible via network
- Valid credentials dengan read access ke tabel anggota
- PHP 8.2+ dengan PDO Firebird extension
- Composer untuk package management
- Frontend framework (Vue.js assumed) untuk UI component

## Success Criteria

- ✅ Sistem bisa connect ke Firebird database
- ✅ Endpoint `/api/import-anggota-firebird/{noAgt}` return preview data
- ✅ POST `/api/import-anggota-firebird` berhasil import data ke MySQL
- ✅ UI menampilkan preview data sebelum import
- ✅ Error handling proper untuk semua failure scenarios
- ✅ Data integrity maintained antara source dan target
- ✅ Performance tidak terdegradasi pada main application