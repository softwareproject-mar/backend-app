<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportAnggotaFromSqlDumpCommand extends Command
{
    protected $signature = 'import:anggota-sql-dump
        {path : Path ke file .sql (mysqldump) yang berisi INSERT INTO `anggota`}
        {--clear : Hapus semua baris di tabel anggota sebelum import (disarankan jika mau full restore)}';

    protected $description = 'Import data anggota dari file SQL mysqldump (hanya statement INSERT INTO `anggota`)';

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

        $sql = file_get_contents($resolved);
        if ($sql === false) {
            $this->error('Gagal membaca file.');

            return self::FAILURE;
        }

        preg_match_all('/INSERT INTO `anggota` VALUES[^;]+;/', $sql, $matches);
        $statements = $matches[0] ?? [];

        if ($statements === []) {
            $this->error('Tidak ada statement INSERT INTO `anggota` di file ini.');

            return self::FAILURE;
        }

        $this->info('Ditemukan '.count($statements).' batch INSERT.');

        if (! Schema::hasTable('anggota')) {
            $this->error('Tabel `anggota` belum ada di database ini.');
            $this->line('Jalankan dulu: php artisan migrate');
            $this->line('(Pastikan .env mengarah ke database server yang benar.)');

            return self::FAILURE;
        }

        try {
            DB::statement('SET SESSION FOREIGN_KEY_CHECKS=0');

            if ($this->option('clear')) {
                $deleted = DB::table('anggota')->delete();
                $this->info("Tabel anggota dikosongkan ({$deleted} baris dihapus).");
            }

            foreach ($statements as $i => $stmt) {
                DB::unprepared($stmt);
                $this->line('  Batch '.($i + 1).'/'.count($statements).' OK');
            }
        } catch (\Throwable $e) {
            $this->error('Import gagal: '.$e->getMessage());

            try {
                DB::statement('SET SESSION FOREIGN_KEY_CHECKS=1');
            } catch (\Throwable) {
            }

            return self::FAILURE;
        }

        DB::statement('SET SESSION FOREIGN_KEY_CHECKS=1');

        $count = DB::table('anggota')->count();
        $this->info("Selesai. Total baris di anggota sekarang: {$count}");

        return self::SUCCESS;
    }
}
