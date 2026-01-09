<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip for SQLite (not supported)
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE data_kunjungan MODIFY NO_URT INT UNSIGNED NOT NULL AUTO_INCREMENT;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip for SQLite (not supported)
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE data_kunjungan MODIFY NO_URT INT UNSIGNED NOT NULL;');
    }
};
