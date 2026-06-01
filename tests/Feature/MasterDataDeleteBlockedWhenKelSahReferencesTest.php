<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\DataAo;
use App\Models\DataLo;
use App\Models\DataPengelola;
use App\Models\KelSah;
use App\Models\KetuaKs;
use App\Models\SekretarisKs;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterDataDeleteBlockedWhenKelSahReferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_ketua_returns_422_when_used_in_kelompok(): void
    {
        $this->seedKelompokGraph('KEL001', 'KET01', 'SEK01', 'LO01', 'AO01');
        Sanctum::actingAs($this->makeApprovedUser());

        $this->deleteJson('/api/ketua-ks/KET01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id']);
        $this->assertTrue(KetuaKs::query()->where('ID_KET', 'KET01')->exists());
    }

    public function test_delete_sekretaris_returns_422_when_used_in_kelompok(): void
    {
        $this->seedKelompokGraph('KEL001', 'KET01', 'SEK01', 'LO01', 'AO01');
        Sanctum::actingAs($this->makeApprovedUser());

        $this->deleteJson('/api/sekretaris-ks/SEK01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id']);
    }

    public function test_delete_lo_returns_422_when_used_in_kelompok(): void
    {
        $this->seedKelompokGraph('KEL001', 'KET01', 'SEK01', 'LO01', 'AO01');
        Sanctum::actingAs($this->makeApprovedUser());

        $this->deleteJson('/api/data-lo/LO01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id']);
    }

    public function test_delete_ao_returns_422_when_used_in_kelompok(): void
    {
        $this->seedKelompokGraph('KEL001', 'KET01', 'SEK01', 'LO01', 'AO01');
        Sanctum::actingAs($this->makeApprovedUser());

        $this->deleteJson('/api/data-ao/AO01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id']);
    }

    public function test_delete_pengelola_returns_422_when_used_in_kelompok(): void
    {
        $this->seedKelompokGraph('KEL001', 'KET01', 'SEK01', 'LO01', 'AO01');
        DataPengelola::create([
            'ID_PENG' => 'PENG00000001',
            'NO_AGT' => null,
            'NO_SK' => null,
        ]);
        KelSah::query()->where('ID_KEL', 'KEL001')->update(['ID_PENGELOLA' => 'PENG00000001']);

        Sanctum::actingAs($this->makeApprovedUser());

        $this->deleteJson('/api/data-pengelola/PENG00000001')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id']);
    }

    private function makeApprovedUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
    }

    private function seedKelompokGraph(
        string $idKel,
        string $idKet,
        string $idSek,
        string $idLo,
        string $idAo,
    ): void {
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
            'NAMA_KEL' => 'Kelompok '.$idKel,
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
