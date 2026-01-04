# CORS Configuration - Backend API

## ✅ Masalah CORS Sudah Fixed!

Backend API sekarang sudah support **Cross-Origin Resource Sharing (CORS)** untuk frontend.

## Konfigurasi CORS yang Aktif

### File: `config/cors.php`

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],           // Allow semua HTTP methods (GET, POST, PUT, DELETE, dll)
'allowed_origins' => ['*'],           // Allow semua origin (termasuk localhost, 192.168.x.x, dll)
'allowed_headers' => ['*'],           // Allow semua headers
'exposed_headers' => ['Authorization'], // Expose Authorization header ke frontend
'supports_credentials' => true,       // Support cookies & credentials untuk Sanctum
```

## Test Results

### ✅ CORS Preflight (OPTIONS)
```
Status: 204 No Content
Headers:
  Access-Control-Allow-Origin: http://localhost:3000
  Access-Control-Allow-Credentials: true
  Access-Control-Allow-Methods: POST
  Access-Control-Allow-Headers: content-type
```

### ✅ Actual Request (POST)
```
Status: 200 OK
Headers:
  Access-Control-Allow-Origin: http://localhost:3000
  Access-Control-Allow-Credentials: true
  Access-Control-Expose-Headers: Authorization
```

## Frontend Configuration (Flutter/Dio)

### 1. Dio Setup (Recommended)

```dart
import 'package:dio/dio.dart';

final dio = Dio(
  BaseOptions(
    baseUrl: 'http://192.168.1.10:8000/api',
    connectTimeout: const Duration(seconds: 5),
    receiveTimeout: const Duration(seconds: 3),
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  ),
);

// Enable logging untuk debugging
dio.interceptors.add(LogInterceptor(
  requestBody: true,
  responseBody: true,
  error: true,
));
```

### 2. Login Request Example

```dart
Future<Map<String, dynamic>> login(String email, String password) async {
  try {
    final response = await dio.post(
      '/auth/login',
      data: {
        'email': email,
        'password': password,
      },
    );
    
    return response.data;
  } on DioException catch (e) {
    if (e.response != null) {
      // Server responded with error (4xx, 5xx)
      print('Error Status: ${e.response?.statusCode}');
      print('Error Data: ${e.response?.data}');
      throw Exception(e.response?.data['message'] ?? 'Login failed');
    } else {
      // Connection error, timeout, etc
      print('Connection Error: ${e.message}');
      throw Exception('Cannot connect to server. Please check your connection.');
    }
  }
}
```

## Troubleshooting

### Issue: `DioException [connection error]` dengan `Status Code: null`

**Penyebab:**
- CORS headers tidak ada/salah
- Server tidak running
- Wrong base URL
- Network firewall blocking

**Solusi:**
1. ✅ **CORS sudah fixed** - config sudah benar
2. ✅ Pastikan server running: `php artisan serve --host=192.168.1.10 --port=8000`
3. ✅ Base URL benar: `http://192.168.1.10:8000/api`
4. Check firewall/antivirus tidak block port 8000
5. Test dengan browser dulu: buka `http://192.168.1.10:8000/api/auth/login`

### Issue: Flutter Web CORS di Chrome

Jika masih ada masalah di Chrome development:

**Option 1: Run Chrome dengan CORS disabled (Development only)**
```bash
# Windows
chrome.exe --user-data-dir="C:/Chrome dev session" --disable-web-security

# macOS
open -n -a /Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome --args --user-data-dir="/tmp/chrome_dev_test" --disable-web-security

# Linux
google-chrome --user-data-dir="/tmp/chrome_dev_test" --disable-web-security
```

**Option 2: Use Flutter Web with different port**
```bash
flutter run -d chrome --web-port=3000
```

### Testing dari Browser Console

```javascript
// Test CORS dari browser console
fetch('http://192.168.1.10:8000/api/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    email: 'admin@test.com',
    password: 'password123'
  })
})
.then(res => res.json())
.then(data => console.log('Success:', data))
.catch(err => console.error('Error:', err));
```

## Production Considerations

Untuk production, ubah `allowed_origins` dari `['*']` menjadi domain spesifik:

```php
// config/cors.php
'allowed_origins' => [
    'https://yourdomain.com',
    'https://app.yourdomain.com',
],
```

Atau gunakan environment variable:

```php
'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),
```

Lalu di `.env`:
```
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://app.yourdomain.com
```

## Test Credentials

| Email | Password | Role |
|-------|----------|------|
| galih.ario2014@gmail.com | passwordbaru123. | user |
| admin@test.com | password123 | admin |

## Support

Jika masih ada masalah:
1. Check server logs: `storage/logs/laravel.log`
2. Check browser console untuk error message
3. Verify network tab di browser DevTools
4. Pastikan IP & port correct: `192.168.1.10:8000`
