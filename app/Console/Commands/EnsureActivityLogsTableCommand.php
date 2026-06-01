<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class EnsureActivityLogsTableCommand extends Command
{
    protected $signature = 'firebird:ensure-activity-logs
                            {--connection= : Nama koneksi database (default: koneksi aktif)}';

    protected $description = 'Buat tabel ACTIVITY_LOGS (+ generator/trigger ID) untuk Firebird';

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
            if (! $this->tableExists($db, 'ACTIVITY_LOGS')) {
                $this->info('Membuat tabel ACTIVITY_LOGS…');
                $db->unprepared(<<<'SQL'
CREATE TABLE ACTIVITY_LOGS (
    ID BIGINT NOT NULL,
    USER_ID BIGINT NOT NULL,
    USER_NAME VARCHAR(255) CHARACTER SET UTF8 NOT NULL,
    RESOURCE_TYPE VARCHAR(100) CHARACTER SET UTF8 NOT NULL,
    RESOURCE_ID VARCHAR(255) CHARACTER SET UTF8,
    ACTION_TYPE VARCHAR(50) CHARACTER SET UTF8 NOT NULL,
    DESCRIPTION BLOB SUB_TYPE TEXT,
    STATUS VARCHAR(50) CHARACTER SET UTF8 DEFAULT 'success',
    ERROR_MESSAGE BLOB SUB_TYPE TEXT,
    OLD_DATA BLOB SUB_TYPE TEXT,
    NEW_DATA BLOB SUB_TYPE TEXT,
    IP_ADDRESS VARCHAR(45) CHARACTER SET UTF8,
    USER_AGENT BLOB SUB_TYPE TEXT,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT PK_ACTIVITY_LOGS PRIMARY KEY (ID)
);
SQL);
            } else {
                $this->info('Tabel ACTIVITY_LOGS sudah ada, cek kolom minimum…');
            }

            $this->ensureColumn($db, 'ACTIVITY_LOGS', 'ID', 'ALTER TABLE ACTIVITY_LOGS ADD ID BIGINT');
            $this->ensureColumn($db, 'ACTIVITY_LOGS', 'USER_ID', 'ALTER TABLE ACTIVITY_LOGS ADD USER_ID BIGINT');
            $this->ensureColumn($db, 'ACTIVITY_LOGS', 'USER_NAME', 'ALTER TABLE ACTIVITY_LOGS ADD USER_NAME VARCHAR(255) CHARACTER SET UTF8');
            $this->ensureColumn($db, 'ACTIVITY_LOGS', 'RESOURCE_TYPE', 'ALTER TABLE ACTIVITY_LOGS ADD RESOURCE_TYPE VARCHAR(100) CHARACTER SET UTF8');
            $this->ensureColumn($db, 'ACTIVITY_LOGS', 'RESOURCE_ID', 'ALTER TABLE ACTIVITY_LOGS ADD RESOURCE_ID VARCHAR(255) CHARACTER SET UTF8');
            $this->ensureColumn($db, 'ACTIVITY_LOGS', 'ACTION_TYPE', 'ALTER TABLE ACTIVITY_LOGS ADD ACTION_TYPE VARCHAR(50) CHARACTER SET UTF8');
            $this->ensureColumn($db, 'ACTIVITY_LOGS', 'DESCRIPTION', 'ALTER TABLE ACTIVITY_LOGS ADD DESCRIPTION BLOB SUB_TYPE TEXT');
            $this->ensureColumn($db, 'ACTIVITY_LOGS', 'STATUS', 'ALTER TABLE ACTIVITY_LOGS ADD STATUS VARCHAR(50) CHARACTER SET UTF8');
            $this->ensureColumn($db, 'ACTIVITY_LOGS', 'ERROR_MESSAGE', 'ALTER TABLE ACTIVITY_LOGS ADD ERROR_MESSAGE BLOB SUB_TYPE TEXT');
            $this->ensureColumn($db, 'ACTIVITY_LOGS', 'OLD_DATA', 'ALTER TABLE ACTIVITY_LOGS ADD OLD_DATA BLOB SUB_TYPE TEXT');
            $this->ensureColumn($db, 'ACTIVITY_LOGS', 'NEW_DATA', 'ALTER TABLE ACTIVITY_LOGS ADD NEW_DATA BLOB SUB_TYPE TEXT');
            $this->ensureColumn($db, 'ACTIVITY_LOGS', 'IP_ADDRESS', 'ALTER TABLE ACTIVITY_LOGS ADD IP_ADDRESS VARCHAR(45) CHARACTER SET UTF8');
            $this->ensureColumn($db, 'ACTIVITY_LOGS', 'USER_AGENT', 'ALTER TABLE ACTIVITY_LOGS ADD USER_AGENT BLOB SUB_TYPE TEXT');
            $this->ensureColumn($db, 'ACTIVITY_LOGS', 'CREATED_AT', 'ALTER TABLE ACTIVITY_LOGS ADD CREATED_AT TIMESTAMP');

            $this->ensureIdGeneratorAndTrigger($db);

            $this->info('Selesai. Tabel ACTIVITY_LOGS siap dipakai.');
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

    private function ensureIdGeneratorAndTrigger($db): void
    {
        $genName = 'GEN_ACTIVITY_LOGS_ID';
        $triggerName = 'TRG_ACTIVITY_LOGS_ID';

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

        $maxId = (int) ($db->table('ACTIVITY_LOGS')->max('ID') ?? 0);
        $db->unprepared("SET GENERATOR {$genName} TO {$maxId}");

        $triggerExists = (int) (
            $db->selectOne(
                'SELECT COUNT(*) AS C FROM RDB$TRIGGERS WHERE RDB$TRIGGER_NAME = ?',
                [$triggerName]
            )->c ?? 0
        );

        if ($triggerExists === 0) {
            $this->line("Membuat trigger {$triggerName}…");
            $db->unprepared(<<<SQL
CREATE TRIGGER {$triggerName} FOR ACTIVITY_LOGS
ACTIVE BEFORE INSERT POSITION 0
AS
BEGIN
  IF (NEW.ID IS NULL) THEN
    NEW.ID = GEN_ID({$genName}, 1);
END
SQL);
        }
    }
}
