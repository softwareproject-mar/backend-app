═══════════════════════════════════════════════════════════════════════
                    AUTHENTICATION MODULE (8 Endpoints)
═══════════════════════════════════════════════════════════════════════

POST   /api/auth/register
       Body: { name, email, password, password_confirmation }
       Validation: Email harus @gmail.com/@yahoo.com, Password min 8 char (huruf besar, kecil, angka, karakter khusus)
       Response: { success: true, message: "Registrasi berhasil", data: { user, token } }

POST   /api/auth/login
       Body: { email, password }
       Response: { success: true, message: "Login berhasil", data: { user, token } }

POST   /api/auth/logout
       Headers: Authorization: Bearer {token}
       Response: { success: true, message: "Logout berhasil" }

POST   /api/auth/forgot-password
       Body: { email }
       Action: Generate OTP dan kirim ke Gmail/Yahoo
       Response: { success: true, message: "OTP telah dikirim ke email Anda", data: { otp_expires_at } }

POST   /api/auth/verify-otp
       Body: { email, otp }
       Response: { success: true, message: "OTP valid", data: { reset_token } }

POST   /api/auth/reset-password
       Body: { email, reset_token, password, password_confirmation }
       Response: { success: true, message: "Password berhasil direset" }

GET    /api/auth/profile
       Headers: Authorization: Bearer {token}
       Response: { success: true, data: { id, name, email, created_at } }

PUT    /api/auth/profile
       Headers: Authorization: Bearer {token}
       Body: { name, email }
       Response: { success: true, message: "Profile berhasil diupdate", data: { user } }


═══════════════════════════════════════════════════════════════════════
                    KETUA KS MODULE (5 Endpoints)
═══════════════════════════════════════════════════════════════════════

GET    /api/ketua-ks
       Headers: Authorization: Bearer {token}
       Query: ?page=1&per_page=10&search=keyword&sort_by=id&order=desc
       Response: { success: true, data: [], meta: { current_page, last_page, total, per_page } }

GET    /api/ketua-ks/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, data: { id_ketua, nama, alamat, telp, id_ks, created_at, updated_at } }

POST   /api/ketua-ks
       Headers: Authorization: Bearer {token}
       Body: { nama, alamat, telp, id_ks, tanggal_lahir, jenis_kelamin }
       Action: Auto-generate ID format 016005100001 (kode obormas + role "1" + running number)
       Response: { success: true, message: "Data berhasil ditambahkan", data: { id_ketua: "016005100001", ... } }

PUT    /api/ketua-ks/{id}
       Headers: Authorization: Bearer {token}
       Body: { nama, alamat, telp, id_ks, tanggal_lahir, jenis_kelamin }
       Response: { success: true, message: "Data berhasil diupdate", data: { ... } }

DELETE /api/ketua-ks/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, message: "Data berhasil dihapus" }


═══════════════════════════════════════════════════════════════════════
                    SEKRE KS MODULE (5 Endpoints)
═══════════════════════════════════════════════════════════════════════

GET    /api/sekre-ks
       Headers: Authorization: Bearer {token}
       Query: ?page=1&per_page=10&search=keyword&sort_by=id&order=desc
       Response: { success: true, data: [], meta: { current_page, last_page, total, per_page } }

GET    /api/sekre-ks/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, data: { id_sekre, nama, alamat, telp, id_ks, created_at, updated_at } }

POST   /api/sekre-ks
       Headers: Authorization: Bearer {token}
       Body: { nama, alamat, telp, id_ks, tanggal_lahir, jenis_kelamin }
       Action: Auto-generate ID format 016005200001 (kode obormas + role "2" + running number)
       Response: { success: true, message: "Data berhasil ditambahkan", data: { id_sekre: "016005200001", ... } }

PUT    /api/sekre-ks/{id}
       Headers: Authorization: Bearer {token}
       Body: { nama, alamat, telp, id_ks, tanggal_lahir, jenis_kelamin }
       Response: { success: true, message: "Data berhasil diupdate", data: { ... } }

