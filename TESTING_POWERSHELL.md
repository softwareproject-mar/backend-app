# Testing API dengan PowerShell

## 1. Test Login (Success)
```powershell
Invoke-WebRequest -Uri "http://192.168.1.10:8000/api/auth/login" `
  -Method POST `
  -Headers @{"Content-Type"="application/json"} `
  -Body '{"email":"admin@test.com","password":"password123"}' `
  -UseBasicParsing
```

## 2. Test Login (Wrong Password)
```powershell
Invoke-WebRequest -Uri "http://192.168.1.10:8000/api/auth/login" `
  -Method POST `
  -Headers @{"Content-Type"="application/json"} `
  -Body '{"email":"admin@test.com","password":"wrongpassword"}' `
  -UseBasicParsing
```

## 3. Test Validation Error
```powershell
Invoke-WebRequest -Uri "http://192.168.1.10:8000/api/auth/login" `
  -Method POST `
  -Headers @{"Content-Type"="application/json"} `
  -Body '{"email":"admin@test.com"}' `
  -UseBasicParsing
```

## 4. Test 404 Not Found
```powershell
Invoke-WebRequest -Uri "http://192.168.1.10:8000/api/endpoint-tidak-ada" `
  -Method GET `
  -UseBasicParsing
```

## 5. Test Unauthenticated
```powershell
Invoke-WebRequest -Uri "http://192.168.1.10:8000/api/auth/me" `
  -Method GET `
  -UseBasicParsing
```

## Dengan Error Handling
```powershell
try {
    $response = Invoke-WebRequest -Uri "http://192.168.1.10:8000/api/auth/login" `
      -Method POST `
      -Headers @{"Content-Type"="application/json"} `
      -Body '{"email":"admin@test.com","password":"password123"}' `
      -UseBasicParsing
    
    Write-Host "✓ Success - Status:" $response.StatusCode
    Write-Host $response.Content
} catch {
    Write-Host "✗ Error - Status:" $_.Exception.Response.StatusCode
    $stream = $_.Exception.Response.GetResponseStream()
    $reader = New-Object System.IO.StreamReader($stream)
    Write-Host $reader.ReadToEnd()
    $reader.Close()
    $stream.Close()
}
```

## Catatan Penting

1. **JANGAN gunakan `curl`** di PowerShell (itu alias untuk Invoke-WebRequest dengan syntax berbeda)
2. **Gunakan `-UseBasicParsing`** untuk menghindari parsing HTML
3. Untuk save response JSON, gunakan:
   ```powershell
   $json = $response.Content | ConvertFrom-Json
   ```

## User Credentials yang Tersedia

| Email | Password | Role |
|-------|----------|------|
| admin@test.com | password123 | admin |
| test@example.com | (unknown) | user |
| galih@gmail.com | (unknown) | user |
| galih.ario2014@gmail.com | (unknown) | user |

## Cara Update Password User Existing

```powershell
php artisan tinker
```

Lalu di tinker:
```php
$user = User::where('email', 'galih.ario2014@gmail.com')->first();
$user->password = Hash::make('passwordbaru123');
$user->save();
exit
```

Atau gunakan script PHP:
```powershell
php update-user-password.php
```
