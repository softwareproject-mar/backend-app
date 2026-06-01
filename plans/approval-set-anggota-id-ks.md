# Plan: Persetujuan akun — taut `no_agt` + set `anggota.ID_KS` dari `kel_sah`

## Context

- Endpoint `POST /api/users/{id}/approve` (`UserApprovalController::approve`) saat ini mengaktifkan user, mengisi opsional `users.no_agt` (harus `exists:anggota,NO_AGT`), dan **tidak** mengubah tabel `anggota`.
- Web Admin `PersetujuanAkun.tsx` memanggil approve dengan body `{}`.
- Kebutuhan bisnis: di satu alur persetujuan, admin mengisi **nomor anggota** (baris sudah ada di `anggota`) dan **kelompok sahabat** (`kel_sah.ID_KEL`); setelah disetujui, **`anggota.ID_KS`** di-update ke `ID_KEL` tersebut (**timpa** jika sudah terisi).

## Goal

1. Backend: terima `no_agt` + `id_kel` secara berpasangan; dalam transaksi DB, update `anggota` lalu `users` (atau urutan setara) lalu dispatch email seperti sekarang.
2. Web Admin: modal Setujui berisi input pemilihan `NO_AGT` dan `ID_KEL` (dengan bantuan pencarian/list API), kirim JSON ke approve.
3. Uji fitur: skenario sukses + validasi 422 untuk pasangan tidak lengkap / ID tidak valid.

## Detailed Specifications

### Request body (JSON)

- `no_agt` (opsional): `string`, `max:15`, harus ada di `anggota.NO_AGT` jika dikirim dan tidak kosong.
- `id_kel` (opsional): `string`, `max:12`, harus ada di `kel_sah.ID_KEL` jika dikirim dan tidak kosong.
- **Aturan berpasangan:** jika `no_agt` terisi (non-empty setelah trim), `id_kel` **wajib** terisi dan valid; sebaliknya jika `id_kel` terisi, `no_agt` **wajib** terisi dan valid. Jika **keduanya kosong/absen**, perilaku sama seperti sekarang (approve tanpa taut anggota / tanpa update `anggota`).
- Normalisasi: trim string untuk `no_agt` dan `id_kel` sebelum validasi/update.

### Backend — file & perubahan

| File | Perubahan |
|------|-----------|
| `app/Http/Requests/ApproveMemberRegistrationRequest.php` | **Baru.** `authorize()` true untuk user terautentikasi (middleware admin sudah memfilter). `prepareForValidation()` trim `no_agt`, `id_kel`. `rules()` memakai `Illuminate\Validation\Rule::requiredIf` (atau setara) untuk pasangan; `exists:anggota,NO_AGT`; `exists:kel_sah,ID_KEL`. Pesan error bahasa Indonesia selaras dengan controller sekarang. |
| `app/Http/Controllers/Api/UserApprovalController.php` | Method `approve()`: ganti `$request->validate([...])` menjadi injeksi/validasi `ApproveMemberRegistrationRequest`. Setelah validasi, bungkus **update `anggota`** + **update `users`** dalam `Illuminate\Support\Facades\DB::transaction` closure. Jika pasangan tidak digunakan (keduanya kosong), **jangan** query update `anggota`. Jika pasangan digunakan: `Anggota::query()->where('NO_AGT', $noAgt)->update(['ID_KS' => $idKel])` (atau `updateOrFail` dengan cek `affected` = 1 jika ingin ketat — default: set `affected === 0` sebagai 422 "Nomor anggota tidak ditemukan" untuk edge race). Pertahankan payload `users` existing (`is_active`, `registration_*`, `no_agt`). Setelah `DB::transaction` sukses, `refresh`, `Log::info`, `SendAccountActivationJob::dispatch` seperti sekarang. |
| `app/Models/Anggota.php` | Tidak wajib diubah; `ID_KS` sudah di `$fillable`. |

### Backend — urutan transaksi

1. Mulai `DB::transaction`.
2. Jika pasangan `no_agt` + `id_kel` aktif: update baris `anggota` dengan `NO_AGT` = nilai terpilih, set `ID_KS` = `id_kel` (overwrite).
3. Update baris `user` dengan kondisi `registration_status = pending` (sama seperti sekarang — optimistic locking via `where`).
4. Commit.
5. Luar transaksi: dispatch job email aktivasi.

### Web Admin — file & perubahan

