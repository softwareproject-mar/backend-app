<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\DataTrs;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DataTrsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_data_trs_from_operational_database(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        Anggota::create([
            'NO_AGT' => 'AGT-TRS-LIST',
            'NAMA' => 'Anggota TRS',
        ]);

        DataTrs::create([
            'NO_AGT' => 'AGT-TRS-LIST',
            'STR_SP' => '100000',
            'STR_SW' => '50000',
            'TGL_LAP' => '2026-03-01',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/data-trs?NO_AGT=AGT-TRS-LIST');

        $response->assertOk();
        $response->assertJsonPath('data.0.NO_AGT', 'AGT-TRS-LIST');
        $response->assertJsonPath('data.0.STR_SP', '100000');
        $this->assertNotNull($response->json('data.0.id'));
    }

    public function test_member_user_sees_only_own_no_agt_rows(): void
    {
        Anggota::create(['NO_AGT' => 'AGT-TRS-MINE', 'NAMA' => 'Mine']);
        Anggota::create(['NO_AGT' => 'AGT-TRS-OTHER', 'NAMA' => 'Other']);

        DataTrs::create([
            'NO_AGT' => 'AGT-TRS-MINE',
            'STR_SP' => '111',
            'TGL_LAP' => '2026-03-01',
        ]);
        DataTrs::create([
            'NO_AGT' => 'AGT-TRS-OTHER',
            'STR_SP' => '999',
            'TGL_LAP' => '2026-03-01',
        ]);

        $member = User::factory()->create([
            'role' => 'user',
            'no_agt' => 'AGT-TRS-MINE',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        Sanctum::actingAs($member);

        $response = $this->getJson('/api/data-trs');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.NO_AGT', 'AGT-TRS-MINE');
        $response->assertJsonPath('data.0.STR_SP', '111');
    }
}
