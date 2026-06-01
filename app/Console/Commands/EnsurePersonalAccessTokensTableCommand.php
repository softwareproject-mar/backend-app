<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Menyediakan tabel Laravel Sanctum di Firebird bila belum ada (menghindari error -204 PERSONAL_ACCESS_TOKENS).
 */
class EnsurePersonalAccessTokensTableCommand extends Command
{
    protected $signature = 'firebird:ensure-personal-access-tokens
                            {--connection= : Nama koneksi database (default: koneksi aktif)}';

    protected $description = 'Buat tabel personal_access_tokens (+ generator/trigger) untuk Sanctum pada Firebird';

    public function handle(): int
    {
        $name = $this->option('connection') ?: config('database.default');
        $config = config("database.connections.{$name}");

        if (($config['driver'] ?? null) !== 'firebird') {
            $this->warn('Koneksi ini bukan firebird. Tidak ada yang dibuat.');

            return self::SUCCESS;
        }

        $db = DB::connection($name);

        $exists = (int) (
            $db->selectOne(
                'SELECT COUNT(*) AS C FROM RDB$RELATIONS WHERE RDB$SYSTEM_FLAG = 0 AND RDB$RELATION_NAME = ?',
                ['PERSONAL_ACCESS_TOKENS']
            )->c ?? 0
        );

        if ($exists === 0) {
            $this->info('Membuat tabel PERSONAL_ACCESS_TOKENS untuk Sanctum…');

            $db->statement(
                <<<'SQL'
CREATE TABLE personal_access_tokens (
    id BIGINT NOT NULL,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT NOT NULL,
    name BLOB SUB_TYPE TEXT NOT NULL,
    token VARCHAR(128) NOT NULL,
    abilities BLOB SUB_TYPE TEXT,
    last_used_at TIMESTAMP,
    expires_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT PK_PAT PRIMARY KEY (id),
    CONSTRAINT UQ_PAT_TOK UNIQUE (token)
)
SQL
            );
        } else {
            $this->info('Tabel PERSONAL_ACCESS_TOKENS sudah ada, cek kolom minimum…');
        }

        try {
            $this->ensureColumn($db, 'PERSONAL_ACCESS_TOKENS', 'ID', 'ALTER TABLE PERSONAL_ACCESS_TOKENS ADD ID BIGINT');
            $this->ensureColumn($db, 'PERSONAL_ACCESS_TOKENS', 'TOKENABLE_TYPE', 'ALTER TABLE PERSONAL_ACCESS_TOKENS ADD TOKENABLE_TYPE VARCHAR(255)');
            $this->ensureColumn($db, 'PERSONAL_ACCESS_TOKENS', 'TOKENABLE_ID', 'ALTER TABLE PERSONAL_ACCESS_TOKENS ADD TOKENABLE_ID BIGINT');
            $this->ensureColumn($db, 'PERSONAL_ACCESS_TOKENS', 'NAME', 'ALTER TABLE PERSONAL_ACCESS_TOKENS ADD NAME BLOB SUB_TYPE TEXT');
            $this->ensureColumn($db, 'PERSONAL_ACCESS_TOKENS', 'TOKEN', 'ALTER TABLE PERSONAL_ACCESS_TOKENS ADD TOKEN VARCHAR(128)');
            $this->ensureColumn($db, 'PERSONAL_ACCESS_TOKENS', 'ABILITIES', 'ALTER TABLE PERSONAL_ACCESS_TOKENS ADD ABILITIES BLOB SUB_TYPE TEXT');
            $this->ensureColumn($db, 'PERSONAL_ACCESS_TOKENS', 'LAST_USED_AT', 'ALTER TABLE PERSONAL_ACCESS_TOKENS ADD LAST_USED_AT TIMESTAMP');
            $this->ensureColumn($db, 'PERSONAL_ACCESS_TOKENS', 'EXPIRES_AT', 'ALTER TABLE PERSONAL_ACCESS_TOKENS ADD EXPIRES_AT TIMESTAMP');
            $this->ensureColumn($db, 'PERSONAL_ACCESS_TOKENS', 'CREATED_AT', 'ALTER TABLE PERSONAL_ACCESS_TOKENS ADD CREATED_AT TIMESTAMP');
            $this->ensureColumn($db, 'PERSONAL_ACCESS_TOKENS', 'UPDATED_AT', 'ALTER TABLE PERSONAL_ACCESS_TOKENS ADD UPDATED_AT TIMESTAMP');

            $this->widenPatTokenColumn($db);

            $genExists = (int) (
                $db->selectOne(
                    'SELECT COUNT(*) AS C FROM RDB$GENERATORS WHERE RDB$GENERATOR_NAME = ?',
                    ['GEN_PERSONAL_ACCESS_TOKENS_ID']
                )->c ?? 0
            );

            if ($genExists === 0) {
                $db->statement('CREATE GENERATOR GEN_personal_access_tokens_id');
            }

            $db->statement('SET GENERATOR GEN_personal_access_tokens_id TO 0');

            $db->statement(
                <<<'SQL'
CREATE TRIGGER TRG_personal_access_tokens_id FOR personal_access_tokens
ACTIVE BEFORE INSERT POSITION 0
AS BEGIN
  IF (NEW.id IS NULL) THEN
    NEW.id = GEN_ID(GEN_personal_access_tokens_id, 1);
END
SQL
            );

            $this->info('Selesai. Silakan coba login lagi.');
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
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

        $this->line("Menambah kolom {$column} di {$table}…");
        $db->unprepared($ddl);
    }

    /**
     * Hash token Sanctum 64 char; beri ruang cadangan & kolom lama yang terlalu ketat.
     */
    private function widenPatTokenColumn($db): void
    {
        try {
            $db->unprepared('ALTER TABLE PERSONAL_ACCESS_TOKENS ALTER COLUMN TOKEN TYPE VARCHAR(128)');
        } catch (Throwable $e) {
            $this->warn('widenPatTokenColumn: '.$e->getMessage());
        }
    }
}
