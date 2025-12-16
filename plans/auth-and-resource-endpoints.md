# Plan: Authentication System + 8 Resource GET Endpoints

## Context

Sistem backend saat ini sudah memiliki:
- Laravel 12 + PHP 8.2+
- MySQL database dengan 21 tables (users, anggota, kel_sah, data_lo, data_ao, realisasi, data_trs, target, data_jlh_keluarga, dll)
- 1 CRUD endpoint untuk `data_kunjungan` (tanpa authentication)
- Model: `User.php` (basic), `DataKunjungan.php`
- Pattern: Controller → Service → Model

Yang belum ada:
- Authentication system (login, register, logout)
- Token-based API protection
- Models untuk 8 tables lainnya
- Controllers untuk 8 resource endpoints
- Services untuk 8 resources
- API Resources untuk response formatting
- Middleware protection untuk routes

## Goal

Menambahkan sistem authentication dan 8 GET endpoints dengan spesifikasi:

1. **Authentication Features:**
   - Register user baru (POST /api/auth/register)
   - Login dengan email/password, return token (POST /api/auth/login)
   - Logout, revoke token (POST /api/auth/logout)
   - Token expire dalam 2 jam
   - Menggunakan Laravel Sanctum

2. **Resource GET Endpoints (Protected):**
   - GET /api/anggota (list + show)
   - GET /api/kel-sah (list + show)
   - GET /api/data-lo (list + show)
   - GET /api/data-ao (list + show)
   - GET /api/realisasi (list + show)
   - GET /api/data-trs (list + show)
   - GET /api/target (list + show)
   - GET /api/data-jlh-keluarga (list + show)

3. **Enhancement:**
   - Protected dengan middleware `auth:sanctum`
   - Response format konsisten (API Resource)
   - Pagination untuk list endpoints (default 15 per page)

## Detailed Specifications

### A. Package & Configuration

