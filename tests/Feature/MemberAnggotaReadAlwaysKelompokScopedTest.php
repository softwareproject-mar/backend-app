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

class MemberAnggotaReadAlwaysKelompokScopedTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        config(['obormas.strict_member_kelompok_scope' => false]);
        parent::tearDown();
    }

    public function test_member_anggota_index_filtered_by_kelompok_when_strict_scope_config_false(): void
    {
        config(['obormas.strict_member_kelompok_scope' => false]);

        $this->seedKelompok('KEL001', 'KET001', 'SEK001', 'LO001', 'AO001');
        $this->seedKelompok('KEL002', 'KET002', 'SEK002', 'LO002', 'AO002');

        Anggota::create(['NO_AGT' => 'M1', 'NAMA' => 'A', 'ID_KS' => 'KEL001']);
        Anggota::create(['NO_AGT' => 'M2', 'NAMA' => 'B', 'ID_KS' => 'KEL002']);

        $user = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'no_agt' => 'M1',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/anggota?per_page=50');
        $response->assertOk();
        $noAgts = collect($response->json('data'))->pluck('NO_AGT')->all();
        $this->assertContains('M1', $noAgts);
        $this->assertNotContains('M2', $noAgts);
    }

    public function test_member_anggota_show_other_kelompok_forbidden_when_strict_scope_config_false(): void
    {
        config(['obormas.strict_member_kelompok_scope' => false]);

        $this->seedKelompok('KEL001', 'KET001', 'SEK001', 'LO001', 'AO001');
        $this->seedKelompok('KEL002', 'KET002', 'SEK002', 'LO002', 'AO002');

        Anggota::create(['NO_AGT' => 'M1', 'NAMA' => 'A', 'ID_KS' => 'KEL001']);
        Anggota::create(['NO_AGT' => 'M2', 'NAMA' => 'B', 'ID_KS' => 'KEL002']);

        $user = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'no_agt' => 'M1',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/anggota/M2')->assertForbidden();
    }

    private function seedKelompok(
        string $idKel,
        string $idKet,
        string $idSek,
        string $idLo,
        string $idAo,
    ): void {
        Anggota::create([
            'NO_AGT' => 'L-'.$idLo,
            'NAMA' => 'Stub',
            'ID_KS' => null,
        ]);

        KetuaKs::create([
            'ID_KET' => $idKet,
            'NO_AGT' => 'K-'.$idKet,
            'NAMA' => 'K',
            'STAT' => null,
            'TGL_STAT' => null,
            'NO_SK' => null,
        ]);
        SekretarisKs::create([
            'ID_SEKRE' => $idSek,
            'NO_AGT' => 'S-'.$idSek,
            'NAMA' => 'S',
            'STAT' => null,
            'TGL_STAT' => null,
            'NO_SK' => null,
        ]);
        DataLo::create([
            'ID_LO' => $idLo,
            'NO_AGT' => 'L-'.$idLo,
            'ID_TP' => null,
            'NAMA' => 'L',
            'STAT' => null,
            'TGL_STAT' => null,
        ]);
        DataAo::create([
            'ID_AO' => $idAo,
            'NO_AGT' => 'A-'.$idAo,
            'NAMA' => 'A',
            'STAT' => null,
            'TGL_STAT' => null,
        ]);
        KelSah::create([
            'ID_KEL' => $idKel,
            'NAMA_KEL' => 'Kel '.$idKel,
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
