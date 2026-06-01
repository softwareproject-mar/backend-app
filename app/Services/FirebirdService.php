<?php

namespace App\Services;

use App\Support\CaseInsensitiveSearch;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

class FirebirdService
{
    protected ?PDO $pdo = null;

    protected array $config;

    public function __construct()
    {
        $this->config = config('database.connections.firebird_legacy');
    }

    /**
     * Establish connection to Firebird database
     */
    public function connect(): bool
    {
        try {
            if ($this->pdo !== null) {
                return true; // Already connected
            }

            $dsn = sprintf(
                'firebird:host=%s;port=%s;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            );

            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            return true;
        } catch (PDOException $e) {
            Log::error('Firebird connection failed: '.$e->getMessage());
            throw new \Exception('Failed to connect to Firebird database: '.$e->getMessage());
        }
    }

    /**
     * Fetch anggota data by NO_AGT from Firebird
     * Returns single anggota record with all fields
     */
    public function fetchAnggota(string $noAgt): ?array
    {
        try {
            // Connect to Firebird
            $this->connect();

            // Query single anggota by NO_AGT
            $sql = 'SELECT NO_AGT, NAMA, ID_KS, ID_LO, ID_AO, ID_KS_ASL, TGL_MTS, TGL_AKTIF, TGL_JA 
                    FROM ANGGOTA 
                    WHERE NO_AGT = ?';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$noAgt]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (! $data) {
                return null; // Not found
            }

            // Normalize column names and trim values
            $normalized = [
                'NO_AGT' => trim($data['NO_AGT'] ?? ''),
                'NAMA' => trim($data['NAMA'] ?? ''),
                'ID_KS' => trim($data['ID_KS'] ?? ''),
                'ID_LO' => trim($data['ID_LO'] ?? ''),
                'ID_AO' => trim($data['ID_AO'] ?? ''),
                'ID_KS_ASL' => ! empty($data['ID_KS_ASL']) ? trim($data['ID_KS_ASL']) : null,
                'TGL_MTS' => ! empty($data['TGL_MTS']) ? date('Y-m-d', strtotime($data['TGL_MTS'])) : null,
                'TGL_AKTIF' => ! empty($data['TGL_AKTIF']) ? date('Y-m-d', strtotime($data['TGL_AKTIF'])) : null,
                'TGL_JA' => ! empty($data['TGL_JA']) ? date('Y-m-d', strtotime($data['TGL_JA'])) : null,
            ];

            Log::info('Firebird anggota fetched successfully', [
                'no_agt' => $noAgt,
            ]);

            return $normalized;

        } catch (PDOException $e) {
            Log::error('Failed to fetch anggota from Firebird: '.$e->getMessage(), [
                'no_agt' => $noAgt,
                'error' => $e->getMessage(),
            ]);

            $this->disconnect();

            throw new \Exception('Failed to fetch anggota from Firebird: '.$e->getMessage());
        }
    }

