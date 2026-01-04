# Backend Requirement: Auto-Generate ID untuk Master Data

**Tanggal:** 4 Januari 2026  
**Requestor:** Frontend Team  
**Priority:** High  
**Estimasi Impact:** Medium (6 endpoints CRUD)

---

## 1. Context & Business Requirements

### 1.1 Latar Belakang

Saat ini, frontend harus **manual input ID/Primary Key** untuk setiap create data baru pada tabel master. Ini menyebabkan:
- User experience buruk (user harus tahu format ID yang benar)
- Potensi error format ID
- Potensi duplicate ID jika multiple user create bersamaan

### 1.2 Solusi yang Diusulkan

Backend akan **auto-generate ID** dengan format yang sudah ditentukan, sehingga:
- Frontend hanya kirim data tanpa ID
- Backend generate ID otomatis & return ke frontend
- User tidak perlu tahu/input format ID

### 1.3 Format ID yang Digunakan

**Total: 12 digit**

```
[KODE_OBORMAS][KODE_ROLE][RUNNING_NUMBER]
    6 digit      1 digit      5 digit
```

**Contoh:**
```
016005500001
├─ 016005: Kode Obormas (fixed)
├─ 5: Kode Role untuk Data AO
└─ 00001: Running number (00001-99999)
```

### 1.4 Entity yang Terpengaruh

| No | Table/Entity | Kode Role | Primary Key Field | Contoh ID |
|----|--------------|-----------|-------------------|-----------|
| 1 | `ketua_ks` | 1 | `ID_KET` | `016005100001` |
| 2 | `kel_sah` | 2 | `ID_KS` | `016005200001` |
| 3 | `data_lo` | 3 | `ID_LO` | `016005300001` |
| 4 | `sekre_ks` | 4 | `ID_SEKRE` | `016005400001` |
| 5 | `data_ao` | 5 | `NO_AO` | `016005500001` |
| 6 | `data_pengelola` | 6 | `ID_PENG` | `016005600001` |

---

## 2. Technical Requirements

### 2.1 Endpoint Baru: Get Next Available ID

**Tujuan:** Frontend request next available ID sebelum submit form

#### Endpoint Specification

```
GET /api/next-id/{entity_type}
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Path Parameters:**
- `entity_type`: Nama entity (ketua-ks, kel-sah, data-lo, sekre-ks, data-ao, data-pengelola)

**Query Parameters (Optional):**
- `reserve`: boolean (default: false) - Jika true, ID akan di-reserve untuk mencegah duplicate

**Example Request:**
```http
GET /api/next-id/data-ao?reserve=true
Authorization: Bearer 1|abcdefgh...
```

**Success Response (200 OK):**
```json
{
  "entity_type": "data-ao",
  "next_id": "016005500043",
  "components": {
    "kode_obormas": "016005",
    "kode_role": "5",
    "running_number": "00043"
  },
  "reserved_until": "2026-01-04T10:35:00Z",
  "message": "ID reserved for 5 minutes"
}
```

**Error Response (400 Bad Request):**
```json
{
  "error": "Invalid entity type",
  "message": "Entity type 'invalid-entity' is not supported",
  "supported_types": [
    "ketua-ks",
    "kel-sah",
    "data-lo",
    "sekre-ks",
    "data-ao",
    "data-pengelola"
  ]
}
```

---

### 2.2 Modify Existing CRUD Endpoints

#### A. CREATE Endpoints - Make ID Optional

**Current Behavior:**
```json
POST /api/data-ao
{
  "NO_AO": "016005500001",  // REQUIRED - User harus input
  "NAMA_AO": "Kantor Pusat",
  "ALAMAT": "Jl. Sudirman"
}
```

**New Behavior:**
```json
POST /api/data-ao
{
  // NO_AO OPTIONAL - Backend auto-generate jika kosong
  "NAMA_AO": "Kantor Pusat",
  "ALAMAT": "Jl. Sudirman"
}

