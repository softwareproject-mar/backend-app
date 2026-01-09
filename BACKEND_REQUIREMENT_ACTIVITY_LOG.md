# Backend Requirement - Activity Log / Riwayat Transaksi

**Tanggal:** 10 Januari 2026  
**Tujuan:** Mencatat semua transaksi CRUD di aplikasi untuk ditampilkan di fitur Riwayat

---

## 1. Database Schema

### Table: `activity_logs`

```sql
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- User Information
    user_id BIGINT UNSIGNED NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    
    -- Activity Details
    resource_type VARCHAR(100) NOT NULL,  -- 'anggota', 'kel_sah', 'data_kunjungan', dll
    resource_id VARCHAR(255),              -- ID dari resource yang di-CRUD
    action_type ENUM('create', 'update', 'delete') NOT NULL,
    
    -- Activity Description
    description TEXT NOT NULL,             -- Deskripsi singkat transaksi
    
    -- Status
    status ENUM('success', 'failed') NOT NULL DEFAULT 'success',
    error_message TEXT NULL,               -- Jika status failed, simpan error message
    
    -- Metadata
    ip_address VARCHAR(45) NULL,           -- IP address user
    user_agent TEXT NULL,                  -- Browser/device info
    
    -- Additional Data (JSON)
    old_data JSON NULL,                    -- Data sebelum perubahan (untuk update/delete)
    new_data JSON NULL,                    -- Data setelah perubahan (untuk create/update)
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_resource (resource_type, resource_id),
    INDEX idx_action_type (action_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    
    -- Foreign Key
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Field Descriptions:

- **user_id**: ID user yang melakukan transaksi (dari token authentication)
- **user_name**: Nama user (untuk redundancy, agar tidak perlu JOIN)
- **resource_type**: Jenis resource yang di-CRUD
  - `anggota`
  - `kel_sah`
  - `data_kunjungan`
  - `data_lo`
  - `data_ao`
  - `ketua_ks`
  - `sekretaris_ks`
  - `data_penghasilan`
  - `data_pengelola`
  - `data_trs`
  - `data_jlh_keluarga`
- **resource_id**: ID dari resource (NO_AGT, ID_KS, NO_URT, dll)
- **action_type**: Jenis aksi (create/update/delete)
- **description**: Deskripsi singkat otomatis generated
  - Contoh: "Menambahkan anggota: Ahmad Rifai"
  - Contoh: "Mengupdate data kunjungan: KS-001"
  - Contoh: "Menghapus data LO: LO-123"
- **status**: Status transaksi (success/failed)
- **error_message**: Pesan error jika status = failed
- **old_data**: Data sebelum perubahan (JSON format)
- **new_data**: Data setelah perubahan (JSON format)

---

## 2. API Endpoints

### 2.1 Get Activity Logs (Paginated)

**Endpoint:** `GET /api/activity-logs`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters:**
```
?page=1                          # Halaman (default: 1)
&per_page=10                     # Jumlah per halaman (default: 10, max: 50)
&resource_type=anggota           # Filter by resource (optional)
&action_type=create              # Filter by action (optional)
&status=success                  # Filter by status (optional)
&user_id=1                       # Filter by user (optional)
&date_from=2026-01-01            # Filter dari tanggal (optional)
&date_to=2026-01-31              # Filter sampai tanggal (optional)
&search=ahmad                    # Search di description/user_name (optional)
```

**Success Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 5,
      "user_name": "Ahmad Rifai",
      "resource_type": "anggota",
      "resource_id": "AGT-001",
      "action_type": "create",
      "description": "Menambahkan anggota: Siti Nurhaliza",
      "status": "success",
      "error_message": null,
      "old_data": null,
      "new_data": {
        "NO_AGT": "AGT-001",
        "NAMA": "Siti Nurhaliza",
        "ID_KS": "KS-001"
      },
      "ip_address": "192.168.1.100",
      "created_at": "2026-01-10T10:30:00.000000Z"
    },
    {
      "id": 2,
      "user_id": 5,
      "user_name": "Ahmad Rifai",
      "resource_type": "data_kunjungan",
      "resource_id": "100",
      "action_type": "update",
      "description": "Mengupdate data kunjungan: Desa Sukamaju",
      "status": "success",
      "error_message": null,
      "old_data": {
        "NO_URT": 100,
        "KETERANGAN": "Kunjungan rutin"
      },
      "new_data": {
        "NO_URT": 100,
        "KETERANGAN": "Kunjungan monitoring"
      },
      "ip_address": "192.168.1.100",
      "created_at": "2026-01-10T09:15:00.000000Z"
    },
    {
      "id": 3,
      "user_id": 5,
      "user_name": "Ahmad Rifai",
      "resource_type": "anggota",
      "resource_id": "AGT-002",
      "action_type": "delete",
      "description": "Menghapus anggota: Budi Santoso",
      "status": "failed",
      "error_message": "Data masih digunakan di transaksi lain",
      "old_data": {
        "NO_AGT": "AGT-002",
        "NAMA": "Budi Santoso"
      },
      "new_data": null,
      "ip_address": "192.168.1.100",
      "created_at": "2026-01-10T08:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 48,
    "from": 1,
    "to": 10
  },
  "links": {
    "first": "http://127.0.0.1:8000/api/activity-logs?page=1",
    "last": "http://127.0.0.1:8000/api/activity-logs?page=5",
    "prev": null,
    "next": "http://127.0.0.1:8000/api/activity-logs?page=2"
  }
}
```