    /**
     * Test connection to Firebird database
     * Returns true if connection successful
     */
    public function testConnection(): bool
    {
        try {
            $this->connect();

            // Simple test query
            $stmt = $this->pdo->query('SELECT FIRST 1 NO_AGT FROM ANGGOTA');
            $result = $stmt->fetch();

            Log::info('Firebird connection test successful');

            return $result !== false;

        } catch (PDOException $e) {
            Log::error('Firebird connection test failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Close connection
     */
    public function disconnect(): void
    {
        $this->pdo = null;
    }

    /**
     * Fetch list of anggota from Firebird with search and pagination
     * Uses hybrid approach: search-first with max 100 results
     * Without search: returns top 100 by NO_AGT
     * With search (min 3 chars): returns matching results up to 100
     */
    public function getAnggotaList(array $filters = []): array
    {
        try {
            // Connect to Firebird
            $this->connect();

            $search = $filters['search'] ?? null;
            $page = $filters['page'] ?? 1;
            $perPage = min($filters['per_page'] ?? 100, 100); // Max 100 per request

            // Build base query
            $sql = 'SELECT NO_AGT, NAMA FROM ANGGOTA WHERE NO_AGT IS NOT NULL';
            $params = [];

            $searchParams = [];
            if ($search) {
                [$searchSql, $searchParams] = CaseInsensitiveSearch::firebirdAnggotaSearchSql((string) $search);
                $sql .= $searchSql;
                $params = array_merge($params, $searchParams);
            }

            // Order by NO_AGT
            $sql .= ' ORDER BY NO_AGT ASC';

            // Get total count (only when search provided for performance)
            $total = 0;
            if ($search) {
                $countSql = 'SELECT COUNT(*) as TOTAL FROM ANGGOTA WHERE NO_AGT IS NOT NULL';
                [$countSearchSql, $countSearchParams] = CaseInsensitiveSearch::firebirdAnggotaSearchSql((string) $search);
                $countSql .= $countSearchSql;
                $countStmt = $this->pdo->prepare($countSql);
                $countStmt->execute($countSearchParams);
                $totalRow = $countStmt->fetch(PDO::FETCH_ASSOC);
                $total = $totalRow['TOTAL'] ?? 0;
            }

            // Always limit results - max 100 per request
            $offset = ($page - 1) * $perPage;
            $from = $offset + 1;
            $to = $offset + $perPage;
            $sql .= ' ROWS '.$from.' TO '.$to;

            // Execute query
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Normalize column names (Firebird returns uppercase)
            $normalizedData = array_map(function ($row) {
                return [
                    'NO_AGT' => trim($row['NO_AGT'] ?? ''),
                    'NAMA' => trim($row['NAMA'] ?? ''),
                ];
            }, $data);

            $showing = count($normalizedData);
            $hasMore = $search && $total > ($page * $perPage);

            Log::info('Firebird anggota list fetched successfully', [
                'showing' => $showing,
                'total' => $total,
                'has_more' => $hasMore,
                'search' => $search,
                'page' => $page,
            ]);

            return [
                'data' => $normalizedData,
                'total' => (int) $total,
                'showing' => $showing,
                'has_more' => $hasMore,
                'page' => $page,
                'per_page' => $perPage,
            ];

        } catch (PDOException $e) {
            Log::error('Failed to fetch anggota list from Firebird: '.$e->getMessage(), [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            $this->disconnect();

            throw new \Exception('Failed to fetch anggota list from Firebird: '.$e->getMessage());
        }
    }

    /**
     * Nama tabel DATA_TRS di Firebird (configurable).
     */
    protected function firebirdDataTrsTable(): string
    {
        $t = (string) config('obormas.firebird_data_trs_table', 'DATA_TRS');
        $t = trim($t);
        if ($t === '' || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $t)) {
            return 'DATA_TRS';
        }

        return strtoupper($t);
    }

    /**
     * Normalisasi satu baris DATA_TRS dari Firebird ke bentuk siap insert MySQL.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalizeDataTrsRow(array $data): array
    {
        $g = static function (array $row, string $key): ?string {
            $v = $row[$key] ?? $row[strtolower($key)] ?? null;
            if ($v === null || $v === '') {
                return null;
            }
            if (is_string($v)) {
                $t = trim($v);

                return $t !== '' ? $t : null;
            }

            return trim((string) $v);
        };

        return [
            'NO_AGT' => trim((string) ($g($data, 'NO_AGT') ?? '')),
            'STR_SP' => $g($data, 'STR_SP'),
            'STR_SW' => $g($data, 'STR_SW'),
            'STR_SKA' => $g($data, 'STR_SKA'),
            'STR_SRI' => $g($data, 'STR_SRI'),
            'STR_SDK' => $g($data, 'STR_SDK'),
            'STR_PJM' => $g($data, 'STR_PJM'),
            'STR_BNG' => $g($data, 'STR_BNG'),
            'PJM_BARU' => $g($data, 'PJM_BARU'),
            'STR_SHR' => $g($data, 'STR_SHR'),
            'STR_SBJ' => $g($data, 'STR_SBJ'),
            'STR_SJP' => $g($data, 'STR_SJP'),
            'STR_SPD' => $g($data, 'STR_SPD'),
            'STR_SRY' => $g($data, 'STR_SRY'),
            'STR_SMD' => $g($data, 'STR_SMD'),
            'TGL_LAP' => $g($data, 'TGL_LAP'),
        ];
    }

    /**
     * Ambil semua baris DATA_TRS untuk satu NO_AGT dari Firebird (bisa lebih dari satu baris).
     *
     * @return list<array<string, mixed>>
     */
    public function fetchDataTrsRows(string $noAgt): array
    {
        try {
            $this->connect();
            $table = $this->firebirdDataTrsTable();
            $cols = 'NO_AGT, STR_SP, STR_SW, STR_SKA, STR_SRI, STR_SDK, STR_PJM, STR_BNG, PJM_BARU, STR_SHR, STR_SBJ, STR_SJP, STR_SPD, STR_SRY, STR_SMD, TGL_LAP';
            $sql = "SELECT {$cols} FROM {$table} WHERE NO_AGT = ? ORDER BY NO_AGT";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$noAgt]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $row) {
                $out[] = $this->normalizeDataTrsRow($row);
            }

            Log::info('Firebird DATA_TRS rows fetched', [
                'no_agt' => $noAgt,
                'count' => count($out),
            ]);

            return $out;
        } catch (PDOException $e) {
            Log::error('Failed to fetch DATA_TRS from Firebird: '.$e->getMessage(), [
                'no_agt' => $noAgt,
            ]);
            $this->disconnect();

            throw new \Exception('Failed to fetch DATA_TRS from Firebird: '.$e->getMessage());
        }
    }

    /**
     * Daftar NO_AGT unik di DATA_TRS (pencarian + paginasi, pola mirip getAnggotaList).
     *
     * @param  array<string, mixed>  $filters
     * @return array{data: list<array{NO_AGT: string}>, total: int, showing: int, has_more: bool, page: int, per_page: int}
     */
    public function getDataTrsNoAgtList(array $filters = []): array
    {
        try {
            $this->connect();
            $table = $this->firebirdDataTrsTable();

            $search = $filters['search'] ?? null;
            $page = $filters['page'] ?? 1;
            $perPage = min($filters['per_page'] ?? 100, 100);

            $baseWhere = 'NO_AGT IS NOT NULL';
            $params = [];

            $sql = "SELECT DISTINCT NO_AGT FROM {$table} WHERE {$baseWhere}";
            if ($search) {
                [$searchSql, $searchParams] = CaseInsensitiveSearch::firebirdNoAgtSearchSql((string) $search);
                $sql .= $searchSql;
                $params = array_merge($params, $searchParams);
            }
            $sql .= ' ORDER BY NO_AGT ASC';

            $total = 0;
            if ($search) {
                [$countSearchSql, $countSearchParams] = CaseInsensitiveSearch::firebirdNoAgtSearchSql((string) $search);
                $countSql = "SELECT COUNT(DISTINCT NO_AGT) AS CNT FROM {$table} WHERE {$baseWhere}{$countSearchSql}";
                $countStmt = $this->pdo->prepare($countSql);
                $countStmt->execute($countSearchParams);
                $totalRow = $countStmt->fetch(PDO::FETCH_ASSOC);
                $total = (int) ($totalRow['CNT'] ?? 0);
            }

            $offset = ($page - 1) * $perPage;
            $from = $offset + 1;
            $to = $offset + $perPage;
            $sql .= ' ROWS '.$from.' TO '.$to;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $normalizedData = array_map(function ($row) {
                return [
                    'NO_AGT' => trim((string) ($row['NO_AGT'] ?? '')),
                ];
            }, $data);

            $showing = count($normalizedData);
            $hasMore = $search && $total > ($page * $perPage);

            Log::info('Firebird DATA_TRS NO_AGT list fetched', [
                'showing' => $showing,
                'total' => $total,
                'search' => $search,
                'page' => $page,
            ]);

            return [
                'data' => $normalizedData,
                'total' => $total,
                'showing' => $showing,
                'has_more' => $hasMore,
                'page' => $page,
                'per_page' => $perPage,
            ];
        } catch (PDOException $e) {
            Log::error('Failed to fetch DATA_TRS list from Firebird: '.$e->getMessage(), [
                'filters' => $filters,
            ]);
            $this->disconnect();

            throw new \Exception('Failed to fetch DATA_TRS list from Firebird: '.$e->getMessage());
        }
    }

    /**
     * Paginasi seluruh baris DATA_TRS dari Firebird (sumber tunggal untuk API read-only).
     *
     * @param  array{NO_AGT?: string|null}  $filters
     * @return array{data: list<array<string, mixed>>, total: int}
     */
    public function paginateDataTrs(array $filters, int $page, int $perPage): array
    {
        try {
            $this->connect();
            $table = $this->firebirdDataTrsTable();
            $cols = 'NO_AGT, STR_SP, STR_SW, STR_SKA, STR_SRI, STR_SDK, STR_PJM, STR_BNG, PJM_BARU, STR_SHR, STR_SBJ, STR_SJP, STR_SPD, STR_SRY, STR_SMD, TGL_LAP';

            $page = max(1, $page);
            $perPage = max(1, min($perPage, 500));

            $noAgt = isset($filters['NO_AGT']) ? trim((string) $filters['NO_AGT']) : '';
            $where = '1=1';
            $params = [];
            if ($noAgt !== '') {
                $where .= ' AND NO_AGT = ?';
                $params[] = $noAgt;
            }

            $countSql = "SELECT COUNT(*) AS CNT FROM {$table} WHERE {$where}";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalRow = $countStmt->fetch(PDO::FETCH_ASSOC);
            $total = (int) ($totalRow['CNT'] ?? 0);

            $offset = ($page - 1) * $perPage;
            $from = $offset + 1;
            $to = $offset + $perPage;

            $sql = "SELECT {$cols} FROM {$table} WHERE {$where} ORDER BY NO_AGT, TGL_LAP ROWS {$from} TO {$to}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $data[] = $this->normalizeDataTrsRow($row);
            }

            Log::info('Firebird DATA_TRS paginated', [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'returned' => count($data),
            ]);

            return [
                'data' => $data,
                'total' => $total,
            ];
        } catch (PDOException $e) {
            Log::error('Failed to paginate DATA_TRS from Firebird: '.$e->getMessage(), [
                'filters' => $filters,
            ]);
            $this->disconnect();

            throw new \Exception('Failed to paginate DATA_TRS from Firebird: '.$e->getMessage());
        }
    }

    /**
     * Ambil banyak baris DATA_TRS untuk export (Firebird), dengan batas aman.
     *
     * @return list<array<string, mixed>>
     */
    public function fetchDataTrsForExport(?string $noAgt, int $limit): array
    {
        try {
            $this->connect();
            $table = $this->firebirdDataTrsTable();
            $cols = 'NO_AGT, STR_SP, STR_SW, STR_SKA, STR_SRI, STR_SDK, STR_PJM, STR_BNG, PJM_BARU, STR_SHR, STR_SBJ, STR_SJP, STR_SPD, STR_SRY, STR_SMD, TGL_LAP';

            $limit = max(1, min($limit, 10000));
            $where = '1=1';
            $params = [];
            $trimNo = $noAgt !== null ? trim($noAgt) : '';
            if ($trimNo !== '') {
                $where .= ' AND NO_AGT = ?';
                $params[] = $trimNo;
            }

            $sql = "SELECT {$cols} FROM {$table} WHERE {$where} ORDER BY NO_AGT, TGL_LAP ROWS 1 TO {$limit}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $row) {
                $out[] = $this->normalizeDataTrsRow($row);
            }

            return $out;
        } catch (PDOException $e) {
            Log::error('Failed to export DATA_TRS from Firebird: '.$e->getMessage());
            $this->disconnect();

            throw new \Exception('Failed to export DATA_TRS from Firebird: '.$e->getMessage());
        }
    }

