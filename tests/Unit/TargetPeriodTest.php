<?php

namespace Tests\Unit;

use App\Support\TargetPeriod;
use Tests\TestCase;

class TargetPeriodTest extends TestCase
{
    public function test_last_day_of_month_february_leap_year(): void
    {
        $this->assertSame('2024-02-29', TargetPeriod::lastDayOfMonth(2024, 2));
    }

    public function test_last_day_of_month_february_non_leap(): void
    {
        $this->assertSame('2025-02-28', TargetPeriod::lastDayOfMonth(2025, 2));
    }

    public function test_is_end_of_month_accepts_last_day_only(): void
    {
        $this->assertTrue(TargetPeriod::isEndOfMonth('2026-03-31'));
        $this->assertFalse(TargetPeriod::isEndOfMonth('2026-03-15'));
        $this->assertFalse(TargetPeriod::isEndOfMonth('2099-01-01'));
    }

    public function test_parse_to_year_month(): void
    {
        $this->assertSame(['year' => 2026, 'month' => 2], TargetPeriod::parseToYearMonth('2026-02-28'));
        $this->assertNull(TargetPeriod::parseToYearMonth('invalid'));
    }
}