#### 1. Install Laravel Sanctum
- **Command:** `composer require laravel/sanctum`
- **Publish config:** `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
- **Run migration:** `php artisan migrate` (untuk table `personal_access_tokens`)

#### 2. Configure Sanctum
- **File:** `config/sanctum.php`
- **Changes:**
  - Set `expiration` => `120` (2 jam dalam menit)
  - Set `middleware` => include `auth:sanctum`

#### 3. Update User Model
- **File:** `app/Models/User.php`
- **Changes:**
  - Add trait: `use Laravel\Sanctum\HasApiTokens;`
  - Update `use` statement dalam class: `use HasApiTokens, HasFactory, Notifiable;`
  - Add to `$fillable`: `'role', 'is_active', 'last_login_at'`
  - Update `casts()` method: add `'last_login_at' => 'datetime', 'is_active' => 'boolean'`

#### 4. Create Migration for Users Table Enhancement
- **File:** `database/migrations/2025_12_16_000000_add_fields_to_users_table.php`
- **Up method:**
  ```php
  Schema::table('users', function (Blueprint $table) {
      $table->string('role')->default('user')->after('email');
      $table->boolean('is_active')->default(true)->after('role');
      $table->timestamp('last_login_at')->nullable()->after('is_active');
  });
  ```
- **Down method:**
  ```php
  Schema::table('users', function (Blueprint $table) {
      $table->dropColumn(['role', 'is_active', 'last_login_at']);
  });
  ```

#### 5. Configure CORS
- **File:** `config/cors.php`
- **Changes:**
  - `paths` => `['api/*']`
  - `allowed_origins` => sesuai frontend domain (atau `['*']` untuk development)
  - `supports_credentials` => `true`

### B. Authentication Implementation

#### 6. Create Auth Controller
- **File:** `app/Http/Controllers/Api/AuthController.php`
- **Methods:**
  - `register(RegisterRequest $request)` – POST /api/auth/register
  - `login(LoginRequest $request)` – POST /api/auth/login
  - `logout(Request $request)` – POST /api/auth/logout
  - `me(Request $request)` – GET /api/auth/me (current user info)

#### 7. Create Auth Requests
- **File 1:** `app/Http/Requests/RegisterRequest.php`
  - Rules:
    - `name` => required, string, max:255
    - `email` => required, email, unique:users,email
    - `password` => required, string, min:8, confirmed
    - `role` => optional, string, in:admin,user (default: user)

- **File 2:** `app/Http/Requests/LoginRequest.php`
  - Rules:
    - `email` => required, email
    - `password` => required, string

#### 8. Create Auth Service
- **File:** `app/Services/AuthService.php`
- **Methods:**
  - `register(array $data): User` – create user dengan hashed password
  - `login(array $credentials): array` – validate credentials, return token + user
  - `logout(User $user): void` – revoke current token

#### 9. Create User Resource
- **File:** `app/Http/Resources/UserResource.php`
- **Fields:**
  - `id`, `name`, `email`, `role`, `is_active`, `last_login_at`, `created_at`
  - TIDAK include: `password`, `remember_token`, `email_verified_at`

### C. Resource Models

#### 10. Create Anggota Model
- **File:** `app/Models/Anggota.php`
- **Config:**
  - `protected $table = 'anggota';`
  - `protected $primaryKey = 'NO_AGT';`
  - `public $incrementing = false;`
  - `protected $keyType = 'string';`
  - `public $timestamps = false;`
  - `protected $fillable = ['NO_AGT', 'NAMA', 'ID_KS', 'ID_LO', 'ID_AO', 'ID_KS_ASL', 'TGL_MTS', 'TGL_AKTIF', 'TGL_JA'];`

#### 11. Create KelSah Model
- **File:** `app/Models/KelSah.php`
- **Config:**
  - `protected $table = 'kel_sah';`
  - `protected $primaryKey = 'ID_KEL';`
  - `public $incrementing = false;`
  - `protected $keyType = 'string';`
  - `public $timestamps = false;`
  - `protected $fillable = ['ID_KEL', 'NAMA_KEL', 'ID_KETUA', 'ID_SEK', 'ID_LO', 'ID_AO', 'ALAMAT', 'STAT', 'TGL_STAT', 'ID_PENGELOLA'];`

#### 12. Create DataLo Model
- **File:** `app/Models/DataLo.php`
- **Config:**
  - `protected $table = 'data_lo';`
  - `protected $primaryKey = 'ID_LO';`
  - `public $incrementing = false;`
  - `protected $keyType = 'string';`
  - `public $timestamps = false;`
  - `protected $fillable = ['ID_LO', 'NO_AGT', 'ID_TP', 'NAMA', 'STAT', 'TGL_STAT'];`

#### 13. Create DataAo Model
- **File:** `app/Models/DataAo.php`
- **Config:**
  - `protected $table = 'data_ao';`
  - `protected $primaryKey = 'ID_AO';`
  - `public $incrementing = false;`
  - `protected $keyType = 'string';`
  - `public $timestamps = false;`
  - `protected $fillable = ['ID_AO', 'NO_AGT', 'NAMA', 'STAT', 'TGL_STAT'];`

#### 14. Create Realisasi Model
- **File:** `app/Models/Realisasi.php`
- **Config:**
  - `protected $table = 'realisasi';`
  - `protected $primaryKey = ['ID_KS', 'TGL_TGT'];` (composite key)
  - `public $incrementing = false;`
  - `public $timestamps = false;`
  - `protected $fillable = ['ID_KS', 'TGL_TGT', 'JLH_AGT_BR', 'STR_SP', 'STR_SW', 'STR_SS', 'STR_SHR', 'STR_SMD', 'STR_SPD', 'STR_SBJ', 'STR_SJP', 'STR_SRY', 'STR_SKA', 'STR_SRI', 'STR_SSD', 'PCR_PJM', 'BNG_PJM', 'ASR_PKK', 'REK_SHR', 'REK_SPD', 'REK_SMD', 'REK_SRY', 'STF_SBJ', 'STF_SJP'];`
  - Override `getKeyName()` method untuk composite key

#### 15. Create DataTrs Model
- **File:** `app/Models/DataTrs.php`
- **Config:**
  - `protected $table = 'data_trs';`
  - `protected $primaryKey = 'NO_AGT';`
  - `public $incrementing = false;`
  - `protected $keyType = 'string';`
  - `public $timestamps = false;`
  - `protected $fillable = ['NO_AGT', 'STR_SP', 'STR_SW', 'STR_SKA', 'STR_SRI', 'STR_SDK', 'STR_PJM', 'STR_BNG', 'PJM_BARU', 'STR_SHR', 'STR_SBJ', 'STR_SJP', 'STR_SPD', 'STR_SRY', 'STR_SMD', 'TGL_LAP'];`

#### 16. Create Target Model
- **File:** `app/Models/Target.php`
- **Config:**
  - `protected $table = 'target';`
  - `protected $primaryKey = ['ID_KS', 'TGL_TGT'];` (composite key)
  - `public $incrementing = false;`
  - `public $timestamps = false;`
  - `protected $fillable = ['ID_KS', 'TGL_TGT', 'JLH_AGT_BR', 'STR_SP', 'SLD_SP', 'STR_SW', 'SLD_SW', 'STR_SS', 'SLD_SS', 'STR_SHR', 'SLD_SHR', 'STR_SMD', 'SLD_SMD', 'STR_SPD', 'SLD_SPD', 'STR_SBJ', 'SLD_SBJ', 'STR_SJP', 'SLD_SJP', 'STR_SRY', 'SLD_SRY', 'STR_SKA', 'SLD_SKA', 'STR_SRI', 'SLD_SRI', 'STR_SSD', 'SLD_SSD', 'PCR_PJM', 'SLD_PJM', 'BNG_PJM', 'SLD_BNG', 'ASR_PKK', 'REK_SHR', 'REK_SPD', 'REK_SMD', 'REK_SRY', 'STF_SBJ', 'STF_SJP', 'JLH_REK', 'JLH_TAB', 'TBN_PK', 'PRC_SHR', 'JLH_TAR_SHR', 'SLD_T_SHR', 'PRC_SMD', 'JLH_TAR_SMD', 'SLD_T_SMD', 'PRC_SPD', 'JLH_TAR_SPD', 'SLD_T_SPD', 'PRC_SRY', 'JLH_TAR_SRY', 'SLD_T_SRY'];`
  - Override `getKeyName()` method untuk composite key

#### 17. Create DataJlhKeluarga Model
- **File:** `app/Models/DataJlhKeluarga.php`
- **Config:**
  - `protected $table = 'data_jlh_keluarga';`
  - `protected $primaryKey = 'NO_AGT';`
  - `public $incrementing = false;`
  - `protected $keyType = 'string';`
  - `public $timestamps = false;`
  - `protected $fillable = ['NO_AGT', 'JLH_AGT_KEL', 'TGL'];`

### D. Resource Services

#### 18. Create AnggotaService
- **File:** `app/Services/AnggotaService.php`
- **Methods:**
  - `paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator`
  - `find(string $id): Anggota`

#### 19. Create KelSahService
- **File:** `app/Services/KelSahService.php`
- **Methods:**
  - `paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator`
  - `find(string $id): KelSah`

#### 20. Create DataLoService
- **File:** `app/Services/DataLoService.php`
- **Methods:**
  - `paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator`
  - `find(string $id): DataLo`

#### 21. Create DataAoService
- **File:** `app/Services/DataAoService.php`
- **Methods:**
  - `paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator`
  - `find(string $id): DataAo`

#### 22. Create RealisasiService
- **File:** `app/Services/RealisasiService.php`
- **Methods:**
  - `paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator`
  - `find(string $idKs, string $tglTgt): Realisasi` (composite key)

#### 23. Create DataTrsService
- **File:** `app/Services/DataTrsService.php`
- **Methods:**
  - `paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator`
  - `find(string $id): DataTrs`

#### 24. Create TargetService
- **File:** `app/Services/TargetService.php`
- **Methods:**
  - `paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator`
  - `find(string $idKs, string $tglTgt): Target` (composite key)

#### 25. Create DataJlhKeluargaService
- **File:** `app/Services/DataJlhKeluargaService.php`
- **Methods:**
  - `paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator`
  - `find(string $id): DataJlhKeluarga`

### E. Resource Controllers

#### 26. Create AnggotaController
- **File:** `app/Http/Controllers/Api/AnggotaController.php`
- **Methods:**
  - `index(Request $request)` – GET /api/anggota (paginated list)
  - `show(string $id)` – GET /api/anggota/{id}

#### 27. Create KelSahController
- **File:** `app/Http/Controllers/Api/KelSahController.php`
- **Methods:**
  - `index(Request $request)` – GET /api/kel-sah (paginated list)
  - `show(string $id)` – GET /api/kel-sah/{id}

#### 28. Create DataLoController
- **File:** `app/Http/Controllers/Api/DataLoController.php`
- **Methods:**
  - `index(Request $request)` – GET /api/data-lo (paginated list)
  - `show(string $id)` – GET /api/data-lo/{id}

#### 29. Create DataAoController
- **File:** `app/Http/Controllers/Api/DataAoController.php`
- **Methods:**
  - `index(Request $request)` – GET /api/data-ao (paginated list)
  - `show(string $id)` – GET /api/data-ao/{id}

#### 30. Create RealisasiController
- **File:** `app/Http/Controllers/Api/RealisasiController.php`
- **Methods:**
  - `index(Request $request)` – GET /api/realisasi (paginated list)
  - `show(Request $request)` – GET /api/realisasi/{idKs}/{tglTgt} (composite key)

#### 31. Create DataTrsController
- **File:** `app/Http/Controllers/Api/DataTrsController.php`
- **Methods:**
  - `index(Request $request)` – GET /api/data-trs (paginated list)
  - `show(string $id)` – GET /api/data-trs/{id}

#### 32. Create TargetController
- **File:** `app/Http/Controllers/Api/TargetController.php`
- **Methods:**
  - `index(Request $request)` – GET /api/target (paginated list)
  - `show(Request $request)` – GET /api/target/{idKs}/{tglTgt} (composite key)

#### 33. Create DataJlhKeluargaController
- **File:** `app/Http/Controllers/Api/DataJlhKeluargaController.php`
- **Methods:**
  - `index(Request $request)` – GET /api/data-jlh-keluarga (paginated list)
  - `show(string $id)` – GET /api/data-jlh-keluarga/{id}

### F. API Resources (Response Formatting)

#### 34. Create AnggotaResource
- **File:** `app/Http/Resources/AnggotaResource.php`
- **Fields:** All fillable fields from model

#### 35. Create KelSahResource
- **File:** `app/Http/Resources/KelSahResource.php`
- **Fields:** All fillable fields from model

#### 36. Create DataLoResource
- **File:** `app/Http/Resources/DataLoResource.php`
- **Fields:** All fillable fields from model

#### 37. Create DataAoResource
- **File:** `app/Http/Resources/DataAoResource.php`
- **Fields:** All fillable fields from model

#### 38. Create RealisasiResource
- **File:** `app/Http/Resources/RealisasiResource.php`
- **Fields:** All fillable fields from model

#### 39. Create DataTrsResource
- **File:** `app/Http/Resources/DataTrsResource.php`
- **Fields:** All fillable fields from model

#### 40. Create TargetResource
- **File:** `app/Http/Resources/TargetResource.php`
- **Fields:** All fillable fields from model

#### 41. Create DataJlhKeluargaResource
- **File:** `app/Http/Resources/DataJlhKeluargaResource.php`
- **Fields:** All fillable fields from model

### G. Routes Configuration

#### 42. Update API Routes
- **File:** `routes/api.php`
- **Changes:**
  - Add auth routes (public)
  - Wrap protected routes dengan `auth:sanctum` middleware
    ```php
    // Public routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        
        // Data Kunjungan (existing)
        Route::apiResource('data-kunjungan', DataKunjunganController::class);
        
        // New resources
        Route::apiResource('anggota', AnggotaController::class)->only(['index', 'show']);
        Route::apiResource('kel-sah', KelSahController::class)->only(['index', 'show']);
        Route::apiResource('data-lo', DataLoController::class)->only(['index', 'show']);
        Route::apiResource('data-ao', DataAoController::class)->only(['index', 'show']);
        Route::apiResource('data-trs', DataTrsController::class)->only(['index', 'show']);
        Route::apiResource('data-jlh-keluarga', DataJlhKeluargaController::class)->only(['index', 'show']);
        
        // Composite key resources (custom routes)
        Route::get('/realisasi', [RealisasiController::class, 'index']);
        Route::get('/realisasi/{idKs}/{tglTgt}', [RealisasiController::class, 'show']);
        Route::get('/target', [TargetController::class, 'index']);
        Route::get('/target/{idKs}/{tglTgt}', [TargetController::class, 'show']);
    });
    ```

### H. Testing & Validation

#### 43. Manual Testing Endpoints
- Test register: POST /api/auth/register
- Test login: POST /api/auth/login (save token)
- Test protected endpoints dengan token di header: `Authorization: Bearer {token}`
- Test logout: POST /api/auth/logout
- Test all GET endpoints dengan pagination: `?page=1&per_page=10`

## Implementation Checklist

### Phase 1: Setup & Configuration (Items 1-5)
1. Install Laravel Sanctum via composer: `composer require laravel/sanctum`
2. Publish Sanctum config: `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
3. Edit `config/sanctum.php` → set `expiration` to 120
4. Create migration file `2025_12_16_000000_add_fields_to_users_table.php` untuk enhance users table
5. Run migration: `php artisan migrate`

