# Plan: Auto-Generate ID Implementation

**Created:** 4 Januari 2026  
**Feature:** Auto-Generate ID untuk 6 Master Data Entities  
**Approach:** Simple & Pragmatic - DB Sequence Table, No Reservation (Phase 1)

---

## Context

Saat ini semua 6 entity (ketua_ks, kel_sah, data_lo, sekre_ks, data_ao, data_pengelola) memerlukan manual input ID dari frontend. Kita akan implement auto-generate ID dengan format 11 digit yang sudah digunakan: `16005` (5 digit kode obormas) + `X` (1 digit kode role) + `NNNNN` (5 digit running number).

**Data Existing:**
- ketua-ks: 2 records, max ID: 16005100003
- kel-sah: 2 records, max ID: 16005200006
- data-lo: 2 records, max ID: 16005300005
- sekretaris-ks: 2 records, max ID: 16005400005
- data-ao: 2 records, max ID: 16005500002
- data-pengelola: 1 record, max ID: 16005600001

---

## Goal

1. Backend auto-generate ID jika frontend tidak kirim ID
2. Frontend masih bisa kirim ID manual (backward compatible)
3. Validate format ID jika manual input
4. Sequential running number per entity
5. Thread-safe dengan DB transaction

---

## Detailed Specifications

### Architecture Decision

**Format ID:** 11 digit (keep existing format)
- Kode Obormas: `16005` (5 digit)
- Kode Role: `1-6` (1 digit)
- Running Number: `00001-99999` (5 digit)

**Generation Strategy:** DB Sequence Table dengan row-level lock
**Reservation:** No reservation (Phase 1 - simple)
**Service Pattern:** Centralized `IdGeneratorService`

### Files to Create

#### 1. Migration: Create id_sequences Table
**File:** `database/migrations/2026_01_04_000001_create_id_sequences_table.php`

**Schema:**
```sql
CREATE TABLE id_sequences (
    entity_type VARCHAR(50) PRIMARY KEY,
    kode_role CHAR(1) NOT NULL,
    last_number INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_entity_type (entity_type)
);
```

**Initial Data:**
```sql
INSERT INTO id_sequences (entity_type, kode_role, last_number) VALUES
('ketua-ks', '1', 0),
('kel-sah', '2', 0),
('data-lo', '3', 0),
('sekre-ks', '4', 0),
('data-ao', '5', 0),
('data-pengelola', '6', 0);
```

#### 2. Migration: Sync Existing Data
**File:** `database/migrations/2026_01_04_000002_sync_existing_id_sequences.php`

**Logic:**
- Extract max running number dari setiap entity
- Update `last_number` di `id_sequences`
- ketua-ks: 3, kel-sah: 6, data-lo: 5, sekre-ks: 5, data-ao: 2, data-pengelola: 1

#### 3. Config File
**File:** `config/id_generator.php`

**Content:**
```php
return [
    'kode_obormas' => env('KODE_OBORMAS', '16005'),
    
    'entity_mappings' => [
        'ketua-ks' => [
            'kode_role' => '1',
            'table' => 'ketua_ks',
            'id_field' => 'ID_KET',
        ],
        'kel-sah' => [
            'kode_role' => '2',
            'table' => 'kel_sah',
            'id_field' => 'ID_KEL',
        ],
        'data-lo' => [
            'kode_role' => '3',
            'table' => 'data_lo',
            'id_field' => 'ID_LO',
        ],
        'sekre-ks' => [
            'kode_role' => '4',
            'table' => 'sekre_ks',
            'id_field' => 'ID_SEKRE',
        ],
        'data-ao' => [
            'kode_role' => '5',
            'table' => 'data_ao',
            'id_field' => 'ID_AO',
        ],
        'data-pengelola' => [
            'kode_role' => '6',
            'table' => 'data_pengelola',
            'id_field' => 'ID_PENG',
        ],
    ],
];
```

