<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KelSahAutoGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        DB::table('id_sequences')->insert([
            'entity_type' => 'kel-sah',
            'kode_role' => '2',
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create prerequisite records for foreign keys
        DB::table('ketua_ks')->insert([
            'ID_KET' => '999999999991',
            'NO_AGT' => 'AGT-KETUA-1',
        ]);

        DB::table('ketua_ks')->insert([
            'ID_KET' => '999999999994',
            'NO_AGT' => 'AGT-KETUA-2',
        ]);

        DB::table('data_lo')->insert([
            'ID_LO' => '999999999992',
        ]);

        DB::table('data_lo')->insert([
            'ID_LO' => '999999999995',
        ]);

        DB::table('data_ao')->insert([
            'ID_AO' => '999999999993',
        ]);

        DB::table('data_ao')->insert([
            'ID_AO' => '999999999996',
        ]);
    }

    public function test_create_without_id_auto_generates(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/kel-sah', [
            'NAMA_KEL' => 'Test Kelompok',
            'ID_KETUA' => '999999999991',
            'ID_LO' => '999999999992',
            'ID_AO' => '999999999993',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['ID_KEL']]);

        $id = $response->json('data.ID_KEL');
        $this->assertEquals(12, strlen($id));
        $this->assertStringStartsWith('0160052', $id);
    }

    public function test_create_with_valid_id_accepts(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/kel-sah', [
            'ID_KEL' => '016005299998',
            'NAMA_KEL' => 'Test Manual',
            'ID_KETUA' => '999999999991',
            'ID_LO' => '999999999992',
            'ID_AO' => '999999999993',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['data' => ['ID_KEL' => '016005299998']]);
    }

    public function test_create_with_duplicate_id_rejects(): void
    {
        $this->actingAs($this->user)->postJson('/api/kel-sah', [
            'ID_KEL' => '016005299997',
            'NAMA_KEL' => 'First',
            'ID_KETUA' => '999999999991',
            'ID_LO' => '999999999992',
            'ID_AO' => '999999999993',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/kel-sah', [
            'ID_KEL' => '016005299997',
            'NAMA_KEL' => 'Second',
            'ID_KETUA' => '999999999994',
            'ID_LO' => '999999999995',
            'ID_AO' => '999999999996',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ID_KEL']);
    }

    public function test_create_with_invalid_format_rejects(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/kel-sah', [
            'ID_KEL' => '99999',
            'NAMA_KEL' => 'Test',
            'ID_KETUA' => '999999999991',
            'ID_LO' => '999999999992',
            'ID_AO' => '999999999993',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ID_KEL']);
    }
}
