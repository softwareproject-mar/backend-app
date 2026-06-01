<?php

namespace App\Services;

use App\Models\Anggota;
use App\Models\KelSah;
use App\Models\Realisasi;
use App\Models\Target;
use App\Support\TargetPeriod;
use App\Support\TargetRealisasiFieldCatalog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class TargetRealisasiMonitoringService
{
    private const LEGACY_MONITORING_DATE = '2099-01-01';

    /**
     * Legacy config sentinel (tidak dipakai sebagai default list/detail).
     */
    public static function monitoringTglTgt(): string
    {
        $v = (string) config('obormas.target_monitoring_tgl_tgt', self::LEGACY_MONITORING_DATE);

        return trim($v) !== '' ? trim($v) : self::LEGACY_MONITORING_DATE;
    }

    public function assertEndOfMonthOrFail(string $ymd): string
    {
        $t = trim($ymd);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $t)) {
            throw ValidationException::withMessages([
                'tgl_tgt' => ['Tanggal target harus akhir bulan (periode bulanan).'],
            ]);
        }
        if (! TargetPeriod::isEndOfMonth($t)) {
            throw ValidationException::withMessages([
                'tgl_tgt' => ['Tanggal target harus akhir bulan (periode bulanan).'],
            ]);
        }

        return $t;
    }

    /**
     * Normalisasi tanggal periode (Y-m-d). Kosong / tidak valid → akhir bulan berjalan.
     */
    public function normalizeMonitoringTgl(?string $tglTgt): string
    {
        if ($tglTgt === null) {
            return TargetPeriod::currentPeriodEnd();
        }
        $t = trim($tglTgt);
        if ($t === '') {
            return TargetPeriod::currentPeriodEnd();
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $t)) {
            return TargetPeriod::currentPeriodEnd();
        }
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $t);

        return $dt !== false ? $dt->format('Y-m-d') : TargetPeriod::currentPeriodEnd();
    }

    private function isLegacyMonitoringDate(string $ymd): bool
    {
        return substr(trim($ymd), 0, 10) === self::LEGACY_MONITORING_DATE;
    }

    /**
     * @return array{year: int, month: int}|null
     */
    private function periodKeyFromYmd(?string $ymd): ?array
    {
        if ($ymd === null || $this->isLegacyMonitoringDate($ymd)) {
            return null;
        }

        return TargetPeriod::parseToYearMonth($ymd);
    }

    /**
     * Satu baris list per (kelompok, tahun, bulan) dari union TARGET + REALISASI.
     *
     * @return \Illuminate\Support\Collection<int, array{id_kel: string, year: int, month: int, tgl_tgt: string}>
     */
    public function collectMonitoringPeriodRows(): \Illuminate\Support\Collection
    {
        $map = [];

        $absorb = function (mixed $idKs, mixed $tglTgt) use (&$map): void {
            $canonical = trim((string) $idKs);
            if ($canonical === '') {
                return;
            }
            $ymd = $this->coerceTglTgtToYmd($tglTgt);
            if ($ymd === null) {
                return;
            }
            $period = $this->periodKeyFromYmd($ymd);
            if ($period === null) {
                return;
            }
            $key = $this->normalizeKelompokIdForMatch($canonical).'|'.$period['year'].'|'.$period['month'];
            $map[$key] = [
                'id_kel' => $canonical,
                'year' => $period['year'],
                'month' => $period['month'],
                'tgl_tgt' => TargetPeriod::lastDayOfMonth($period['year'], $period['month']),
            ];
        };

        foreach (Target::query()->select('ID_KS', 'TGL_TGT')->get() as $row) {
            $absorb($row->getAttribute('ID_KS'), $row->getAttribute('TGL_TGT'));
        }
        foreach (Realisasi::query()->select('ID_KS', 'TGL_TGT')->get() as $row) {
            $absorb($row->getAttribute('ID_KS'), $row->getAttribute('TGL_TGT'));
        }

        return collect(array_values($map))
            ->sortBy([
                ['id_kel', 'asc'],
                ['year', 'desc'],
                ['month', 'desc'],
            ])
            ->values();
    }

    /**
     * ID kelompok untuk ringkasan admin: irisan ID yang ada di tabel **target** dan **realisasi**
     * (ID_KS sama muncul di keduanya — baris tampil hanya jika ada di dua tabel).
     * Perbandingan ID memakai trim + huruf besar agar beda casing/spasi tipis tidak memutus irisan.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function collectMonitoringKelompokIds(): \Illuminate\Support\Collection
    {
        $targetCanonByNorm = [];
        // Firebird PDO: baris mentah pakai kunci lowercase (id_ks); pluck() query builder
        // memakai $row->ID_KS → error. Ambil lewat model agar newFromBuilder menormalisasi kolom.
        foreach (Target::query()->orderBy('ID_KS')->select('ID_KS')->get()->pluck('ID_KS') as $raw) {
            $canonical = trim((string) $raw);
            if ($canonical === '') {
                continue;
            }
            $norm = $this->normalizeKelompokIdForMatch($canonical);
            $targetCanonByNorm[$norm] ??= $canonical;
        }

        $realNormPresent = [];
        foreach (Realisasi::query()->orderBy('ID_KS')->select('ID_KS')->get()->pluck('ID_KS') as $raw) {
            $canonical = trim((string) $raw);
            if ($canonical === '') {
                continue;
            }
            $realNormPresent[$this->normalizeKelompokIdForMatch($canonical)] = true;
        }

        $intersection = [];
        foreach ($targetCanonByNorm as $norm => $canonicalId) {
            if (isset($realNormPresent[$norm])) {
                $intersection[] = $canonicalId;
            }
        }

        return collect($intersection)->sort()->values();
    }

    /**
     * Ringkas kondisi DB untuk troubleshooting (admin): driver, jumlah baris, irisan ID.
     *
     * @return array<string, mixed>
     */
    public function diagnosticsForAdmin(): array
    {
        $model = new Target;
        $conn = $model->getConnectionName() ?? (string) config('database.default');
        $driver = (string) (config('database.connections.'.$conn.'.driver') ?? '');

        $targetRows = (int) Target::query()->count();
        $realisasiRows = (int) Realisasi::query()->count();

        $targetDistinct = Target::query()
            ->distinct()
            ->select('ID_KS')
            ->get()
            ->pluck('ID_KS')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn (string $v) => $v !== '')
            ->unique()
            ->count();

        $realisasiDistinct = Realisasi::query()
            ->distinct()
            ->select('ID_KS')
            ->get()
            ->pluck('ID_KS')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn (string $v) => $v !== '')
            ->unique()
            ->count();

        $intersection = $this->collectMonitoringKelompokIds();

        return [
            'database_connection' => $conn,
            'database_driver' => $driver,
            'target_table_row_count' => $targetRows,
            'realisasi_table_row_count' => $realisasiRows,
            'target_distinct_id_ks_count' => $targetDistinct,
            'realisasi_distinct_id_ks_count' => $realisasiDistinct,
            'intersection_id_count' => $intersection->count(),
            'intersection_id_sample' => $intersection->take(25)->values()->all(),
            'monitoring_tgl_tgt' => self::monitoringTglTgt(),
            'monitoring_period_row_count' => $this->collectMonitoringPeriodRows()->count(),
        ];
    }

    private function normalizeKelompokIdForMatch(string $id): string
    {
        return strtoupper(trim($id));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listSummariesForAdmin(): array
    {
        $out = [];
        foreach ($this->collectMonitoringPeriodRows() as $periodRow) {
            $id = trim((string) ($periodRow['id_kel'] ?? ''));
            if ($id === '') {
                continue;
            }
            $tgl = (string) ($periodRow['tgl_tgt'] ?? '');
            $detail = $this->getKelompokDetail($id, $tgl, false, true);
            $strSp = $this->findFieldInDetail($detail['fields'], 'STR_SP');

            $out[] = [
                'id_kel' => $detail['id_kel'],
                'nama_kelompok' => $detail['nama_kelompok'],
                'tgl_tgt' => $detail['tgl_tgt'],
                'period_year' => $detail['period_year'] ?? null,
                'period_month' => $detail['period_month'] ?? null,
                'tgl_baris_target' => $detail['tgl_baris_target'] ?? null,
                'jumlah_anggota' => $detail['jumlah_anggota'],
                'has_target' => $this->kelompokHasAnyTarget($detail['fields']),
                'fields' => $detail['fields'],
                'nominal_target' => $strSp['target'] ?? null,
                'total_realisasi' => $strSp['realisasi'] ?? '0.00',
                'persentase_pencapaian' => $strSp['persentase'] ?? null,
                'status_target' => $strSp['status'] ?? 'no_target',
            ];
        }

        return $out;
    }

    /**
     * Blok ekspor per kelompok (selaras tabel Web Admin + panel setoran).
     *
     * @return list<array<string, mixed>>
     */
    public function buildStructuredExportBlocks(int $maxKelompok): array
    {
        $summaries = $this->listSummariesForAdmin();
        if ($maxKelompok > 0 && count($summaries) > $maxKelompok) {
            $summaries = array_slice($summaries, 0, $maxKelompok);
        }

        $blocks = [];
        foreach ($summaries as $r) {
            /** @var list<array<string, mixed>> $fields */
            $fields = $r['fields'] ?? [];
            $byKey = [];
            foreach ($fields as $f) {
                $k = $f['key'] ?? '';
                if ($k !== '') {
                    $byKey[$k] = $f;
                }
            }

            $setorans = [];
            foreach (TargetRealisasiFieldCatalog::MONITORING_FIELDS_PHASE1 as $key) {
                if ($key === 'JLH_AGT_BR') {
                    continue;
                }
                $f = $byKey[$key] ?? null;
                $setorans[] = [
                    'label' => $this->setoranExportLabel($key),
                    'target' => $this->formatExportAmount($f['target'] ?? null),
                    'realisasi' => $this->formatExportAmount($f['realisasi'] ?? null),
                    'persentase' => $this->formatExportPersentase($f['persentase'] ?? null),
                ];
            }

            $jlh = $byKey['JLH_AGT_BR'] ?? null;
            $blocks[] = [
                'nama_kelompok' => $r['nama_kelompok'] ?? '',
                'id_kel' => $r['id_kel'] ?? '',
                'tanggal_target' => $this->formatExportDate($r['tgl_tgt'] ?? null),
                'jumlah_anggota_baru' => $this->formatJlhExportCell($jlh),
                'setorans' => $setorans,
            ];
        }

        return $blocks;
    }

    private function setoranExportLabel(string $key): string
    {
        $label = TargetRealisasiFieldCatalog::labelFor($key);
        if (str_starts_with($label, 'Setoran ')) {
            return substr($label, strlen('Setoran '));
        }

        return $label;
    }

    private function formatExportDate(mixed $tgl): string
    {
        if ($tgl === null) {
            return '';
        }
        $s = trim((string) $tgl);
        if ($s === '') {
            return '';
        }
        $ymd = substr($s, 0, 10);
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $ymd);

        return $dt !== false ? $dt->format('d/m/Y') : $s;
    }

    private function formatExportAmount(mixed $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }
        $n = (float) str_replace(',', '', (string) $value);
        if (! is_finite($n)) {
            return trim((string) $value);
        }

        return number_format($n, 0, ',', '.');
    }

    private function formatExportPersentase(mixed $pct): string
    {
        if ($pct === null || trim((string) $pct) === '') {
            return '';
        }
        $n = (float) str_replace(',', '', (string) $pct);
        if (! is_finite($n)) {
            return trim((string) $pct);
        }
        $rounded = abs($n - round($n)) < 0.0001 ? (string) (int) round($n) : rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');

        return $rounded.'%';
    }

    /**
     * @param  array<string, mixed>|null  $jlhField
     */
    private function formatJlhExportCell(?array $jlhField): string
    {
        if ($jlhField === null) {
            return '';
        }
        $t = $jlhField['target'] ?? null;
        $r = $jlhField['realisasi'] ?? null;
        $tStr = $t !== null && trim((string) $t) !== '' ? trim((string) $t) : '';
        $rStr = $r !== null && trim((string) $r) !== '' ? trim((string) $r) : '';
        if ($tStr === '' && $rStr === '') {
            return '';
        }
        if ($tStr === '') {
            return $rStr;
        }
        if ($rStr === '') {
            return $tStr;
        }

        return $tStr.' / '.$rStr;
    }

    /**
     * @return array<string, mixed>
     */
    public function getKelompokDetail(string $idKel, ?string $tglTgt = null, bool $allowTargetCrossPeriodFallback = false, bool $allowUnknownKelompok = false): array
    {
        $id = trim($idKel);
        if ($id === '') {
            throw new ModelNotFoundException('Kelompok not found');
        }
        if (! $this->kelompokExists($id) && ! $allowUnknownKelompok) {
            throw new ModelNotFoundException('Kelompok not found');
        }

        $tgl = $tglTgt !== null && trim($tglTgt) !== ''
            ? $this->assertEndOfMonthOrFail(trim($tglTgt))
            : TargetPeriod::currentPeriodEnd();

        $period = TargetPeriod::parseToYearMonth($tgl);
        if ($period === null) {
            throw new ModelNotFoundException('Kelompok not found');
        }

        [$target, $tglBarisTarget] = $this->resolveTargetRowForPeriod(
            $id,
            $period['year'],
            $period['month'],
            $allowTargetCrossPeriodFallback,
        );
        $realisasi = $this->resolveRealisasiRowForPeriod($id, $period['year'], $period['month']);

        $memberCount = $this->memberCountForKelompok($id);
        $namaKelompok = $this->kelompokExists($id)
            ? ($this->kelompokNama($id) ?? '')
            : ($allowUnknownKelompok ? '(Belum terdaftar di Kelompok Sahabat)' : '');
        $fields = [];

        foreach (TargetRealisasiFieldCatalog::MONITORING_FIELDS_PHASE1 as $key) {
            $targetVal = $this->readTargetFieldValue($target, $key);
            $realVal = $this->readRealisasiFieldValue($realisasi, $key, $memberCount);
            [$pct, $status] = $this->computePercentAndStatus($targetVal, $realVal);
            if (TargetRealisasiFieldCatalog::isCountField($key)) {
                $pct = null;
                $status = $this->countFieldStatus($targetVal, $realVal);
            }

            $fields[] = [
                'key' => $key,
                'label' => TargetRealisasiFieldCatalog::labelFor($key),
                'target' => $targetVal,
                'realisasi' => $realVal,
                'persentase' => $pct,
                'status' => $status,
            ];
        }

        $strSp = $this->findFieldInDetail($fields, 'STR_SP');

        return [
            'id_kel' => $id,
            'nama_kelompok' => $namaKelompok,
            'tgl_tgt' => $tgl,
            'period_year' => $period['year'],
            'period_month' => $period['month'],
            'tgl_baris_target' => ($tglBarisTarget !== null && $tglBarisTarget !== $tgl) ? $tglBarisTarget : null,
            'jumlah_anggota' => $memberCount,
            'fields' => $fields,
            'nominal_target' => $strSp['target'] ?? null,
            'total_realisasi' => $strSp['realisasi'] ?? '0.00',
            'persentase_pencapaian' => $strSp['persentase'] ?? null,
            'status_target' => $strSp['status'] ?? 'no_target',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummaryForKelompok(string $idKel): array
    {
        return $this->getKelompokDetail($idKel, null, true, false);
    }

    /**
     * @param  array<string, mixed>  $targets
     */
    public function upsertTargetsForKelompok(string $idKel, array $targets, ?string $tglTgt = null): Target
    {
        $id = trim($idKel);
        if ($id === '' || ! $this->kelompokExists($id)) {
            throw new ModelNotFoundException('Kelompok not found');
        }

        $normalized = $tglTgt !== null && trim($tglTgt) !== ''
            ? $this->assertEndOfMonthOrFail(trim($tglTgt))
            : TargetPeriod::currentPeriodEnd();
        $payload = ['ID_KS' => $id, 'TGL_TGT' => $normalized];

        foreach ($targets as $key => $value) {
            $key = (string) $key;
            if (! TargetRealisasiFieldCatalog::isAllowedTargetKey($key)) {
                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }
            if (TargetRealisasiFieldCatalog::isCountField($key)) {
                $payload[$key] = (string) (int) round((float) $value);
            } else {
                $payload[$key] = number_format((float) $value, 2, '.', '');
            }
        }

        return Target::query()->updateOrCreate(
            [
                'ID_KS' => $id,
                'TGL_TGT' => $normalized,
            ],
            $payload
        );
    }

    public function setNominalTarget(string $idKel, string $decimalInput): Target
    {
        return $this->upsertTargetsForKelompok($idKel, [
            'STR_SP' => $decimalInput,
        ], null);
    }

    public function kelompokExists(string $idKelTrim): bool
    {
        return KelSah::query()->where('ID_KEL', $idKelTrim)->exists();
    }

    public function memberCountForKelompok(string $idKelTrim): int
    {
        return (int) Anggota::query()->where('ID_KS', $idKelTrim)->count();
    }

    public function kelompokNama(string $idKelTrim): ?string
    {
        $v = KelSah::query()->where('ID_KEL', $idKelTrim)->value('NAMA_KEL');

        return is_string($v) ? trim($v) : null;
    }

    /**
     * @deprecated Gunakan getKelompokDetail; dipertahankan untuk kompatibilitas.
     */
    public function nominalTargetStringForKelompok(string $idKelTrim): ?string
    {
        $detail = $this->getKelompokDetail($idKelTrim, null, true, false);
        $strSp = $this->findFieldInDetail($detail['fields'], 'STR_SP');

        return $strSp['target'] ?? null;
    }

    /**
     * @return array{0: ?Target, 1: ?string}
     */
    private function resolveTargetRowForPeriod(string $idKel, int $year, int $month, bool $allowFallback): array
    {
        $canonicalTgl = TargetPeriod::lastDayOfMonth($year, $month);

        $inPeriod = Target::query()
            ->where('ID_KS', $idKel)
            ->whereYear('TGL_TGT', $year)
            ->whereMonth('TGL_TGT', $month)
            ->orderByDesc('TGL_TGT')
            ->get();

        foreach ($inPeriod as $row) {
            if ($this->targetRowHasAnyPhase1Value($row)) {
                return [$row, $this->coerceTglTgtToYmd($row->getAttribute('TGL_TGT'))];
            }
        }

        $firstInPeriod = $inPeriod->first();
        if ($firstInPeriod !== null) {
            return [$firstInPeriod, $this->coerceTglTgtToYmd($firstInPeriod->getAttribute('TGL_TGT'))];
        }

        if (! $allowFallback) {
            return [null, null];
        }

        $rows = Target::query()
            ->where('ID_KS', $idKel)
            ->orderByDesc('TGL_TGT')
            ->get();

        foreach ($rows as $row) {
            if ($this->targetRowHasAnyPhase1Value($row)) {
                return [$row, $this->coerceTglTgtToYmd($row->getAttribute('TGL_TGT'))];
            }
        }

        return [null, $canonicalTgl];
    }

    private function resolveRealisasiRowForPeriod(string $idKel, int $year, int $month): ?Realisasi
    {
        return Realisasi::query()
            ->where('ID_KS', $idKel)
            ->whereYear('TGL_TGT', $year)
            ->whereMonth('TGL_TGT', $month)
            ->orderByDesc('TGL_TGT')
            ->first();
    }

    /**
     * @deprecated Dipakai hanya untuk referensi legacy; gunakan resolveTargetRowForPeriod.
     *
     * @return array{0: ?Target, 1: ?string}
     */
    private function resolveTargetRowForMonitoring(string $idKel, string $tglMonitor, bool $allowFallback): array
    {
        $primary = Target::query()
            ->where('ID_KS', $idKel)
            ->where('TGL_TGT', $tglMonitor)
            ->first();

        if ($primary !== null && $this->targetRowHasAnyPhase1Value($primary)) {
            return [$primary, $tglMonitor];
        }

        if (! $allowFallback) {
            return [$primary, null];
        }

        $rows = Target::query()
            ->where('ID_KS', $idKel)
            ->orderByDesc('TGL_TGT')
            ->get();

        foreach ($rows as $row) {
            if ($this->targetRowHasAnyPhase1Value($row)) {
                /** @var Target $row */
                $tglAttr = $row->getAttribute('TGL_TGT');

                return [$row, $this->coerceTglTgtToYmd($tglAttr)];
            }
        }

        return [$primary, null];
    }

    /**
     * Baris realisasi untuk monitoring: dulu periode monitoring (config), lalu periode sama
     * dengan baris target yang dipakai (fallback lintas tanggal), lalu baris terbaru per ID.
     * Tanpa ini, data PROD yang hanya ada di TGL_TGT operasional (bukan 2099-01-01) tampil
     * di ringkasan dengan realisasi nol walau irisan ID target+realisasi ada.
     */
    private function resolveRealisasiRowForMonitoring(
        string $idKel,
        string $monitorTgl,
        ?Target $target,
        bool $allowFallback,
    ): ?Realisasi {
        $hit = Realisasi::query()
            ->where('ID_KS', $idKel)
            ->where('TGL_TGT', $monitorTgl)
            ->first();
        if ($hit !== null) {
            return $hit;
        }

        if ($target !== null) {
            $pairTgl = $this->coerceTglTgtToYmd($target->getAttribute('TGL_TGT'));
            if ($pairTgl !== null && $pairTgl !== '') {
                $hit = Realisasi::query()
                    ->where('ID_KS', $idKel)
                    ->where('TGL_TGT', $pairTgl)
                    ->first();
                if ($hit !== null) {
                    return $hit;
                }
            }
        }

        if ($allowFallback) {
            return Realisasi::query()
                ->where('ID_KS', $idKel)
                ->orderByDesc('TGL_TGT')
                ->first();
        }

        return null;
    }

    private function coerceTglTgtToYmd(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $s, $m)) {
            return substr($s, 0, 10);
        }

        return $s;
    }

    private function targetRowHasAnyPhase1Value(?Target $target): bool
    {
        if ($target === null) {
            return false;
        }
        foreach (TargetRealisasiFieldCatalog::MONITORING_FIELDS_PHASE1 as $key) {
            $raw = $target->getAttribute($key);
            if ($raw === null || $raw === '') {
                continue;
            }

            return true;
        }

        return false;
    }

    private function readTargetFieldValue(?Target $target, string $key): ?string
    {
        if ($target === null) {
            return null;
        }
        $raw = $target->getAttribute($key);
        if ($raw === null || $raw === '') {
            return null;
        }
        if (TargetRealisasiFieldCatalog::isCountField($key)) {
            return (string) (int) round((float) $raw);
        }

        return number_format((float) $raw, 2, '.', '');
    }

    private function readRealisasiFieldValue(?Realisasi $realisasi, string $key, int $memberCount): string
    {
        if (TargetRealisasiFieldCatalog::isCountField($key)) {
            return (string) $memberCount;
        }
        if ($realisasi === null) {
            return '0.00';
        }
        $raw = $realisasi->getAttribute($key);
        if ($raw === null || $raw === '') {
            return '0.00';
        }

        return number_format((float) $raw, 2, '.', '');
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private function findFieldInDetail(array $fields, string $key): array
    {
        foreach ($fields as $field) {
            if (($field['key'] ?? '') === $key) {
                return $field;
            }
        }

        return [
            'target' => null,
            'realisasi' => '0.00',
            'persentase' => null,
            'status' => 'no_target',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     */
    private function kelompokHasAnyTarget(array $fields): bool
    {
        foreach ($fields as $field) {
            $t = $field['target'] ?? null;
            if ($t !== null && $t !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Status untuk field jumlah (bukan rasio nominal).
     */
    private function countFieldStatus(?string $targetVal, string $realVal): string
    {
        if ($targetVal === null || $targetVal === '') {
            return 'no_target';
        }
        $t = (int) round((float) $targetVal);
        $r = (int) round((float) $realVal);
        if ($t <= 0) {
            return 'no_target';
        }
        if ($r >= $t) {
            return 'on_target';
        }

        return 'below_target';
    }

    /**
     * @return array{0: float|null, 1: string}
     */
    private function computePercentAndStatus(?string $nominalTarget, string $totalRealisasi): array
    {
        $t = $nominalTarget !== null && $nominalTarget !== '' ? (float) $nominalTarget : 0.0;
        $r = (float) $totalRealisasi;

        if ($t <= 0.0) {
            return [null, 'no_target'];
        }

        $pct = round(($r / $t) * 100, 2);
        if ($r >= $t) {
            return [$pct, 'on_target'];
        }

        return [$pct, 'below_target'];
    }

    /**
     * Serialisasi JSON ringkasan (admin summary & endpoint anggota). Logika sama dengan
     * {@see \App\Http\Resources\TargetRealisasiSummaryResource} — dipindah ke service agar
     * server yang belum menerima file Resource terpisah tetap bisa memuat endpoint.
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    public function serializeSummaryRowForApi(array $r): array
    {
        /** @var list<array<string, mixed>> $fields */
        $fields = $r['fields'] ?? [];
        $outFields = [];
        foreach ($fields as $f) {
            if (is_array($f)) {
                $outFields[] = $this->serializeFieldRowForApi($f);
            }
        }

        return $this->appendPeriodFieldsToApiRow([
            'id_kel' => $r['id_kel'] ?? null,
            'nama_kelompok' => $r['nama_kelompok'] ?? '',
            'tgl_tgt' => $r['tgl_tgt'] ?? null,
            'tgl_baris_target' => $r['tgl_baris_target'] ?? null,
            'jumlah_anggota' => (int) ($r['jumlah_anggota'] ?? 0),
            'has_target' => (bool) ($r['has_target'] ?? false),
            'fields' => $outFields,
            'nominal_target' => $r['nominal_target'] ?? null,
            'total_realisasi' => $r['total_realisasi'] ?? '0.00',
            'persentase_pencapaian' => $r['persentase_pencapaian'] ?? null,
            'status_target' => $r['status_target'] ?? 'no_target',
        ], $r);
    }

    /**
     * Serialisasi JSON detail kelompok (GET/PUT admin). Tanpa key `has_target` (beda dari ringkasan).
     *
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    public function serializeKelompokDetailForApi(array $r): array
    {
        /** @var list<array<string, mixed>> $fields */
        $fields = $r['fields'] ?? [];
        $outFields = [];
        foreach ($fields as $f) {
            if (is_array($f)) {
                $outFields[] = $this->serializeFieldRowForApi($f);
            }
        }

        return $this->appendPeriodFieldsToApiRow([
            'id_kel' => $r['id_kel'] ?? null,
            'nama_kelompok' => $r['nama_kelompok'] ?? '',
            'tgl_tgt' => $r['tgl_tgt'] ?? null,
            'tgl_baris_target' => $r['tgl_baris_target'] ?? null,
            'jumlah_anggota' => (int) ($r['jumlah_anggota'] ?? 0),
            'fields' => $outFields,
            'nominal_target' => $r['nominal_target'] ?? null,
            'total_realisasi' => $r['total_realisasi'] ?? '0.00',
            'persentase_pencapaian' => $r['persentase_pencapaian'] ?? null,
            'status_target' => $r['status_target'] ?? 'no_target',
        ], $r);
    }

    /**
     * @param  array<string, mixed>  $apiRow
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private function appendPeriodFieldsToApiRow(array $apiRow, array $source): array
    {
        $year = $source['period_year'] ?? null;
        $month = $source['period_month'] ?? null;
        if ($year === null || $month === null) {
            $parsed = TargetPeriod::parseToYearMonth(isset($apiRow['tgl_tgt']) ? (string) $apiRow['tgl_tgt'] : null);
            if ($parsed !== null) {
                $year = $parsed['year'];
                $month = $parsed['month'];
            }
        }

        if ($year !== null && $month !== null) {
            $apiRow['period_year'] = (int) $year;
            $apiRow['period_month'] = (int) $month;
        }

        return $apiRow;
    }

    /**
     * @param  array<string, mixed>  $f
     * @return array<string, mixed>
     */
    private function serializeFieldRowForApi(array $f): array
    {
        return [
            'key' => $f['key'] ?? null,
            'label' => $f['label'] ?? '',
            'target' => $f['target'] ?? null,
            'realisasi' => $f['realisasi'] ?? '0.00',
            'persentase' => $f['persentase'] ?? null,
            'status' => $f['status'] ?? 'no_target',
        ];
    }
}