#### 4. Exception Classes
**File:** `app/Exceptions/MaximumIdLimitException.php`
- HTTP Status: 422
- Message: "Maximum ID limit (99999) reached for entity {entity}"

**File:** `app/Exceptions/InvalidEntityTypeException.php`
- HTTP Status: 400
- Message: "Invalid entity type: {entity}"

#### 5. Validation Rule
**File:** `app/Rules/ValidIdFormat.php`

**Logic:**
- Check length = 11 digit
- Check numeric
- Check starts with kode_obormas (16005)
- Check kode_role match entity type
- Check running number 00001-99999

#### 6. Service: IdGeneratorService
**File:** `app/Services/IdGeneratorService.php`

**Methods:**
```php
public function generate(string $entityType): string
public function getEntityConfig(string $entityType): array
protected function getNextRunningNumber(string $entityType): int
protected function formatId(string $kodeObormas, string $kodeRole, int $number): string
public function validateEntityType(string $entityType): void
```

**Core Algorithm:**
```php
public function generate(string $entityType): string
{
    $this->validateEntityType($entityType);
    
    DB::beginTransaction();
    try {
        // Lock row untuk prevent race condition
        $sequence = DB::table('id_sequences')
            ->where('entity_type', $entityType)
            ->lockForUpdate()
            ->first();
        
        // Increment
        $nextNumber = $sequence->last_number + 1;
        
        // Check limit
        if ($nextNumber > 99999) {
            throw new MaximumIdLimitException($entityType);
        }
        
        // Format ID
        $kodeObormas = config('id_generator.kode_obormas');
        $kodeRole = $sequence->kode_role;
        $id = $this->formatId($kodeObormas, $kodeRole, $nextNumber);
        
        // Update sequence
        DB::table('id_sequences')
            ->where('entity_type', $entityType)
            ->update([
                'last_number' => $nextNumber,
                'updated_at' => now(),
            ]);
        
        DB::commit();
        return $id;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

### Files to Modify

#### 7-12. Form Request Classes (6 files)

**Pattern untuk semua:**
- Change ID field dari `required` → `nullable`
- Add `ValidIdFormat` rule jika ID provided
- Add `unique` validation jika ID provided

**Files:**
1. `app/Http/Requests/StoreKetuaKsRequest.php`
   - Field: `ID_KET`
   - Rule: `['nullable', 'string', 'size:11', new ValidIdFormat('ketua-ks'), 'unique:ketua_ks,ID_KET']`

2. `app/Http/Requests/StoreKelSahRequest.php`
   - Field: `ID_KEL`
   - Rule: `['nullable', 'string', 'size:11', new ValidIdFormat('kel-sah'), 'unique:kel_sah,ID_KEL']`

3. `app/Http/Requests/StoreDataLoRequest.php`
   - Field: `ID_LO`
   - Rule: `['nullable', 'string', 'size:11', new ValidIdFormat('data-lo'), 'unique:data_lo,ID_LO']`

4. `app/Http/Requests/StoreSekretarisKsRequest.php`
   - Field: `ID_SEKRE`
   - Rule: `['nullable', 'string', 'size:11', new ValidIdFormat('sekre-ks'), 'unique:sekre_ks,ID_SEKRE']`

5. `app/Http/Requests/StoreDataAoRequest.php`
   - Field: `ID_AO`
   - Rule: `['nullable', 'string', 'size:11', new ValidIdFormat('data-ao'), 'unique:data_ao,ID_AO']`

6. `app/Http/Requests/StoreDataPengelolaRequest.php`
   - Field: `ID_PENG`
   - Rule: `['nullable', 'string', 'size:11', new ValidIdFormat('data-pengelola'), 'unique:data_pengelola,ID_PENG']`

#### 13-18. Service Classes (6 files)

**Pattern untuk semua:**
- Inject `IdGeneratorService` via constructor
- Modify `create()` method: check if ID provided, if not → auto-generate

**Files & Changes:**

1. **`app/Services/KetuaKsService.php`**
```php
// Constructor
public function __construct(
    private IdGeneratorService $idGenerator
) {}

