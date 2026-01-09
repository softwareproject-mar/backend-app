# Plan: Activity Log Implementation

## Context
Backend saat ini **tidak memiliki mekanisme logging** untuk operasi CRUD. Tidak ada tracking:
- Siapa yang melakukan aksi (create/update/delete)
- Apakah aksi berhasil atau gagal
- Kapan aksi dilakukan
- Error message jika gagal

User butuh fitur untuk **melihat riwayat aktivitas CRUD mereka sendiri** dengan filter per user yang login.

## Goal
1. Mencatat **semua operasi CRUD** (create, update, delete) dengan status success/failed
2. Simpan log ke **database table `activity_logs`** (unlimited, permanent storage)
3. User **hanya bisa lihat riwayat aktivitas sendiri** (filtered by `user_id`)
4. Provide API endpoint untuk fetch activity logs dengan pagination & filter

## Technical Approach
Menggunakan **Hybrid Trait Pattern** (OPSI 6 dari INNOVATE mode):
- Buat `Trait LogsActivity` dengan helper method `performWithLog()`
- Auto try-catch untuk capture success/failed status
- Manual call di setiap service method (readable & maintainable)
- Centralized logic di trait untuk easy maintenance

## Detailed Specifications

### 1. Database Migration
**File:** `database/migrations/2026_01_10_000001_create_activity_logs_table.php`

**Schema:**
```php
Schema::create('activity_logs', function (Blueprint $table) {
    $table->id();
    
    // User Information
    $table->unsignedBigInteger('user_id');
    $table->string('user_name');
    
    // Activity Details
    $table->string('resource_type', 100);      // 'anggota', 'data_kunjungan', dll
    $table->string('resource_id')->nullable(); // ID dari resource
    $table->enum('action_type', ['create', 'update', 'delete']);
    
    // Description & Status
    $table->text('description');
    $table->enum('status', ['success', 'failed'])->default('success');
    $table->text('error_message')->nullable();
    
    // Data Snapshot (JSON)
    $table->json('old_data')->nullable();
    $table->json('new_data')->nullable();
    
    // Metadata
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    
    // Timestamp
    $table->timestamp('created_at')->useCurrent();
    
    // Indexes
    $table->index('user_id');
    $table->index(['resource_type', 'resource_id']);
    $table->index('action_type');
    $table->index('status');
    $table->index('created_at');
    
    // Foreign Key
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
});
```

### 2. Model ActivityLog
**File:** `app/Models/ActivityLog.php`

**Properties:**
- `$fillable`: semua field kecuali `id` dan `created_at`
- `$casts`: `old_data` dan `new_data` as `array`, `created_at` as `datetime`
- `const UPDATED_AT = null` (hanya pakai created_at)
- Relationship: `belongsTo(User::class)`

### 3. Trait LogsActivity
**File:** `app/Traits/LogsActivity.php`

**Methods:**
```php
protected function performWithLog(
    string $action,           // 'create', 'update', 'delete'
    callable $operation,      // The actual CRUD operation
    array $context            // ['resource_type', 'resource_id', 'description', 'data']
)
```