DELETE /api/sekre-ks/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, message: "Data berhasil dihapus" }


═══════════════════════════════════════════════════════════════════════
                    DATA LO MODULE (5 Endpoints)
═══════════════════════════════════════════════════════════════════════

GET    /api/data-lo
       Headers: Authorization: Bearer {token}
       Query: ?page=1&per_page=10&search=keyword&sort_by=id&order=desc
       Response: { success: true, data: [], meta: { current_page, last_page, total, per_page } }

GET    /api/data-lo/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, data: { id_lo, nama, wilayah, telp, email, created_at, updated_at } }

POST   /api/data-lo
       Headers: Authorization: Bearer {token}
       Body: { nama, wilayah, telp, email, alamat }
       Action: Auto-generate ID format 016005300001 (kode obormas + role "3" + running number)
       Response: { success: true, message: "Data berhasil ditambahkan", data: { id_lo: "016005300001", ... } }

PUT    /api/data-lo/{id}
       Headers: Authorization: Bearer {token}
       Body: { nama, wilayah, telp, email, alamat }
       Response: { success: true, message: "Data berhasil diupdate", data: { ... } }

DELETE /api/data-lo/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, message: "Data berhasil dihapus" }


═══════════════════════════════════════════════════════════════════════
                    DATA AO MODULE (5 Endpoints)
═══════════════════════════════════════════════════════════════════════

GET    /api/data-ao
       Headers: Authorization: Bearer {token}
       Query: ?page=1&per_page=10&search=keyword&sort_by=id&order=desc
       Response: { success: true, data: [], meta: { current_page, last_page, total, per_page } }

GET    /api/data-ao/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, data: { id_ao, nama, wilayah, telp, email, created_at, updated_at } }

POST   /api/data-ao
       Headers: Authorization: Bearer {token}
       Body: { nama, wilayah, telp, email, alamat }
       Action: Auto-generate ID format 016005500001 (kode obormas + role "5" + running number)
       Response: { success: true, message: "Data berhasil ditambahkan", data: { id_ao: "016005500001", ... } }

PUT    /api/data-ao/{id}
       Headers: Authorization: Bearer {token}
       Body: { nama, wilayah, telp, email, alamat }
       Response: { success: true, message: "Data berhasil diupdate", data: { ... } }

DELETE /api/data-ao/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, message: "Data berhasil dihapus" }


═══════════════════════════════════════════════════════════════════════
                    KEL SAH MODULE (5 Endpoints)
═══════════════════════════════════════════════════════════════════════

GET    /api/kel-sah
       Headers: Authorization: Bearer {token}
       Query: ?page=1&per_page=10&search=keyword&sort_by=id&order=desc
       Response: { success: true, data: [], meta: { current_page, last_page, total, per_page } }

GET    /api/kel-sah/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, data: { id_kel_sah, nama_kk, alamat, jumlah_anggota, kategori, created_at, updated_at } }

POST   /api/kel-sah
       Headers: Authorization: Bearer {token}
       Body: { nama_kk, alamat, jumlah_anggota, kategori, rt, rw, kelurahan, kecamatan }
       Action: Auto-generate ID format 016005400001 (kode obormas + role "4" + running number)
       Response: { success: true, message: "Data berhasil ditambahkan", data: { id_kel_sah: "016005400001", ... } }

PUT    /api/kel-sah/{id}
       Headers: Authorization: Bearer {token}
       Body: { nama_kk, alamat, jumlah_anggota, kategori, rt, rw, kelurahan, kecamatan }
       Response: { success: true, message: "Data berhasil diupdate", data: { ... } }

DELETE /api/kel-sah/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, message: "Data berhasil dihapus" }


═══════════════════════════════════════════════════════════════════════
                    DATA JUMLAH KELUARGA MODULE (5 Endpoints)
═══════════════════════════════════════════════════════════════════════

