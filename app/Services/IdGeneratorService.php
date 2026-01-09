<?php

namespace App\Services;

use App\Exceptions\InvalidEntityTypeException;
use App\Exceptions\MaximumIdLimitException;
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

        DB::beginTransaction();

        try {
            // Lock row untuk prevent race condition
            $sequence = DB::table('id_sequences')
                ->where('entity_type', $entityType)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                throw new InvalidEntityTypeException($entityType);
            }

            // Increment running number
            $nextNumber = $sequence->last_number + 1;

            // Check maximum limit
            if ($nextNumber > 99999) {
                throw new MaximumIdLimitException($entityType);
            }

            // Format ID
            $kodeObormas = config('id_generator.kode_obormas', '16005');
            $kodeRole = $sequence->kode_role;
            $id = $this->formatId($kodeObormas, $kodeRole, $nextNumber);

            // Update sequence
            DB::table('id_sequences')
                ->where('entity_type', $entityType)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return $id;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
