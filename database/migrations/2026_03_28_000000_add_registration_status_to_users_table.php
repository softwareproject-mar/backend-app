<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'registration_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('registration_status', 20)
                    ->default('approved')
                    ->after('is_active');
            });
        }

        // Selalu sinkronkan nilai (fresh install atau kolom sudah ada tanpa backfill sebelumnya).
        DB::table('users')->where('is_active', true)->update(['registration_status' => 'approved']);

        // User biasa nonaktif: pending, kecuali sudah ditolak.
        DB::table('users')
            ->where('is_active', false)
            ->where('role', 'user')
            ->where('registration_status', '!=', 'rejected')
            ->update(['registration_status' => 'pending']);

        DB::table('users')
            ->where('is_active', false)
            ->whereIn('role', ['admin', 'super_admin'])
            ->update(['registration_status' => 'approved']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'registration_status')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('registration_status');
        });
    }
};
