<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Basis URL file storage untuk export (Excel/PDF) — kolom URL foto kunjungan
    |--------------------------------------------------------------------------
    |
    | Default di-hardcode untuk demo/show. Ganti lewat .env tanpa edit kode:
    | PUBLIC_STORAGE_BASE=https://domain-anda/storage
    |
    */
    'export_storage_base' => env('PUBLIC_STORAGE_BASE', 'http://103.253.212.105/obormas/storage'),

    /*
    |--------------------------------------------------------------------------
    | HANYA migrasi data lama — BUKAN aturan "siapa boleh approve"
    |--------------------------------------------------------------------------
    |
    | Setujui/Tolak di API selalu menyimpan registration_reviewed_by = user yang sedang login
    | (Sanctum). Env di bawah TIDAK dipakai saat approve/reject berjalan.
    |
    | Env ini hanya dibaca oleh migrasi (sekali jalan) untuk mengisi NULL pada baris lama yang
    | belum pernah mencatat pemroses, atau memperbaiki salah backfill. Boleh dikosongkan;
    | bukan wajib "admin@obormas" — isi email admin mana pun sebagai tebakan untuk data historis.
    |
    */
    'legacy_registration_reviewer_email' => env('REGISTRATION_LEGACY_REVIEWER_EMAIL'),

    /*
    | Hanya migrasi repoint: dari admin (email) ini ke target di legacy_registration_reviewer_email.
    |
    */
    'legacy_registration_reviewer_replace_from_email' => env('REGISTRATION_LEGACY_REVIEWER_REPLACE_FROM_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Scope ketat untuk role user (anggota) — default MATI agar API app lama tidak berubah
    |--------------------------------------------------------------------------
    |
    | strict_member_kelompok_scope:
    | - Listing & detail master anggota (GET /anggota, GET /anggota/{id}) untuk role user
    |   SELALU terbatas ke ID_KS dari no_agt (tanpa tergantung flag ini).
    | - Flag ini mengatur: blok CRUD/export master anggota untuk role user, filter kel-sah
    |   (dan modul lain yang memeriksa flag), serta aturan terkait.
    | strict_member_no_agt_same_kelompok: NO_AGT pada form data harus sekelompok dengan akun.
    |
    */
    'strict_member_kelompok_scope' => (bool) env('STRICT_MEMBER_KELOMPOK_SCOPE', false),

    'strict_member_no_agt_same_kelompok' => (bool) env('STRICT_MEMBER_NO_AGT_SAME_KELOMPOK', false),

    /*
    |--------------------------------------------------------------------------
    | Firebird — nama tabel legacy (biasanya huruf besar)
    |--------------------------------------------------------------------------
    */
    'firebird_data_trs_table' => env('FIREBIRD_DATA_TRS_TABLE', 'DATA_TRS'),

    /*
    |--------------------------------------------------------------------------
    | Modul Target & Realisasi — baris `target` untuk nominal monitoring
    |--------------------------------------------------------------------------
    |
    | Legacy sentinel (tidak dipakai sebagai default list/detail setelah periode bulanan).
    | Input admin memakai akhir bulan berjalan; simpan target ke `target.TGL_TGT` akhir bulan.
    |
    */
    'target_monitoring_tgl_tgt' => env('TARGET_MONITORING_TGL_TGT', '2099-01-01'),

    /*
    |--------------------------------------------------------------------------
    | Modul Target & Realisasi — kolom DATA_TRS yang dijumlahkan per baris
    |--------------------------------------------------------------------------
    |
    | Daftar nama kolom (huruf besar) harus subset kolom yang dibaca FirebirdService.
    | Default STR_SP — sesuai plan; ubah env TARGET_REALISASI_SUM_COLUMNS=STR_SP,STR_SW
    | (koma) bila rumus bisnis berubah.
    |
    */
    'target_realisasi_sum_columns' => array_values(array_filter(array_map('trim', explode(',', (string) env('TARGET_REALISASI_SUM_COLUMNS', 'STR_SP'))))) ?: ['STR_SP'],

];
