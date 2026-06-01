# Plan: Banyak baris per NO_AGT (modul anak), master anggota tetap unik

## Context

- Tabel **`anggota`** tetap **satu record per `NO_AGT`** (primary key) — tidak diubah.
- Untuk modul **data anak** yang menyimpan `NO_AGT`, user ingin **banyak baris dengan nomor anggota yang sama** (bisa input berulang).
- Saat ini **skema DB** memaksa satu baris per `NO_AGT` pada: `data_jlh_keluarga`, `data_penghasilan`, `data_trs` (primary key = `NO_AGT`). **`data_ao`** dan **`data_lo`** punya **unique** pada `NO_AGT`. **`ketua_ks`** dan **`sekre_ks`** punya **unique** pada `NO_AGT`.
- **`data_kunjungan`** sudah memakai `NO_URT` sebagai PK — **banyak kunjungan per `NO_AGT` sudah didukung** skema; tidak perlu migrasi untuk tujuan duplikasi `NO_AGT` (hanya verifikasi tidak ada constraint tambahan di environment produksi).
- **API + mobile** saat ini mengidentifikasi resource penghasilan / jlh keluarga lewat **`NO_AGT` di URL** (`PUT/DELETE .../data-penghasilan/{noAnggota}`) dan **`id` UI = `toSafeClientId([noAnggota])`** — akan bentrok jika ada banyak baris dengan `NO_AGT` sama. **Wajib** beralih ke **ID numerik surrokat** di backend dan frontend.

## Goal

1. Menambah **primary key surrokat** (`id` bigint auto-increment) pada `data_jlh_keluarga`, `data_penghasilan`, `data_trs`; menghapus PK pada `NO_AGT`; mempertahankan **foreign key** ke `anggota(NO_AGT)` (banyak baris anak boleh mereferensikan satu anggota).
2. Menghapus **unique constraint** pada `NO_AGT` di `data_ao`, `data_lo`, `ketua_ks`, `sekre_ks` agar skema selaras dengan kebijakan bisnis.
3. Mengekspos **`id`** di JSON resource; **show/update/destroy** memakai `{id}` numerik.
4. Menyesuaikan **Kelompok Sahabat Obor Mas** (services + screen): simpan **`recordId`** dari API untuk edit/hapus; key list React memakai `id` unik.
5. **Data transaksi**: menambah **store/update/destroy** di API (service + controller + routes + Form Request) agar konsisten dengan modul lain (saat ini route hanya `index`/`show`).

## Detailed Specifications

### Database (Laravel migrations — file baru di `database/migrations/`)

**Satu migrasi atau beberapa migrasi terpisah** (disarankan satu migrasi per “kelompok” agar rollback jelas):

**A. `data_jlh_keluarga`, `data_penghasilan`, `data_trs` (urutan per tabel):**

1. `dropForeign` constraint yang mereferensikan `anggota` (`FK_DATA_JLH_KELUARGA_1`, `FK_DATA_PENGHASILAN_1`, `FK_DATA_TRS_1`).
2. `dropPrimary` pada kolom `NO_AGT`.
3. Tambah kolom `id` — `bigIncrements` (PK baru), posisi default akhir OK; pastikan semua baris existing terisi `id` (MySQL mengisi auto-increment otomatis saat kolom ditambahkan sebagai AI PK).
4. Index pada `NO_AGT` (jika hilang setelah drop PK, tambahkan `$table->index('NO_AGT')` atau nama index konsisten).
5. `foreign('NO_AGT')->references('NO_AGT')->on('anggota')` dengan opsi sama seperti semula (`onUpdate`/`onDelete` sesuai migrasi asli).

**B. `data_ao`:** `dropUnique('unq1_data_ao')` (nama index sesuai migrasi create).

**C. `data_lo`:** `dropUnique('unq1_data_lo')`.

**D. `ketua_ks`:** `dropUnique` pada index unique `NO_AGT` (nama: `unq1_ketua_ks` dari migrasi create).

**E. `sekre_ks`:** `dropUnique` pada index unique `NO_AGT` (nama: `unq1_sekre_ks` dari migrasi create).

**Catatan:** Jalankan dan uji di DB yang sama dengan produksi (MySQL vs Firebird dll.); syntax `dropPrimary` / foreign key names harus cocok dengan nama constraint aktual.

### Backend models (`app/Models/`)

- **`DataJlhKeluarga`**, **`DataPenghasilan`**, **`DataTrs`**:  
  - Hapus `$primaryKey = 'NO_AGT'`, `$incrementing = false`, `$keyType = 'string'`.  
  - Gunakan default PK `id` (integer, incrementing).  
  - Pastikan `'id'` tidak perlu di `$fillable` (default); `'NO_AGT'` tetap di `$fillable`.

### Backend services