GET    /api/data-jlh-keluarga
       Headers: Authorization: Bearer {token}
       Query: ?page=1&per_page=10&id_kel_sah=xxx
       Response: { success: true, data: [], meta: { current_page, last_page, total, per_page } }

GET    /api/data-jlh-keluarga/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, data: { id, id_kel_sah, jumlah_laki, jumlah_perempuan, total, created_at } }

POST   /api/data-jlh-keluarga
       Headers: Authorization: Bearer {token}
       Body: { id_kel_sah, jumlah_laki, jumlah_perempuan }
       Action: Auto-calculate total = jumlah_laki + jumlah_perempuan
       Response: { success: true, message: "Data berhasil ditambahkan", data: { id, total, ... } }

PUT    /api/data-jlh-keluarga/{id}
       Headers: Authorization: Bearer {token}
       Body: { id_kel_sah, jumlah_laki, jumlah_perempuan }
       Response: { success: true, message: "Data berhasil diupdate", data: { ... } }

DELETE /api/data-jlh-keluarga/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, message: "Data berhasil dihapus" }


═══════════════════════════════════════════════════════════════════════
                    DATA PENGHASILAN MODULE (5 Endpoints)
═══════════════════════════════════════════════════════════════════════

GET    /api/data-penghasilan
       Headers: Authorization: Bearer {token}
       Query: ?page=1&per_page=10&id_kel_sah=xxx
       Response: { success: true, data: [], meta: { current_page, last_page, total, per_page } }

GET    /api/data-penghasilan/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, data: { id, id_kel_sah, penghasilan_utama, penghasilan_tambahan, total, created_at } }

POST   /api/data-penghasilan
       Headers: Authorization: Bearer {token}
       Body: { id_kel_sah, penghasilan_utama, penghasilan_tambahan }
       Action: Auto-calculate total = penghasilan_utama + penghasilan_tambahan
       Response: { success: true, message: "Data berhasil ditambahkan", data: { id, total, ... } }

PUT    /api/data-penghasilan/{id}
       Headers: Authorization: Bearer {token}
       Body: { id_kel_sah, penghasilan_utama, penghasilan_tambahan }
       Response: { success: true, message: "Data berhasil diupdate", data: { ... } }

DELETE /api/data-penghasilan/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, message: "Data berhasil dihapus" }


═══════════════════════════════════════════════════════════════════════
                    ANGGOTA MODULE (6 Endpoints - Special Logic)
═══════════════════════════════════════════════════════════════════════

GET    /api/anggota
       Headers: Authorization: Bearer {token}
       Query: ?page=1&per_page=10&search=keyword&id_ks=xxx
       Response: { success: true, data: [], meta: { current_page, last_page, total, per_page } }

GET    /api/anggota/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, data: { id_anggota, nama, alamat, telp, id_ks, created_at, updated_at } }

POST   /api/anggota/check
       Headers: Authorization: Bearer {token}
       Body: { id_anggota }
       Action: Cek apakah id_anggota exist, jika ya return data lengkap untuk auto-populate form
       Response: { success: true, exists: true, data: { id_anggota, nama, alamat, telp, email, ... } }

POST   /api/anggota
       Headers: Authorization: Bearer {token}
       Body: { id_anggota, id_ks }
       Logic: User input id_anggota → system auto-populate nama, alamat, telp → user pilih id_ks → save
       Response: { success: true, message: "Anggota berhasil ditambahkan", data: { ... } }

PUT    /api/anggota/{id}
       Headers: Authorization: Bearer {token}
       Body: { id_ks }
       Note: Hanya bisa update id_ks, data lain readonly karena dari master anggota
       Response: { success: true, message: "Data berhasil diupdate", data: { ... } }

DELETE /api/anggota/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, message: "Data berhasil dihapus" }


═══════════════════════════════════════════════════════════════════════
                    DATA PENGELOLA MODULE (5 Endpoints)
