<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KetuaKsAutoGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        DB::table('id_sequences')->insert([
            'entity_type' => 'ketua-ks',
            'kode_role' => '1',
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_create_without_id_auto_generates(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/ketua-ks', [
            'NO_AGT' => '123456',
            'NAMA' => 'Test Ketua',
            'STAT' => 'Active',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['ID_KET']]);

        $id = $response->json('data.ID_KET');
        $this->assertEquals(12, strlen($id));
        $this->assertStringStartsWith('0160051', $id);
    }

    public function test_create_with_valid_id_accepts(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/ketua-ks', [
            'ID_KET' => '016005199998',
            'NO_AGT' => '123456',
            'NAMA' => 'Test Manual',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['data' => ['ID_KET' => '016005199998']]);
    }

    public function test_create_with_duplicate_id_rejects(): void
    {
        $this->actingAs($this->user)->postJson('/api/ketua-ks', [
            'ID_KET' => '016005199997',
            'NO_AGT' => '123456',
            'NAMA' => 'First',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/ketua-ks', [
            'ID_KET' => '016005199997',
            'NO_AGT' => '123457',
            'NAMA' => 'Second',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ID_KET']);
    }

    public function test_create_with_invalid_format_rejects(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/ketua-ks', [
            'ID_KET' => 'INVALID',
            'NO_AGT' => '123456',
            'NAMA' => 'Test',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ID_KET']);
    }
}
