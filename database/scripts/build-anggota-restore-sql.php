<?php

/**
 * Ekstrak statement INSERT untuk tabel `anggota` dari file mysqldump besar,
 * tulis file .sql kecil yang aman dijalankan setelah `php artisan migrate`.
 *
 * Usage:
 *   php database/scripts/build-anggota-restore-sql.php <input-dump.sql> [output.sql]
 */

declare(strict_types=1);

$inPath = $argv[1] ?? null;
$outPath = $argv[2] ?? dirname(__DIR__).'/dumps/anggota_restore_inserts.sql';

if (! is_string($inPath) || $inPath === '' || ! is_readable($inPath)) {
    fwrite(STDERR, "Usage: php build-anggota-restore-sql.php <input.sql> [output.sql]\n");
    exit(1);
}

$dir = dirname($outPath);
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$content = file_get_contents($inPath);
if ($content === false) {
    fwrite(STDERR, "Cannot read: {$inPath}\n");
    exit(1);
}

preg_match_all('/INSERT INTO `anggota` VALUES[^;]+;/', $content, $matches);
$inserts = $matches[0] ?? [];

if ($inserts === []) {
    fwrite(STDERR, "No INSERT INTO `anggota` found.\n");
    exit(1);
}

$header = <<<'SQL'
-- Restore data anggota (generated from mysqldump extract)
-- Jalankan SETELAH struktur tabel ada: php artisan migrate
SET NAMES utf8mb4;
SET SESSION FOREIGN_KEY_CHECKS=0;

SQL;

$footer = <<<'SQL'

SET SESSION FOREIGN_KEY_CHECKS=1;

SQL;

file_put_contents($outPath, $header.implode("\n", $inserts).$footer);

fwrite(STDOUT, 'Wrote '.count($inserts)." INSERT batch(es) to {$outPath}\n");