**Error Response (401 Unauthorized):**
```json
{
  "message": "Unauthenticated."
}
```

---

### 2.2 Get Single Activity Log

**Endpoint:** `GET /api/activity-logs/{id}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Success Response (200 OK):**
```json
{
  "data": {
    "id": 1,
    "user_id": 5,
    "user_name": "Ahmad Rifai",
    "resource_type": "anggota",
    "resource_id": "AGT-001",
    "action_type": "create",
    "description": "Menambahkan anggota: Siti Nurhaliza",
    "status": "success",
    "error_message": null,
    "old_data": null,
    "new_data": {
      "NO_AGT": "AGT-001",
      "NAMA": "Siti Nurhaliza",
      "ID_KS": "KS-001"
    },
    "ip_address": "192.168.1.100",
    "user_agent": "Mozilla/5.0...",
    "created_at": "2026-01-10T10:30:00.000000Z"
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "Activity log not found"
}
```

---

### 2.3 Create Activity Log (Internal Use)

**Endpoint:** `POST /api/activity-logs`

**Note:** Endpoint ini untuk **internal use only** (dipanggil oleh backend logic, bukan dari frontend)

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "resource_type": "anggota",
  "resource_id": "AGT-001",
  "action_type": "create",
  "description": "Menambahkan anggota: Siti Nurhaliza",
  "status": "success",
  "error_message": null,
  "old_data": null,
  "new_data": {
    "NO_AGT": "AGT-001",
    "NAMA": "Siti Nurhaliza"
  }
}
```

**Success Response (201 Created):**
```json
{
  "message": "Activity log created successfully",
  "data": {
    "id": 1,
    "user_id": 5,
    "user_name": "Ahmad Rifai",
    "resource_type": "anggota",
    "resource_id": "AGT-001",
    "action_type": "create",
    "description": "Menambahkan anggota: Siti Nurhaliza",
    "status": "success",
    "created_at": "2026-01-10T10:30:00.000000Z"
  }
}
```

---

### 2.4 Delete Old Logs (Maintenance)

**Endpoint:** `DELETE /api/activity-logs/cleanup`

**Purpose:** Hapus log lama untuk menghemat storage (opsional)

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "older_than_days": 90  // Hapus log lebih dari 90 hari
}
```

**Success Response (200 OK):**
```json
{
  "message": "Deleted 1523 old activity logs",
  "deleted_count": 1523
}
```

---

## 3. Backend Implementation Logic

### 3.1 Logging Mechanism

Setiap kali ada operasi CRUD di **semua controller**, backend harus:

1. **Capture request data**
2. **Execute operasi CRUD**
3. **Log hasil ke `activity_logs` table**

### 3.2 Implementation Strategy

**Option A: Middleware Approach (Recommended)**

Buat middleware `LogActivityMiddleware` yang otomatis log setiap request POST/PUT/DELETE.

```php
// app/Http/Middleware/LogActivityMiddleware.php

class LogActivityMiddleware
{
    public function handle($request, Closure $next)
    {
        // Execute the request first
        $response = $next($request);
        
        // If response is successful (2xx), log it
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $this->logActivity($request, $response, 'success');
        } else {
            $this->logActivity($request, $response, 'failed');
        }
        
