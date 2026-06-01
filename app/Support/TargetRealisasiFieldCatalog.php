<?php

namespace App\Support;

final class TargetRealisasiFieldCatalog
{
    /** @var list<string> */
    public const MONITORING_FIELDS_PHASE1 = [
        'JLH_AGT_BR',
        'STR_SP',
        'STR_SW',
        'STR_SHR',
        'STR_SMD',
        'STR_SPD',
        'STR_SBJ',
        'STR_SRY',
        'STR_SKA',
    ];

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'JLH_AGT_BR' => 'Jumlah anggota baru',
            'STR_SP' => 'Setoran Simpanan Pokok',
            'STR_SW' => 'Setoran Simpanan Wajib',
            'STR_SHR' => 'Setoran Simpanan Hari Raya',
            'STR_SMD' => 'Setoran Simpanan Masa Depan',
            'STR_SPD' => 'Setoran Simpanan Pendidikan',
            'STR_SBJ' => 'Setoran Simpanan Berjangka',
            'STR_SRY' => 'Setoran Simpanan Raya',
            'STR_SKA' => 'Setoran Simpanan Khusus Anggota',
        ];
    }

    public static function isAllowedTargetKey(string $key): bool
    {
        return in_array($key, self::MONITORING_FIELDS_PHASE1, true);
    }

    public static function labelFor(string $key): string
    {
        return self::labels()[$key] ?? $key;
    }

    public static function isCountField(string $key): bool
    {
        return $key === 'JLH_AGT_BR';
    }
}
