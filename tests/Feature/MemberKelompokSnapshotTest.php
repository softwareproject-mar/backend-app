<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\DataAo;
use App\Models\DataLo;
use App\Models\KelSah;
use App\Models\KetuaKs;
use App\Models\SekretarisKs;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberKelompokSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_returns_kelompok_and_anggota_sekelompok(): void
    {
        $this->seedKelompokGraph('KEL001', 'KET001', 'SEK001', 'LO001', 'AO001');

        Anggota::create([
            'NO_AGT' => 'MEM001',
            'NAMA' => 'Anggota Satu',
            'ID_KS' => 'KEL001',
        ]);
        Anggota::create([
            'NO_AGT' => 'MEM002',
            'NAMA' => 'Anggota Dua',
            'ID_KS' => 'KEL001',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'no_agt' => 'MEM001',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me/kelompok?per_page=10');

        $response->assertOk();
        $response->assertJsonPath('data.kelompok.ID_KEL', 'KEL001');
        $response->assertJsonPath('data.anggota_saya.NO_AGT', 'MEM001');
        $response->assertJsonPath('data.ketua.ID_KET', 'KET001');
        $response->assertJsonPath('data.sekretaris.ID_SEKRE', 'SEK001');
        $response->assertJsonPath('data.lo.ID_LO', 'LO001');
        $response->assertJsonPath('data.ao.ID_AO', 'AO001');
        $this->assertCount(2, $response->json('data.anggota_sekelompok.data'));
        $response->assertJsonPath('data.anggota_sekelompok.total', 2);
    }

    public function test_snapshot_returns_404_when_user_has_no_kelompok(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'no_agt' => null,
            'id_kel' => null,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/me/kelompok')->assertNotFound();
    }

    public function test_snapshot_works_with_only_user_id_kel_without_no_agt(): void
    {
        $this->seedKelompokGraph('KEL001', 'KET001', 'SEK001', 'LO001', 'AO001');

        Anggota::create([
            'NO_AGT' => 'MEM001',
            'NAMA' => 'Anggota Satu',
            'ID_KS' => 'KEL001',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'no_agt' => null,
            'id_kel' => 'KEL001',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me/kelompok?per_page=10');

        $response->assertOk();
        $response->assertJsonPath('data.kelompok.ID_KEL', 'KEL001');
        $response->assertJsonPath('data.anggota_saya', null);
        $response->assertJsonPath('data.ketua.ID_KET', 'KET001');
        $this->assertCount(1, $response->json('data.anggota_sekelompok.data'));
    }

    private function seedKelompokGraph(
        string $idKel,
        string $idKet,
        string $idSek,
        string $idLo,
        string $idAo,
    ): void {
        // data_lo.NO_AGT → FK anggota.NO_AGT (harus ada dulu)
        Anggota::create([
            'NO_AGT' => 'LO-'.$idLo,
            'NAMA' => 'Stub LO',
            'ID_KS' => null,
        ]);

        KetuaKs::create([
            'ID_KET' => $idKet,
            'NO_AGT' => 'KET-'.$idKet,
            'NAMA' => 'Ketua',
            'STAT' => null,
            'TGL_STAT' => null,
            'NO_SK' => null,
        ]);
        SekretarisKs::create([
            'ID_SEKRE' => $idSek,
            'NO_AGT' => 'SEK-'.$idSek,
            'NAMA' => 'Sekre',
            'STAT' => null,
            'TGL_STAT' => null,
            'NO_SK' => null,
        ]);
        DataLo::create([
            'ID_LO' => $idLo,
            'NO_AGT' => 'LO-'.$idLo,
            'ID_TP' => null,
            'NAMA' => 'LO',
            'STAT' => null,
            'TGL_STAT' => null,
        ]);
        DataAo::create([
            'ID_AO' => $idAo,
            'NO_AGT' => 'AO-'.$idAo,
            'NAMA' => 'AO',
            'STAT' => null,
            'TGL_STAT' => null,
        ]);
        KelSah::create([
            'ID_KEL' => $idKel,
            'NAMA_KEL' => 'Kelompok Test',
            'ID_KETUA' => $idKet,
            'ID_SEK' => $idSek,
            'ID_LO' => $idLo,
            'ID_AO' => $idAo,
            'ALAMAT' => null,
            'STAT' => null,
            'TGL_STAT' => null,
            'ID_PENGELOLA' => null,
        ]);
    }
}
