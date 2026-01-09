<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DataAoAutoGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Seed id_sequences
        DB::table('id_sequences')->insert([
            'entity_type' => 'data-ao',
            'kode_role' => '5',
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_create_without_id_auto_generates(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/data-ao', [
            'NO_AGT' => '123456',
            'NAMA' => 'Test AO Auto',
            'STAT' => 'Active',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['ID_AO']]);

        $idAo = $response->json('data.ID_AO');
        $this->assertEquals(12, strlen($idAo));
        $this->assertStringStartsWith('0160055', $idAo);
        $this->assertEquals('016005500001', $idAo);
    }

    public function test_create_with_valid_id_accepts(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/data-ao', [
            'ID_AO' => '016005599998',
            'NO_AGT' => '123456',
            'NAMA' => 'Test AO Manual',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['data' => ['ID_AO' => '016005599998']]);
    }

    public function test_create_with_duplicate_id_rejects(): void
    {
        // Create first record
        $this->actingAs($this->user)->postJson('/api/data-ao', [
            'ID_AO' => '016005599997',
            'NO_AGT' => '123456',
            'NAMA' => 'First',
        ]);

        // Try duplicate
        $response = $this->actingAs($this->user)->postJson('/api/data-ao', [
            'ID_AO' => '016005599997',
            'NO_AGT' => '123457',
            'NAMA' => 'Second',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ID_AO']);
    }

    public function test_create_with_invalid_format_rejects(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/data-ao', [
            'ID_AO' => '12345',
            'NO_AGT' => '123456',
            'NAMA' => 'Test',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ID_AO']);
    }

    public function test_sequential_ids_on_multiple_creates(): void
    {
        $response1 = $this->actingAs($this->user)->postJson('/api/data-ao', [
            'NAMA' => 'AO 1',
        ]);

        $response2 = $this->actingAs($this->user)->postJson('/api/data-ao', [
            'NAMA' => 'AO 2',
        ]);

        $response3 = $this->actingAs($this->user)->postJson('/api/data-ao', [
            'NAMA' => 'AO 3',
        ]);

        $this->assertEquals('016005500001', $response1->json('data.ID_AO'));
        $this->assertEquals('016005500002', $response2->json('data.ID_AO'));
        $this->assertEquals('016005500003', $response3->json('data.ID_AO'));
    }
}
