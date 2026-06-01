<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Menyediakan tabel USERS pada Firebird bila belum ada.
 * Dipakai ketika migrasi default tidak bisa dijalankan penuh di Firebird.
 */
class EnsureUsersTableCommand extends Command
{
    protected $signature = 'firebird:ensure-users-table
                            {--connection= : Nama koneksi database (default: koneksi aktif)}';

    protected $description = 'Buat tabel USERS (format standar aplikasi) untuk Firebird';

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
            if (! $this->tableExists($db, 'USERS')) {
                $this->info('Membuat tabel USERS…');
                $db->unprepared(<<<'SQL'
CREATE TABLE USERS (
    ID BIGINT NOT NULL,
    NAME VARCHAR(255) CHARACTER SET UTF8 NOT NULL,
    EMAIL VARCHAR(255) CHARACTER SET UTF8 NOT NULL,
    NO_AGT VARCHAR(15) CHARACTER SET UTF8,
    ROLE VARCHAR(255) CHARACTER SET UTF8 DEFAULT 'user',
    IS_ACTIVE SMALLINT DEFAULT 1,
    REGISTRATION_STATUS VARCHAR(50) CHARACTER SET UTF8 DEFAULT 'approved',
    REGISTRATION_REVIEWED_AT TIMESTAMP,
    REGISTRATION_REVIEWED_BY BIGINT,
    LAST_LOGIN_AT TIMESTAMP,
    EMAIL_VERIFIED_AT TIMESTAMP,
    PASSWORD VARCHAR(255) CHARACTER SET UTF8 NOT NULL,
    REMEMBER_TOKEN VARCHAR(100) CHARACTER SET UTF8,
    CREATED_AT TIMESTAMP,
    UPDATED_AT TIMESTAMP,
    ID_KEL VARCHAR(12) CHARACTER SET UTF8,
    DEVICE_ID VARCHAR(191) CHARACTER SET UTF8,
    CONSTRAINT PK_USERS PRIMARY KEY (ID)
);
SQL);
            } else {
                $this->info('Tabel USERS sudah ada, cek kolom minimum…');
            }

            // Tambah kolom penting bila belum ada (tanpa mengubah kolom existing).
            $this->ensureColumn($db, 'USERS', 'NAME', 'ALTER TABLE USERS ADD NAME VARCHAR(255) CHARACTER SET UTF8');
            $this->ensureColumn($db, 'USERS', 'EMAIL', 'ALTER TABLE USERS ADD EMAIL VARCHAR(255) CHARACTER SET UTF8');
            $this->ensureColumn($db, 'USERS', 'NO_AGT', 'ALTER TABLE USERS ADD NO_AGT VARCHAR(15) CHARACTER SET UTF8');
            $this->ensureColumn($db, 'USERS', 'ROLE', 'ALTER TABLE USERS ADD ROLE VARCHAR(255) CHARACTER SET UTF8');
            $this->ensureColumn($db, 'USERS', 'IS_ACTIVE', 'ALTER TABLE USERS ADD IS_ACTIVE SMALLINT');
            $this->ensureColumn($db, 'USERS', 'REGISTRATION_STATUS', 'ALTER TABLE USERS ADD REGISTRATION_STATUS VARCHAR(50) CHARACTER SET UTF8');
            $this->ensureColumn($db, 'USERS', 'REGISTRATION_REVIEWED_AT', 'ALTER TABLE USERS ADD REGISTRATION_REVIEWED_AT TIMESTAMP');
            $this->ensureColumn($db, 'USERS', 'REGISTRATION_REVIEWED_BY', 'ALTER TABLE USERS ADD REGISTRATION_REVIEWED_BY BIGINT');
            $this->ensureColumn($db, 'USERS', 'LAST_LOGIN_AT', 'ALTER TABLE USERS ADD LAST_LOGIN_AT TIMESTAMP');
            $this->ensureColumn($db, 'USERS', 'EMAIL_VERIFIED_AT', 'ALTER TABLE USERS ADD EMAIL_VERIFIED_AT TIMESTAMP');
            $this->ensureColumn($db, 'USERS', 'PASSWORD', 'ALTER TABLE USERS ADD PASSWORD VARCHAR(255) CHARACTER SET UTF8');
            $this->ensureColumn($db, 'USERS', 'REMEMBER_TOKEN', 'ALTER TABLE USERS ADD REMEMBER_TOKEN VARCHAR(100) CHARACTER SET UTF8');
            $this->ensureColumn($db, 'USERS', 'CREATED_AT', 'ALTER TABLE USERS ADD CREATED_AT TIMESTAMP');
            $this->ensureColumn($db, 'USERS', 'UPDATED_AT', 'ALTER TABLE USERS ADD UPDATED_AT TIMESTAMP');
            $this->ensureColumn($db, 'USERS', 'ID_KEL', 'ALTER TABLE USERS ADD ID_KEL VARCHAR(12) CHARACTER SET UTF8');
            $this->ensureColumn($db, 'USERS', 'DEVICE_ID', 'ALTER TABLE USERS ADD DEVICE_ID VARCHAR(191) CHARACTER SET UTF8');
            $this->ensureUsersAutoIncrement($db);

