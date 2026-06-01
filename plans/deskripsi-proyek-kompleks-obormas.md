# Cerita Proyek: Sistem Kelompok Sahabat Obor Mas (Backend + Aplikasi + Web Admin)

Dokumen ini merangkum **alur**, **peran**, **tantangan**, dan **solusi** pada proyek yang menurut saya kompleks—cocok sebagai bahan penjelasan singkat ke HRD atau dokumentasi teknis ringkas.

---

## Ringkasannya (elevator pitch)

Saya terlibat dalam pembangunan **sistem informasi berbasis API** untuk organisasi kelompok: **backend Laravel** sebagai otak data dan autentikasi, **aplikasi mobile hybrid (React + Capacitor)** untuk anggota di lapangan, dan **Web Admin (React)** untuk admin mengelola master data, persetujuan akun, dan operasional. Kompleksitas utamanya ada pada **aturan bisnis per peran**, **keamanan login per perangkat khusus anggota**, serta **integrasi banyak modul data** (anggota, LO/AO, kelompok, kunjungan, ekspor, dll.) dengan konsistensi antara dua klien.

---

## Alur sistem (high level)

1. **Pendaftaran anggota**: OTP email → data masuk sebagai menunggu persetujuan → admin/super admin menyetujui atau menolak dari Web Admin.
2. **Login**:
   - **Anggota (aplikasi)**: email/password + **device ID**; backend mengikat satu perangkat; perangkat lain ditolak sampai admin melakukan **reset perangkat**.
   - **Admin / super admin (web)**: email/password **tanpa** device ID; akses multi-perangkat untuk operasional web.
3. **Operasional data**: CRUD dan pencarian modul (anggota, data LO/AO, ketua/sekretaris KS, kelompok sahabat, kunjungan dengan foto & koordinat, dll.) dengan **scope per role** (anggota hanya melihat data kelompoknya, admin mengelola global, dll. sesuai kebijakan yang diimplementasi).
4. **Pelaporan**: ekspor PDF/Excel dari backend untuk beberapa modul agar format seragam.

Alur data umum: **klien (app/web) → REST API Laravel → validasi + service layer → database (dan sumber lain bila ada) → response JSON / file unduhan**.

---

## Peran saya (contoh framing untuk HRD)

- Merancang dan mengimplementasi **logika autentikasi & pembatasan perangkat** (hanya role `user`) serta **endpoint reset device** untuk admin.
- Menyelaraskan **kontrak API** dengan dua frontend: memastikan field seperti `device_id` **wajib di aplikasi** tetapi **tidak membebani login web admin** (validasi bertingkat: FormRequest vs `AuthService`).
- Mengerjakan fitur **UX data**: autocomplete (misalnya nomor anggota → nama terisi otomatis di Data LO seperti Data AO; ID pengelola menampilkan nama di form Kelompok Sahabat), tabel & label modul, serta penyesuaian export (mis. PDF kunjungan).
- Menjaga **konsistensi perilaku** antar modul dan **uji otomatis** di area yang riskan (mis. device lock, approval).

*(Sesuaikan kalimat “saya” dengan konteks tim Anda: solo vs peran tim.)*

---

## Tantangan utama

| Tantangan | Kenapa sulit |
|-----------|----------------|
| **Satu API, dua pola klien** | App anggota butuh `device_id`; web admin tidak. Jika `device_id` diset `required` global, **admin tidak bisa login** walau logika bisnis sudah benar di service. |
| **Keamanan vs UX** | Kunci perangkat harus tegas untuk anggota, tetapi tidak boleh mengunci admin yang sah akses dari browser / banyak perangkat. |
| **Banyak modul & relasi** | Kelompok sahabat menghubungkan ketua, sekretaris, LO, AO, pengelola; autocomplete harus konsisten dan data **nama** harus tersedia dari API (join/resource). |
| **Legacy & migrasi** | Perubahan skema (status registrasi, `no_agt`, multi-row, dll.) perlu migrasi dan kadang backfill agar data lama tidak rusak. |
| **Regresi** | Setiap perubahan di auth mempengaruhi seluruh klien; perlu tes fitur untuk device lock dan login admin tanpa device. |

---

## Solusi (yang diambil)

1. **Validasi login bertingkat**  
   - Di **FormRequest**: `device_id` **nullable** agar payload web admin (email + password saja) lolos validasi.  
   - Di **`AuthService::login`**: untuk **`user`**, `device_id` wajib non-kosong + pengecekan cocok dengan yang tersimpan / auto-bind pertama kali; untuk **admin/super_admin**, blok device **dilewati** sepenuhnya.

2. **Pemisahan concern**  
   Controller tipis, **service** untuk aturan bisnis auth dan data, **resource** API untuk bentuk JSON konsisten (mis. nama pengelola dari join anggota).

3. **Keseragaman di frontend**  
   Pola yang sama (autocomplete + field nama read-only) diterapkan antar modul agar pengguna tidak bingung dan mengurangi kesalahan input.

4. **Automated tests**  
   Skenario kunci (login user dengan/mismatch device, admin tanpa device, reset device) di-cover agar refactor tidak merusak kebijakan keamanan.

---

## Apa yang ingin ditonjolkan ke HRD (poin “rumit dijelaskan singkat”)

- Saya membangun sistem **multi-platform** dengan **satu backend** dan **aturan akses berbeda** per channel (mobile vs web) **tanpa mengorbankan keamanan anggota**.  
- Kompleksitasnya bukan sekadar CRUD, tetapi **kebijakan produk yang dikodekan**: siapa boleh login dari mana, dengan bukti perangkat apa, dan bagaimana admin memperbaiki kasus ganti HP—semuanya harus **jelas, teruji, dan mudah dijelaskan ke non-teknis**.

---

## Stack teknologi (referensi singkat)

- **Backend**: PHP 8+, Laravel, Sanctum, migrasi DB, queue/job untuk email, ekspor (Dompdf, PhpSpreadsheet).  
- **Aplikasi**: React, TypeScript, Capacitor (`@capacitor/device` untuk ID perangkat).  
- **Web Admin**: React, TypeScript, integrasi REST ke backend yang sama.

---

## Lampiran

Cuplikan pertanyaan HRD pada gambar Anda: *menceritakan alur project paling kompleks, bagian yang diikuti, tantangan & solusi, serta kemampuan menjelaskan hal rumit secara singkat*—isi dokumen ini dirancang untuk menjawab kerangka tersebut dengan konteks proyek Obormas.

Jika Anda ingin versi **bahasa Inggris** atau versi **satu paragraf (<500 karakter)** khusus form lamaran, beri tahu saja.