═══════════════════════════════════════════════════════════════════════

GET    /api/data-pengelola
       Headers: Authorization: Bearer {token}
       Query: ?page=1&per_page=10&search=keyword&sort_by=id&order=desc
       Response: { success: true, data: [], meta: { current_page, last_page, total, per_page } }

GET    /api/data-pengelola/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, data: { id_pengelola, nama, jabatan, telp, email, created_at, updated_at } }

POST   /api/data-pengelola
       Headers: Authorization: Bearer {token}
       Body: { nama, jabatan, telp, email, alamat }
       Action: Auto-generate ID format 016005600001 (kode obormas + role "6" + running number)
       Response: { success: true, message: "Data berhasil ditambahkan", data: { id_pengelola: "016005600001", ... } }

PUT    /api/data-pengelola/{id}
       Headers: Authorization: Bearer {token}
       Body: { nama, jabatan, telp, email, alamat }
       Response: { success: true, message: "Data berhasil diupdate", data: { ... } }

DELETE /api/data-pengelola/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, message: "Data berhasil dihapus" }


═══════════════════════════════════════════════════════════════════════
                    DATA TRS MODULE - READ ONLY (2 Endpoints)
═══════════════════════════════════════════════════════════════════════

GET    /api/data-trs
       Headers: Authorization: Bearer {token}
       Query: ?page=1&per_page=10&start_date=2025-01-01&end_date=2025-12-31&jenis=xxx
       Response: { success: true, data: [], meta: { current_page, last_page, total, per_page } }
       Note: READ ONLY - Tidak ada POST/PUT/DELETE

GET    /api/data-trs/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, data: { id_trs, tanggal, jenis, jumlah, keterangan, created_at } }
       Note: READ ONLY


═══════════════════════════════════════════════════════════════════════
                    REALISASI MODULE - READ ONLY (2 Endpoints)
═══════════════════════════════════════════════════════════════════════

GET    /api/realisasi
       Headers: Authorization: Bearer {token}
       Query: ?periode=2025-01&kategori=ao&wilayah=xxx
       Response: { success: true, data: [], summary: { total_realisasi, total_target, persentase } }
       Note: READ ONLY - Data aktual dari lapangan

GET    /api/realisasi/{id}
       Headers: Authorization: Bearer {token}
       Response: { success: true, data: { id_realisasi, periode, kategori, jumlah, tanggal, created_at } }
       Note: READ ONLY


═══════════════════════════════════════════════════════════════════════
                    DASHBOARD MODULE (3 Endpoints)
═══════════════════════════════════════════════════════════════════════

GET    /api/dashboard
       Headers: Authorization: Bearer {token}
       Query: ?periode=2025-01
       Action: JOIN master data (target) + realisasi (actual)
       Response: { 
         success: true, 
         data: {
           summary: {
             total_ketua_ks: 100,
             total_sekre_ks: 100,
             total_ao: 50,
             total_lo: 25,
             total_anggota: 500,
             total_kel_sah: 300
           },
           target_realisasi: [
             { kategori: "Ketua KS", target: 100, realisasi: 85, persentase: 85 },
             { kategori: "AO", target: 50, realisasi: 42, persentase: 84 },
             ...
           ],
           chart_data: {
             labels: ["Jan", "Feb", "Mar", ...],
             target: [100, 150, 200, ...],
             realisasi: [85, 130, 180, ...]
           }
         }
       }

GET    /api/dashboard/target-vs-realisasi
       Headers: Authorization: Bearer {token}
       Query: ?start_date=2025-01-01&end_date=2025-12-31&kategori=ao
       Response: { 
         success: true, 
         data: {
           comparison: [
             { bulan: "Januari", target: 50, realisasi: 42, gap: -8, persentase: 84 },
             { bulan: "Februari", target: 55, realisasi: 50, gap: -5, persentase: 91 },
             ...
           ]
         }
       }