**Logic:**
- Try-catch wrapper
- On success: create ActivityLog with `status = 'success'`
- On exception: create ActivityLog with `status = 'failed'`, capture error message
- Always re-throw exception (don't swallow errors)
- Auto-capture: `user_id`, `user_name`, `ip_address`, `user_agent`

### 4. Update Service Classes
**Files to modify (11 services):**
1. `app/Services/AnggotaService.php`
2. `app/Services/KelSahService.php`
3. `app/Services/DataKunjunganService.php`
4. `app/Services/DataLoService.php`
5. `app/Services/DataAoService.php`
6. `app/Services/KetuaKsService.php`
7. `app/Services/SekretarisKsService.php`
8. `app/Services/DataPenghasilanService.php`
9. `app/Services/DataPengelolaService.php`
10. `app/Services/DataJlhKeluargaService.php`
11. `app/Services/DataTrsService.php` (read-only, skip logging)

**Changes per service:**
- Add `use LogsActivity;` trait
- Wrap `create()` method with `performWithLog()`
- Wrap `update()` method with `performWithLog()` (include old_data)
- Wrap `delete()` method with `performWithLog()` (include old_data)

**Example for AnggotaService:**
```php
use App\Traits\LogsActivity;

class AnggotaService
{
    use LogsActivity;
    
    public function create(array $data): Anggota
    {
        return $this->performWithLog('create', function() use ($data) {
            return Anggota::create($data);
        }, [
            'resource_type' => 'anggota',
            'resource_id' => $data['NO_AGT'] ?? null,
            'description' => "Menambahkan anggota: " . ($data['NAMA'] ?? 'Unknown'),
            'new_data' => $data,
        ]);
    }
    
    public function update(string $id, array $data): Anggota
    {
        $old = $this->find($id);
        
        return $this->performWithLog('update', function() use ($old, $data) {
            $old->update($data);
            return $old->fresh();
        }, [
            'resource_type' => 'anggota',
            'resource_id' => $id,
            'description' => "Mengupdate anggota: " . ($old->NAMA ?? $id),
            'old_data' => $old->toArray(),
            'new_data' => $data,
        ]);
    }
    
    public function delete(string $id): void
    {
        $record = $this->find($id);
        
        $this->performWithLog('delete', function() use ($record) {
            $record->delete();
        }, [
            'resource_type' => 'anggota',
            'resource_id' => $id,
            'description' => "Menghapus anggota: " . ($record->NAMA ?? $id),
            'old_data' => $record->toArray(),
        ]);
    }
}
```

### 5. API Resource
**File:** `app/Http/Resources/ActivityLogResource.php`

**Transform:**
```php
return [
    'id' => $this->id,
    'user_id' => $this->user_id,
    'user_name' => $this->user_name,
    'resource_type' => $this->resource_type,
    'resource_id' => $this->resource_id,
    'action_type' => $this->action_type,
    'description' => $this->description,
    'status' => $this->status,
    'error_message' => $this->error_message,
    'old_data' => $this->old_data,
    'new_data' => $this->new_data,
    'ip_address' => $this->ip_address,
    'created_at' => $this->created_at?->toIso8601String(),
];
```

### 6. Controller
**File:** `app/Http/Controllers/Api/ActivityLogController.php`

**Methods:**
- `index(Request $request)`: List activity logs dengan filter
- `show(int $id)`: Get single activity log

**Authorization Logic:**
- `index()`: Auto filter `WHERE user_id = auth()->id()` (user hanya lihat log sendiri)
- `show($id)`: Check ownership, return 403 jika bukan milik user yang login

**Filters (query params):**
- `resource_type` (optional)
- `action_type` (optional)
- `status` (optional)
- `date_from` (optional)
- `date_to` (optional)
- `search` (optional, search in description)
- `per_page` (default: 10, max: 50)

### 7. Routes
**File:** `routes/api.php`

**Add inside `auth:sanctum` middleware group:**
```php
Route::get('activity-logs', [ActivityLogController::class, 'index']);
Route::get('activity-logs/{id}', [ActivityLogController::class, 'show']);
```

### 8. Resource Type Mapping
**Standardized resource_type values:**
- `anggota` → from AnggotaService
- `kel_sah` → from KelSahService
- `data_kunjungan` → from DataKunjunganService
- `data_lo` → from DataLoService
- `data_ao` → from DataAoService
- `ketua_ks` → from KetuaKsService
- `sekretaris_ks` → from SekretarisKsService
- `data_penghasilan` → from DataPenghasilanService
- `data_pengelola` → from DataPengelolaService
- `data_jlh_keluarga` → from DataJlhKeluargaService

### 9. Description Templates
**Format per action:**
- **Create:** "Menambahkan {resource_name}: {identifier}"
  - Example: "Menambahkan anggota: Siti Nurhaliza"
- **Update:** "Mengupdate {resource_name}: {identifier}"
  - Example: "Mengupdate data kunjungan: KS-001"
- **Delete:** "Menghapus {resource_name}: {identifier}"
  - Example: "Menghapus data LO: LO-123"

**Identifier field mapping:**
- `anggota` → NAMA
- `kel_sah` → ID_KS (no name field)
- `data_kunjungan` → ID_KEL_SAH or KETERANGAN
- `data_lo` → ID_LO (no name field)
- `data_ao` → ID_AO (no name field)
- `ketua_ks` → NAMA
- `sekretaris_ks` → NAMA
- `data_penghasilan` → resource_id only
- `data_pengelola` → NAMA
- `data_jlh_keluarga` → resource_id only

## Implementation Checklist

### Phase 1: Database & Core Components
1. Create migration file `2026_01_10_000001_create_activity_logs_table.php`
2. Create Model `app/Models/ActivityLog.php` with fillable, casts, and relationship
3. Create Trait `app/Traits/LogsActivity.php` with `performWithLog()` method
4. Run migration: `php artisan migrate`

### Phase 2: Update Service Classes (CRUD Logging)
5. Update `app/Services/AnggotaService.php` - add trait, wrap create/update/delete
6. Update `app/Services/KelSahService.php` - add trait, wrap create/update/delete
7. Update `app/Services/DataKunjunganService.php` - add trait, wrap create/update/delete
8. Update `app/Services/DataLoService.php` - add trait, wrap create/update/delete
9. Update `app/Services/DataAoService.php` - add trait, wrap create/update/delete
10. Update `app/Services/KetuaKsService.php` - add trait, wrap create/update/delete
11. Update `app/Services/SekretarisKsService.php` - add trait, wrap create/update/delete
12. Update `app/Services/DataPenghasilanService.php` - add trait, wrap create/update/delete
13. Update `app/Services/DataPengelolaService.php` - add trait, wrap create/update/delete
14. Update `app/Services/DataJlhKeluargaService.php` - add trait, wrap create/update/delete

### Phase 3: API Endpoint
15. Create Resource `app/Http/Resources/ActivityLogResource.php`
16. Create Controller `app/Http/Controllers/Api/ActivityLogController.php` with index() and show()
17. Add routes to `routes/api.php` inside auth:sanctum middleware group
18. Add use statement for ActivityLogController in `routes/api.php`

### Phase 4: Testing & Validation
19. Test create operation (POST /api/anggota) → check activity_logs table
20. Test update operation (PUT /api/anggota/{id}) → verify old_data saved
21. Test delete operation (DELETE /api/anggota/{id}) → verify old_data saved
22. Test failed operation (invalid data) → verify status='failed' and error_message
23. Test GET /api/activity-logs → verify user only sees their own logs
24. Test GET /api/activity-logs with filters (resource_type, action_type, status)
25. Test GET /api/activity-logs/{id} → verify 403 if not owner
26. Test pagination (per_page, page)
27. Verify indexes created (check EXPLAIN query performance)

### Phase 5: Documentation & Cleanup
28. Update API documentation with new endpoints
29. Verify all 10 services properly logging (except DataTrsService - read only)
30. Code review: ensure consistent description format across all services

## Risks / Catatan

### Performance Considerations:
- **Insert overhead**: Setiap CRUD akan ada 1 extra INSERT ke activity_logs
  - Mitigasi: Indexes sudah dibuat, INSERT ke table terpisah (tidak blocking)
- **Storage growth**: Table activity_logs akan grow unlimited
  - Acceptable: User ingin unlimited storage, database modern handle millions of rows
  
### Security:
- **User isolation**: CRITICAL - user hanya boleh lihat log sendiri
  - Implemented via WHERE clause di controller: `->where('user_id', auth()->id())`
- **Authorization check**: show() endpoint harus verify ownership
  - Implemented: 403 Forbidden jika user coba akses log orang lain

### Error Handling:
- **Transaction consistency**: Jika log gagal, CRUD operation tetap success
  - Acceptable: Log bersifat audit, tidak boleh block business logic
- **Exception propagation**: performWithLog() tetap throw exception setelah log
  - Correct behavior: error tidak di-suppress, Laravel error handler tetap jalan

### Edge Cases:
- **Auth context missing**: Jika somehow auth()->user() null (should not happen in auth:sanctum)
  - Will throw error on `auth()->id()` → acceptable, better than silent failure
- **Circular dependency**: Trait import Model ActivityLog
  - Safe: No circular, trait used by Services, ActivityLog is independent Model
- **Race condition**: Concurrent updates
  - Non-issue: Each log is independent row, no FK constraint to CRUD tables

### Maintenance:
- **New resource added**: Developer harus remember add logging
  - Mitigasi: Document in PROJECT_RULES.md, code review checklist
- **Description consistency**: Developer harus follow template
  - Mitigasi: Examples in this plan, trait could enforce format (future improvement)

### Trade-offs Accepted:
- **Manual call**: `performWithLog()` harus dipanggil manual (not fully automatic)
  - Acceptable: Trade-off for flexibility & readability
- **Closure overhead**: Minor performance cost
  - Negligible: Modern PHP handles closures efficiently
- **Code duplication**: Similar code in 10 services
  - Acceptable: Template-based, easy to copy-paste, centralized logic in trait

## Estimated Time
- Phase 1 (Database & Core): **2 hours**
- Phase 2 (Update Services): **4 hours** (15-20 min per service × 10)
- Phase 3 (API Endpoint): **1.5 hours**
- Phase 4 (Testing): **2 hours**
- Phase 5 (Documentation): **0.5 hours**

**Total: ~10 hours (1.5 hari kerja)**
