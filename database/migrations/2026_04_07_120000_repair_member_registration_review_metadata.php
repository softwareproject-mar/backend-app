<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Anggota dengan status approved/rejected tetapi registration_reviewed_* masih NULL
 * (jalur lama / toggle Super Admin saat status bukan pending) membuat kolom
 * "Diproses oleh" / "Tanggal diproses" kosong di Web Admin.
 *
 * Mengisi reviewer default (sama logika dengan 2026_04_05) dan tanggal dari updated_at.
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

        if ($defaultReviewerId !== null) {
            DB::table('users')
                ->where('role', 'user')
                ->whereIn('registration_status', ['approved', 'rejected'])
                ->whereNull('registration_reviewed_by')
                ->update(['registration_reviewed_by' => $defaultReviewerId]);
        }

        if (Schema::hasColumn('users', 'registration_reviewed_at')) {
            DB::table('users')
                ->where('role', 'user')
                ->whereIn('registration_status', ['approved', 'rejected'])
                ->whereNull('registration_reviewed_at')
                ->update(['registration_reviewed_at' => DB::raw('updated_at')]);
        }
    }

    public function down(): void
    {
        // Tidak di-rollback: tidak ada cara aman membedakan baris yang diperbaiki vs tercatat benar.
    }
};
