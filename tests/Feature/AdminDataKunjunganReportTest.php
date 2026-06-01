<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\DataAo;
use App\Models\DataKunjungan;
use App\Models\DataLo;
use App\Models\KelSah;
use App\Models\KetuaKs;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDataKunjunganReportTest extends TestCase
{
    use RefreshDatabase;

    private function seedKunjunganFixtures(): array
    {
        $member = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        KetuaKs::query()->create([
            'ID_KET' => 'KETUA00000001',
            'NO_AGT' => 'AGTTEMP00000001',
            'NAMA' => 'Ketua Uji',
            'STAT' => null,
            'TGL_STAT' => null,
            'NO_SK' => null,
        ]);

        DataLo::query()->create([
            'ID_LO' => 'LO0000000001',
            'NO_AGT' => null,
            'ID_TP' => null,
            'NAMA' => 'LO Uji',
            'STAT' => null,
            'TGL_STAT' => null,
        ]);

        DataAo::query()->create([
            'ID_AO' => 'AO0000000001',
            'NO_AGT' => null,
            'NAMA' => 'AO Uji',
            'STAT' => null,
            'TGL_STAT' => null,
        ]);

        KelSah::query()->create([
            'ID_KEL' => 'KS0000000001',
            'NAMA_KEL' => 'Kelompok Uji',
            'ID_KETUA' => 'KETUA00000001',
            'ID_SEK' => null,
            'ID_LO' => 'LO0000000001',
            'ID_AO' => 'AO0000000001',
            'ALAMAT' => null,
            'STAT' => null,
            'TGL_STAT' => null,
            'ID_PENGELOLA' => null,
        ]);

        Anggota::query()->create([
            'NO_AGT' => 'AGT00000000001',
            'NAMA' => 'Budi Uji',
            'ID_KS' => 'KS0000000001',
            'ID_LO' => null,
            'ID_AO' => null,
            'ID_KS_ASL' => null,
            'TGL_MTS' => null,
            'TGL_AKTIF' => null,
            'TGL_JA' => null,
        ]);

        DataKunjungan::query()->create([
            'ID_LO' => null,
            'NO_AGT' => 'AGT00000000001',
            'ID_KEL_SAH' => 'KS0000000001',
            'TGL_KUN' => '2026-01-15',
            'KEGIATAN' => 'Kunjungan uji',
            'ID_PIC' => 'PIC1',
            'JLH_PESERTA' => 5,
            'FOTO_PATH' => null,
            'LATITUDE' => null,
            'LONGITUDE' => null,
            'created_by' => $member->id,
        ]);

        DataKunjungan::query()->create([
            'ID_LO' => null,
            'NO_AGT' => 'AGT00000000001',
            'ID_KEL_SAH' => 'KS0000000001',
            'TGL_KUN' => '2026-02-01',
            'KEGIATAN' => 'Kunjungan uji 2',
            'ID_PIC' => 'PIC1',
            'JLH_PESERTA' => 3,
            'FOTO_PATH' => null,
            'LATITUDE' => null,
            'LONGITUDE' => null,
            'created_by' => $member->id,
        ]);

        return ['member' => $member];
    }

    public function test_admin_can_fetch_group_summary(): void
    {
        $this->seedKunjunganFixtures();
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/data-kunjungan/report/group-summary');

        $response->assertOk()
            ->assertJsonPath('data.0.id_kel_sah', 'KS0000000001')
            ->assertJsonPath('data.0.nama_kelompok', 'Kelompok Uji')
            ->assertJsonPath('data.0.frekuensi', 2);
    }

    public function test_admin_group_summary_search_by_member_name(): void
    {
        $this->seedKunjunganFixtures();
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/data-kunjungan/report/group-summary?search=Budi')
            ->assertOk()
            ->assertJsonPath('data.0.frekuensi', 2);

        $this->getJson('/api/admin/data-kunjungan/report/group-summary?search=ZZZNOBODY')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_member_cannot_fetch_group_summary(): void
    {
        $fixtures = $this->seedKunjunganFixtures();
        Sanctum::actingAs($fixtures['member']);

        $this->getJson('/api/admin/data-kunjungan/report/group-summary')
            ->assertForbidden();
    }

    public function test_admin_can_fetch_anggota_summary_for_kelompok(): void
    {
        $this->seedKunjunganFixtures();
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/data-kunjungan/report/by-kelompok/KS0000000001/anggota-summary');

        $response->assertOk()
            ->assertJsonPath('data.0.no_agt', 'AGT00000000001')
            ->assertJsonPath('data.0.nama_anggota', 'Budi Uji')
            ->assertJsonPath('data.0.frekuensi', 2)
            ->assertJsonPath('data.0.tanggal_terakhir', '2026-02-01');
    }

    public function test_admin_cannot_post_data_kunjungan(): void
    {
        $this->seedKunjunganFixtures();
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/data-kunjungan', [
            'NO_AGT' => 'AGT00000000001',
            'TGL_KUN' => '2026-03-01',
            'KEGIATAN' => 'X',
            'ID_PIC' => 'P',
            'JLH_PESERTA' => 1,
        ])->assertForbidden();
    }

    public function test_admin_cannot_delete_data_kunjungan(): void
    {
        $this->seedKunjunganFixtures();
        $row = DataKunjungan::query()->firstOrFail();
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);

        Sanctum::actingAs($admin);

        $this->deleteJson('/api/data-kunjungan/'.$row->NO_URT)
            ->assertForbidden();
    }
}
