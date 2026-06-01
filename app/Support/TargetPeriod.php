<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;

class TargetPeriod
{
    public static function appTimezone(): DateTimeZone
    {
        $tz = (string) config('app.timezone', 'UTC');

        return new DateTimeZone($tz !== '' ? $tz : 'UTC');
    }

    public static function lastDayOfMonth(int $year, int $month): string
    {
        $dt = new DateTimeImmutable(
            sprintf('%04d-%02d-01', $year, $month),
            self::appTimezone()
        );

        return $dt->modify('last day of this month')->format('Y-m-d');
    }

    public static function fromYearMonth(int $year, int $month): string
    {
        return self::lastDayOfMonth($year, $month);
    }

    public static function isEndOfMonth(string $ymd): bool
    {
        $parsed = self::parseToYearMonth($ymd);
        if ($parsed === null) {
            return false;
        }

        return self::lastDayOfMonth($parsed['year'], $parsed['month']) === substr(trim($ymd), 0, 10);
    }

    /**
     * @return array{year: int, month: int}|null
     */
    public static function parseToYearMonth(?string $ymd): ?array
    {
        if ($ymd === null) {
            return null;
        }
        $t = trim($ymd);
        if ($t === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $t)) {
            return null;
        }
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $t, self::appTimezone());
        if ($dt === false) {
            return null;
        }

        return [
            'year' => (int) $dt->format('Y'),
            'month' => (int) $dt->format('m'),
        ];
    }

    public static function currentPeriodEnd(): string
    {
        $now = new DateTimeImmutable('now', self::appTimezone());

        return self::lastDayOfMonth((int) $now->format('Y'), (int) $now->format('m'));
    }
}
