<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Laravel queue driver "database" menyimpan payload job sebagai longText.
 * Di Firebird, VARCHAR pendek pada JOBS.PAYLOAD menyebabkan error -303 string truncation saat dispatch.
 */
class EnsureJobsTableCommand extends Command
{
    protected $signature = 'firebird:ensure-jobs-table
                            {--connection= : Nama koneksi database (default: koneksi aktif)}';

    protected $description = 'Buat / perbaiki tabel JOBS untuk queue database (+ generator ID, payload BLOB teks)';

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
            if (! Schema::connection($name)->hasTable('jobs')) {
                $this->info('Membuat tabel JOBS…');
                $db->unprepared(<<<'SQL'
CREATE TABLE JOBS (
    ID BIGINT NOT NULL,
    QUEUE VARCHAR(255) NOT NULL,
    PAYLOAD BLOB SUB_TYPE TEXT NOT NULL,
    ATTEMPTS SMALLINT NOT NULL,
    RESERVED_AT INTEGER,
    AVAILABLE_AT INTEGER NOT NULL,
    CREATED_AT INTEGER NOT NULL,
    CONSTRAINT PK_JOBS PRIMARY KEY (ID)
)
SQL);
                $db->unprepared('CREATE INDEX IDX_JOBS_QUEUE_AVAILABLE ON JOBS (QUEUE, AVAILABLE_AT)');
                $this->info('Tabel JOBS dibuat.');
            } else {
                $this->info('Tabel JOBS sudah ada — pastikan PAYLOAD cukup besar…');
            }

            $this->widenJobsPayloadColumn($db);
            $this->ensureJobsIdGeneratorAndTrigger($db);

            $this->info('Selesai. Queue database dapat menyimpan payload job.');
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function widenJobsPayloadColumn($db): void
    {
        try {
            $db->unprepared('ALTER TABLE JOBS ALTER COLUMN PAYLOAD TYPE BLOB SUB_TYPE TEXT NOT NULL');
        } catch (Throwable $e) {
            $this->warn('widenJobsPayloadColumn: '.$e->getMessage());
        }
    }

    private function ensureJobsIdGeneratorAndTrigger($db): void
    {
        $genName = 'GEN_JOBS_ID';
        $triggerName = 'TRG_JOBS_ID';

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

        $maxId = (int) ($db->table('JOBS')->max('ID') ?? 0);
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
CREATE TRIGGER {$triggerName} FOR JOBS
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