- **`DataJlhKeluargaService`**, **`DataPenghasilanService`**:  
  - `find(string $id)` → terima **numeric id** (tetap string di signature route OK); gunakan `findOrFail($id)`.  
  - `update` / `delete` memakai PK baru.  
  - Activity log: `resource_id` setelah create gunakan **`(string) $record->id`** (atau id numerik konsisten dengan pola log lain), bukan hanya `NO_AGT`.  
  - `paginate` / `listForExport`: tambah **`orderBy('id', 'desc')`** (atau `asc` jika produk ingin urutan lama dulu) agar urutan stabil dan konsisten.

- **`DataTrsService`**:  
  - Tambah method **`create`**, **`update`**, **`delete`** mengikuti pola `DataPenghasilanService` (pertimbangkan trait **`LogsActivity`** untuk parity).  
  - `find` / `paginate` / `listForExport` sama seperti di atas (`orderBy` id).

### Backend HTTP

- **`DataJlhKeluargaResource`**, **`DataPenghasilanResource`**, **`DataTrsResource`**: tambah field **`'id' => $this->id`** (integer) di `toArray`. Urutan field bebas; mobile akan memakai `id`.

- **Controller** `DataJlhKeluargaController`, `DataPenghasilanController`, `DataTrsController`:  
  - Parameter route `{id}` untuk show/update/destroy — dokumentasi implisit: **numeric id**.  
  - Validasi opsional: `whereUuid` tidak dipakai; bisa `abort_if(!ctype_digit($id), 404)` atau biarkan `findOrFail`.

- **`DataTrsController`**: tambah **`store`**, **`update`**, **`destroy`**; set `created_by` pada store seperti penghasilan (merge dari `OwnerScope` / user).

- **`routes/api.php`**: ubah `Route::apiResource('data-trs', ...)->only([...])` untuk menyertakan **`store`**, **`update`**, **`destroy`**.

- **Form Request baru**: `StoreDataTrsRequest`, `UpdateDataTrsRequest` — rules mirror kolom `fillable` / nullable seperti `DataTrs` model (tanpa `unique` pada `NO_AGT`). `UpdateDataTrsRequest` strip `created_by` seperti request update lain.

### Backend — out of scope perubahan perilaku API (kecuali jika nanti diminta)

