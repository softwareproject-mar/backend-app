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
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApprovalUpdatesAnggotaIdKsTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_with_id_kel_sets_user_id_kel_and_does_not_change_anggota_id_ks(): void
    {
        Mail::fake();

        $this->seedKelompokGraph('KEL001', 'KET01', 'SEK01', 'LO01', 'AO01');
        $this->seedKelompokGraph('KEL002', 'KET02', 'SEK02', 'LO02', 'AO02');

        Anggota::create([
            'NO_AGT' => 'MEMLINK',
            'NAMA' => 'Calon Taut',
            'ID_KS' => 'KEL001',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        $member = User::factory()->create([
            'role' => 'user',
            'is_active' => false,
            'registration_status' => User::REGISTRATION_PENDING,
            'no_agt' => null,
            'id_kel' => null,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/users/{$member->id}/approve", [
            'id_kel' => 'KEL002',
        ]);

        $response->assertOk();

        $member->refresh();
        $this->assertSame('KEL002', trim((string) $member->id_kel));
        $this->assertNull($member->no_agt);
        $this->assertSame(User::REGISTRATION_APPROVED, $member->registration_status);

        $anggota = Anggota::query()->where('NO_AGT', 'MEMLINK')->first();
        $this->assertNotNull($anggota);
        $this->assertSame('KEL001', trim((string) $anggota->ID_KS));
    }

    public function test_approve_without_id_kel_clears_or_leaves_id_kel_null(): void
    {
        Mail::fake();

        $this->seedKelompokGraph('KEL001', 'KET01', 'SEK01', 'LO01', 'AO01');

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        $member = User::factory()->create([
            'role' => 'user',
            'is_active' => false,
            'registration_status' => User::REGISTRATION_PENDING,
            'id_kel' => null,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/users/{$member->id}/approve", [])->assertOk();

        $member->refresh();
        $this->assertNull($member->id_kel);
        $this->assertSame(User::REGISTRATION_APPROVED, $member->registration_status);
    }

    public function test_approve_with_invalid_id_kel_returns_422(): void
    {
        Mail::fake();

        $this->seedKelompokGraph('KEL001', 'KET01', 'SEK01', 'LO01', 'AO01');

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        $member = User::factory()->create([
            'role' => 'user',
            'is_active' => false,
            'registration_status' => User::REGISTRATION_PENDING,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/users/{$member->id}/approve", [
            'id_kel' => 'INVALID999',
        ])->assertUnprocessable()->assertJsonValidationErrors(['id_kel']);
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
