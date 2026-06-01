# Plan: registration_reviewed_by selalu dari pengguna terautentikasi

## Context

- Halaman Web Admin Persetujuan Akun mengirim `registration_reviewed_by` dari `localStorage` (`getUser().id`). Jika `id` hilang/kadaluarsa atau body berisi ID tidak valid, perilaku bisa tidak sesuai harapan.
- `config/obormas.php` menyatakan bahwa setujui/tolak harus menyimpan pemroses = user yang sedang login (Sanctum).
- `UserApprovalController::approve` / `reject` saat ini **mencampur** sumber: body jika ada, kalau tidak pakai token. Validasi `exists:users,id` pada body menyebabkan **422 seluruh request** jika klien mengirim ID salah (lihat `UserApprovalPersistsReviewerTest::test_approve_returns_422_when_registration_reviewed_by_user_missing`), tanpa fallback ke token.

## Goal

- **Satu sumber kebenaran:** `registration_reviewed_by` dan `registration_reviewed_at` pada approve/reject **hanya** diturunkan dari pengguna yang terautentikasi pada request (`$request->user()`), bukan dari body JSON.
- **Klien tidak perlu** mengirim `registration_reviewed_by`; body aneh/tidak valid tidak boleh membatalkan approve karena field itu.
- **Tes** mencerminkan kontrak baru; Web Admin disederhanakan (tidak bergantung pada `id` di localStorage untuk reviewer).

## Detailed Specifications

### Backend — `app/Http/Controllers/Api/UserApprovalController.php`

- **`approve(Request $request, int $id)`**
  - Hapus `registration_reviewed_by` dari array `$request->validate(...)` untuk approve.
  - Set `$reviewerId = (int) $request->user()->id` (tanpa membaca body untuk reviewer).
  - Pertahankan validasi `no_agt` seperti sekarang.
  - Tetap jalankan pengecekan `$reviewerId < 1 || !User::query()->whereKey($reviewerId)->exists()` (defensif jika `user()` aneh).

- **`reject(Request $request, int $id)`**
  - Hapus `registration_reviewed_by` dari validasi.
  - Set `$reviewerId = (int) $request->user()->id` dengan pola yang sama seperti approve.

- **Impor:** Hapus `Rule` dari `use` hanya jika tidak lagi dipakai di file ini; jika masih dipakai di tempat lain di controller, biarkan.

### Backend — tes — `tests/Feature/UserApprovalPersistsReviewerTest.php`

- Ubah / ganti nama tes agar mencerminkan sumber reviewer = **acting user**:
  - Satu tes: `POST approve` dengan body kosong → `registration_reviewed_by` = admin yang `actingAs`.
  - Satu tes: `POST approve` **dengan** `registration_reviewed_by` di body yang **berbeda** dari acting user (misalnya ID user lain yang valid) → assert yang tersimpan adalah **ID acting user**, bukan body (anti-spoofing).
  - Hapus atau ganti `test_approve_returns_422_when_registration_reviewed_by_user_missing`: setelah perubahan, kirim body `registration_reviewed_by: 999_999` harus **tidak** 422 karena field diabaikan; assert member ter-approve dan `registration_reviewed_by` = acting admin.

- Sesuaikan nama metode tes (misalnya `test_approve_stores_registration_reviewed_by_from_request_body` → nama yang menjelaskan token/acting user).

### Frontend — `Web Admin/src/app/pages/PersetujuanAkun.tsx`

- **`handleApprove`:** Panggil `api.post(path, body)` dengan `body` hanya berisi field yang memang untuk approve (misalnya `{}` atau `{ no_agt: ... }` jika UI mengirim nomor anggota). Jika saat ini tidak ada `no_agt` dari form, gunakan `{}` atau `undefined` sesuai pola `api.post` yang ada.
- **`handleReject`:** `POST` dengan body kosong `{}` (atau tanpa body jika helper mendukung).
- **Hapus** fungsi `registrationReviewPayload()` jika tidak dipakai lagi, **atau** refactor menjadi helper hanya untuk `no_agt` jika nanti diperlukan; jangan lagi mengirim `registration_reviewed_by`.
- **Hapus** pemanggilan yang hanya untuk mendapatkan reviewer id (alert "ID pengguna tidak ada di sesi" pada alur approve/reject tidak lagi diperlukan untuk reviewer).
- **Opsional ringan:** Biarkan `useEffect` + `syncUserFromMe()` jika masih berguna untuk data profil di UI; tidak wajib diubah untuk goal ini.

### Dokumentasi konfigurasi

- **Tidak wajib** mengubah `config/obormas.php` (sudah selaras). Boleh tambahkan satu baris komentar di `UserApprovalController` bahwa body `registration_reviewed_by` sengaja tidak dipakai — hanya jika singkat.

## Implementation Checklist

1. Edit `app/Http/Controllers/Api/UserApprovalController.php` method `approve`: hapus validasi dan pemakaian `registration_reviewed_by` dari input; set `$reviewerId` hanya dari `$request->user()->id`.
2. Edit method `reject` pada file yang sama: sama seperti item 1.
3. Rapikan import `Rule` (dan `use` lain) di `UserApprovalController.php` jika tidak terpakai.
4. Update `tests/Feature/UserApprovalPersistsReviewerTest.php`: sesuaikan nama dan assert tes approve/reject dengan perilaku server-only reviewer.
5. Ganti tes 422 untuk `registration_reviewed_by` invalid menjadi tes yang mengharapkan sukses + reviewer = acting user.
6. Tambah tes assert body `registration_reviewed_by` berbeda dari acting user **diabaikan** (pemroses = acting user).
7. Edit `Web Admin/src/app/pages/PersetujuanAkun.tsx`: `handleApprove` / `handleReject` tidak mengirim `registration_reviewed_by`; hapus/refactor `registrationReviewPayload` sesuai kebutuhan `no_agt`.
8. Jalankan `php artisan test --filter=UserApprovalPersistsReviewer` di folder backend (atau suite terkait) dan pastikan lulus.

## Risks / Catatan

- **Klien lain** (Postman, skrip) yang sengaja mengirim `registration_reviewed_by` untuk “mewakili” admin lain akan **tidak lagi didukung** — itu selaras dengan keamanan dan requirement “siapa login, dia pemroses”.
- Jika ada UI yang mengisi `no_agt` lewat body approve, pastikan body approve tetap mengirim hanya `no_agt` (cek form di `PersetujuanAkun.tsx` sebelum eksekusi).
