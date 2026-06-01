<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\DataPenghasilan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MultiRowNoAgtChildRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_two_penghasilan_rows_with_same_no_agt(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        Anggota::create([
            'NO_AGT' => 'AGT-DUP-01',
            'NAMA' => 'Tester',
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'NO_AGT' => 'AGT-DUP-01',
            'PENGHASILAN' => '100',
            'PENGELUARAN' => '10',
            'TGL_DATA' => '2026-01-01',
        ];

        $r1 = $this->postJson('/api/data-penghasilan', $payload);
        $r1->assertCreated();
        $id1 = $r1->json('data.id');
        $this->assertNotNull($id1);

        $payload['PENGHASILAN'] = '200';
        $r2 = $this->postJson('/api/data-penghasilan', $payload);
        $r2->assertCreated();
        $id2 = $r2->json('data.id');
        // Skema: satu baris per NO_AGT — POST kedua meng-update baris yang sama (upsert).
        $this->assertSame((string) $id1, (string) $id2);
        $this->assertSame(1, DataPenghasilan::query()->where('NO_AGT', 'AGT-DUP-01')->count());

        $upd = $this->putJson("/api/data-penghasilan/{$id1}", [
            'PENGHASILAN' => '150',
        ]);
        $upd->assertOk();
        $this->assertSame('150', DataPenghasilan::query()->find($id1)?->PENGHASILAN);
        $this->assertSame('150', DataPenghasilan::query()->find($id2)?->PENGHASILAN);

        $this->deleteJson("/api/data-penghasilan/{$id1}")->assertNoContent();
        $this->assertSame(0, DataPenghasilan::query()->where('NO_AGT', 'AGT-DUP-01')->count());
    }

    public function test_data_trs_http_create_route_removed(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/data-trs', [
            'NO_AGT' => 'AGT-TRS-01',
            'STR_SP' => '1',
            'TGL_LAP' => '2026-02-01',
        ])->assertMethodNotAllowed();
    }
}
