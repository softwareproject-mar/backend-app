<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidIdFormat implements ValidationRule
{
    protected string $entityType;

    public function __construct(string $entityType)
    {
        $this->entityType = $entityType;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if value is string
        if (! is_string($value)) {
            $fail("The {$attribute} must be a string.");

            return;
        }

        // Check length = 12 digit
        if (strlen($value) !== 12) {
            $fail("The {$attribute} must be exactly 12 digits.");

            return;
        }

        // Check numeric
        if (! ctype_digit($value)) {
            $fail("The {$attribute} must contain only numeric characters.");

            return;
        }

        // Check starts with kode_obormas
        $kodeObormas = config('id_generator.kode_obormas', '016005');
        if (! str_starts_with($value, $kodeObormas)) {
            $fail("The {$attribute} must start with {$kodeObormas}.");

            return;
        }

        // Check kode_role matches entity type
        $entityConfig = config("id_generator.entity_mappings.{$this->entityType}");
        if (! $entityConfig) {
            $fail("Invalid entity type: {$this->entityType}.");

            return;
        }

        $expectedKodeRole = $entityConfig['kode_role'];
        $actualKodeRole = substr($value, 6, 1); // Position 7 (0-indexed: 6)

        if ($actualKodeRole !== $expectedKodeRole) {
            $fail("The {$attribute} has invalid role code. Expected: {$expectedKodeRole}, Got: {$actualKodeRole}.");

            return;
        }

        // Check running number is valid (00001-99999)
        $runningNumber = (int) substr($value, 7);
        if ($runningNumber < 1 || $runningNumber > 99999) {
            $fail("The {$attribute} has invalid running number. Must be between 00001 and 99999.");

            return;
        }
    }
}
