<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreAnggotaDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_anggota_rejects_duplicate_no_agt_with_clear_message(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        Anggota::query()->create([
            'NO_AGT' => 'AGT001',
            'NAMA' => 'Sudah Ada',
            'ID_KS' => 'KS01',
            'ID_LO' => null,
            'ID_AO' => null,
            'ID_KS_ASL' => null,
            'TGL_MTS' => null,
            'TGL_AKTIF' => null,
            'TGL_JA' => null,
        ]);

        $response = $this->postJson('/api/anggota', [
            'NO_AGT' => 'AGT001',
            'NAMA' => 'Baru',
            'ID_KS' => 'KS02',
            'ID_KS_ASL' => null,
            'TGL_MTS' => null,
            'TGL_AKTIF' => null,
            'TGL_JA' => null,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['NO_AGT']);
        $msg = $response->json('errors.NO_AGT.0');
        $this->assertStringContainsString('sudah ada', strtolower((string) $msg));
        $this->assertStringNotContainsString('sql', strtolower((string) $msg));
    }

    public function test_store_anggota_allows_empty_id_ks_asl(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/anggota', [
            'NO_AGT' => 'AGT999',
            'NAMA' => 'Tanpa KS Asal',
            'ID_KS' => 'KS01',
            'ID_KS_ASL' => null,
            'TGL_MTS' => '2024-01-01',
            'TGL_AKTIF' => '2024-01-02',
            'TGL_JA' => '2024-01-03',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('anggota', [
            'NO_AGT' => 'AGT999',
            'ID_KS_ASL' => null,
        ]);
    }

    public function test_store_anggota_allows_empty_tgl_mts(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/anggota', [
            'NO_AGT' => 'AGT888',
            'NAMA' => 'Tanpa Tanggal MTS',
            'ID_KS' => 'KS01',
            'ID_KS_ASL' => null,
            'TGL_MTS' => null,
            'TGL_AKTIF' => '2024-06-01',
            'TGL_JA' => '2024-06-02',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('anggota', [
            'NO_AGT' => 'AGT888',
            'TGL_MTS' => null,
        ]);
    }
}
