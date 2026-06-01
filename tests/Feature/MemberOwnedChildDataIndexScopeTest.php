<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberOwnedChildDataIndexScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_data_jlh_keluarga_index_only_lists_own_created_by(): void
    {
        Anggota::create(['NO_AGT' => 'IDX-JLH-A', 'NAMA' => 'Alfa']);
        Anggota::create(['NO_AGT' => 'IDX-JLH-B', 'NAMA' => 'Beta']);

        $userA = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'no_agt' => 'IDX-JLH-A',
        ]);
        $userB = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'no_agt' => 'IDX-JLH-B',
        ]);

        Sanctum::actingAs($userA);
        $this->postJson('/api/data-jlh-keluarga', [
            'NO_AGT' => 'IDX-JLH-A',
            'JLH_AGT_KEL' => 3,
            'TGL' => '2026-01-01',
        ])->assertCreated();

        Sanctum::actingAs($userB);
        $this->postJson('/api/data-jlh-keluarga', [
            'NO_AGT' => 'IDX-JLH-B',
            'JLH_AGT_KEL' => 5,
            'TGL' => '2026-01-02',
        ])->assertCreated();

        Sanctum::actingAs($userA);
        $response = $this->getJson('/api/data-jlh-keluarga');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('IDX-JLH-A', $response->json('data.0.NO_AGT'));
    }

    public function test_member_data_penghasilan_index_only_lists_own_created_by(): void
    {
        Anggota::create(['NO_AGT' => 'IDX-PEN-A', 'NAMA' => 'Alfa']);
        Anggota::create(['NO_AGT' => 'IDX-PEN-B', 'NAMA' => 'Beta']);

        $userA = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'no_agt' => 'IDX-PEN-A',
        ]);
        $userB = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'no_agt' => 'IDX-PEN-B',
        ]);

        Sanctum::actingAs($userA);
        $this->postJson('/api/data-penghasilan', [
            'NO_AGT' => 'IDX-PEN-A',
            'PENGHASILAN' => '100',
            'PENGELUARAN' => '10',
            'TGL_DATA' => '2026-01-01',
        ])->assertCreated();

        Sanctum::actingAs($userB);
        $this->postJson('/api/data-penghasilan', [
            'NO_AGT' => 'IDX-PEN-B',
            'PENGHASILAN' => '200',
            'PENGELUARAN' => '20',
            'TGL_DATA' => '2026-01-02',
        ])->assertCreated();

        Sanctum::actingAs($userA);
        $response = $this->getJson('/api/data-penghasilan');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('IDX-PEN-A', $response->json('data.0.NO_AGT'));
    }

    public function test_whitespace_role_user_still_gets_created_by_scope(): void
    {
        Anggota::create(['NO_AGT' => 'IDX-ROLE-A', 'NAMA' => 'Alfa']);
        Anggota::create(['NO_AGT' => 'IDX-ROLE-B', 'NAMA' => 'Beta']);

        $userA = User::factory()->create([
            'role' => ' user ',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'no_agt' => 'IDX-ROLE-A',
        ]);
        $userB = User::factory()->create([
            'role' => 'User',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'no_agt' => 'IDX-ROLE-B',
        ]);

        Sanctum::actingAs($userA);
        $this->postJson('/api/data-jlh-keluarga', [
            'NO_AGT' => 'IDX-ROLE-A',
            'JLH_AGT_KEL' => 2,
            'TGL' => '2026-03-01',
        ])->assertCreated();

        Sanctum::actingAs($userB);
        $this->postJson('/api/data-jlh-keluarga', [
            'NO_AGT' => 'IDX-ROLE-B',
            'JLH_AGT_KEL' => 7,
            'TGL' => '2026-03-02',
        ])->assertCreated();

        Sanctum::actingAs($userA);
        $response = $this->getJson('/api/data-jlh-keluarga');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('IDX-ROLE-A', $response->json('data.0.NO_AGT'));
    }
}