Response:
{
  "NO_AO": "016005500043",  // Backend generated
  "NAMA_AO": "Kantor Pusat",
  "ALAMAT": "Jl. Sudirman",
  "created_at": "2026-01-04T10:30:00Z",
  "updated_at": "2026-01-04T10:30:00Z"
}
```

**Validation Rules:**
1. Jika ID provided:
   - Validate format (12 digit)
   - Validate structure (6+1+5)
   - Check uniqueness
   - Jika duplicate → reject dengan 422
2. Jika ID not provided:
   - Auto-generate dengan format yang benar
   - Ensure uniqueness (dengan DB transaction)
   - Return generated ID di response

#### B. Endpoints yang Perlu Dimodifikasi

| Endpoint | ID Field | Changes |
|----------|----------|---------|
| `POST /ketua-ks` | `ID_KET` | Make optional, auto-generate |
| `POST /kel-sah` | `ID_KS` | Make optional, auto-generate |
| `POST /data-lo` | `ID_LO` | Make optional, auto-generate |
| `POST /sekretaris-ks` | `ID_SEKRE` | Make optional, auto-generate |
| `POST /data-ao` | `NO_AO` | Make optional, auto-generate |
| `POST /data-pengelola` | `ID_PENG` | Make optional, auto-generate |

---

## 3. Implementation Details

### 3.1 Database Changes (Optional - Recommended)

**Tabel baru untuk tracking running number:**

```sql
CREATE TABLE id_sequences (
    entity_type VARCHAR(50) PRIMARY KEY,
    kode_role CHAR(1) NOT NULL,
    last_number INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_entity_type (entity_type)
);