            // Kolom lama sering lebih pendek dari skema Laravel → error -303 string truncation saat daftar/login.
            $this->widenUsersStringColumns($db);

            $this->info('Selesai. Tabel USERS siap dipakai.');
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function tableExists($db, string $table): bool
    {
        $row = $db->selectOne(
            'SELECT COUNT(*) AS C FROM RDB$RELATIONS WHERE COALESCE(RDB$SYSTEM_FLAG, 0) = 0 AND RDB$RELATION_NAME = ?',
            [strtoupper($table)]
        );

        return (int) ($row->c ?? 0) > 0;
    }

    private function columnExists($db, string $table, string $column): bool
    {
        $row = $db->selectOne(
            'SELECT COUNT(*) AS C FROM RDB$RELATION_FIELDS WHERE RDB$RELATION_NAME = ? AND RDB$FIELD_NAME = ?',
            [strtoupper($table), strtoupper($column)]
        );

        return (int) ($row->c ?? 0) > 0;
    }

    private function ensureColumn($db, string $table, string $column, string $ddl): void
    {
        if ($this->columnExists($db, $table, $column)) {
            return;
        }

        $this->line("Menambah kolom {$column}…");
        $db->unprepared($ddl);
    }

    private function ensureUsersAutoIncrement($db): void
    {
        // Firebird 2.5 tidak punya AUTO INCREMENT native; pakai generator + trigger.
        $genName = 'GEN_USERS_ID';
        $trgName = 'TRG_USERS_ID';

        $genExists = (int) (
            $db->selectOne(
                'SELECT COUNT(*) AS C FROM RDB$GENERATORS WHERE RDB$GENERATOR_NAME = ?',
                [$genName]
            )->c ?? 0
        );

        if ($genExists === 0) {
            $this->line("Membuat generator {$genName}…");
            $db->unprepared("CREATE GENERATOR {$genName}");
        }

        $maxId = (int) ($db->table('USERS')->max('ID') ?? 0);
        $db->unprepared("SET GENERATOR {$genName} TO {$maxId}");

        $trgExists = (int) (
            $db->selectOne(
                'SELECT COUNT(*) AS C FROM RDB$TRIGGERS WHERE RDB$TRIGGER_NAME = ?',
                [$trgName]
            )->c ?? 0
        );

        if ($trgExists === 0) {
            $this->line("Membuat trigger {$trgName}…");
            $db->unprepared(<<<SQL
CREATE TRIGGER {$trgName} FOR USERS
ACTIVE BEFORE INSERT POSITION 0
AS
BEGIN
  IF (NEW.ID IS NULL) THEN
    NEW.ID = GEN_ID({$genName}, 1);
END
SQL);
        }
    }

    /**
     * Perlebar VARCHAR yang sudah ada agar selaras Laravel (register: nama, email, device_id, hash password, dll.).
     */
    private function widenUsersStringColumns($db): void
    {
        $statements = [
            'ALTER TABLE USERS ALTER COLUMN NAME TYPE VARCHAR(255) CHARACTER SET UTF8',
            'ALTER TABLE USERS ALTER COLUMN EMAIL TYPE VARCHAR(255) CHARACTER SET UTF8',
            'ALTER TABLE USERS ALTER COLUMN PASSWORD TYPE VARCHAR(255) CHARACTER SET UTF8',
            'ALTER TABLE USERS ALTER COLUMN ROLE TYPE VARCHAR(255) CHARACTER SET UTF8',
            'ALTER TABLE USERS ALTER COLUMN REGISTRATION_STATUS TYPE VARCHAR(50) CHARACTER SET UTF8',
            'ALTER TABLE USERS ALTER COLUMN DEVICE_ID TYPE VARCHAR(191) CHARACTER SET UTF8',
            'ALTER TABLE USERS ALTER COLUMN REMEMBER_TOKEN TYPE VARCHAR(100) CHARACTER SET UTF8',
            'ALTER TABLE USERS ALTER COLUMN NO_AGT TYPE VARCHAR(64) CHARACTER SET UTF8',
            'ALTER TABLE USERS ALTER COLUMN ID_KEL TYPE VARCHAR(32) CHARACTER SET UTF8',
        ];

        foreach ($statements as $sql) {
            try {
                $db->unprepared($sql);
            } catch (Throwable $e) {
                $this->warn('widenUsersStringColumns: '.$e->getMessage());
            }
        }
    }
}
