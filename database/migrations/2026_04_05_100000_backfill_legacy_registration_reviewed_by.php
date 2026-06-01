<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data anggota yang disetujui/ditolak sebelum kolom registration_reviewed_by dipakai
 * sering punya NULL di sini sehingga UI "Diproses oleh" kosong.
 *
 * Ini migrasi SEKALI; bukan aturan siapa yang approve di aplikasi (itu = user login di API).
 * Prioritas: REGISTRATION_LEGACY_REVIEWER_EMAIL jika di-set (tebakan historis, bebas email admin).
 * Fallback: admin/super_admin ber-ID terkecil.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'registration_reviewed_by')) {
            return;
        }

        $legacyEmail = config('obormas.legacy_registration_reviewer_email');
        $defaultReviewerId = null;
        if (is_string($legacyEmail) && $legacyEmail !== '') {
            $defaultReviewerId = DB::table('users')
                ->where('email', $legacyEmail)
                ->whereIn('role', ['admin', 'super_admin'])
                ->value('id');
        }

        if ($defaultReviewerId === null) {
            $defaultReviewerId = DB::table('users')
                ->whereIn('role', ['admin', 'super_admin'])
                ->orderBy('id')
                ->value('id');
        }

        if ($defaultReviewerId === null) {
            return;
        }

        DB::table('users')
            ->where('role', 'user')
            ->whereIn('registration_status', ['approved', 'rejected'])
            ->whereNull('registration_reviewed_by')
            ->update(['registration_reviewed_by' => $defaultReviewerId]);
    }

    public function down(): void
    {
        // Tidak di-rollback: tidak ada cara aman membedakan baris yang di-backfill vs dicatat benar.
    }
};
