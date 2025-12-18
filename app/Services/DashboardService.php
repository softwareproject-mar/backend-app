<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get dashboard data by joining Target and Realisasi
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getDashboardData(array $filters = []): array
    {
        $query = DB::table('target as t')
            ->leftJoin('realisasi as r', function ($join) {
                $join->on('t.ID_KS', '=', 'r.ID_KS')
                     ->on('t.TGL_TGT', '=', 'r.TGL_TGT');
            })
            ->select([
                't.ID_KS',
                't.TGL_TGT',
                // Target columns
                't.JLH_AGT_BR as target_jlh_agt_br',
                't.STR_SP as target_str_sp',
                't.SLD_SP as target_sld_sp',
                't.STR_SW as target_str_sw',
                't.SLD_SW as target_sld_sw',
                't.STR_SS as target_str_ss',
                't.SLD_SS as target_sld_ss',
                't.STR_SHR as target_str_shr',
                't.SLD_SHR as target_sld_shr',
                't.STR_SMD as target_str_smd',
                't.SLD_SMD as target_sld_smd',
                't.STR_SPD as target_str_spd',
                't.SLD_SPD as target_sld_spd',
                't.STR_SBJ as target_str_sbj',
                't.SLD_SBJ as target_sld_sbj',
                't.STR_SJP as target_str_sjp',
                't.SLD_SJP as target_sld_sjp',
                't.STR_SRY as target_str_sry',
                't.SLD_SRY as target_sld_sry',
                't.REK_SHR as target_rek_shr',
                't.REK_SPD as target_rek_spd',
                't.REK_SMD as target_rek_smd',
                't.REK_SRY as target_rek_sry',
                // Realisasi columns
                'r.JLH_AGT_BR as realisasi_jlh_agt_br',
                'r.STR_SP as realisasi_str_sp',
                'r.STR_SW as realisasi_str_sw',
                'r.STR_SS as realisasi_str_ss',
                'r.STR_SHR as realisasi_str_shr',
                'r.STR_SMD as realisasi_str_smd',
                'r.STR_SPD as realisasi_str_spd',
                'r.STR_SBJ as realisasi_str_sbj',
                'r.STR_SJP as realisasi_str_sjp',
                'r.STR_SRY as realisasi_str_sry',
                'r.REK_SHR as realisasi_rek_shr',
                'r.REK_SPD as realisasi_rek_spd',
                'r.REK_SMD as realisasi_rek_smd',
                'r.REK_SRY as realisasi_rek_sry',
            ]);

        // Apply filters
        if (isset($filters['ID_KS'])) {
            $query->where('t.ID_KS', $filters['ID_KS']);
        }

        if (isset($filters['TGL_TGT'])) {
            $query->where('t.TGL_TGT', $filters['TGL_TGT']);
        }

        return [
            'data' => $query->get()->toArray(),
            'summary' => $this->calculateSummary($query),
        ];
    }

    /**
     * Calculate summary statistics
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return array<string, mixed>
     */
    private function calculateSummary($query): array
    {
        $data = $query->get();

        return [
            'total_records' => $data->count(),
            'total_target_anggota' => $data->sum('target_jlh_agt_br'),
            'total_realisasi_anggota' => $data->sum('realisasi_jlh_agt_br'),
            'total_target_rekening_shr' => $data->sum('target_rek_shr'),
            'total_realisasi_rekening_shr' => $data->sum('realisasi_rek_shr'),
        ];
    }
}