### Phase 2: Update User Model (Item 6)
6. Edit `app/Models/User.php` → add HasApiTokens trait, update fillable & casts

### Phase 3: Authentication System (Items 7-9)
7. Create `app/Http/Controllers/Api/AuthController.php` dengan 4 methods (register, login, logout, me)
8. Create `app/Http/Requests/RegisterRequest.php` dengan validation rules
9. Create `app/Http/Requests/LoginRequest.php` dengan validation rules
10. Create `app/Services/AuthService.php` dengan 3 methods (register, login, logout)
11. Create `app/Http/Resources/UserResource.php` untuk format response user

### Phase 4: Resource Models (Items 10-17)
12. Create `app/Models/Anggota.php` dengan config lengkap
13. Create `app/Models/KelSah.php` dengan config lengkap
14. Create `app/Models/DataLo.php` dengan config lengkap
15. Create `app/Models/DataAo.php` dengan config lengkap
16. Create `app/Models/Realisasi.php` dengan composite key config
17. Create `app/Models/DataTrs.php` dengan config lengkap
18. Create `app/Models/Target.php` dengan composite key config
19. Create `app/Models/DataJlhKeluarga.php` dengan config lengkap

### Phase 5: Resource Services (Items 18-25)
20. Create `app/Services/AnggotaService.php` dengan methods paginate & find
21. Create `app/Services/KelSahService.php` dengan methods paginate & find
22. Create `app/Services/DataLoService.php` dengan methods paginate & find
23. Create `app/Services/DataAoService.php` dengan methods paginate & find
24. Create `app/Services/RealisasiService.php` dengan methods paginate & find (composite key)
25. Create `app/Services/DataTrsService.php` dengan methods paginate & find
26. Create `app/Services/TargetService.php` dengan methods paginate & find (composite key)
27. Create `app/Services/DataJlhKeluargaService.php` dengan methods paginate & find

