<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\DataAo;
use App\Models\DataLo;
use App\Models\KelSah;
use App\Models\KetuaKs;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CaseInsensitiveSearchApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedKelompok(string $idKel, string $namaKel): void
    {
        $idKet = 'KTUA00000099';
        $idLo = 'LO0000000099';
        $idAo = 'AO0000000099';

        KetuaKs::create([
            'ID_KET' => $idKet,
            'NO_AGT' => 'K000000000099',
            'NAMA' => 'Ketua',
        ]);
        DataLo::create([
            'ID_LO' => $idLo,
            'NO_AGT' => null,
            'ID_TP' => null,
            'NAMA' => null,
        ]);
        DataAo::create([
            'ID_AO' => $idAo,
            'NO_AGT' => 'A000000000099',
            'NAMA' => null,
        ]);
        KelSah::create([
            'ID_KEL' => $idKel,
            'NAMA_KEL' => $namaKel,
            'ID_KETUA' => $idKet,
            'ID_SEK' => null,
            'ID_LO' => $idLo,
            'ID_AO' => $idAo,
        ]);
    }

    public function test_anggota_index_search_is_case_insensitive_for_name(): void
    {
        $idKel = 'KEL000000099';
        $this->seedKelompok($idKel, 'Kel Test Search');

        $noAgt = '016005000099901';
        Anggota::create([
            'NO_AGT' => $noAgt,
            'NAMA' => 'Fidelis Vin Coro',
            'ID_KS' => $idKel,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/anggota?search=fidelis')
            ->assertOk()
            ->assertJsonFragment(['NO_AGT' => $noAgt]);

        $this->getJson('/api/anggota?search=FIDELIS VIN')
            ->assertOk()
            ->assertJsonFragment(['NO_AGT' => $noAgt]);

        $this->getJson('/api/anggota?search=Fidelis Vin Coro')
            ->assertOk()
            ->assertJsonFragment(['NO_AGT' => $noAgt]);

        $this->getJson('/api/anggota?search='.rawurlencode('fidelis   vin   coro'))
            ->assertOk()
            ->assertJsonFragment(['NO_AGT' => $noAgt]);
    }

    public function test_anggota_index_search_matches_each_word_in_name(): void
    {
        $idKel = 'KEL000000101';
        $this->seedKelompok($idKel, 'Kel Test Multi Word');

        $noAgt = '016005000099903';
        Anggota::create([
            'NO_AGT' => $noAgt,
            'NAMA' => 'Rosalia Maria Sapi',
            'ID_KS' => $idKel,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/anggota?search='.rawurlencode('rosalia sapi'))
            ->assertOk()
            ->assertJsonFragment(['NO_AGT' => $noAgt]);
    }

    public function test_anggota_index_search_matches_id_substring(): void
    {
        $idKel = 'KEL000000100';
        $this->seedKelompok($idKel, 'Kel Test ID');

        $noAgt = '016005000099902';
        Anggota::create([
            'NO_AGT' => $noAgt,
            'NAMA' => 'Test Anggota',
            'ID_KS' => $idKel,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/anggota?search=016005000099902')
            ->assertOk()
            ->assertJsonFragment(['NO_AGT' => $noAgt]);
    }

    public function test_anggota_index_search_matches_date_fields(): void
    {
        $idKel = 'KEL000000103';
        $this->seedKelompok($idKel, 'Kel Test Date');

        $noAgt = '016005000099904';
        Anggota::create([
            'NO_AGT' => $noAgt,
            'NAMA' => 'Date Test Anggota',
            'ID_KS' => $idKel,
            'TGL_AKTIF' => '2026-05-28',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/anggota?search=2026')
            ->assertOk()
            ->assertJsonFragment(['NO_AGT' => $noAgt]);
    }

    public function test_kel_sah_index_search_matches_each_word_in_name(): void
    {
        $idKel = 'KEL000000102';
        $this->seedKelompok($idKel, 'Kelompok Maumere Utara');

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/kel-sah?search='.rawurlencode('maumere utara'))
            ->assertOk()
            ->assertJsonFragment(['ID_KEL' => $idKel]);
    }

    public function test_ketua_ks_index_search_matches_no_sk(): void
    {
        KetuaKs::create([
            'ID_KET' => 'KTUA00000111',
            'NO_AGT' => 'K000000000111',
            'NAMA' => 'Ketua Search SK',
            'STAT' => 'A',
            'NO_SK' => 778899,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/ketua-ks?search=778899')
            ->assertOk()
            ->assertJsonFragment(['ID_KET' => 'KTUA00000111']);
    }
}
