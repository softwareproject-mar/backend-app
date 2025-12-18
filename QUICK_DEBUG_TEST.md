# 🔧 QUICK DEBUG TEST - Ketua KS

## Problem: Response 200 OK tapi data tidak masuk database

---

## ✅ STEP 1: Test Debug Route (Tanpa Auth)

### Postman Setup:
```
Method: POST
URL: http://localhost/backend-app/public/api/debug-ketua-ks
Headers: Content-Type: application/json
Body (raw JSON):
```

```json
{
  "ID_KET": "DEBUG01",
  "NO_AGT": "AGTDEBUG",
  "NAMA": "Test Debug",
  "STAT": "AKTIF",
  "TGL_STAT": "2025-01-01",
  "NO_SK": 999
}
```

### Expected Response:
```json
{
  "success": true,
  "message": "Data inserted successfully",
  "data": {
    "ID_KET": "DEBUG01",
    "NO_AGT": "AGTDEBUG",
    "NAMA": "Test Debug",
    "STAT": "AKTIF",
    "TGL_STAT": "2025-01-01",
    "NO_SK": 999
  },
  "request_data": { ... }
}
```

### Check Database:
```bash
cd /Applications/MAMP/htdocs/backend-app
php artisan tinker --execute="echo 'Latest records:' . PHP_EOL; \$records = App\Models\KetuaKs::latest()->take(3)->get(); foreach(\$records as \$r) { echo \$r->ID_KET . ' - ' . \$r->NAMA . PHP_EOL; }"
```

### Check Logs:
```bash
tail -20 storage/logs/laravel.log
```

---

## ✅ STEP 2: Test Real Route (With Auth)

### A. Login First:
```
POST http://localhost/backend-app/public/api/auth/login
Content-Type: application/json

{
  "email": "your@email.com",
  "password": "yourpassword"
}
```

Copy token from response.

### B. Test Create with Auth:
```
Method: POST
URL: http://localhost/backend-app/public/api/ketua-ks
Headers:
  - Authorization: Bearer {paste_token_here}
  - Content-Type: application/json

Body (raw JSON):
```

```json
{
  "ID_KET": "AUTH001",
  "NO_AGT": "AGTAUTH1",
  "NAMA": "Test With Auth",
  "STAT": "AKTIF",
  "TGL_STAT": "2025-01-01",
  "NO_SK": 888
}
```

---

## 🔍 DIAGNOSIS RESULTS

### If Debug Route Works ✅
- Problem is with **Authentication** or **Middleware**
- Check token is valid
- Check Authorization header format: `Bearer {token}` (with space)

### If Debug Route Fails ❌
- Problem is with **Request Body** or **JSON Format**
- Check Content-Type header
- Check JSON is valid (use jsonlint.com)
- Check Postman Body is set to "raw" + "JSON"

### If Both Work But Database Empty 🤔
- Check you're looking at correct database
- Run: `php artisan tinker --execute="DB::connection()->getDatabaseName();"`
- Check database credentials in `.env`

---

## 🚨 COMMON MISTAKES

### 1. Wrong Content-Type
❌ `Content-Type: text/plain`  
❌ `Content-Type: application/x-www-form-urlencoded`  
✅ `Content-Type: application/json`

### 2. Wrong Authorization Format
❌ `Authorization: {token}`  
❌ `Authorization:Bearer{token}` (no space)  
✅ `Authorization: Bearer {token}` (with space)

### 3. Wrong Body Type in Postman
❌ Body → form-data  
❌ Body → x-www-form-urlencoded  
✅ Body → raw → JSON (dropdown)

### 4. Invalid JSON
❌ Trailing comma:
```json
{
  "ID_KET": "KET001",
  "NO_AGT": "AGT001",  // ← trailing comma
}
```

✅ Valid JSON:
```json
{
  "ID_KET": "KET001",
  "NO_AGT": "AGT001"
}
```

### 5. Using String for Integer
❌ `"NO_SK": "123"` (string)  
✅ `"NO_SK": 123` (number, no quotes)

---

## 📊 VERIFICATION COMMANDS

### Check Records Count:
```bash
php artisan tinker --execute="echo App\Models\KetuaKs::count() . ' records';"
```

### Show Latest 5 Records:
```bash
php artisan tinker --execute="App\Models\KetuaKs::latest()->take(5)->get(['ID_KET', 'NAMA'])->each(fn(\$r) => print(\$r->ID_KET . ' - ' . \$r->NAMA . PHP_EOL));"
```

### Delete Test Records:
```bash
php artisan tinker --execute="App\Models\KetuaKs::whereIn('ID_KET', ['DEBUG01', 'AUTH001', 'TEST001'])->delete(); echo 'Deleted test records';"
```

---

## 💡 QUICK FIX CHECKLIST

- [ ] Postman Body type: raw + JSON
- [ ] Content-Type header: application/json
- [ ] Authorization header: Bearer {token} (if auth required)
- [ ] JSON valid (no syntax errors)
- [ ] NO_SK is number (no quotes)
- [ ] Field lengths within limits (ID_KET max 12, etc)
- [ ] Unique constraint: ID_KET not duplicate
- [ ] Database connection working
- [ ] Cache cleared: `php artisan cache:clear`

---

## 🎯 WHAT TO DO NEXT

### If Debug Route Works:
1. ✅ Model OK
2. ✅ Database OK
3. ❌ Problem with auth/middleware
   - Check token validity
   - Try re-login

### If Both Routes Work:
1. ✅ Everything OK
2. Check you're querying correct database
3. Refresh database view
4. Check filters in SELECT query

### If Nothing Works:
Share these outputs:
1. Postman request screenshot
2. Postman response
3. Last 30 lines: `tail -30 storage/logs/laravel.log`
4. Database info: `SHOW TABLES; DESCRIBE ketua_ks;`

---

**Status:** Debug route added ✅  
**Next:** Test with Postman and report results
