<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DataPengelolaAutoGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        DB::table('id_sequences')->insert([
            'entity_type' => 'data-pengelola',
            'kode_role' => '6',
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_create_without_id_auto_generates(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/data-pengelola', [
            'NO_AGT' => '123456',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['ID_PENG']]);

        $id = $response->json('data.ID_PENG');
        $this->assertEquals(12, strlen($id));
        $this->assertStringStartsWith('0160056', $id);
    }

    public function test_create_with_valid_id_accepts(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/data-pengelola', [
            'ID_PENG' => '016005699998',
            'NO_AGT' => '123456',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['data' => ['ID_PENG' => '016005699998']]);
    }

    public function test_create_with_duplicate_id_rejects(): void
    {
        $this->actingAs($this->user)->postJson('/api/data-pengelola', [
            'ID_PENG' => '016005699997',
            'NO_AGT' => '123456',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/data-pengelola', [
            'ID_PENG' => '016005699997',
            'NO_AGT' => '123457',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ID_PENG']);
    }

    public function test_create_with_invalid_format_rejects(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/data-pengelola', [
            'ID_PENG' => 'WRONG',
            'NO_AGT' => '123456',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ID_PENG']);
    }
}