- **`data_ao` / `data_lo` / ketua / sekretaris`**: PK tetap `ID_*`; hanya **DB unique** dihapus. Tidak wajib ubah URL atau resource kecuali ada kebutuhan terpisah.

### Frontend — `Kelompok Sahabat Obor Mas`

**File:** `src/services/dataPenghasilanService.ts`, `src/services/dataJlhKeluargaService.ts`

- Tambah `id` ke DTO (`id?: number` atau `number` wajib setelah backend deploy).
- `mapDtoToItem`: `id: String(dto.id ?? '')` atau pastikan backend selalu kirim `id`; **jangan** pakai `toSafeClientId([noAnggota])` sebagai satu-satunya id.
- `updateXxx(id: string | number, payload)` — URL `.../${encodeURIComponent(String(id))}`.
- `deleteXxx(id: string | number)` — sama.

**File:** `src/services/dataTrsService.ts`

- `DataTrsDto` + `mapDtoToItem`: pakai **`id` dari API** untuk `DataTrsItem.id` (bukan hanya `noAnggota`).
- Jika nanti ditambah CRUD UI transaksi: fungsi create/update/delete memakai path numerik; jika UI tetap read-only, minimal perbaikan **list key** dan konsistensi dengan response.

**File:** `src/app/components/DataPenghasilanScreen.tsx`, `JlhKeluargaDataScreen.tsx`

- State `selectedItem` / item list: sertakan **`id`** dari API.
- Panggilan `updateDataPenghasilan(selectedItem.id, …)`, `deleteDataPenghasilan(item.id)`.
- Key pada `map` rows: **`key={item.id}`** (bukan `noAnggota` saja).

**File:** `DataTrsScreen.tsx` (jika ada tabel dengan key): gunakan **`item.id`** dari API setelah `mapDtoToItem` diperbaiki.

### Testing

- Tambah **Feature test** (Pest/PHPUnit): user terautentikasi membuat **dua** `POST /api/.../data-penghasilan` (atau jlh) dengan **`NO_AGT` sama**, keduanya **201**; `GET` index mengembalikan **2** item; `PUT` / `DELETE` ke **`id`** yang benar hanya mengubah satu baris.
- Satu test untuk **`data_trs`** store + duplicate `NO_AGT` jika CRUD ditambahkan.

### Duplikat repo UI

- Workspace juga memuat **`Email Verification Version`** dengan salinan folder serupa: **terapkan diff yang sama** pada service/screen terkait, atau catat di catatan deploy agar tidak ketinggalan.

## Implementation Checklist

1. Buat migrasi: untuk tabel **`data_jlh_keluarga`** — drop FK `FK_DATA_JLH_KELUARGA_1`, drop primary key pada `NO_AGT`, tambah kolom **`id`** `bigIncrements` sebagai primary key, index `NO_AGT`, restore FK ke `anggota(NO_AGT)` dengan opsi sama seperti migrasi asli.
2. Buat migrasi (bisa gabung dengan langkah berikutnya di file sama): **`data_penghasilan`** — langkah setara dengan FK `FK_DATA_PENGHASILAN_1`.
3. Dalam migrasi yang sama atau berikutnya: **`data_trs`** — langkah setara dengan FK `FK_DATA_TRS_1`.
4. Migrasi: **`data_ao`** — `dropUnique('unq1_data_ao')`.
5. Migrasi: **`data_lo`** — `dropUnique('unq1_data_lo')`.
6. Migrasi: **`ketua_ks`** — `dropUnique('unq1_ketua_ks')`.
7. Migrasi: **`sekre_ks`** — `dropUnique('unq1_sekre_ks')`.
8. Jalankan `php artisan migrate` di environment dev; perbaiki nama constraint jika berbeda dari migrasi asli.
9. Update model **`DataJlhKeluarga`**: hapus konfigurasi PK string `NO_AGT`, gunakan default PK `id`.
10. Update model **`DataPenghasilan`**: sama.
11. Update model **`DataTrs`**: sama.
12. Update **`DataJlhKeluargaService`**: `find`/`update`/`delete` memakai id numerik; log aktivitas `resource_id` memakai id record; `paginate` dan `listForExport` **`orderBy('id', 'desc')`**.
13. Update **`DataPenghasilanService`**: sama seperti poin 12.
14. Update **`DataTrsService`**: `paginate`/`listForExport` **`orderBy('id', 'desc')`**; tambah **`create`**, **`update`**, **`delete`**; (opsional disarankan) gunakan **`LogsActivity`** seperti penghasilan.
15. Tambah **`StoreDataTrsRequest`** dan **`UpdateDataTrsRequest`** dengan rules kolom yang selaras model (tanpa `unique` pada `NO_AGT`).
16. Update **`DataJlhKeluargaResource`**: sertakan **`id`**.
17. Update **`DataPenghasilanResource`**: sertakan **`id`**.
18. Update **`DataTrsResource`**: sertakan **`id`**.
19. Update **`DataTrsController`**: implement **`store`**, **`update`**, **`destroy`**; set **`created_by`** pada create.
20. Update **`routes/api.php`**: perluas **`data-trs`** resource agar mencakup **`store`**, **`update`**, **`destroy`**.
21. Update **`src/services/dataPenghasilanService.ts`**: DTO + **`mapDtoToItem`** memakai **`id`** dari API; **`updateDataPenghasilan`** / **`deleteDataPenghasilan`** memakai parameter **id numerik**.
22. Update **`src/services/dataJlhKeluargaService.ts`**: sama seperti poin 21.
23. Update **`src/services/dataTrsService.ts`**: **`mapDtoToItem`** memakai **`id`** dari API untuk **`DataTrsItem.id`**.
24. Update **`DataPenghasilanScreen.tsx`**: simpan dan gunakan **`item.id`** untuk update/delete dan **`key`** list.
25. Update **`JlhKeluargaDataScreen.tsx`**: sama seperti poin 24.
26. Update **`DataTrsScreen.tsx`**: **`key`** baris (dan field id) memakai **`item.id`** dari service.
27. Tambah **feature test** backend untuk **duplikat `NO_AGT`** pada penghasilan atau jlh + operasi by **id**.
28. (Jika ada salinan app) Terapkan perubahan service/screen yang sama pada folder **`Email Verification Version`**.
29. Verifikasi manual: deploy migrasi ke staging; smoke test mobile **tambah dua entri** nomor anggota sama untuk penghasilan & jlh; edit/hapus salah satu tidak mempengaruhi yang lain.

## Risks / Catatan

- **Breaking change API**: client lama yang memanggil `PUT /data-penghasilan/{NO_AGT}` akan gagal setelah deploy backend baru; **deploy backend + mobile bersamaan** atau sediakan jendela migrasi.
- **Nama constraint FK/unique** bisa berbeda jika DB pernah dimigrasi manual — cek `information_schema` / error migrate.
- **`data_kunjungan`** tidak perlu diubah untuk multi-`NO_AGT`; tetap verifikasi tidak ada unique tambahan di produksi.
- **Bisnis**: beberapa entitas (`ketua_ks`, `sekre_ks`) secara domain mungkin “satu per orang”; keputusan user adalah mengizinkan duplikat `NO_AGT` di skema — dokumentasikan jika perlu aturan di aplikasi lain (admin) nanti.
