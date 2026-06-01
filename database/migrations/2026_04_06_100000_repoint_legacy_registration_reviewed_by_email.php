<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Koreksi setelah backfill salah: pindahkan registration_reviewed_by dari satu akun admin ke lain.
 * Hanya migrasi data lama; tidak mempengaruhi logika approve (tetap = user yang login).
 *
 * .env (opsional): REGISTRATION_LEGACY_REVIEWER_EMAIL + REPLACE_FROM — lihat config/obormas.php
 *
 * Jika REPLACE_FROM kosong: baris anggota dengan reviewed_by = id admin terkecil (≠ target)
 * diarahkan ke user dengan email REGISTRATION_LEGACY_REVIEWER_EMAIL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'registration_reviewed_by')) {
            return;
        }

        $targetEmail = config('obormas.legacy_registration_reviewer_email');
        if (! is_string($targetEmail) || $targetEmail === '') {
            return;
        }

        $targetId = DB::table('users')
            ->where('email', $targetEmail)
            ->whereIn('role', ['admin', 'super_admin'])
            ->value('id');

        if ($targetId === null) {
            return;
        }

        $fromEmail = config('obormas.legacy_registration_reviewer_replace_from_email');
        $fromId = null;
        if (is_string($fromEmail) && $fromEmail !== '') {
            $fromId = DB::table('users')->where('email', $fromEmail)->value('id');
        }

        if ($fromId === null) {
            $fromId = DB::table('users')
                ->whereIn('role', ['admin', 'super_admin'])
                ->orderBy('id')
                ->value('id');
        }

        if ($fromId === null || (int) $fromId === (int) $targetId) {
            return;
        }

        DB::table('users')
            ->where('role', 'user')
            ->where('registration_reviewed_by', $fromId)
            ->update(['registration_reviewed_by' => $targetId]);
    }

    public function down(): void
    {
        // Tidak di-rollback.
    }
};
