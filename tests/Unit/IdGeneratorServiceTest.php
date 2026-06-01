<?php

namespace Tests\Unit;

use App\Exceptions\InvalidEntityTypeException;
use App\Exceptions\MaximumIdLimitException;
use App\Services\IdGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IdGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected IdGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IdGeneratorService;

        // Seed id_sequences table ONLY if empty
        if (DB::table('id_sequences')->count() === 0) {
            DB::table('id_sequences')->insert([
                ['entity_type' => 'ketua-ks', 'kode_role' => '1', 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['entity_type' => 'kel-sah', 'kode_role' => '2', 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['entity_type' => 'data-lo', 'kode_role' => '3', 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['entity_type' => 'sekre-ks', 'kode_role' => '4', 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['entity_type' => 'data-ao', 'kode_role' => '5', 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['entity_type' => 'data-pengelola', 'kode_role' => '6', 'last_number' => 0, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function test_generate_id_with_correct_format(): void
    {
        $id = $this->service->generate('data-ao');

        $this->assertEquals(12, strlen($id));
        $this->assertStringStartsWith('016005', $id);
        $this->assertMatchesRegularExpression('/^\d{12}$/', $id);
    }

    public function test_generate_id_with_correct_role_code(): void
    {
        $idKetuaKs = $this->service->generate('ketua-ks');
        $idDataAo = $this->service->generate('data-ao');
        $idDataPengelola = $this->service->generate('data-pengelola');

        // Check kode_role at position 7 (0-indexed: 6)
        $this->assertEquals('1', substr($idKetuaKs, 6, 1));
        $this->assertEquals('5', substr($idDataAo, 6, 1));
        $this->assertEquals('6', substr($idDataPengelola, 6, 1));
    }

    public function test_sequential_running_number(): void
    {
        $id1 = $this->service->generate('data-ao');
        $id2 = $this->service->generate('data-ao');
        $id3 = $this->service->generate('data-ao');

        $num1 = (int) substr($id1, 7);
        $num2 = (int) substr($id2, 7);
        $num3 = (int) substr($id3, 7);

        $this->assertEquals(1, $num1);
        $this->assertEquals(2, $num2);
        $this->assertEquals(3, $num3);
    }

    public function test_throws_exception_for_invalid_entity(): void
    {
        $this->expectException(InvalidEntityTypeException::class);
        $this->expectExceptionMessage("Invalid entity type: 'invalid-entity'");

        $this->service->generate('invalid-entity');
    }

    public function test_throws_exception_at_maximum_limit(): void
    {
        // Set last_number to 99999
        DB::table('id_sequences')
            ->where('entity_type', 'data-ao')
            ->update(['last_number' => 99999]);

        $this->expectException(MaximumIdLimitException::class);
        $this->expectExceptionMessage("Maximum ID limit (99999) has been reached for entity 'data-ao'");

        $this->service->generate('data-ao');
    }

    public function test_format_running_number_with_leading_zeros(): void
    {
        // Reset sequence to 0 for consistent test
        DB::table('id_sequences')->where('entity_type', 'ketua-ks')->update(['last_number' => 0]);

        $id1 = $this->service->generate('ketua-ks'); // 00001
        $this->assertEquals('00001', substr($id1, 7));

        // Generate 97 more to get to 00098, then next will be 00099
        for ($i = 0; $i < 97; $i++) {
            $this->service->generate('ketua-ks');
        }

        $id99 = $this->service->generate('ketua-ks'); // 00099
        $this->assertEquals('00099', substr($id99, 7));

        $id100 = $this->service->generate('ketua-ks'); // 00100
        $this->assertEquals('00100', substr($id100, 7));

        // Generate 899 more to reach 00999, then next will be 01000
        for ($i = 0; $i < 899; $i++) {
            $this->service->generate('ketua-ks');
        }

        $id1000 = $this->service->generate('ketua-ks'); // 01000
        $this->assertEquals('01000', substr($id1000, 7));
    }

    public function test_validate_entity_type_passes_for_valid(): void
    {
        $this->service->validateEntityType('data-ao');
        $this->assertTrue(true); // No exception thrown
    }

    public function test_get_entity_config_returns_correct_data(): void
    {
        $config = $this->service->getEntityConfig('data-ao');

        $this->assertArrayHasKey('kode_role', $config);
        $this->assertArrayHasKey('table', $config);
        $this->assertArrayHasKey('id_field', $config);
        $this->assertEquals('5', $config['kode_role']);
        $this->assertEquals('data_ao', $config['table']);
        $this->assertEquals('ID_AO', $config['id_field']);
    }

    public function test_bootstraps_id_sequence_row_when_missing(): void
    {
        DB::table('id_sequences')->where('entity_type', 'sekre-ks')->delete();
        $this->assertSame(0, DB::table('id_sequences')->where('entity_type', 'sekre-ks')->count());

        $id = $this->service->generate('sekre-ks');

        $this->assertSame(12, strlen($id));
        $this->assertSame('4', substr($id, 6, 1));
        $this->assertTrue(DB::table('id_sequences')->where('entity_type', 'sekre-ks')->exists());
    }
}
