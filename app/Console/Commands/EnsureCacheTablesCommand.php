<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Menyediakan tabel cache Laravel (driver database) di Firebird bila belum ada
 * (menghindari error -204 Table unknown CACHE saat php artisan cache:clear).
 *
 * Tipe selaras MySQL/InnoDB; nama kolom di DDL pakai huruf besar dalam kutip (KEY, VALUE, …) supaya sama dengan
 * query Laravel lewat App\Database\Query\Grammars\FirebirdUppercaseGrammar.
 */
class EnsureCacheTablesCommand extends Command
{
    protected $signature = 'firebird:ensure-cache-tables
                            {--connection= : Nama koneksi database (default: koneksi aktif)}';

    protected $description = 'Buat tabel cache + cache_locks untuk CACHE_STORE=database pada Firebird';

    public function handle(): int
    {
        $name = $this->option('connection') ?: config('database.default');
        $config = config("database.connections.{$name}");

        if (($config['driver'] ?? null) !== 'firebird') {
            $this->warn('Koneksi ini bukan firebird. Tidak ada yang dibuat.');

            return self::SUCCESS;
        }

        $db = DB::connection($name);

        try {
            // Nama tabel tanpa quote → Firebird menyimpan CACHE / CACHE_LOCKS (uppercase),
            // selaras dengan query Laravel/Firebird: DELETE FROM "CACHE".
            if (! Schema::connection($name)->hasTable('cache')) {
                $this->info('Membuat tabel CACHE…');
                $db->unprepared(<<<'SQL'
CREATE TABLE CACHE (
    "KEY" VARCHAR(255) NOT NULL,
    "VALUE" BLOB SUB_TYPE 1 NOT NULL,
    "EXPIRATION" INTEGER NOT NULL,
    CONSTRAINT PK_CACHE PRIMARY KEY ("KEY")
);
SQL);
                $this->info('Tabel CACHE dibuat.');
            } else {
                $this->info('Tabel CACHE sudah ada.');
            }

            if (! Schema::connection($name)->hasTable('cache_locks')) {
                $this->info('Membuat tabel CACHE_LOCKS…');
                $db->unprepared(<<<'SQL'
CREATE TABLE CACHE_LOCKS (
    "KEY" VARCHAR(255) NOT NULL,
    "OWNER" VARCHAR(255) NOT NULL,
    "EXPIRATION" INTEGER NOT NULL,
    CONSTRAINT PK_CACHE_LOCKS PRIMARY KEY ("KEY")
);
SQL);
                $this->info('Tabel CACHE_LOCKS dibuat.');
            } else {
                $this->info('Tabel CACHE_LOCKS sudah ada.');
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Selesai. Silakan jalankan: php artisan cache:clear');

        return self::SUCCESS;
    }
}
