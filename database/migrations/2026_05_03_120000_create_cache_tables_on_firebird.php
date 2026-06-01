<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel cache Laravel (driver database) di Firebird.
 * Jalankan hanya jika pakai DB_CONNECTION=firebird dan CACHE_STORE=database.
 *
 * Alternatif (setara, seperti `firebird:ensure-personal-access-tokens`):
 *   php artisan firebird:ensure-cache-tables
 *
 * Selaras dengan MySQL/InnoDB (Laravel 0001_01_01_000001), hanya tipe Firebird yang disetara:
 * - cache: varchar(255) key PK | mediumtext value | int expiration → VARCHAR + BLOB SUB_TYPE 1 + INTEGER
 * Kolom Firebird di-quote UPPERCASE ("KEY", "VALUE", …) agar cocok dengan FirebirdUppercaseGrammar.
 */
return new class extends Migration
{
    private const CONNECTION = 'firebird';

    public function up(): void
    {
        if (config('database.default') !== self::CONNECTION) {
            return;
        }

        if (! array_key_exists(self::CONNECTION, config('database.connections', []))) {
            return;
        }

        try {
            DB::connection(self::CONNECTION)->getPdo();
        } catch (\Throwable) {
            return;
        }

        // Hindari duplikat per tabel (nama fisik Firebird biasanya CACHE / CACHE_LOCKS).
        // Jangan return sekali jika hanya cache yang ada — CACHE_LOCKS tetap bisa dibuat.
        if (! Schema::connection(self::CONNECTION)->hasTable('cache')) {
            DB::connection(self::CONNECTION)->unprepared(<<<'SQL'
CREATE TABLE CACHE (
    "KEY" VARCHAR(255) NOT NULL,
    "VALUE" BLOB SUB_TYPE 1 NOT NULL,
    "EXPIRATION" INTEGER NOT NULL,
    CONSTRAINT PK_CACHE PRIMARY KEY ("KEY")
);
SQL);
        }

        if (! Schema::connection(self::CONNECTION)->hasTable('cache_locks')) {
            DB::connection(self::CONNECTION)->unprepared(<<<'SQL'
CREATE TABLE CACHE_LOCKS (
    "KEY" VARCHAR(255) NOT NULL,
    "OWNER" VARCHAR(255) NOT NULL,
    "EXPIRATION" INTEGER NOT NULL,
    CONSTRAINT PK_CACHE_LOCKS PRIMARY KEY ("KEY")
);
SQL);
        }
    }

    public function down(): void
    {
        if (config('database.default') !== self::CONNECTION) {
            return;
        }

        if (! array_key_exists(self::CONNECTION, config('database.connections', []))) {
            return;
        }

        try {
            DB::connection(self::CONNECTION)->getPdo();
        } catch (\Throwable) {
            return;
        }

        if (Schema::connection(self::CONNECTION)->hasTable('cache_locks')) {
            DB::connection(self::CONNECTION)->unprepared('DROP TABLE CACHE_LOCKS;');
        }
        if (Schema::connection(self::CONNECTION)->hasTable('cache')) {
            DB::connection(self::CONNECTION)->unprepared('DROP TABLE CACHE;');
        }
    }
};
