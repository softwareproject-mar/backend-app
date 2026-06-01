<?php

namespace Tests\Unit;

use App\Services\ActivityLogService;
use PHPUnit\Framework\TestCase;

class ActivityLogLabelFormatterTest extends TestCase
{
    public function test_kelompok_sahabat_label_prefers_name(): void
    {
        $label = ActivityLogService::kelompokSahabatLabel('1605200009', 'Kelompok Sahabat Mawar');

        $this->assertSame('Kelompok Sahabat Mawar', $label);
    }

    public function test_anggota_label_prefers_fallback_name(): void
    {
        $label = ActivityLogService::anggotaLabelByNoAgt('016005500001', 'Budi Santoso');

        $this->assertSame('Budi Santoso', $label);
    }

    public function test_anggota_label_returns_default_when_no_data(): void
    {
        $label = ActivityLogService::anggotaLabelByNoAgt(null, null);

        $this->assertSame(ActivityLogService::EMPTY_LABEL, $label);
    }
}