GET    /api/dashboard/summary
       Headers: Authorization: Bearer {token}
       Response: { 
         success: true, 
         data: {
           total_master_data: 800,
           total_realisasi_bulan_ini: 350,
           realisasi_hari_ini: 15,
           persentase_pencapaian: 87.5,
           trending_up: true,
           last_updated: "2025-12-25 23:16:00"
         }
       }


═══════════════════════════════════════════════════════════════════════
                    UTILITY/HELPER MODULE (5 Endpoints)
═══════════════════════════════════════════════════════════════════════

GET    /api/kode-obormas
       Headers: Authorization: Bearer {token}
       Response: { success: true, data: { kode: "016005" } }
       Note: Untuk frontend display info

GET    /api/kode-role
       Headers: Authorization: Bearer {token}
       Response: { 
         success: true, 
         data: {
           ketua_ks: "1",
           sekre_ks: "2",
           lo: "3",
           kel_sah: "4",
           ao: "5",
           pengelola: "6"
         }
       }

POST   /api/generate-id-preview
       Headers: Authorization: Bearer {token}
       Body: { entity_type: "ao" }
       Action: Generate preview ID tanpa save ke database
       Response: { success: true, data: { preview_id: "016005500001" } }

GET    /api/ks-list
       Headers: Authorization: Bearer {token}
       Query: ?search=keyword
       Response: { success: true, data: [{ id_ks: "KS001", nama_ks: "KS Mawar", wilayah: "Jakarta" }, ...] }
       Note: Untuk dropdown select id_ks di form anggota

GET    /api/statistics
       Headers: Authorization: Bearer {token}
       Response: {
         success: true,
         data: {
           total_users: 50,
           total_entities: 1250,
           growth_rate: 12.5,
           active_sessions: 23
         }
       }


═══════════════════════════════════════════════════════════════════════
                    EXPORT/REPORT MODULE - OPTIONAL (5 Endpoints)
═══════════════════════════════════════════════════════════════════════

GET    /api/export/ketua-ks
       Headers: Authorization: Bearer {token}
       Query: ?format=excel&start_date=2025-01-01&end_date=2025-12-31
       Response: File download (.xlsx)

GET    /api/export/data-ao
       Headers: Authorization: Bearer {token}
       Query: ?format=pdf
       Response: File download (.pdf)

GET    /api/export/dashboard
       Headers: Authorization: Bearer {token}
       Query: ?format=pdf&periode=2025-01
       Response: PDF report dengan chart dan table

GET    /api/export/realisasi
       Headers: Authorization: Bearer {token}
       Query: ?format=excel&periode=2025-01
       Response: File download (.xlsx)

GET    /api/export/all-master-data
       Headers: Authorization: Bearer {token}
       Query: ?format=excel
       Response: File download (.xlsx) berisi semua master data dalam multiple sheets


═══════════════════════════════════════════════════════════════════════
                    TOTAL: 72 ENDPOINTS
═══════════════════════════════════════════════════════════════════════

Breakdown:
✓ Authentication: 8 endpoints
✓ Ketua KS: 5 endpoints
✓ Sekre KS: 5 endpoints
✓ Data LO: 5 endpoints
✓ Data AO: 5 endpoints
✓ Kel Sah: 5 endpoints
✓ Data Jumlah Keluarga: 5 endpoints
✓ Data Penghasilan: 5 endpoints
✓ Anggota (special): 6 endpoints
✓ Data Pengelola: 5 endpoints
✓ Data TRS (read-only): 2 endpoints
✓ Realisasi (read-only): 2 endpoints
✓ Dashboard: 3 endpoints
✓ Utility/Helper: 5 endpoints
✓ Export/Report: 5 endpoints

Standard Response Format:
Success: { success: true, message: "...", data: {...} }
Error: { success: false, message: "...", errors: {...} }

Authentication: Semua endpoint (kecuali auth/register, auth/login) wajib menggunakan:
Headers: Authorization: Bearer {token}