### Phase 6: Resource Controllers (Items 26-33)
28. Create `app/Http/Controllers/Api/AnggotaController.php` dengan index & show
29. Create `app/Http/Controllers/Api/KelSahController.php` dengan index & show
30. Create `app/Http/Controllers/Api/DataLoController.php` dengan index & show
31. Create `app/Http/Controllers/Api/DataAoController.php` dengan index & show
32. Create `app/Http/Controllers/Api/RealisasiController.php` dengan index & show (composite key)
33. Create `app/Http/Controllers/Api/DataTrsController.php` dengan index & show
34. Create `app/Http/Controllers/Api/TargetController.php` dengan index & show (composite key)
35. Create `app/Http/Controllers/Api/DataJlhKeluargaController.php` dengan index & show

### Phase 7: API Resources (Items 34-41)
36. Create `app/Http/Resources/AnggotaResource.php`
37. Create `app/Http/Resources/KelSahResource.php`
38. Create `app/Http/Resources/DataLoResource.php`
39. Create `app/Http/Resources/DataAoResource.php`
40. Create `app/Http/Resources/RealisasiResource.php`
41. Create `app/Http/Resources/DataTrsResource.php`
42. Create `app/Http/Resources/TargetResource.php`
43. Create `app/Http/Resources/DataJlhKeluargaResource.php`

