# 🔍 TROUBLESHOOTING GUIDE - Data Tidak Masuk Database

**Issue:** Response 200 OK tapi data tidak masuk ke database

---

## ✅ HASIL DIAGNOSIS

### Test 1: Database Connection - ✅ PASSED
```bash
Records in ketua_ks: 2
```
✅ Database connection OK, table exists, sudah ada data

### Test 2: Model Create - ✅ PASSED
```bash
Success! ID: TEST001
Data: {"ID_KET":"TEST001","NO_AGT":"AGTEST01",...}
```
✅ Model `KetuaKs::create()` berfungsi dengan baik

---

## 🔍 KEMUNGKINAN PENYEBAB

### 1. **Request Body Tidak Sampai ke Controller** ⚠️
**Gejala:** Response 200 tapi data kosong masuk ke database

**Penyebab:**
- Content-Type header salah
- Body request tidak dalam format JSON yang benar
- Middleware mengubah/menghapus request body

**Cek:**
```bash
# Postman Headers:
Content-Type: application/json

# Raw Body (pilih JSON):
{
  "ID_KET": "KET001",
  "NO_AGT": "AGT001",
  ...
}
```

### 2. **Validation Gagal Tapi Tidak Return Error** ⚠️
**Penyebab:** Field yang dikirim tidak sesuai dengan validation rules

**Solusi:** Cek validation di `StoreKetuaKsRequest.php`

---

## 🧪 TESTING STEP-BY-STEP

### Step 1: Test Tanpa Authentication (Public Route)

Tambahkan route testing sementara:

**File:** `routes/api.php`

```php
// Temporary test route (REMOVE after testing)
Route::post('/test-ketua-ks', function(Request $request) {
    \Log::info('Request Data:', $request->all());
    
    try {
        $data = [
            'ID_KET' => $request->ID_KET,
            'NO_AGT' => $request->NO_AGT,
            'NAMA' => $request->NAMA,
            'STAT' => $request->STAT,
            'TGL_STAT' => $request->TGL_STAT,
            'NO_SK' => $request->NO_SK,
        ];
        
        \Log::info('Prepared Data:', $data);
        
        $result = \App\Models\KetuaKs::create($data);
        
        \Log::info('Created Record:', $result->toArray());
        
        return response()->json([
            'success' => true,
            'message' => 'Data inserted successfully',
            'data' => $result
        ], 201);
        
    } catch (\Exception $e) {
        \Log::error('Error:', ['message' => $e->getMessage()]);
        
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});
```

**Test dengan Postman:**
```
POST http://localhost/backend-app/public/api/test-ketua-ks
Content-Type: application/json

{
  "ID_KET": "DEBUG01",
  "NO_AGT": "AGT999",
  "NAMA": "Debug Test",
  "STAT": "AKTIF",
  "TGL_STAT": "2025-01-01",
  "NO_SK": 888
}
```

**Cek Log:**
```bash
tail -f storage/logs/laravel.log
```

---

### Step 2: Test dengan Authentication

**Login dulu:**
```
POST http://localhost/backend-app/public/api/auth/login
Content-Type: application/json

{
  "email": "your@email.com",
  "password": "yourpassword"
}
```

**Simpan token, lalu test:**
```
POST http://localhost/backend-app/public/api/ketua-ks
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "ID_KET": "KET999",
  "NO_AGT": "AGT999",
  "NAMA": "Test Auth",
  "STAT": "AKTIF",
  "TGL_STAT": "2025-01-01",
  "NO_SK": 777
}
```

---

## 🔧 COMMON ISSUES & SOLUTIONS

### Issue 1: Content-Type Header Salah
**Problem:** 
- Postman menggunakan form-data atau x-www-form-urlencoded
- Seharusnya: application/json

**Solution:**
```
Postman → Body → raw → Pilih "JSON" (bukan Text)
Headers otomatis: Content-Type: application/json
```

---

### Issue 2: Authorization Header Format Salah
**Problem:**
- Header: `Authorization: {token}` ❌
- Seharusnya: `Authorization: Bearer {token}` ✅

**Solution:**
```
Postman → Headers:
Key: Authorization
Value: Bearer eyJ0eXAiOiJKV1QiLC...
       ^^^^^^ SPACE after "Bearer"
```

---

### Issue 3: Request Body Structure Salah
**Problem:** JSON tidak valid atau nested

**BAD:**
```json
{
  "data": {
    "ID_KET": "KET001"  // ❌ Nested
  }
}
```

**GOOD:**
```json
{
  "ID_KET": "KET001"  // ✅ Flat structure
}
```

---

### Issue 4: Validation Rules Terlalu Strict
**Problem:** Field yang dikirim tidak sesuai max length