        return $response;
    }
    
    private function logActivity($request, $response, $status)
    {
        // Determine resource type from URL
        $resourceType = $this->getResourceTypeFromUrl($request->path());
        
        // Determine action type from HTTP method
        $actionType = $this->getActionType($request->method());
        
        // Skip if not CRUD operation
        if (!$actionType) return;
        
        // Create log
        ActivityLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'resource_type' => $resourceType,
            'resource_id' => $this->getResourceId($request),
            'action_type' => $actionType,
            'description' => $this->generateDescription($resourceType, $actionType, $request),
            'status' => $status,
            'error_message' => $status === 'failed' ? $response->getData()->message : null,
            'old_data' => $actionType === 'delete' ? $this->getOldData($request) : null,
            'new_data' => in_array($actionType, ['create', 'update']) ? $request->all() : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
```

**Option B: Trait Approach**

Buat trait `LogsActivity` yang digunakan di semua controller.

```php
// app/Traits/LogsActivity.php

trait LogsActivity
{
    protected function logActivity($resourceType, $resourceId, $actionType, $description, $status = 'success', $errorMessage = null, $oldData = null, $newData = null)
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'action_type' => $actionType,
            'description' => $description,
            'status' => $status,
            'error_message' => $errorMessage,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

// Usage in Controller:
class AnggotaController extends Controller
{
    use LogsActivity;
    
    public function store(Request $request)
    {
        $validated = $request->validate([...]);
        
        try {
            $anggota = Anggota::create($validated);
            
            // Log success
            $this->logActivity(
                'anggota',
                $anggota->NO_AGT,
                'create',
                "Menambahkan anggota: {$anggota->NAMA}",
                'success',
                null,
                null,
                $validated
            );
            
            return response()->json([...], 201);
        } catch (\Exception $e) {
            // Log failure
            $this->logActivity(
                'anggota',
                $request->NO_AGT,
                'create',
                "Gagal menambahkan anggota",
                'failed',
                $e->getMessage(),
                null,
                $validated
            );
            
            return response()->json([...], 500);
        }
    }
}
```

---

## 4. Model & Migration

### 4.1 Model: `ActivityLog`

```php
// app/Models/ActivityLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'resource_type',
        'resource_id',
        'action_type',
        'description',
        'status',
        'error_message',
        'old_data',
        'new_data',
        'ip_address',
        'user_agent',
    ];
    
    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'created_at' => 'datetime',
    ];
    
    // Disable updated_at (tidak perlu)
    const UPDATED_AT = null;
    
    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### 4.2 Migration

```php
// database/migrations/2026_01_10_000000_create_activity_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            
            // User Information
            $table->unsignedBigInteger('user_id');
            $table->string('user_name');
            
            // Activity Details
            $table->string('resource_type', 100);
            $table->string('resource_id')->nullable();
            $table->enum('action_type', ['create', 'update', 'delete']);
            
            // Activity Description
            $table->text('description');
            
            // Status
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->text('error_message')->nullable();
            
            // Metadata
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Additional Data (JSON)
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            
            // Timestamps
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
    }
    
    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};
```

---

## 5. Resource & Controller

### 5.1 API Resource

```php
// app/Http/Resources/ActivityLogResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray($request)
    {
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
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

### 5.2 Controller

```php
// app/Http/Controllers/ActivityLogController.php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Http\Resources\ActivityLogResource;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::query()
            ->orderBy('created_at', 'desc');
        
        // Filters
        if ($request->has('resource_type')) {
            $query->where('resource_type', $request->resource_type);
        }
        
        if ($request->has('action_type')) {
            $query->where('action_type', $request->action_type);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%");
            });
        }
        
        // Pagination
        $perPage = min($request->get('per_page', 10), 50);
        $logs = $query->paginate($perPage);
        
        return ActivityLogResource::collection($logs);
    }
    
    public function show($id)
    {
        $log = ActivityLog::findOrFail($id);
        return new ActivityLogResource($log);
    }
}
```

---

## 6. Routes

```php
// routes/api.php

Route::middleware('auth:sanctum')->group(function () {
    // Activity Logs (Read-Only for mobile app)
    Route::get('activity-logs', [ActivityLogController::class, 'index']);
    Route::get('activity-logs/{id}', [ActivityLogController::class, 'show']);
    
    // For admin only: cleanup old logs
    Route::delete('activity-logs/cleanup', [ActivityLogController::class, 'cleanup'])
        ->middleware('role:admin');
});
```

---

## 7. Auto-Generate Description Logic

### Helper Function

```php
// app/Helpers/ActivityHelper.php

