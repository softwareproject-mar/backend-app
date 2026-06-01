<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Memperbaiki user role "user" yang belum aktif tetapi masih registration_status "approved"
 * karena default kolom DB / migrasi sebelumnya melewati backfill (kolom sudah ada).
 * Hanya mengubah baris approved → pending; status "rejected" tidak disentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'registration_status')) {
            return;
        }

        DB::table('users')->where('is_active', true)->update(['registration_status' => 'approved']);

        DB::table('users')
            ->where('is_active', false)
            ->where('role', 'user')
            ->where('registration_status', 'approved')
            ->update(['registration_status' => 'pending']);

        DB::table('users')
            ->where('is_active', false)
            ->whereIn('role', ['admin', 'super_admin'])
            ->update(['registration_status' => 'approved']);
    }

    public function down(): void
    {
        // Tidak mengembalikan data; perbaikan satu arah.
    }
};
