<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Firebird tidak mengisi BIGINT PK otomatis seperti MySQL AUTO_INCREMENT.
 * Tanpa generator + BEFORE INSERT trigger, INSERT dari EmailVerification::create()
 * mengirim ID null → error -625 pada kolom "EMAIL_VERIFICATIONS"."ID".
 */
class EnsureEmailVerificationsTableCommand extends Command
{
    protected $signature = 'firebird:ensure-email-verifications
                            {--connection= : Nama koneksi database (default: koneksi aktif)}';

    protected $description = 'Buat / lengkapi tabel EMAIL_VERIFICATIONS (+ generator/trigger ID) untuk OTP';

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
            if (! Schema::connection($name)->hasTable('email_verifications')) {
                $this->info('Membuat tabel EMAIL_VERIFICATIONS…');
                $db->unprepared(<<<'SQL'
CREATE TABLE EMAIL_VERIFICATIONS (
    ID BIGINT NOT NULL,
    EMAIL VARCHAR(255) NOT NULL,
    PURPOSE VARCHAR(255) DEFAULT 'register' NOT NULL,
    OTP_CODE VARCHAR(6) NOT NULL,
    EXPIRES_AT TIMESTAMP NOT NULL,
    ATTEMPTS SMALLINT DEFAULT 0 NOT NULL,
    VERIFIED_AT TIMESTAMP,
    CREATED_AT TIMESTAMP,
    UPDATED_AT TIMESTAMP,
    CONSTRAINT PK_EMAIL_VERIFICATIONS PRIMARY KEY (ID)
)
SQL);
                $db->unprepared('CREATE INDEX IDX_EV_EMAIL ON EMAIL_VERIFICATIONS (EMAIL)');
                $db->unprepared('CREATE INDEX IDX_EV_EMAIL_PURPOSE ON EMAIL_VERIFICATIONS (EMAIL, PURPOSE)');
                $this->info('Tabel EMAIL_VERIFICATIONS dibuat.');
            } else {
                $this->info('Tabel EMAIL_VERIFICATIONS sudah ada, cek kolom minimum…');
            }

            $this->ensureColumn($db, 'EMAIL_VERIFICATIONS', 'ID', 'ALTER TABLE EMAIL_VERIFICATIONS ADD ID BIGINT NOT NULL');
            $this->ensureColumn($db, 'EMAIL_VERIFICATIONS', 'EMAIL', 'ALTER TABLE EMAIL_VERIFICATIONS ADD EMAIL VARCHAR(255)');
            $this->ensureColumn($db, 'EMAIL_VERIFICATIONS', 'PURPOSE', 'ALTER TABLE EMAIL_VERIFICATIONS ADD PURPOSE VARCHAR(255) DEFAULT \'register\' NOT NULL');
            $this->ensureColumn($db, 'EMAIL_VERIFICATIONS', 'OTP_CODE', 'ALTER TABLE EMAIL_VERIFICATIONS ADD OTP_CODE VARCHAR(6)');
            $this->ensureColumn($db, 'EMAIL_VERIFICATIONS', 'EXPIRES_AT', 'ALTER TABLE EMAIL_VERIFICATIONS ADD EXPIRES_AT TIMESTAMP');
            $this->ensureColumn($db, 'EMAIL_VERIFICATIONS', 'ATTEMPTS', 'ALTER TABLE EMAIL_VERIFICATIONS ADD ATTEMPTS SMALLINT DEFAULT 0 NOT NULL');
            $this->ensureColumn($db, 'EMAIL_VERIFICATIONS', 'VERIFIED_AT', 'ALTER TABLE EMAIL_VERIFICATIONS ADD VERIFIED_AT TIMESTAMP');
            $this->ensureColumn($db, 'EMAIL_VERIFICATIONS', 'CREATED_AT', 'ALTER TABLE EMAIL_VERIFICATIONS ADD CREATED_AT TIMESTAMP');
            $this->ensureColumn($db, 'EMAIL_VERIFICATIONS', 'UPDATED_AT', 'ALTER TABLE EMAIL_VERIFICATIONS ADD UPDATED_AT TIMESTAMP');

            $this->ensureIdGeneratorAndTrigger($db);

            $this->info('Selesai. Generator/trigger ID untuk EMAIL_VERIFICATIONS siap.');
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

        $this->line("Menambah kolom {$column}…");
        $db->unprepared($ddl);
    }

    private function ensureIdGeneratorAndTrigger($db): void
    {
        $genName = 'GEN_EMAIL_VERIFICATIONS_ID';
        $triggerName = 'TRG_EMAIL_VERIFICATIONS_ID';

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

        $maxId = (int) ($db->table('EMAIL_VERIFICATIONS')->max('ID') ?? 0);
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
CREATE TRIGGER {$triggerName} FOR EMAIL_VERIFICATIONS
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
