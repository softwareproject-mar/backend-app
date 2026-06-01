<?php

namespace App\Services;

use App\Exceptions\InvalidEntityTypeException;
use App\Exceptions\MaximumIdLimitException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class IdGeneratorService
{
    /**
     * Generate new ID untuk entity type tertentu.
     *
     * @throws InvalidEntityTypeException
     * @throws MaximumIdLimitException
     */
    public function generate(string $entityType): string
    {
        $this->validateEntityType($entityType);

        $this->ensureSequenceRowExists($entityType);

        // Firebird tidak mendukung nested transaction / lockForUpdate via PDO.
        // Gunakan atomic increment langsung: UPDATE … SET last_number = last_number + 1,
        // lalu baca kembali nilainya. Satu statement = satu auto-commit di Firebird.
        DB::table('id_sequences')
            ->where('entity_type', $entityType)
            ->update([
                'last_number' => DB::raw('"LAST_NUMBER" + 1'),
                'updated_at' => now(),
            ]);

        $sequence = DB::table('id_sequences')
            ->where('entity_type', $entityType)
            ->first();

        if (! $sequence) {
            throw new InvalidEntityTypeException($entityType);
        }

        // PDO Firebird dengan CASE_LOWER mengembalikan properti stdClass lowercase
        $row = (array) $sequence;
        $nextNumber = (int) ($row['last_number'] ?? $row['LAST_NUMBER'] ?? 0);

        if ($nextNumber > 99999) {
            throw new MaximumIdLimitException($entityType);
        }

        $kodeObormas = config('id_generator.kode_obormas', '016005');
        $kodeRole = (string) ($row['kode_role'] ?? $row['KODE_ROLE'] ?? '');

        return $this->formatId($kodeObormas, $kodeRole, $nextNumber);
    }

    /**
     * Get entity configuration.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidEntityTypeException
     */
    public function getEntityConfig(string $entityType): array
    {
        $config = config("id_generator.entity_mappings.{$entityType}");

        if (! $config) {
            throw new InvalidEntityTypeException($entityType);
        }

        return $config;
    }

    /**
     * Validate entity type is supported.
     *
     * @throws InvalidEntityTypeException
     */
    public function validateEntityType(string $entityType): void
    {
        $mappings = config('id_generator.entity_mappings', []);

        if (! isset($mappings[$entityType])) {
            throw new InvalidEntityTypeException($entityType);
        }
    }

    /**
     * Pastikan baris id_sequences ada (deploy lama sering hanya punya tabel tanpa seed).
     * last_number diisi dari MAX id di tabel master agar tidak bentrok dengan data existing.
     */
    protected function ensureSequenceRowExists(string $entityType): void
    {
        if (DB::table('id_sequences')->where('entity_type', $entityType)->exists()) {
            return;
        }

        $config = config("id_generator.entity_mappings.{$entityType}");
        if (! is_array($config)) {
            return;
        }

        $kodeObormas = config('id_generator.kode_obormas', '016005');
        $last = $this->resolveLastRunningNumberFromTable(
            (string) $config['table'],
            (string) $config['id_field'],
            $kodeObormas
        );

        $now = now()->toDateTimeString();
        try {
            // Firebird: INSERT harus eksplisit, tidak pakai transaksi di luar
            DB::statement(
                'INSERT INTO "ID_SEQUENCES" ("ENTITY_TYPE","KODE_ROLE","LAST_NUMBER","CREATED_AT","UPDATED_AT") VALUES (?,?,?,?,?)',
                [$entityType, (string) $config['kode_role'], $last, $now, $now]
            );
        } catch (QueryException $e) {
            // Race: request lain sudah insert baris yang sama
            if (! DB::table('id_sequences')->where('entity_type', $entityType)->exists()) {
                throw $e;
            }
        }
    }

    /**
     * Ambil angka urut terakhir (5 digit) dari ID 12 digit di tabel master, atau 0.
     */
    protected function resolveLastRunningNumberFromTable(string $table, string $idField, string $kodeObormas): int
    {
        try {
            $maxId = DB::table($table)->max($idField);
            if ($maxId === null || $maxId === '') {
                return 0;
            }

            $maxId = (string) $maxId;
            if (strlen($maxId) !== 12 || ! ctype_digit($maxId)) {
                return 0;
            }

            if (! str_starts_with($maxId, $kodeObormas)) {
                return 0;
            }

            return max(0, (int) substr($maxId, 7));
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Format ID dari komponen-komponennya.
     *
     * Format: [KODE_OBORMAS][KODE_ROLE][RUNNING_NUMBER]
     *         016005         X          00001
     *         (6 digit)      (1 digit)  (5 digit)
     */
    protected function formatId(string $kodeObormas, string $kodeRole, int $number): string
    {
        $formattedNumber = str_pad((string) $number, 5, '0', STR_PAD_LEFT);

        return $kodeObormas.$kodeRole.$formattedNumber;
    }
}
