<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Impor baris user dari mysqldump yang strukturnya seperti dump lama (tanpa no_agt / registration_*),
 * ke tabel users Laravel saat ini dengan pemetaan kolom di layer SQL.
 */
class ImportUsersFromSqlDumpCommand extends Command
{
    protected $signature = 'import:users-sql-dump
        {path : Path ke file .sql yang berisi INSERT INTO `users`}
        {--update : Jika email sudah ada, update name/password/role/is_active/last_login dari dump}';

    protected $description = 'Import users dari mysqldump ke skema users Laravel (staging + INSERT…SELECT)';

    private const STAGING = 'users_import_staging';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (! is_string($path) || $path === '') {
            $this->error('Path tidak valid.');

            return self::FAILURE;
        }

        $resolved = realpath($path);
        if ($resolved === false || ! is_readable($resolved)) {
            $this->error("File tidak ditemukan atau tidak bisa dibaca: {$path}");

            return self::FAILURE;
        }

        if (! Schema::hasTable('users')) {
            $this->error('Tabel `users` belum ada. Jalankan: php artisan migrate');

            return self::FAILURE;
        }

        $required = ['no_agt', 'registration_status', 'registration_reviewed_at', 'registration_reviewed_by'];
        foreach ($required as $col) {
            if (! Schema::hasColumn('users', $col)) {
                $this->error("Kolom `users`.`{$col}` tidak ada. Pastikan migrasi terbaru sudah jalan.");

                return self::FAILURE;
            }
        }

        $sql = file_get_contents($resolved);
        if ($sql === false) {
            $this->error('Gagal membaca file.');

            return self::FAILURE;
        }

        preg_match_all('/INSERT INTO `users` VALUES[^;]+;/', $sql, $matches);
        $inserts = $matches[0] ?? [];
        if ($inserts === []) {
            $this->error('Tidak ada INSERT INTO `users` di file ini.');

            return self::FAILURE;
        }

        $this->info('Ditemukan '.count($inserts).' batch INSERT users.');

        try {
            DB::statement('SET SESSION FOREIGN_KEY_CHECKS=0');

            DB::statement('DROP TABLE IF EXISTS `'.self::STAGING.'`');
            DB::statement($this->stagingCreateSql());

            foreach ($inserts as $i => $stmt) {
                $rewritten = str_replace(
                    'INSERT INTO `users`',
                    'INSERT INTO `'.self::STAGING.'`',
                    $stmt,
                    $count
                );
                if ($count === 0) {
                    $this->error('Gagal menulis ulang statement INSERT.');

                    return self::FAILURE;
                }
                DB::unprepared($rewritten);
                $this->line('  Staging batch '.($i + 1).'/'.count($inserts).' OK');
            }

            $merged = (int) DB::affectingStatement($this->mergeInsertSql());
            $this->info("Baris baru dimasukkan ke `users`: {$merged}");

            if ($this->option('update')) {
                $updated = (int) DB::affectingStatement($this->updateExistingSql());
                $this->info("Baris diperbarui (email cocok): {$updated}");
            }

            $this->fixAutoIncrement();
        } catch (\Throwable $e) {
            $this->error('Import gagal: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            try {
                DB::statement('DROP TABLE IF EXISTS `'.self::STAGING.'`');
            } catch (\Throwable) {
            }
            try {
                DB::statement('SET SESSION FOREIGN_KEY_CHECKS=1');
            } catch (\Throwable) {
            }
        }

        $total = DB::table('users')->count();
        $this->info("Total user di database sekarang: {$total}");

        return self::SUCCESS;
    }

    private function stagingCreateSql(): string
    {
        return 'CREATE TABLE `'.self::STAGING.'` (
  `id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_import_staging_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }

    private function mergeInsertSql(): string
    {
        $st = self::STAGING;

        return <<<SQL
INSERT INTO `users` (
  `id`, `name`, `email`, `no_agt`, `email_verified_at`, `password`, `remember_token`,
  `role`, `is_active`, `registration_status`, `last_login_at`,
  `registration_reviewed_at`, `registration_reviewed_by`, `created_at`, `updated_at`
)
SELECT
  s.`id`,
  s.`name`,
  s.`email`,
  NULL,
  s.`email_verified_at`,
  s.`password`,
  s.`remember_token`,
  s.`role`,
  s.`is_active`,
  CASE
    WHEN s.`is_active` = 1 THEN 'approved'
    WHEN s.`role` IN ('admin', 'super_admin') THEN 'approved'
    ELSE 'pending'
  END,
  s.`last_login_at`,
  NULL,
  NULL,
  s.`created_at`,
  s.`updated_at`
FROM `{$st}` s
WHERE NOT EXISTS (SELECT 1 FROM `users` u WHERE u.`email` = s.`email` OR u.`id` = s.`id`)
SQL;
    }

    private function updateExistingSql(): string
    {
        $st = self::STAGING;

        return <<<SQL
UPDATE `users` u
INNER JOIN `{$st}` s ON s.`email` = u.`email`
SET
  u.`name` = s.`name`,
  u.`password` = s.`password`,
  u.`remember_token` = s.`remember_token`,
  u.`email_verified_at` = s.`email_verified_at`,
  u.`role` = s.`role`,
  u.`is_active` = s.`is_active`,
  u.`last_login_at` = s.`last_login_at`,
  u.`registration_status` = CASE
    WHEN s.`is_active` = 1 THEN 'approved'
    WHEN s.`role` IN ('admin', 'super_admin') THEN 'approved'
    ELSE 'pending'
  END,
  u.`updated_at` = s.`updated_at`
SQL;
    }

    private function fixAutoIncrement(): void
    {
        $max = (int) DB::table('users')->max('id');
        if ($max > 0) {
            DB::statement('ALTER TABLE `users` AUTO_INCREMENT = '.($max + 1));
        }
    }
}
