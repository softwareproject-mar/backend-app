<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DataLoAutoGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        DB::table('id_sequences')->insert([
            'entity_type' => 'data-lo',
            'kode_role' => '3',
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_create_without_id_auto_generates(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/data-lo', [
            'NAMA' => 'Test LO',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['ID_LO']]);

        $id = $response->json('data.ID_LO');
        $this->assertEquals(12, strlen($id));
        $this->assertStringStartsWith('0160053', $id);
    }

    public function test_create_with_valid_id_accepts(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/data-lo', [
            'ID_LO' => '016005399998',
            'NAMA' => 'Test Manual',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['data' => ['ID_LO' => '016005399998']]);
    }

    public function test_create_with_duplicate_id_rejects(): void
    {
        $this->actingAs($this->user)->postJson('/api/data-lo', [
            'ID_LO' => '016005399997',
            'NAMA' => 'First',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/data-lo', [
            'ID_LO' => '016005399997',
            'NAMA' => 'Second',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ID_LO']);
    }

    public function test_create_with_invalid_format_rejects(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/data-lo', [
            'ID_LO' => 'ABC123',
            'NAMA' => 'Test',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ID_LO']);
    }
}
