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
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            // SQLite tidak mendukung ALTER COLUMN TYPE seperti Postgres; tes pakai :memory:.
            return;
        }
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE activity_logs MODIFY action_type VARCHAR(50) NOT NULL');
        } else {
            DB::statement('ALTER TABLE activity_logs ALTER COLUMN action_type TYPE VARCHAR(50)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            return;
        }
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE activity_logs MODIFY action_type ENUM('create', 'update', 'delete') NOT NULL");
        } else {
            DB::statement('ALTER TABLE activity_logs ALTER COLUMN action_type TYPE VARCHAR(20)');
        }
    }
};