    /**
     * Kolom DATA_TRS yang boleh dipakai untuk agregasi realisasi (cegah injeksi SQL).
     *
     * @return list<string>
     */
    public static function allowedTrsRealisasiSumColumns(): array
    {
        return [
            'STR_SP', 'STR_SW', 'STR_SKA', 'STR_SRI', 'STR_SDK', 'STR_PJM', 'STR_BNG',
            'PJM_BARU', 'STR_SHR', 'STR_SBJ', 'STR_SJP', 'STR_SPD', 'STR_SRY', 'STR_SMD',
        ];
    }

    /**
     * @param  list<string>  $requested
     * @return list<string>
     */
    public function normalizeRealisasiSumColumnNames(array $requested): array
    {
        $allowed = array_fill_keys(self::allowedTrsRealisasiSumColumns(), true);
        $out = [];
        foreach ($requested as $c) {
            $u = strtoupper(trim((string) $c));
            if ($u !== '' && isset($allowed[$u])) {
                $out[] = $u;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Jumlah nominal realisasi per kelompok: SUM per baris DATA_TRS (kolom terpilih) untuk anggota dengan ID_KS = id kelompok.
     *
     * @param  list<string>|null  $columnNames  null = pakai config obormas.target_realisasi_sum_columns
     */
    public function sumRealisasiNominalForKelompok(string $idKel, ?array $columnNames = null): string
    {
        $idKelTrim = trim($idKel);
        if ($idKelTrim === '') {
            return '0.00';
        }

        $fromConfig = $columnNames ?? config('obormas.target_realisasi_sum_columns', ['STR_SP']);
        $cols = $this->normalizeRealisasiSumColumnNames(is_array($fromConfig) ? $fromConfig : ['STR_SP']);
        if ($cols === []) {
            $cols = ['STR_SP'];
        }

        try {
            $this->connect();
            $table = $this->firebirdDataTrsTable();
            $selectParts = array_map(static fn (string $c): string => 'D.'.$c, $cols);
            $sql = 'SELECT '.implode(', ', $selectParts).' FROM '.$table.' D INNER JOIN ANGGOTA A ON D.NO_AGT = A.NO_AGT WHERE TRIM(A.ID_KS) = ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$idKelTrim]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $total = 0.0;
            foreach ($rows as $row) {
                foreach ($cols as $col) {
                    $raw = $row[$col] ?? $row[strtolower($col)] ?? null;
                    $total += $this->floatFromTrsCell($raw);
                }
            }

            return number_format($total, 2, '.', '');
        } catch (PDOException $e) {
            Log::error('Failed to sum DATA_TRS realisasi for kelompok: '.$e->getMessage(), [
                'id_kel' => $idKelTrim,
            ]);
            $this->disconnect();

            throw new \Exception('Failed to sum DATA_TRS realisasi for kelompok: '.$e->getMessage());
        }
    }

    private function floatFromTrsCell(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }
        if (is_string($value)) {
            $s = str_replace(',', '.', trim($value));
            if ($s === '') {
                return 0.0;
            }

            return is_numeric($s) ? (float) $s : 0.0;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        return 0.0;
    }
}