// Modify create method
public function create(array $data): KetuaKs
{
    if (!isset($data['ID_KET']) || empty($data['ID_KET'])) {
        $data['ID_KET'] = $this->idGenerator->generate('ketua-ks');
    }
    return KetuaKs::create($data);
}
```

2. **`app/Services/KelSahService.php`**
```php
// Constructor
public function __construct(
    private IdGeneratorService $idGenerator
) {}

// Modify create method
public function create(array $data): KelSah
{
    if (!isset($data['ID_KEL']) || empty($data['ID_KEL'])) {
        $data['ID_KEL'] = $this->idGenerator->generate('kel-sah');
    }
    return KelSah::create($data);
}
```

3. **`app/Services/DataLoService.php`**
```php
// Constructor
public function __construct(
    private IdGeneratorService $idGenerator
) {}

// Modify create method
public function create(array $data): DataLo
{
    if (!isset($data['ID_LO']) || empty($data['ID_LO'])) {
        $data['ID_LO'] = $this->idGenerator->generate('data-lo');
    }
    return DataLo::create($data);
}
```

4. **`app/Services/SekretarisKsService.php`**
```php
// Constructor
public function __construct(
    private IdGeneratorService $idGenerator
) {}

// Modify create method
public function create(array $data): SekretarisKs
{
    if (!isset($data['ID_SEKRE']) || empty($data['ID_SEKRE'])) {
        $data['ID_SEKRE'] = $this->idGenerator->generate('sekre-ks');
    }
    return SekretarisKs::create($data);
}
```

5. **`app/Services/DataAoService.php`**
```php
// Constructor
public function __construct(
    private IdGeneratorService $idGenerator
) {}

// Modify create method
public function create(array $data): DataAo
{
    if (!isset($data['ID_AO']) || empty($data['ID_AO'])) {
        $data['ID_AO'] = $this->idGenerator->generate('data-ao');
    }
    return DataAo::create($data);
}
```

6. **`app/Services/DataPengelolaService.php`**
```php
// Constructor
public function __construct(
    private IdGeneratorService $idGenerator
) {}

// Modify create method
public function create(array $data): DataPengelola
{
    if (!isset($data['ID_PENG']) || empty($data['ID_PENG'])) {
        $data['ID_PENG'] = $this->idGenerator->generate('data-pengelola');
    }
    return DataPengelola::create($data);
}
```

#### 19. Environment Configuration

**File:** `.env`

**Add:**
```env
# ID Generator Configuration
KODE_OBORMAS=16005
```

### Testing Files to Create

#### 20. Unit Test: IdGeneratorService
**File:** `tests/Unit/IdGeneratorServiceTest.php`

**Test Cases:**
- `test_generate_id_with_correct_format()` - 11 digit, starts with 16005
- `test_generate_id_with_correct_role_code()` - kode role match entity
- `test_sequential_running_number()` - increment by 1
- `test_throws_exception_for_invalid_entity()` - InvalidEntityTypeException
- `test_throws_exception_at_maximum_limit()` - MaximumIdLimitException at 99999
- `test_format_running_number_with_leading_zeros()` - 00001, 00099, 01000

#### 21. Unit Test: ValidIdFormat Rule
**File:** `tests/Unit/Rules/ValidIdFormatTest.php`

**Test Cases:**
- `test_passes_with_valid_id()` - 11 digit valid format
- `test_fails_with_wrong_length()` - not 11 digit
- `test_fails_with_non_numeric()` - contains letters
- `test_fails_with_wrong_kode_obormas()` - not starts with 16005
- `test_fails_with_wrong_kode_role()` - role code mismatch entity

#### 22-27. Feature Tests: POST Endpoints (6 files)

**Pattern untuk semua:**
- Test POST without ID → auto-generate
- Test POST with valid ID → accept
- Test POST with duplicate ID → 422 error
- Test POST with invalid format ID → 422 error

**Files:**
1. `tests/Feature/KetuaKsAutoGenerateTest.php`
2. `tests/Feature/KelSahAutoGenerateTest.php`
3. `tests/Feature/DataLoAutoGenerateTest.php`
4. `tests/Feature/SekretarisKsAutoGenerateTest.php`
5. `tests/Feature/DataAoAutoGenerateTest.php`
6. `tests/Feature/DataPengelolaAutoGenerateTest.php`

**Example Test Structure (DataAoAutoGenerateTest.php):**
```php
public function test_create_without_id_auto_generates()
{
    $response = $this->actingAs($user)->postJson('/api/data-ao', [
        'NO_AGT' => '123456',
        'NAMA' => 'Test AO',
    ]);
    
    $response->assertStatus(201);
    $response->assertJsonStructure(['ID_AO']);
    $this->assertEquals(11, strlen($response->json('ID_AO')));
    $this->assertStringStartsWith('160055', $response->json('ID_AO'));
}