class ActivityHelper
{
    public static function generateDescription($resourceType, $actionType, $data)
    {
        $resourceNames = [
            'anggota' => 'anggota',
            'kel_sah' => 'keluarga sejahtera',
            'data_kunjungan' => 'data kunjungan',
            'data_lo' => 'data LO',
            'data_ao' => 'data AO',
            'ketua_ks' => 'ketua KS',
            'sekretaris_ks' => 'sekretaris KS',
            'data_penghasilan' => 'data penghasilan',
            'data_pengelola' => 'data pengelola',
            'data_trs' => 'data TRS',
            'data_jlh_keluarga' => 'data jumlah keluarga',
        ];
        
        $resourceName = $resourceNames[$resourceType] ?? $resourceType;
        
        switch ($actionType) {
            case 'create':
                $name = self::getNameField($resourceType, $data);
                return "Menambahkan {$resourceName}" . ($name ? ": {$name}" : "");
                
            case 'update':
                $name = self::getNameField($resourceType, $data);
                return "Mengupdate {$resourceName}" . ($name ? ": {$name}" : "");
                
            case 'delete':
                $name = self::getNameField($resourceType, $data);
                return "Menghapus {$resourceName}" . ($name ? ": {$name}" : "");
                
            default:
                return "Aksi tidak dikenal";
        }
    }
    
    private static function getNameField($resourceType, $data)
    {
        $nameFields = [
            'anggota' => 'NAMA',
            'kel_sah' => 'KEPALA_KELUARGA',
            'data_kunjungan' => 'KETERANGAN',
            'data_lo' => 'NAMA_LO',
            'data_ao' => 'NAMA_AO',
            // Add more as needed
        ];
        
        $field = $nameFields[$resourceType] ?? null;
        return $field && isset($data[$field]) ? $data[$field] : null;
    }
}
```

---

## 8. Testing Requirements

### 8.1 Unit Tests

- Test `ActivityLog` model creation
- Test description generator helper
- Test query filters (resource_type, action_type, status, date range)

### 8.2 Integration Tests

- Test logging on successful CRUD operations
- Test logging on failed CRUD operations
- Test pagination response
- Test filter combinations

### 8.3 Example Test

```php
public function test_activity_log_created_on_anggota_creation()
{
    $user = User::factory()->create();
    $this->actingAs($user);
    
    $response = $this->postJson('/api/anggota', [
        'NO_AGT' => 'AGT-001',
        'NAMA' => 'Test User',
        'ID_KS' => 'KS-001',
    ]);
    
    $response->assertStatus(201);
    
    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $user->id,
        'resource_type' => 'anggota',
        'resource_id' => 'AGT-001',
        'action_type' => 'create',
        'status' => 'success',
    ]);
}
```

---

## 9. Performance Considerations

### 9.1 Indexing

Pastikan indexes sudah ada di:
- `user_id` (untuk filter by user)
- `resource_type`, `resource_id` (untuk filter by resource)
- `action_type` (untuk filter by action)
- `status` (untuk filter by status)
- `created_at` (untuk sorting dan date range filter)

### 9.2 Data Retention Policy

Implement automatic cleanup untuk log lama:

```php
// Schedule in app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Delete logs older than 90 days every week
    $schedule->call(function () {
        ActivityLog::where('created_at', '<', now()->subDays(90))->delete();
    })->weekly();
}
```

### 9.3 Query Optimization

- Use eager loading jika perlu user data
- Limit maximum per_page to 50
- Add caching untuk frequently accessed logs

---

## 10. Summary Checklist

Backend developer perlu membuat:

- [ ] Migration untuk table `activity_logs`
- [ ] Model `ActivityLog` dengan proper casts
- [ ] API Resource `ActivityLogResource`
- [ ] Controller `ActivityLogController` dengan index & show methods
- [ ] Routes untuk activity logs endpoints
- [ ] Middleware atau Trait untuk auto-logging CRUD operations
- [ ] Helper function untuk generate description
- [ ] Update semua existing controllers (Anggota, KelSah, DataKunjungan, dll) untuk call logging function
- [ ] Seeder untuk sample data (optional)
- [ ] Unit tests
- [ ] Documentation update

---

## 11. Estimated Development Time

- Database migration & model: **1 jam**
- Controller & API Resource: **2 jam**
- Logging mechanism (Middleware/Trait): **3 jam**
- Update all existing controllers: **4 jam**
- Testing: **2 jam**
- Documentation: **1 jam**

**Total:** ~13 jam (1.5 - 2 hari kerja)

---

## 12. Notes untuk Frontend Developer

Setelah backend ready, frontend akan:

1. Consume endpoint `GET /api/activity-logs?page=1&per_page=10`
2. Display dengan pagination (10 items per page)
3. Filter by status (success/failed)
4. Search functionality
5. Automatic refresh saat ada transaksi baru (optional)

Frontend **TIDAK** perlu call endpoint create activity log - semua otomatis di backend.