### Phase 8: Routes Configuration (Item 42)
44. Edit `routes/api.php` → add v1 prefix, auth routes, protected group dengan middleware auth:sanctum

### Phase 9: Testing & Validatio
45. Test register endpoint dengan Postman/curl
46. Test login endpoint, save token dari response
47. Test protected endpoints dengan Authorization header
48. Test logout endpoint
49. Test all 8 GET endpoints (index & show) dengan token
50. Test pagination query params untuk list endpoints

## Risks / Catatan

### 🔴 High Priority
- **Composite Keys:** Model `Realisasi` dan `Target` pakai composite primary key → butuh override method `getKeyName()` dan custom query di Service untuk `find()`
- **Token Security:** Pastikan token hanya dikirim via HTTPS di production
- **Rate Limiting:** Login endpoint rentan brute force → perlu throttle middleware
- **CORS:** Pastikan config CORS sesuai domain frontend untuk avoid preflight issues

### 🟡 Medium Priority
- **Naming Convention:** Table & column pakai uppercase (ID_KS, NO_AGT) → unusual tapi legacy, keep as-is
- **No Timestamps:** Semua table tanpa `created_at`/`updated_at` → by design, accepted
- **Performance:** Untuk table besar, consider indexing di migration (sudah ada di existing migrations)
- **Validation:** GET endpoints tanpa Form Request → inline validation di controller acceptable

