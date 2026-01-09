<?php

namespace Tests\Unit\Rules;

use App\Rules\ValidIdFormat;
use Tests\TestCase;

class ValidIdFormatTest extends TestCase
{
    public function test_passes_with_valid_id(): void
    {
        $rule = new ValidIdFormat('data-ao');

        $passes = true;
        $fail = function ($message) use (&$passes) {
            $passes = false;
        };

        $rule->validate('ID_AO', '016005500001', $fail);

        $this->assertTrue($passes);
    }

    public function test_fails_with_wrong_length(): void
    {
        $rule = new ValidIdFormat('data-ao');

        $passes = true;
        $message = '';
        $fail = function ($msg) use (&$passes, &$message) {
            $passes = false;
            $message = $msg;
        };

        $rule->validate('ID_AO', '12345', $fail);

        $this->assertFalse($passes);
        $this->assertStringContainsString('exactly 12 digits', $message);
    }

    public function test_fails_with_non_numeric(): void
    {
        $rule = new ValidIdFormat('data-ao');

        $passes = true;
        $message = '';
        $fail = function ($msg) use (&$passes, &$message) {
            $passes = false;
            $message = $msg;
        };

        $rule->validate('ID_AO', '01600550000A', $fail);

        $this->assertFalse($passes);
        $this->assertStringContainsString('only numeric', $message);
    }

    public function test_fails_with_wrong_kode_obormas(): void
    {
        $rule = new ValidIdFormat('data-ao');

        $passes = true;
        $message = '';
        $fail = function ($msg) use (&$passes, &$message) {
            $passes = false;
            $message = $msg;
        };

        $rule->validate('ID_AO', '099999500001', $fail);

        $this->assertFalse($passes);
        $this->assertStringContainsString('must start with 016005', $message);
    }

    public function test_fails_with_wrong_kode_role(): void
    {
        $rule = new ValidIdFormat('data-ao');

        $passes = true;
        $message = '';
        $fail = function ($msg) use (&$passes, &$message) {
            $passes = false;
            $message = $msg;
        };

        // Using kode_role 6 (data-pengelola) for data-ao entity
        $rule->validate('ID_AO', '016005600001', $fail);

        $this->assertFalse($passes);
        $this->assertStringContainsString('invalid role code', $message);
    }

    public function test_fails_with_zero_running_number(): void
    {
        $rule = new ValidIdFormat('data-ao');

        $passes = true;
        $message = '';
        $fail = function ($msg) use (&$passes, &$message) {
            $passes = false;
            $message = $msg;
        };

        $rule->validate('ID_AO', '016005500000', $fail);

        $this->assertFalse($passes);
        $this->assertStringContainsString('invalid running number', $message);
    }

    public function test_passes_with_maximum_running_number(): void
    {
        $rule = new ValidIdFormat('ketua-ks');

        $passes = true;
        $fail = function ($message) use (&$passes) {
            $passes = false;
        };

        $rule->validate('ID_KET', '016005199999', $fail);

        $this->assertTrue($passes);
    }

    public function test_passes_for_all_entity_types(): void
    {
        $entities = [
            ['type' => 'ketua-ks', 'id' => '016005100001'],
            ['type' => 'kel-sah', 'id' => '016005200001'],
            ['type' => 'data-lo', 'id' => '016005300001'],
            ['type' => 'sekre-ks', 'id' => '016005400001'],
            ['type' => 'data-ao', 'id' => '016005500001'],
            ['type' => 'data-pengelola', 'id' => '016005600001'],
        ];

        foreach ($entities as $entity) {
            $rule = new ValidIdFormat($entity['type']);

            $passes = true;
            $fail = function ($message) use (&$passes) {
                $passes = false;
            };

            $rule->validate('ID', $entity['id'], $fail);

            $this->assertTrue($passes, "Failed for entity: {$entity['type']}");
        }
    }
}
