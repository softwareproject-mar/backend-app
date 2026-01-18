# Plan: Firebird Anggota List API

## Context
- Sistem sudah memiliki endpoint import anggota dari Firebird dengan preview single record
- Frontend membutuhkan fitur dropdown untuk memilih NO_AGT dari semua anggota yang tersedia di Firebird
- User ingin UX yang lebih baik: search/filter NO_AGT tanpa perlu hafal kode
- Kebutuhan: endpoint GET /api/import-anggota-firebird (tanpa parameter) untuk list semua NO_AGT + NAMA

## Goal
- Membuat endpoint baru untuk mendapatkan list anggota dari Firebird database
- Mendukung search/filter berdasarkan NO_AGT atau NAMA
- Implementasi pagination jika data banyak
- Response format sederhana untuk populate dropdown frontend
- Error handling yang proper untuk Firebird connection issues

## Detailed Specifications

### File yang akan diubah/dibuat
- `app/Services/FirebirdService.php`: Tambah method `getAnggotaList()` dengan search & pagination
- `app/Http/Controllers/Api/AnggotaImportController.php`: Tambah method `index()` untuk list endpoint
- `routes/api.php`: Tambah route GET `/import-anggota-firebird` (tanpa parameter)
- `app/Http/Requests/ListAnggotaRequest.php`: Form request untuk validasi query parameters

### Function/Method Names
- `FirebirdService::getAnggotaList(array $filters)`: Query list anggota dengan filter
- `AnggotaImportController::index(ListAnggotaRequest $request)`: Handle list endpoint
- `ListAnggotaRequest`: Validasi search, page, per_page parameters

### Props, Types, Interfaces
- `filters`: array dengan keys: 'search', 'page', 'per_page'
- `anggotaList`: Collection of arrays dengan keys: 'NO_AGT', 'NAMA'
- Response format: standard Laravel pagination atau simple list
- Error responses: 401 (unauth), 500 (connection error)

## Implementation Checklist

1. **Create ListAnggotaRequest**: Buat form request untuk validasi query parameters (search, page, per_page)
2. **Add getAnggotaList method to FirebirdService**: Implement method untuk query list dari Firebird dengan search & pagination support
3. **Add index method to AnggotaImportController**: Implement controller method untuk handle GET /import-anggota-firebird
4. **Add route for list endpoint**: Tambah route GET /import-anggota-firebird (tanpa parameter) di routes/api.php
5. **Implement search functionality**: Support LIKE query untuk NO_AGT dan NAMA
6. **Implement pagination logic**: Conditional pagination jika data > 1000 records
7. **Add proper error handling**: Handle Firebird connection errors, query timeouts
8. **Add response formatting**: Return data dalam format yang sesuai untuk dropdown
9. **Test with mock data**: Test endpoint dengan mock data sebelum real Firebird
10. **Test search functionality**: Verify search by NO_AGT and NAMA works
11. **Test pagination**: Test pagination parameters jika diimplementasikan
12. **Add logging**: Log successful queries dan errors
13. **Performance optimization**: Add query limits dan indexing considerations
14. **Update API documentation**: Update API_DOCUMENTATION_COMPLETE.md dengan endpoint baru
15. **Integration testing**: Test dengan frontend untuk dropdown population

## Risks / Catatan

- **Performance Risk**: Query tanpa limit bisa lambat jika Firebird punya jutaan records
- **Connection Risk**: Firebird server bisa down atau timeout
- **Data Volume Risk**: Response size terlalu besar untuk mobile apps
- **Search Performance**: LIKE query di Firebird mungkin lambat tanpa proper indexing
- **Memory Usage**: Loading semua data ke memory bisa bermasalah
- **Security Risk**: Exposing semua NO_AGT bisa jadi information leakage (meskipun internal)
- **Consistency Risk**: Data di Firebird bisa stale atau berbeda dengan MySQL
- **Rate Limiting**: Endpoint ini bisa di-abuse untuk enumeration

## Dependencies & Prerequisites

- FirebirdService sudah ada dengan mock/real connection
- AnggotaImportController sudah ada
- Firebird database accessible
- Table anggota di Firebird punya data
- Frontend siap consume endpoint baru

## Success Criteria

- ✅ GET /api/import-anggota-firebird return list NO_AGT + NAMA dari Firebird
- ✅ Search parameter berfungsi untuk filter data
- ✅ Response format sesuai spesifikasi (data + meta)
- ✅ Error handling proper untuk connection issues
- ✅ Performance acceptable (< 2 detik response time)
- ✅ Frontend bisa populate dropdown dengan data
- ✅ Integration dengan existing preview/import flow works

## Estimated Effort

- Backend implementation: 2-3 jam
- Testing: 1-2 jam
- Documentation: 30 menit
- Total: ~4 jam

## Priority

High - Critical untuk UX improvement di frontend import flow