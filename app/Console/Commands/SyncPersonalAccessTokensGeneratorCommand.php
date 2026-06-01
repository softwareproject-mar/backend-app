<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Setelah impor data ke PERSONAL_ACCESS_TOKENS dengan ID eksplisit, jalankan ini
 * supaya GEN_personal_access_tokens_id mengikuti MAX(id) — kalau tidak, insert baru
 * bisa bentrok dengan PK_PAT (mis. ID = 1 lagi).
 */
class SyncPersonalAccessTokensGeneratorCommand extends Command
{
    protected $signature = 'firebird:sync-personal-access-tokens-generator
                            {--connection= : Nama koneksi database (default: koneksi aktif)}';

    protected $description = 'Set GEN_personal_access_tokens_id ke MAX(id) pada PERSONAL_ACCESS_TOKENS';

    public function handle(): int
    {
        $name = $this->option('connection') ?: config('database.default');
        if ((config("database.connections.{$name}.driver") ?? null) !== 'firebird') {
            $this->warn('Bukan koneksi firebird.');

            return self::SUCCESS;
        }

        $db = DB::connection($name);

        try {
            $max = $db->table('personal_access_tokens')->max('id');
            $max = (int) ($max ?? 0);

            $db->statement('SET GENERATOR GEN_personal_access_tokens_id TO '.$max);

            $this->info("GEN_personal_access_tokens_id diset ke {$max} (MAX(id)). Insert berikutnya akan memakai ID > {$max}.");
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
