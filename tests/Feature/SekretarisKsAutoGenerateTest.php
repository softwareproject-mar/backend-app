<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SekretarisKsAutoGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        DB::table('id_sequences')->insert([
            'entity_type' => 'sekre-ks',
            'kode_role' => '4',
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_create_without_id_auto_generates(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/sekretaris-ks', [
            'NO_AGT' => '123456',
            'NAMA' => 'Test Sekretaris',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['ID_SEKRE']]);

        $id = $response->json('data.ID_SEKRE');
        $this->assertEquals(12, strlen($id));
        $this->assertStringStartsWith('0160054', $id);
    }

    public function test_create_with_valid_id_accepts(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/sekretaris-ks', [
            'ID_SEKRE' => '016005499998',
            'NO_AGT' => '123456',
            'NAMA' => 'Test Manual',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['data' => ['ID_SEKRE' => '016005499998']]);
    }

    public function test_create_with_duplicate_id_rejects(): void
    {
        $this->actingAs($this->user)->postJson('/api/sekretaris-ks', [
            'ID_SEKRE' => '016005499997',
            'NO_AGT' => '123456',
            'NAMA' => 'First',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/sekretaris-ks', [
            'ID_SEKRE' => '016005499997',
            'NO_AGT' => '123457',
            'NAMA' => 'Second',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ID_SEKRE']);
    }

    public function test_create_with_invalid_format_rejects(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/sekretaris-ks', [
            'ID_SEKRE' => '123',
            'NO_AGT' => '123456',
            'NAMA' => 'Test',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ID_SEKRE']);
    }
}