**Check:**
```php
// StoreKetuaKsRequest.php
'ID_KET' => ['required', 'string', 'max:12'],  // Max 12 karakter
'NO_AGT' => ['required', 'string', 'max:15'],  // Max 15 karakter
```

**Solution:** Pastikan data tidak melebihi max length

---

### Issue 5: Primary Key Duplicate
**Problem:** ID_KET sudah ada di database

**Check:**
```bash
php artisan tinker
>>> App\Models\KetuaKs::where('ID_KET', 'KET001')->exists()
```

**Solution:** Gunakan ID yang berbeda atau delete existing record dulu

---

### Issue 6: Database Transaction Rollback
**Problem:** Ada error di middleware atau event listener yang rollback transaction

**Check Log:**
```bash
tail -f storage/logs/laravel.log
```

**Add Debug:**
```php
// In KetuaKsService::create()
public function create(array $data): KetuaKs
{
    \Log::info('Creating KetuaKs with data:', $data);
    
    $result = KetuaKs::create($data);
    
    \Log::info('KetuaKs created:', $result->toArray());
    
    return $result;
}
```

---

## 📋 DEBUG CHECKLIST

### Postman Configuration
- [ ] Method: POST
- [ ] URL: `http://localhost/backend-app/public/api/ketua-ks`
- [ ] Headers → Authorization: `Bearer {token}`
- [ ] Headers → Content-Type: `application/json`
- [ ] Body → raw → JSON (not Text)
- [ ] JSON valid (no trailing comma, proper quotes)

### Request Data
- [ ] ID_KET: max 12 characters
- [ ] NO_AGT: max 15 characters
- [ ] NAMA: max 50 characters
- [ ] STAT: max 50 characters
- [ ] TGL_STAT: max 50 characters (or date format)
- [ ] NO_SK: integer (number, no quotes)

### Database
- [ ] Table `ketua_ks` exists
- [ ] Database connection working
- [ ] No unique constraint violation
- [ ] User has INSERT permission

### Laravel
- [ ] APP_DEBUG=true in .env
- [ ] Log file writable: `storage/logs/laravel.log`
- [ ] Cache cleared: `php artisan cache:clear`
- [ ] Config cached: `php artisan config:cache`

---

## 🚀 QUICK FIX STEPS

### 1. Clear All Caches
```bash
cd /Applications/MAMP/htdocs/backend-app
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 2. Check Logs
```bash
tail -f storage/logs/laravel.log
```

### 3. Test Direct Insert
```bash
php artisan tinker
>>> App\Models\KetuaKs::create(['ID_KET' => 'TEST99', 'NO_AGT' => 'AGT99', 'NAMA' => 'Test'])
```

### 4. Enable Query Log
Add to `app/Providers/AppServiceProvider.php`:

```php
public function boot(): void
{
    if (config('app.debug')) {
        \DB::listen(function($query) {
            \Log::info('SQL: ' . $query->sql);
            \Log::info('Bindings: ' . json_encode($query->bindings));
        });
    }
}
```

Restart server dan cek log saat POST request.

---

## 📊 EXPECTED RESPONSE

### Success Response (201 Created)
```json
{
  "data": {
    "ID_KET": "KET001",
    "NO_AGT": "AGT001",
    "NAMA": "Ahmad Suryanto",
    "STAT": "AKTIF",
    "TGL_STAT": "2025-01-01",
    "NO_SK": 123
  }
}
```

### Error Response (422 Validation)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "ID_KET": [
      "The ID KET field is required."
    ]
  }
}
```

### Error Response (500 Server Error)
```json
{
  "message": "Server Error"
}
```
Check `storage/logs/laravel.log` for details.

---

## 💡 PRO TIPS

1. **Use Postman Console:**
   - View → Show Postman Console
   - See actual HTTP request & response

2. **Check Network Tab:**
   - Browser DevTools → Network
   - Verify request payload

3. **Test with cURL:**
```bash
curl -X POST http://localhost/backend-app/public/api/ketua-ks \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "ID_KET": "CURL001",
    "NO_AGT": "AGT001",
    "NAMA": "Test cURL",
    "STAT": "AKTIF",
    "TGL_STAT": "2025-01-01",
    "NO_SK": 123
  }'
```

4. **Verify Database Directly:**
```sql
SELECT * FROM ketua_ks ORDER BY ID_KET DESC LIMIT 5;
```

---

## ✅ JIKA MASIH TIDAK BERHASIL

Kirimkan informasi berikut:

1. **Postman Request Screenshot**
2. **Postman Response Screenshot**
3. **Laravel Log:** `storage/logs/laravel.log` (last 50 lines)
4. **Database Query:**
```sql
DESCRIBE ketua_ks;
SELECT * FROM ketua_ks;
```

---

**Last Updated:** December 18, 2025  
**Status:** Ready for Debugging
