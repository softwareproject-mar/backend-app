# File untuk deploy: scope data `created_by` (role `user`)

Daftar ini **hanya** file yang terlibat fitur isolasi data berbasis `created_by` + penyesuaian KelSah / Anggota / LO / AO.  
**Bukan** snapshot penuh `git status`.

Setelah file di-copy ke server, jalankan migrasi:

```bash
php artisan migrate --no-interaction --force
```

---

## File baru

| Path |
|------|
| `app/Support/OwnerScope.php` |
| `database/migrations/2026_04_03_000000_add_created_by_to_scoped_data_tables.php` |

---

## File yang diubah

### Model

| Path |
|------|
| `app/Models/DataPenghasilan.php` |
| `app/Models/DataJlhKeluarga.php` |
| `app/Models/DataTrs.php` |
| `app/Models/DataKunjungan.php` |

### Service

| Path |
|------|
| `app/Services/DataPenghasilanService.php` |
| `app/Services/DataJlhKeluargaService.php` |
| `app/Services/DataTrsService.php` |
| `app/Services/DataKunjunganService.php` |
| `app/Services/KelSahService.php` |
| `app/Services/DataLoService.php` |
| `app/Services/DataAoService.php` |
| `app/Services/AnggotaService.php` |

### Controller API

| Path |
|------|
| `app/Http/Controllers/Api/DataPenghasilanController.php` |
| `app/Http/Controllers/Api/DataJlhKeluargaController.php` |
| `app/Http/Controllers/Api/DataTrsController.php` |
| `app/Http/Controllers/Api/DataKunjunganController.php` |
| `app/Http/Controllers/Api/KelSahController.php` |
| `app/Http/Controllers/Api/AnggotaController.php` |
| `app/Http/Controllers/Api/DataLoController.php` |
| `app/Http/Controllers/Api/DataAoController.php` |

### Form request

| Path |
|------|
| `app/Http/Requests/StoreDataPenghasilanRequest.php` |
| `app/Http/Requests/StoreDataJlhKeluargaRequest.php` |
| `app/Http/Requests/StoreDataKunjunganRequest.php` |
| `app/Http/Requests/UpdateDataKunjunganRequest.php` |
| `app/Http/Requests/UpdateDataPenghasilanRequest.php` |
| `app/Http/Requests/UpdateDataJlhKeluargaRequest.php` |

---

## Catatan

- Baris data lama dengan `created_by` **NULL** tidak ikut tampil untuk role **`user`** sampai di-backfill (jika diperlukan).
- Dokumen rencana asli: `plans/data-scope-created-by-role-user.md` (opsional untuk referensi, tidak wajib untuk runtime).
