<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\DataAo;
use App\Models\DataLo;
use App\Models\KelSah;
use App\Models\KetuaKs;
use App\Models\Realisasi;
use App\Models\SekretarisKs;
use App\Models\Target;
use App\Models\User;
use App\Support\TargetPeriod;
use App\Support\TargetRealisasiFieldCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TargetRealisasiApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedKelompokGraph(
        string $idKel,
        string $namaKel,
        string $idKet,
        string $idSek,
        string $idLo,
        string $idAo
    ): void {
        $tail = str_pad(substr(preg_replace('/\D/', '', $idKel), -9, 9), 9, '0', STR_PAD_LEFT);
        $noKetua = 'K'.str_pad($tail, 14, '0', STR_PAD_LEFT);
        $noSekre = 'S'.str_pad($tail, 14, '0', STR_PAD_LEFT);
        $noAo = 'A'.str_pad($tail, 14, '0', STR_PAD_LEFT);

        KetuaKs::create([
            'ID_KET' => $idKet,
            'NO_AGT' => $noKetua,
            'NAMA' => 'Ketua',
            'STAT' => null,
            'TGL_STAT' => null,
            'NO_SK' => null,
        ]);
        SekretarisKs::create([
            'ID_SEKRE' => $idSek,
            'NO_AGT' => $noSekre,
            'NAMA' => 'Sek',
            'STAT' => null,
            'TGL_STAT' => null,
            'NO_SK' => null,
        ]);
        DataLo::create([
            'ID_LO' => $idLo,
            'NO_AGT' => null,
            'ID_TP' => null,
            'NAMA' => null,
            'STAT' => null,
            'TGL_STAT' => null,
        ]);
        DataAo::create([
            'ID_AO' => $idAo,
            'NO_AGT' => $noAo,
            'NAMA' => null,
            'STAT' => null,
            'TGL_STAT' => null,
        ]);
        KelSah::create([
            'ID_KEL' => $idKel,
            'NAMA_KEL' => $namaKel,
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

    public function test_admin_can_put_targets_and_get_detail_per_field(): void
    {
        $idKel = 'KEL000000001';
        $tgl = TargetPeriod::currentPeriodEnd();

        $this->seedKelompokGraph(
            $idKel,
            'Kelompok TR',
            'KTUA00000001',
            'SEK0000000001',
            'LO0000000001',
            'AO0000000001'
        );

        Anggota::create(['NO_AGT' => 'AGT-TR-01', 'NAMA' => 'A1', 'ID_KS' => $idKel]);
        Anggota::create(['NO_AGT' => 'AGT-TR-02', 'NAMA' => 'A2', 'ID_KS' => $idKel]);

        Realisasi::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => $tgl,
            'STR_SP' => '800000',
            'STR_SW' => '100000',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $put = $this->putJson("/api/admin/target-realisasi/kelompok/{$idKel}", [
            'targets' => [
                'JLH_AGT_BR' => 5,
                'STR_SP' => 1_000_000,
                'STR_SW' => 500_000,
            ],
        ]);
        $put->assertOk();
        $put->assertJsonPath('data.fields.1.key', 'STR_SP');
        $put->assertJsonPath('data.fields.1.target', '1000000.00');
        $put->assertJsonPath('data.fields.1.realisasi', '800000.00');
        $put->assertJsonPath('data.fields.1.persentase', 80);
        $put->assertJsonPath('data.fields.1.status', 'below_target');
        $put->assertJsonPath('data.nominal_target', '1000000.00');

        $get = $this->getJson("/api/admin/target-realisasi/kelompok/{$idKel}");
        $get->assertOk();
        $get->assertJsonCount(count(TargetRealisasiFieldCatalog::MONITORING_FIELDS_PHASE1), 'data.fields');

        $this->assertDatabaseHas('target', [
            'ID_KS' => $idKel,
            'TGL_TGT' => $tgl,
        ]);
    }

    public function test_admin_get_detail_respects_tgl_tgt_query(): void
    {
        $idKel = 'KEL000000011';
        $tglA = '2050-01-31';
        $tglB = '2050-02-28';

        $this->seedKelompokGraph(
            $idKel,
            'Kelompok Tgl',
            'KTUA00000011',
            'SEK0000000011',
            'LO0000000011',
            'AO0000000011'
        );

        Realisasi::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => $tglA,
            'STR_SP' => '100',
        ]);
        Target::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => $tglA,
            'STR_SP' => '200.00',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson("/api/admin/target-realisasi/kelompok/{$idKel}?tgl_tgt={$tglA}")
            ->assertOk()
            ->assertJsonPath('data.tgl_tgt', $tglA)
            ->assertJsonPath('data.fields.1.realisasi', '100.00');

        $this->getJson("/api/admin/target-realisasi/kelompok/{$idKel}?tgl_tgt={$tglB}")
            ->assertOk()
            ->assertJsonPath('data.tgl_tgt', $tglB)
            ->assertJsonPath('data.fields.1.realisasi', '0.00');
    }

    public function test_admin_put_with_custom_tgl_tgt(): void
    {
        $idKel = 'KEL000000012';
        $tgl = '2035-12-31';

        $this->seedKelompokGraph(
            $idKel,
            'Kelompok Tgl Put',
            'KTUA00000012',
            'SEK0000000012',
            'LO0000000012',
            'AO0000000012'
        );

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $this->putJson("/api/admin/target-realisasi/kelompok/{$idKel}", [
            'tgl_tgt' => $tgl,
            'targets' => [
                'STR_SP' => 50_000,
            ],
        ])->assertOk()
            ->assertJsonPath('data.tgl_tgt', $tgl)
            ->assertJsonPath('data.fields.1.target', '50000.00');

        $this->assertDatabaseHas('target', [
            'ID_KS' => $idKel,
            'TGL_TGT' => $tgl,
        ]);
    }

    public function test_admin_summary_includes_fields_per_kelompok(): void
    {
        $idKel = 'KEL000000010';
        $tgl = TargetPeriod::currentPeriodEnd();

        $this->seedKelompokGraph(
            $idKel,
            'Kelompok Summary',
            'KTUA00000010',
            'SEK0000000010',
            'LO0000000010',
            'AO0000000010'
        );

        Anggota::create(['NO_AGT' => 'AGT-SM-01', 'NAMA' => 'A1', 'ID_KS' => $idKel]);

        Realisasi::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => $tgl,
            'STR_SP' => '500000',
        ]);

        Target::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => $tgl,
            'STR_SP' => '1000000.00',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $res = $this->getJson('/api/admin/target-realisasi/summary');
        $res->assertOk();
        $res->assertJsonCount(1, 'data');
        $res->assertJsonCount(count(TargetRealisasiFieldCatalog::MONITORING_FIELDS_PHASE1), 'data.0.fields');
        $res->assertJsonPath('data.0.id_kel', $idKel);
        $res->assertJsonPath('data.0.tgl_tgt', $tgl);
        $res->assertJsonPath('data.0.fields.1.key', 'STR_SP');
        $res->assertJsonPath('data.0.fields.1.target', '1000000.00');
        $res->assertJsonPath('data.0.fields.1.realisasi', '500000.00');
    }

    public function test_admin_can_put_legacy_nominal_target(): void
    {
        $idKel = 'KEL000000003';
        $this->seedKelompokGraph(
            $idKel,
            'Kelompok Legacy',
            'KTUA00000003',
            'SEK0000000003',
            'LO0000000003',
            'AO0000000003'
        );

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $this->putJson("/api/admin/target-realisasi/kelompok/{$idKel}", [
            'nominal_target' => 2_000_000,
        ])->assertOk()
            ->assertJsonPath('data.nominal_target', '2000000.00');
    }

    public function test_member_can_get_target_realisasi_me(): void
    {
        $idKel = 'KEL000000002';
        $tgl = TargetPeriod::currentPeriodEnd();

        $this->seedKelompokGraph(
            $idKel,
            'Kelompok Member',
            'KTUA00000002',
            'SEK0000000002',
            'LO0000000002',
            'AO0000000002'
        );

        Target::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => $tgl,
            'STR_SP' => '1000.00',
        ]);

        Realisasi::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => $tgl,
            'STR_SP' => '100.00',
        ]);

        $member = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'no_agt' => 'NOAGT00000001',
            'id_kel' => $idKel,
        ]);

        Anggota::create([
            'NO_AGT' => 'NOAGT00000001',
            'NAMA' => 'Anggota',
            'ID_KS' => $idKel,
        ]);

        Sanctum::actingAs($member);

        $this->getJson('/api/target-realisasi/me')
            ->assertOk()
            ->assertJsonPath('data.id_kel', $idKel)
            ->assertJsonPath('data.nominal_target', '1000.00')
            ->assertJsonPath('data.total_realisasi', '100.00');
    }

    public function test_member_cannot_put_admin_target_route(): void
    {
        $member = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($member);

        $this->putJson('/api/admin/target-realisasi/kelompok/KEL000000097', [
            'targets' => ['STR_SP' => 100],
        ])->assertForbidden();
    }

    public function test_admin_detail_loads_target_and_realisasi_for_explicit_period(): void
    {
        $idKel = 'KEL000000099';
        $tglPeriod = '2025-01-31';

        $this->seedKelompokGraph(
            $idKel,
            'Kelompok Merge',
            'KTUA00000099',
            'SEK0000000099',
            'LO0000000099',
            'AO0000000099'
        );

        Realisasi::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => '2025-01-15',
            'STR_SP' => '950000',
            'STR_SW' => '100000',
        ]);

        Target::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => $tglPeriod,
            'JLH_AGT_BR' => '10',
            'STR_SP' => '1000000.00',
            'STR_SW' => '2500000.00',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson("/api/admin/target-realisasi/kelompok/{$idKel}?tgl_tgt={$tglPeriod}")
            ->assertOk()
            ->assertJsonPath('data.tgl_tgt', $tglPeriod)
            ->assertJsonPath('data.period_year', 2025)
            ->assertJsonPath('data.period_month', 1)
            ->assertJsonPath('data.fields.0.key', 'JLH_AGT_BR')
            ->assertJsonPath('data.fields.0.target', '10')
            ->assertJsonPath('data.fields.1.target', '1000000.00')
            ->assertJsonPath('data.fields.1.realisasi', '950000.00');
    }

    public function test_admin_export_excel_returns_spreadsheet(): void
    {
        $idKel = 'KEL000000088';
        $tgl = TargetPeriod::currentPeriodEnd();

        $this->seedKelompokGraph(
            $idKel,
            'Kelompok Exp',
            'KTUA00000088',
            'SEK0000000088',
            'LO0000000088',
            'AO0000000088'
        );

        Target::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => $tgl,
            'STR_SP' => '100.00',
        ]);
        Realisasi::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => $tgl,
            'STR_SP' => '50.00',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $res = $this->get('/api/admin/target-realisasi/export/excel');
        $res->assertOk();
        $ct = strtolower((string) $res->headers->get('content-type'));
        $this->assertStringContainsString('spreadsheet', $ct);
    }

    public function test_admin_summary_lists_period_from_target_even_without_realisasi(): void
    {
        $idKel = 'KEL000000077';
        $tgl = '2027-03-31';

        $this->seedKelompokGraph(
            $idKel,
            'Kelompok Target Saja',
            'KTUA00000077',
            'SEK0000000077',
            'LO0000000077',
            'AO0000000077'
        );

        Target::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => $tgl,
            'STR_SP' => '999.00',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $rowsBefore = $this->getJson('/api/admin/target-realisasi/summary')
            ->assertOk()
            ->json('data');
        $rowTargetOnly = collect($rowsBefore)->first(
            fn (array $r) => ($r['id_kel'] ?? '') === $idKel && ($r['tgl_tgt'] ?? '') === $tgl
        );
        $this->assertNotNull($rowTargetOnly, 'Periode dari target harus muncul walau belum ada realisasi.');
        $this->assertSame('0.00', $rowTargetOnly['total_realisasi'] ?? null);

        Realisasi::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => '2027-03-20',
            'STR_SP' => '1.00',
        ]);

        $rowsAfter = $this->getJson('/api/admin/target-realisasi/summary')->json('data');
        $rowBoth = collect($rowsAfter)->first(
            fn (array $r) => ($r['id_kel'] ?? '') === $idKel && ($r['tgl_tgt'] ?? '') === $tgl
        );
        $this->assertNotNull($rowBoth);
        $this->assertSame('1.00', $rowBoth['total_realisasi'] ?? null);
    }

    public function test_admin_summary_loads_realisasi_for_operational_month(): void
    {
        $idKel = 'KEL000000055';
        $tglPeriod = '2025-06-30';
        $tglOp = '2025-06-15';

        $this->seedKelompokGraph(
            $idKel,
            'Kelompok Operasional',
            'KTUA00000055',
            'SEK0000000055',
            'LO0000000055',
            'AO0000000055'
        );

        Target::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => $tglPeriod,
            'STR_SP' => '500.00',
        ]);
        Realisasi::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => $tglOp,
            'STR_SP' => '125.00',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $rows = $this->getJson('/api/admin/target-realisasi/summary')
            ->assertOk()
            ->json('data');

        $row = collect($rows)->first(
            fn (array $r) => ($r['id_kel'] ?? '') === $idKel && ($r['tgl_tgt'] ?? '') === $tglPeriod
        );
        $this->assertNotNull($row, 'Ringkasan harus memuat periode Juni 2025.');
        $this->assertSame('125.00', $row['total_realisasi'] ?? null);
        $this->assertSame('500.00', $row['nominal_target'] ?? null);
    }

    public function test_admin_summary_shows_two_rows_for_two_periods_same_kelompok(): void
    {
        $idKel = 'KEL000000066';

        $this->seedKelompokGraph(
            $idKel,
            'Kelompok Dua Periode',
            'KTUA00000066',
            'SEK0000000066',
            'LO0000000066',
            'AO0000000066'
        );

        Target::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => '2026-01-31',
            'STR_SP' => '100.00',
        ]);
        Target::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => '2026-02-28',
            'STR_SP' => '200.00',
        ]);
        Realisasi::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => '2026-01-20',
            'STR_SP' => '10.00',
        ]);
        Realisasi::create([
            'ID_KS' => $idKel,
            'TGL_TGT' => '2026-02-28',
            'STR_SP' => '20.00',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $rows = $this->getJson('/api/admin/target-realisasi/summary')
            ->assertOk()
            ->json('data');

        $forKel = collect($rows)->where('id_kel', $idKel)->values();
        $this->assertCount(2, $forKel);

        $jan = $forKel->firstWhere('tgl_tgt', '2026-01-31');
        $feb = $forKel->firstWhere('tgl_tgt', '2026-02-28');
        $this->assertNotNull($jan);
        $this->assertNotNull($feb);
        $this->assertSame('10.00', $jan['total_realisasi'] ?? null);
        $this->assertSame('20.00', $feb['total_realisasi'] ?? null);
    }

    public function test_put_rejects_tgl_tgt_not_end_of_month(): void
    {
        $idKel = 'KEL000000013';
        $this->seedKelompokGraph(
            $idKel,
            'Kelompok Reject',
            'KTUA00000013',
            'SEK0000000013',
            'LO0000000013',
            'AO0000000013'
        );

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $this->putJson("/api/admin/target-realisasi/kelompok/{$idKel}", [
            'tgl_tgt' => '2026-03-15',
            'targets' => ['STR_SP' => 100],
        ])->assertStatus(422);
    }

    public function test_get_detail_rejects_tgl_tgt_not_end_of_month(): void
    {
        $idKel = 'KEL000000014';
        $this->seedKelompokGraph(
            $idKel,
            'Kelompok Get Reject',
            'KTUA00000014',
            'SEK0000000014',
            'LO0000000014',
            'AO0000000014'
        );

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson("/api/admin/target-realisasi/kelompok/{$idKel}?tgl_tgt=2026-04-10")
            ->assertStatus(422);
    }

    public function test_admin_diagnostic_returns_counts_and_structure(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/target-realisasi/diagnostic')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'database_connection',
                    'database_driver',
                    'target_table_row_count',
                    'realisasi_table_row_count',
                    'target_distinct_id_ks_count',
                    'realisasi_distinct_id_ks_count',
                    'intersection_id_count',
                    'intersection_id_sample',
                    'monitoring_tgl_tgt',
                ],
            ]);
    }
}