public function test_create_with_valid_id_accepts()
{
    $response = $this->actingAs($user)->postJson('/api/data-ao', [
        'ID_AO' => '16005599998',
        'NO_AGT' => '123456',
        'NAMA' => 'Test AO',
    ]);
    
    $response->assertStatus(201);
    $response->assertJson(['ID_AO' => '16005599998']);
}

public function test_create_with_duplicate_id_rejects()
{
    // Create first
    $this->actingAs($user)->postJson('/api/data-ao', [
        'ID_AO' => '16005599997',
        'NO_AGT' => '123456',
        'NAMA' => 'First',
    ]);
    
    // Try duplicate
    $response = $this->actingAs($user)->postJson('/api/data-ao', [
        'ID_AO' => '16005599997',
        'NO_AGT' => '123457',
        'NAMA' => 'Second',
    ]);
    
    $response->assertStatus(422);
}

public function test_create_with_invalid_format_rejects()
{
    $response = $this->actingAs($user)->postJson('/api/data-ao', [
        'ID_AO' => '12345',
        'NO_AGT' => '123456',
        'NAMA' => 'Test',
    ]);
    
    $response->assertStatus(422);
}
```

---

## Implementation Checklist

### Phase 1: Database & Configuration (Day 1)

1. ✅ Create migration file: `2026_01_04_000001_create_id_sequences_table.php`
   - Define schema with entity_type, kode_role, last_number
   - Add index on entity_type
   - Insert initial data for 6 entities with last_number = 0

2. ✅ Create migration file: `2026_01_04_000002_sync_existing_id_sequences.php`
   - Query max ID from ketua_ks, extract running number (3)
   - Query max ID from kel_sah, extract running number (6)
   - Query max ID from data_lo, extract running number (5)
   - Query max ID from sekre_ks, extract running number (5)
   - Query max ID from data_ao, extract running number (2)
   - Query max ID from data_pengelola, extract running number (1)
   - Update id_sequences table with extracted numbers

3. ✅ Create config file: `config/id_generator.php`
   - Add kode_obormas config (default: 16005)
   - Add entity_mappings array with 6 entities
   - Each mapping: kode_role, table, id_field

4. ✅ Add to `.env`: KODE_OBORMAS=16005

5. ✅ Run migrations: `php artisan migrate`

### Phase 2: Core Logic & Exceptions (Day 2)

6. ✅ Create exception: `app/Exceptions/MaximumIdLimitException.php`
   - Extend Exception
   - Constructor accepts entity_type
   - Render method returns 422 JSON response

7. ✅ Create exception: `app/Exceptions/InvalidEntityTypeException.php`
   - Extend Exception
   - Constructor accepts entity_type
   - Render method returns 400 JSON response

8. ✅ Create validation rule: `app/Rules/ValidIdFormat.php`
   - Constructor accepts entity_type
   - Validate length = 11
   - Validate numeric
   - Validate starts with kode_obormas
   - Validate kode_role matches entity
   - Validate running number range (1-99999)

9. ✅ Create service: `app/Services/IdGeneratorService.php`
   - Add validateEntityType() method
   - Add getEntityConfig() method
   - Add formatId() method (kode_obormas + kode_role + str_pad(number, 5))
   - Add generate() method with DB transaction and lockForUpdate()

### Phase 3: Modify Form Requests (Day 3)

10. ✅ Modify `app/Http/Requests/StoreKetuaKsRequest.php`
    - Change ID_KET rule from 'required' to 'nullable'
    - Add 'size:11' validation
    - Add ValidIdFormat rule: new ValidIdFormat('ketua-ks')
    - Add 'unique:ketua_ks,ID_KET' validation

11. ✅ Modify `app/Http/Requests/StoreKelSahRequest.php`
    - Change ID_KEL rule from 'required' to 'nullable'
    - Add 'size:11' validation
    - Add ValidIdFormat rule: new ValidIdFormat('kel-sah')
    - Add 'unique:kel_sah,ID_KEL' validation

12. ✅ Modify `app/Http/Requests/StoreDataLoRequest.php`
    - Change ID_LO rule from 'required' to 'nullable'
    - Add 'size:11' validation
    - Add ValidIdFormat rule: new ValidIdFormat('data-lo')
    - Add 'unique:data_lo,ID_LO' validation

13. ✅ Modify `app/Http/Requests/StoreSekretarisKsRequest.php`
    - Change ID_SEKRE rule from 'required' to 'nullable'
    - Add 'size:11' validation
    - Add ValidIdFormat rule: new ValidIdFormat('sekre-ks')
    - Add 'unique:sekre_ks,ID_SEKRE' validation

14. ✅ Modify `app/Http/Requests/StoreDataAoRequest.php`
    - Change ID_AO rule from 'required' to 'nullable'
    - Add 'size:11' validation
    - Add ValidIdFormat rule: new ValidIdFormat('data-ao')
    - Add 'unique:data_ao,ID_AO' validation

15. ✅ Modify `app/Http/Requests/StoreDataPengelolaRequest.php`
    - Change ID_PENG rule from 'required' to 'nullable'
    - Add 'size:11' validation
    - Add ValidIdFormat rule: new ValidIdFormat('data-pengelola')
    - Add 'unique:data_pengelola,ID_PENG' validation

### Phase 4: Modify Service Classes (Day 4)

16. ✅ Modify `app/Services/KetuaKsService.php`
    - Add IdGeneratorService to constructor dependencies
    - Modify create() method: check if ID_KET is null/empty
    - If null/empty: call $this->idGenerator->generate('ketua-ks')
    - Assign generated ID to $data['ID_KET']

17. ✅ Modify `app/Services/KelSahService.php`
    - Add IdGeneratorService to constructor dependencies
    - Modify create() method: check if ID_KEL is null/empty
    - If null/empty: call $this->idGenerator->generate('kel-sah')
    - Assign generated ID to $data['ID_KEL']

18. ✅ Modify `app/Services/DataLoService.php`
    - Add IdGeneratorService to constructor dependencies
    - Modify create() method: check if ID_LO is null/empty
    - If null/empty: call $this->idGenerator->generate('data-lo')
    - Assign generated ID to $data['ID_LO']

19. ✅ Modify `app/Services/SekretarisKsService.php`
    - Add IdGeneratorService to constructor dependencies
    - Modify create() method: check if ID_SEKRE is null/empty
    - If null/empty: call $this->idGenerator->generate('sekre-ks')
    - Assign generated ID to $data['ID_SEKRE']

20. ✅ Modify `app/Services/DataAoService.php`
    - Add IdGeneratorService to constructor dependencies
    - Modify create() method: check if ID_AO is null/empty
    - If null/empty: call $this->idGenerator->generate('data-ao')
    - Assign generated ID to $data['ID_AO']

21. ✅ Modify `app/Services/DataPengelolaService.php`
    - Add IdGeneratorService to constructor dependencies
    - Modify create() method: check if ID_PENG is null/empty
    - If null/empty: call $this->idGenerator->generate('data-pengelola')
    - Assign generated ID to $data['ID_PENG']

### Phase 5: Unit Tests (Day 5)

22. ✅ Create test: `tests/Unit/IdGeneratorServiceTest.php`
    - Test: generate_id_with_correct_format (assert 11 chars, starts with 16005)
    - Test: generate_id_with_correct_role_code (assert 7th char matches entity)
    - Test: sequential_running_number (generate 2x, assert second = first + 1)
    - Test: throws_exception_for_invalid_entity (assert InvalidEntityTypeException)
    - Test: throws_exception_at_maximum_limit (set last_number=99999, assert MaximumIdLimitException)
    - Test: format_running_number_with_leading_zeros (assert padding)

23. ✅ Create test: `tests/Unit/Rules/ValidIdFormatTest.php`
    - Test: passes_with_valid_id (16005500001 → pass)
    - Test: fails_with_wrong_length (12345 → fail)
    - Test: fails_with_non_numeric (1600550000A → fail)
    - Test: fails_with_wrong_kode_obormas (99999500001 → fail)
    - Test: fails_with_wrong_kode_role (16005600001 for data-ao → fail)

### Phase 6: Feature Tests (Day 6)

24. ✅ Create test: `tests/Feature/KetuaKsAutoGenerateTest.php`
    - Test: create_without_id_auto_generates
    - Test: create_with_valid_id_accepts
    - Test: create_with_duplicate_id_rejects
    - Test: create_with_invalid_format_rejects

25. ✅ Create test: `tests/Feature/KelSahAutoGenerateTest.php`
    - Test: create_without_id_auto_generates
    - Test: create_with_valid_id_accepts
    - Test: create_with_duplicate_id_rejects
    - Test: create_with_invalid_format_rejects

26. ✅ Create test: `tests/Feature/DataLoAutoGenerateTest.php`
    - Test: create_without_id_auto_generates
    - Test: create_with_valid_id_accepts
    - Test: create_with_duplicate_id_rejects
    - Test: create_with_invalid_format_rejects

27. ✅ Create test: `tests/Feature/SekretarisKsAutoGenerateTest.php`
    - Test: create_without_id_auto_generates
    - Test: create_with_valid_id_accepts
    - Test: create_with_duplicate_id_rejects
    - Test: create_with_invalid_format_rejects

28. ✅ Create test: `tests/Feature/DataAoAutoGenerateTest.php`
    - Test: create_without_id_auto_generates
    - Test: create_with_valid_id_accepts
    - Test: create_with_duplicate_id_rejects
    - Test: create_with_invalid_format_rejects

29. ✅ Create test: `tests/Feature/DataPengelolaAutoGenerateTest.php`
    - Test: create_without_id_auto_generates
    - Test: create_with_valid_id_accepts
    - Test: create_with_duplicate_id_rejects
    - Test: create_with_invalid_format_rejects

### Phase 7: Testing & Verification (Day 7)

30. ✅ Run unit tests: `php artisan test --filter=IdGeneratorServiceTest`
    - Verify all tests pass
    - Fix any failures

31. ✅ Run unit tests: `php artisan test --filter=ValidIdFormatTest`
    - Verify all tests pass
    - Fix any failures

32. ✅ Run feature tests: `php artisan test --testsuite=Feature`
    - Verify all auto-generate tests pass
    - Fix any failures

33. ✅ Manual testing: POST /api/ketua-ks without ID_KET
    - Verify auto-generated ID returned
    - Verify ID format correct (11 digit, starts with 160051)
    - Verify data saved to database

34. ✅ Manual testing: POST /api/data-ao with valid manual ID
    - Verify manual ID accepted
    - Verify data saved with provided ID

35. ✅ Manual testing: POST /api/data-lo with duplicate ID
    - Verify 422 error returned
    - Verify error message clear

36. ✅ Manual testing: Sequential ID generation
    - Create 3 records without ID
    - Verify IDs are sequential (e.g., 00004, 00005, 00006)

37. ✅ Verify database: Check id_sequences table
    - Verify last_number updated correctly after creates
    - Verify all 6 entities have correct running numbers

### Phase 8: Documentation & Cleanup (Day 8)

38. ✅ Update API documentation (if using Swagger/Postman)
    - Mark ID fields as optional in POST endpoints
    - Add examples with and without ID
    - Document error responses

39. ✅ Add code comments in IdGeneratorService
    - Explain locking mechanism
    - Document format structure
    - Add PHPDoc blocks

40. ✅ Clean up debug/test files
    - Remove check-existing-ids.php (temporary script)
    - Remove any debug routes

41. ✅ Final verification: Run full test suite
    - `php artisan test`
    - Ensure 100% pass rate

42. ✅ Performance check: Test concurrent requests (optional)
    - Use tool like Apache Bench or custom script
    - Create 10 concurrent POST requests
    - Verify no duplicate IDs generated

---

## Risks / Catatan

### Identified Risks

1. **Race Condition pada Concurrent Requests**
   - **Mitigation:** DB transaction dengan `lockForUpdate()` pada id_sequences
   - **Monitoring:** Log jika ada deadlock atau timeout

2. **Maximum Limit (99999)**
   - **Impact:** Setelah 99999, tidak bisa create data baru
   - **Mitigation:** Throw clear exception dengan suggestion contact admin
   - **Future:** Add alert/monitoring saat approaching limit (e.g., 95000)

3. **Performance pada High Traffic**
   - **Current:** DB lock bisa bottleneck pada >100 req/s
   - **Mitigation:** Index sudah ada, transaction cepat
   - **Future:** Upgrade ke Redis counter jika perlu

4. **Backward Compatibility**
   - **Risk:** Frontend lama masih kirim ID manual
   - **Mitigation:** Accept both manual & auto (nullable field)
   - **Timeline:** Gradual migration frontend

### Technical Debt

1. **No Reservation Mechanism**
   - Phase 1 tidak implement preview/reservation
   - Bisa ditambahkan di Phase 2 jika diperlukan

2. **Format ID (11 vs 12 digit)**
   - Keep 11 digit untuk sekarang
   - Requirement doc perlu update untuk reflect reality

### Trade-offs Taken

| Decision | Trade-off | Rationale |
|----------|-----------|-----------|
| 11 digit (not 12) | Docs tidak match | Data existing compatibility |
| No reservation | No preview ID | Simplicity, faster delivery |
| DB sequence (not Redis) | Slower (~100ms) | No extra infra, proven pattern |
| Nullable ID (not remove) | Manual ID still possible | Backward compatibility |

---

## Success Criteria

- ✅ POST tanpa ID → auto-generate dengan format benar
- ✅ POST dengan ID valid → accept & save
- ✅ POST dengan ID duplicate → reject 422
- ✅ POST dengan ID invalid format → reject 422
- ✅ Sequential running number per entity
- ✅ No duplicate IDs even with concurrent requests
- ✅ All tests pass (unit + feature)
- ✅ Performance <200ms per generate
- ✅ Data existing tetap valid & compatible

---

**Plan Status:** READY FOR APPROVAL  
**Total Tasks:** 42 sequential steps  
**Estimated Duration:** 7-8 working days  
**Risk Level:** LOW-MEDIUM  
**Dependencies:** None (all Laravel core features)