### 🟢 Low Priority / Future Enhancement
- **Role-based Authorization:** Saat ini semua authenticated user bisa akses semua data → nanti bisa add Policies
- **Soft Delete:** Saat ini hard delete → nanti bisa add SoftDeletes trait
- **API Documentation:** Consider Swagger/OpenAPI spec nanti
- **Unit Tests:** Add tests untuk Service layer di `tests/Unit/`
- **Feature Tests:** Add tests untuk endpoints di `tests/Feature/`

### Trade-offs Accepted
- **Sanctum vs JWT:** Pilih Sanctum (stateful) untuk simplicity → tradeoff: slight DB overhead acceptable
- **No Eager Loading:** Saat ini tidak ada relationship loading → bisa ditambahkan nanti kalau perlu
- **Simple Pagination:** Pakai default Laravel pagination → cukup untuk MVP

## Success Criteria

✅ User bisa register dan login, mendapat token yang expire dalam 2 jam  
✅ Semua protected endpoints tidak bisa diakses tanpa token  
✅ Token valid bisa akses 8 GET endpoints + data-kunjungan  
✅ Response format konsisten menggunakan API Resource  
✅ Pagination work untuk semua list endpoints  
✅ Logout berhasil revoke token  
✅ Semua endpoints accessible via `/api/...` (no versioning)  