| File | Perubahan |
|------|-----------|
| `src/app/pages/PersetujuanAkun.tsx` | Perluas state modal approve (mis. `confirmApprove`: `{ id, name, noAgt: string, idKel: string }` atau state terpisah untuk dua field). Di modal "Setujui akun?": tambah dua blok input: (1) **Nomor anggota** — minimal UX: input teks + tombol cari / debounce memanggil `GET /anggota?search=...&per_page=20` (reuse pola `api.get` seperti tab lain); tampilkan daftar singkat untuk klik memilih `NO_AGT`. (2) **Kelompok sahabat** — `GET /kel-sah?search=...&per_page=20`, pilih `ID_KEL` + tampilkan `NAMA_KEL`. Validasi klien: jika salah satu field terisi, yang lain tidak boleh kosong sebelum `Setujui`. `handleApprove`: `api.post(\`/users/${id}/approve\`, { no_agt, id_kel })` — kirim kunci **`id_kel`** (snake_case) agar konsisten dengan `no_agt`; map ke kolom DB di backend. Jika keduanya kosong, kirim `{}` atau omit keys (kompatibel approve lama). Perlebar modal (`max-w-lg` atau scroll) jika perlu. |

### API contract (ringkas)

- `POST /api/users/{id}/approve`
  - Body opsional: `{ "no_agt": "...", "id_kel": "..." }`
  - Keduanya harus berpasangan jika salah satu non-kosong.
  - Response sukses tetap seperti sekarang (message + `UserResource`).

### Tests

| File | Perubahan |
|------|-----------|
| `tests/Feature/ApprovalUpdatesAnggotaIdKsTest.php` | **Baru** (atau gabung ke `UserApprovalPersistsReviewerTest.php` jika tim prefer satu file). `RefreshDatabase`, `Mail::fake()`, Sanctum admin. Siapkan `kel_sah` row (`ID_KEL`), `anggota` row (`NO_AGT`, `ID_KS` awal beda dari target). `postJson('/api/users/{id}/approve', ['no_agt' => ..., 'id_kel' => ...])`. Assert `anggota.ID_KS` = `id_kel`, user approved, `users.no_agt` = input. |
| | Test 422: hanya `no_agt`, hanya `id_kel`, `id_kel` tidak ada di `kel_sah`, `no_agt` tidak ada di `anggota`. |

## Implementation Checklist

1. Buat `app/Http/Requests/ApproveMemberRegistrationRequest.php` dengan `prepareForValidation` (trim), aturan `exists`, dan **requiredIf** berpasangan untuk `no_agt` ↔ `id_kel`.
2. Ubah `UserApprovalController::approve` untuk memakai `ApproveMemberRegistrationRequest`, membaca `no_agt` dan `id_kel` yang tervalidasi.
3. Dalam `approve`, bungkus update `anggota` (hanya jika pasangan dipakai) + update `users` dalam `DB::transaction`; pertahankan logika `where registration_status = pending` pada update user.
4. Jika update `anggota` mengembalikan 0 baris terpengaruh saat pasangan dipakai, kembalikan **422** dengan pesan jelas (nomor anggota tidak ditemukan).
5. Pastikan `SendAccountActivationJob::dispatch` dipanggil setelah transaksi sukses (bukan di dalam closure transaksi jika job membutuhkan data sudah commit — pola sama seperti sekarang setelah `update`).
6. Tambah file tes fitur baru dengan minimal: sukses update `ID_KS` + `no_agt` user; 422 untuk pasangan tidak lengkap; 422 untuk `id_kel` / `no_agt` tidak valid.
7. Jalankan `php artisan test` pada tes terkait persetujuan / file baru sampai hijau.
8. Di `Web Admin/src/app/pages/PersetujuanAkun.tsx`, perluas state modal approve untuk menyimpan `noAgt` dan `idKel` (atau ekuivalen).
9. Tambah UI di modal Setujui: pencarian/pilihan `anggota` via `GET /anggota?search=...` dan pilihan `kel_sah` via `GET /kel-sah?search=...` (sesuaikan `per_page` kecil, mis. 20).
10. Validasi klien: salah satu terisi ⇒ keduanya wajib sebelum submit; keduanya kosong ⇒ boleh submit seperti sekarang.
11. Ubah `handleApprove` untuk `POST` body `{ no_agt, id_kel }` ketika ada nilai (trim); body `{}` jika keduanya kosong.
12. Uji manual: approve dengan pasangan valid, cek DB `anggota.ID_KS` dan `users.no_agt`; approve tanpa body tetap OK.

## Risks / Catatan

- **Konkurensi:** admin lain menghapus baris `anggota` antara validasi dan update — tangani dengan cek rows affected.
- **Member scope HP:** setelah approve, user memakai `no_agt` → `MemberScope::memberKelompokId` membaca `anggota.ID_KS`; pastikan nilai yang ditulis = `ID_KEL` yang dipilih agar scope konsisten.
- **Activity log:** tidak wajib di scope ini; jika produk memerlukan audit update `anggota`, tambahkan di fase berikutnya (mis. `ActivityLogService`).
- **Super admin vs admin:** keduanya sudah lewat middleware `admin`; tidak ada perubahan route.