-- Initial data
INSERT INTO id_sequences (entity_type, kode_role, last_number) VALUES
('ketua-ks', '1', 0),
('kel-sah', '2', 0),
('data-lo', '3', 0),
('sekre-ks', '4', 0),
('data-ao', '5', 0),
('data-pengelola', '6', 0);
```

**Tabel untuk ID reservation (mencegah duplicate pada concurrent request):**

```sql
CREATE TABLE id_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    reserved_id VARCHAR(12) NOT NULL,
    reserved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    INDEX idx_entity_reserved (entity_type, reserved_id),
    INDEX idx_expires (expires_at)
);
```

### 3.2 Algorithm Untuk Generate ID

```php
function generateNextId(string $entityType): string 
{
    DB::beginTransaction();
    
    try {
        // 1. Get kode role
        $sequence = DB::table('id_sequences')
            ->where('entity_type', $entityType)
            ->lockForUpdate()  // Row-level lock
            ->first();
        
        if (!$sequence) {
            throw new Exception("Invalid entity type: {$entityType}");
        }
        
        // 2. Increment running number
        $nextNumber = $sequence->last_number + 1;
        
        // 3. Check max limit
        if ($nextNumber > 99999) {
            throw new Exception("Maximum ID limit reached for {$entityType}");
        }
        
        // 4. Format running number (5 digit dengan leading zero)
        $formattedNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        
        // 5. Compose ID
        $kodeObormas = config('app.kode_obormas', '016005');
        $newId = $kodeObormas . $sequence->kode_role . $formattedNumber;
        
        // 6. Update sequence
        DB::table('id_sequences')
            ->where('entity_type', $entityType)
            ->update([
                'last_number' => $nextNumber,
                'updated_at' => now()
            ]);
        
        DB::commit();
        
        return $newId;
        
    } catch (Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

### 3.3 Algorithm Untuk Reserve ID (Optional - Untuk Prevent Race Condition)

```php
function reserveNextId(string $entityType, int $durationMinutes = 5): array
{
    // Generate next ID
    $nextId = $this->generateNextId($entityType);
    
    // Reserve it
    DB::table('id_reservations')->insert([
        'entity_type' => $entityType,
        'reserved_id' => $nextId,
        'reserved_at' => now(),
        'expires_at' => now()->addMinutes($durationMinutes),
        'used' => false
    ]);
    
    return [
        'next_id' => $nextId,
        'reserved_until' => now()->addMinutes($durationMinutes)->toIso8601String()
    ];
}

function validateReservedId(string $entityType, string $id): bool
{
    $reservation = DB::table('id_reservations')
        ->where('entity_type', $entityType)
        ->where('reserved_id', $id)
        ->where('used', false)
        ->where('expires_at', '>', now())
        ->first();
    
    if ($reservation) {
        // Mark as used
        DB::table('id_reservations')
            ->where('id', $reservation->id)
            ->update(['used' => true]);
        return true;
    }
    
    return false;
}
```

---

## 4. Configuration

### 4.1 Environment Variables

Tambahkan ke `.env`:

```env
# Kode Obormas (6 digit)
KODE_OBORMAS=016005

# ID Reservation Duration (minutes)
ID_RESERVATION_DURATION=5
```

### 4.2 Config File

Buat file `config/id_generator.php`:

```php
<?php

return [
    'kode_obormas' => env('KODE_OBORMAS', '016005'),
    
    'entity_mappings' => [
        'ketua-ks' => [
            'kode_role' => '1',
            'table' => 'ketua_ks',
            'id_field' => 'ID_KET'
        ],
        'kel-sah' => [
            'kode_role' => '2',
            'table' => 'kel_sah',
            'id_field' => 'ID_KS'
        ],
        'data-lo' => [
            'kode_role' => '3',
            'table' => 'data_lo',
            'id_field' => 'ID_LO'
        ],
        'sekre-ks' => [
            'kode_role' => '4',
            'table' => 'sekre_ks',
            'id_field' => 'ID_SEKRE'
        ],
        'data-ao' => [
            'kode_role' => '5',
            'table' => 'data_ao',
            'id_field' => 'NO_AO'
        ],
        'data-pengelola' => [
            'kode_role' => '6',
            'table' => 'data_pengelola',
            'id_field' => 'ID_PENG'
        ],
    ],
    
    'reservation_duration' => env('ID_RESERVATION_DURATION', 5), // minutes
];
```

---

## 5. Error Handling

### 5.1 Error Scenarios & Responses

#### Scenario 1: Invalid Entity Type

**Request:**
```http
GET /api/next-id/invalid-entity
```

**Response (400):**
```json
{
  "error": "InvalidEntityType",
  "message": "Entity type 'invalid-entity' is not supported",
  "supported_types": ["ketua-ks", "kel-sah", "data-lo", "sekre-ks", "data-ao", "data-pengelola"]
}
```

#### Scenario 2: Maximum ID Limit Reached

**Response (422):**
```json
{
  "error": "MaximumIdLimitReached",
  "message": "Maximum ID limit (99999) has been reached for entity 'data-ao'",
  "current_count": 99999,
  "suggestion": "Contact administrator to reset sequence or use different entity structure"
}
```

#### Scenario 3: Duplicate ID (Manual Input)

**Request:**
```json
POST /api/data-ao
{
  "NO_AO": "016005500042",  // Already exists
  "NAMA_AO": "Test"
}
```

**Response (422):**
```json
{
  "error": "DuplicateId",
  "message": "The provided ID already exists",
  "field": "NO_AO",
  "value": "016005500042",
  "suggestion": "Remove the ID field to auto-generate a new one"
}
```

#### Scenario 4: Invalid ID Format (Manual Input)

**Request:**
```json
POST /api/data-ao
{
  "NO_AO": "12345",  // Invalid format
  "NAMA_AO": "Test"
}
```

**Response (422):**
```json
{
  "error": "InvalidIdFormat",
  "message": "ID must be 12 digits with format: OOOOOORRRRRR (O=Obormas, R=Role, N=Number)",
  "field": "NO_AO",
  "value": "12345",
  "expected_format": "016005500001",
  "suggestion": "Remove the ID field to auto-generate a new one"
}
```

---

## 6. Testing Requirements

### 6.1 Unit Tests

```php
// Test 1: Generate ID dengan format yang benar
public function test_generate_id_with_correct_format()
{
    $id = IdGenerator::generate('data-ao');
    
    $this->assertEquals(12, strlen($id));
    $this->assertStringStartsWith('016005', $id);
    $this->assertEquals('5', substr($id, 6, 1)); // Kode role
}

// Test 2: Sequential running number
public function test_sequential_running_number()
{
    $id1 = IdGenerator::generate('data-ao');
    $id2 = IdGenerator::generate('data-ao');
    
    $num1 = intval(substr($id1, 7));
    $num2 = intval(substr($id2, 7));
    
    $this->assertEquals($num1 + 1, $num2);
}

// Test 3: Concurrent requests tidak duplicate
public function test_concurrent_requests_no_duplicate()
{
    // Simulate 10 concurrent requests
    $ids = [];
    for ($i = 0; $i < 10; $i++) {
        $ids[] = IdGenerator::generate('data-ao');
    }
    
    $uniqueIds = array_unique($ids);
    $this->assertCount(10, $uniqueIds);
}

// Test 4: Maximum limit error
public function test_maximum_limit_error()
{
    // Set sequence to 99999
    DB::table('id_sequences')
        ->where('entity_type', 'data-ao')
        ->update(['last_number' => 99999]);
    
    $this->expectException(MaximumIdLimitException::class);
    IdGenerator::generate('data-ao');
}
```

### 6.2 Integration Tests

```php
// Test 1: POST tanpa ID → auto-generate
public function test_post_without_id_auto_generates()
{
    $response = $this->postJson('/api/data-ao', [
        'NAMA_AO' => 'Test Kantor',
        'ALAMAT' => 'Jl. Test'
    ]);
    
    $response->assertStatus(201);
    $response->assertJsonStructure(['NO_AO']);
    
    $id = $response->json('NO_AO');
    $this->assertEquals(12, strlen($id));
}

// Test 2: POST dengan ID valid → accept
public function test_post_with_valid_id_accepts()
{
    $response = $this->postJson('/api/data-ao', [
        'NO_AO' => '016005599999',
        'NAMA_AO' => 'Test'
    ]);
    
    $response->assertStatus(201);
    $response->assertJson(['NO_AO' => '016005599999']);
}

// Test 3: POST dengan ID duplicate → reject
public function test_post_with_duplicate_id_rejects()
{
    // Create first record
    $this->postJson('/api/data-ao', [
        'NO_AO' => '016005500001',
        'NAMA_AO' => 'First'
    ]);
    
    // Try to create second with same ID
    $response = $this->postJson('/api/data-ao', [
        'NO_AO' => '016005500001',
        'NAMA_AO' => 'Second'
    ]);
    
    $response->assertStatus(422);
    $response->assertJsonFragment(['error' => 'DuplicateId']);
}
```

---

## 7. Migration Strategy

### 7.1 Untuk Data Existing

Jika sudah ada data dengan ID manual, perlu migration untuk sync running number:

```php
// Migration: Sync existing data
public function syncExistingData()
{
    $entities = ['ketua-ks', 'kel-sah', 'data-lo', 'sekre-ks', 'data-ao', 'data-pengelola'];
    
    foreach ($entities as $entity) {
        $mapping = config("id_generator.entity_mappings.{$entity}");
        
        // Get max running number dari data existing
        $maxId = DB::table($mapping['table'])
            ->max($mapping['id_field']);
        
        if ($maxId) {
            // Extract running number (5 digit terakhir)
            $maxNumber = intval(substr($maxId, 7));
            
            // Update sequence
            DB::table('id_sequences')
                ->where('entity_type', $entity)
                ->update(['last_number' => $maxNumber]);
        }
    }
}
```

### 7.2 Backward Compatibility

**Phase 1: Soft Launch (1-2 minggu)**
- Endpoint `/next-id` tersedia
- POST masih accept ID manual (untuk testing)
- Monitor error rate & duplicate cases

**Phase 2: Full Implementation**
- Frontend sudah pakai auto-generate
- POST masih accept ID manual (untuk backward compatibility)
- Add warning log jika ada manual ID

**Phase 3: Deprecation (Optional)**
- POST reject manual ID
- Force auto-generate untuk semua request

---

## 8. Performance Considerations

### 8.1 Database Indexing

Pastikan index untuk:
```sql
-- Primary key sudah auto-indexed
-- Tambahan untuk performance:
CREATE INDEX idx_entity_type ON id_sequences(entity_type);
CREATE INDEX idx_reservation_lookup ON id_reservations(entity_type, reserved_id, used, expires_at);
```

### 8.2 Cleanup Job untuk Expired Reservations

```php
// Scheduled job (cron) - jalankan setiap 1 jam
Schedule::command('id-reservations:cleanup')->hourly();

// Command
public function cleanupExpiredReservations()
{
    $deleted = DB::table('id_reservations')
        ->where('expires_at', '<', now())
        ->orWhere('used', true)
        ->delete();
    
    $this->info("Deleted {$deleted} expired/used reservations");
}
```

### 8.3 Expected Performance

- **Generate ID:** < 100ms (dengan DB lock)
- **Concurrent requests:** Handle up to 100 req/s (dengan proper indexing)
- **Reservation table growth:** ~500 MB per 1 million reservations

---

## 9. API Documentation Update

### 9.1 Tambahkan ke Postman/Swagger

**Endpoint baru:**
```yaml
/api/next-id/{entity_type}:
  get:
    summary: Get next available ID for entity
    parameters:
      - name: entity_type
        in: path
        required: true
        schema:
          type: string
          enum: [ketua-ks, kel-sah, data-lo, sekre-ks, data-ao, data-pengelola]
      - name: reserve
        in: query
        required: false
        schema:
          type: boolean
          default: false
    responses:
      200:
        description: Next ID generated successfully
      400:
        description: Invalid entity type
```

**Update existing POST endpoints:**
```yaml
/api/data-ao:
  post:
    summary: Create new Data AO
    requestBody:
      content:
        application/json:
          schema:
            properties:
              NO_AO:
                type: string
                maxLength: 12
                description: Optional - Auto-generated if not provided
                example: "016005500001"
              NAMA_AO:
                type: string
```

---

## 10. Deliverables Checklist

### 10.1 Backend Tasks

- [ ] Create migration untuk tabel `id_sequences`
- [ ] Create migration untuk tabel `id_reservations` (optional)
- [ ] Create `IdGenerator` service class
- [ ] Create `GET /api/next-id/{entity_type}` endpoint
- [ ] Modify 6 POST endpoints untuk support auto-generate
- [ ] Add validation untuk ID format
- [ ] Add unit tests
- [ ] Add integration tests
- [ ] Update API documentation
- [ ] Create cleanup scheduled job
- [ ] Migration script untuk sync existing data

### 10.2 Frontend Tasks (After Backend Ready)

- [ ] Update form screens untuk hide ID field
- [ ] Implement auto-generate ID logic
- [ ] Add error handling untuk duplicate ID
- [ ] Update form validation
- [ ] Testing end-to-end flow

---

## 11. Timeline Estimate

| Phase | Tasks | Duration |
|-------|-------|----------|
| **Phase 1: Database** | Migrations, Config | 1 day |
| **Phase 2: Core Logic** | IdGenerator service, Algorithm | 2 days |
| **Phase 3: Endpoints** | New endpoint + Modify CRUD | 2 days |
| **Phase 4: Testing** | Unit + Integration tests | 2 days |
| **Phase 5: Documentation** | API docs update | 1 day |
| **Phase 6: Deployment** | Migration + Monitoring | 1 day |
| **TOTAL** | | **9 working days** |

---

## 12. Questions & Clarifications Needed

### From Backend Team to Requester:

1. **Kode Obormas Value:**
   - Apakah benar `016005`?
   - Atau ada nilai lain?

2. **ID Reservation:**
   - Apakah perlu implement reservation mechanism?
   - Atau cukup simple generate saat POST?

3. **Maximum ID Limit:**
   - Apa yang terjadi setelah 99999?
   - Reset ke 00001?
   - Atau error?

4. **Data Migration:**
   - Apakah ada data existing yang perlu di-sync?
   - Berapa jumlah record per entity?

5. **Backward Compatibility:**
   - Apakah perlu support manual ID input?
   - Atau force auto-generate untuk semua request baru?

---

## 13. Contact & Support

**Requester:** Frontend Team  
**Backend PIC:** [To be assigned]  
**Review Date:** [To be scheduled]  

---

**Document Version:** 1.0  
**Last Updated:** 4 Januari 2026
