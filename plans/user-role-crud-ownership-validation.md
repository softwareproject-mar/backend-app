# Plan: User Role CRUD Ownership Validation

## Context
- Saat ini MemberScope menggunakan logika kompleks: user dengan `no_agt` vs user tanpa `no_agt`
- Requirement baru: SEMUA user role hanya filter berdasarkan `created_by = user.id`
- NO_AGT menjadi field biasa (wajib diisi, boleh duplicate, tidak untuk authorization)
- Admin tetap bisa akses semua data, DataTrs tetap read-only untuk user

## Goal
- Sederhanakan MemberScope logic: user role = filter by `created_by` only
- Standardisasi Form Requests: NO_AGT always required, no auto-injection
- Pastikan user hanya bisa CRUD data sendiri, admin bisa CRUD semua data
- Verifikasi frontend integration tetap berfungsi

## Detailed Specifications

### File yang akan diubah/dibuat dengan path lengkap

#### 1. MemberScope Helper Simplification
**File**: `app/Support/MemberScope.php`
- `mergeOwnershipFilterForCrud()`: Hapus logika `no_agt`, user role selalu filter `created_by = user.id`
- `validateOwnershipForCrud()`: Hapus logika `no_agt`, user role selalu validate `created_by = user.id`
- `injectNoAgtForUser()`: Hapus auto-injection logic, return data as-is untuk user role

#### 2. Form Requests Standardization
**Files**:
- `app/Http/Requests/StoreDataPenghasilanRequest.php`
- `app/Http/Requests/UpdateDataPenghasilanRequest.php`
- `app/Http/Requests/StoreDataJlhKeluargaRequest.php`
- `app/Http/Requests/UpdateDataJlhKeluargaRequest.php`
- `app/Http/Requests/StoreDataKunjunganRequest.php`
- `app/Http/Requests/UpdateDataKunjunganRequest.php`

**Changes**:
- Remove `prepareForValidation()` auto-injection logic
- Standardize `NO_AGT` validation: `required|string|max:15|exists:anggota,NO_AGT` untuk semua role
- Remove conditional validation based on user's `no_agt`

#### 3. Service Layer Cleanup
**Files**:
- `app/Services/DataPenghasilanService.php`
- `app/Services/DataJlhKeluargaService.php`
- `app/Services/DataKunjunganService.php`
- `app/Services/DataTrsService.php`

**Changes**:
- Remove any remaining empty result return logic
- Ensure consistent behavior across all methods

#### 4. Test Command Update
**File**: `app/Console/Commands/TestUserCrudCommand.php`
- Update test to use valid NO_AGT from anggota table
- Test user can only see/modify own data
- Test admin can see/modify all data

## Implementation Checklist

### Phase 1: MemberScope Simplification
1. [Simplify mergeOwnershipFilterForCrud() - remove no_agt logic, user role always filter by created_by]
2. [Simplify validateOwnershipForCrud() - remove no_agt logic, user role always validate by created_by]
3. [Simplify injectNoAgtForUser() - remove auto-injection, return data as-is for user role]

### Phase 2: Form Requests Standardization
4. [Update StoreDataPenghasilanRequest - remove prepareForValidation auto-injection]
5. [Update StoreDataPenghasilanRequest - standardize NO_AGT validation to required|string|max:15|exists:anggota,NO_AGT]
6. [Update UpdateDataPenghasilanRequest - remove prepareForValidation logic]
7. [Update StoreDataJlhKeluargaRequest - remove prepareForValidation auto-injection]
8. [Update StoreDataJlhKeluargaRequest - standardize NO_AGT validation]
9. [Update UpdateDataJlhKeluargaRequest - remove prepareForValidation logic]
10. [Update StoreDataKunjunganRequest - remove prepareForValidation auto-injection]
11. [Update StoreDataKunjunganRequest - standardize NO_AGT validation]
12. [Update UpdateDataKunjunganRequest - remove prepareForValidation logic]

### Phase 3: Service Layer Cleanup
13. [Clean DataPenghasilanService - ensure no empty result logic remains]
14. [Clean DataJlhKeluargaService - ensure no empty result logic remains]
15. [Clean DataKunjunganService - ensure no empty result logic remains]
16. [Clean DataTrsService - ensure consistent read-only behavior for users]

### Phase 4: Testing & Verification
17. [Update TestUserCrudCommand - use valid NO_AGT for testing]
18. [Test user ID 10 can CRUD own data (3 penghasilan, 1 jlh_keluarga records)]
19. [Test user ID 1 cannot access user ID 10's data]
20. [Test admin can access all data regardless of created_by]
21. [Test DataTrs read-only access for user role]
22. [Test NO_AGT validation requires existing anggota record]

### Phase 5: Frontend Integration Verification
23. [Test mobile app login with user role]
24. [Test user can see only own data in list views]
25. [Test user can create new data with manual NO_AGT input]
26. [Test user can update/delete own data]
27. [Test user gets 403 error when trying to access others' data]
28. [Test admin can see all data in list views]

## Risks / Catatan

### Technical Risks
- **Data Consistency**: Existing data mungkin ada yang `created_by` null atau salah
- **Frontend Cache**: Mobile app mungkin cache data lama
- **Performance**: Filter by `created_by` di semua query user

### Mitigation Strategies
- **Data Audit**: Check dan backfill `created_by` yang missing sebelum deploy
- **Database Index**: Pastikan `created_by` sudah ter-index untuk performance
- **Frontend Testing**: Clear app cache setelah backend update
- **Gradual Rollout**: Test dengan user ID 10 yang sudah punya data

### Business Impact
- **User Experience**: User hanya lihat data sendiri (sesuai requirement)
- **Admin Workflow**: Admin tetap bisa manage semua data
- **Data Entry**: NO_AGT jadi input manual (tidak auto-fill dari user profile)

### Compatibility Notes
- **Database**: No schema changes needed, semua kolom sudah ada
- **API Contracts**: Response format tetap sama, hanya filtering yang berubah
- **Mobile App**: Seharusnya tidak perlu update jika error handling sudah proper