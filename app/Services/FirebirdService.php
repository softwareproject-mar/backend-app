<?php

namespace App\Services;

use PDO;
use PDOException;
use Illuminate\Support\Facades\Log;

class FirebirdService
{
    protected ?PDO $pdo = null;
    protected array $config;

    public function __construct()
    {
        $this->config = config('database.connections.firebird');
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
                "firebird:host=%s;port=%s;dbname=%s;charset=%s",
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
            Log::error('Firebird connection failed: ' . $e->getMessage());
            throw new \Exception('Failed to connect to Firebird database: ' . $e->getMessage());
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
            $sql = "SELECT NO_AGT, NAMA, ID_KS, ID_LO, ID_AO, ID_KS_ASL, TGL_MTS, TGL_AKTIF, TGL_JA 
                    FROM ANGGOTA 
                    WHERE NO_AGT = ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$noAgt]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return null; // Not found
            }

            // Normalize column names and trim values
            $normalized = [
                'NO_AGT' => trim($data['NO_AGT'] ?? ''),
                'NAMA' => trim($data['NAMA'] ?? ''),
                'ID_KS' => trim($data['ID_KS'] ?? ''),
                'ID_LO' => trim($data['ID_LO'] ?? ''),
                'ID_AO' => trim($data['ID_AO'] ?? ''),
                'ID_KS_ASL' => !empty($data['ID_KS_ASL']) ? trim($data['ID_KS_ASL']) : null,
                'TGL_MTS' => !empty($data['TGL_MTS']) ? date('Y-m-d', strtotime($data['TGL_MTS'])) : null,
                'TGL_AKTIF' => !empty($data['TGL_AKTIF']) ? date('Y-m-d', strtotime($data['TGL_AKTIF'])) : null,
                'TGL_JA' => !empty($data['TGL_JA']) ? date('Y-m-d', strtotime($data['TGL_JA'])) : null,
            ];

            Log::info('Firebird anggota fetched successfully', [
                'no_agt' => $noAgt
            ]);

            return $normalized;

        } catch (PDOException $e) {
            Log::error('Failed to fetch anggota from Firebird: ' . $e->getMessage(), [
                'no_agt' => $noAgt,
                'error' => $e->getMessage()
            ]);

            $this->disconnect();

            throw new \Exception('Failed to fetch anggota from Firebird: ' . $e->getMessage());
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
            $stmt = $this->pdo->query("SELECT FIRST 1 NO_AGT FROM ANGGOTA");
            $result = $stmt->fetch();
            
            Log::info('Firebird connection test successful');
            
            return $result !== false;
            
        } catch (PDOException $e) {
            Log::error('Firebird connection test failed: ' . $e->getMessage());
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
            $sql = "SELECT NO_AGT, NAMA FROM ANGGOTA WHERE NO_AGT IS NOT NULL";
            $params = [];

            // Add search condition
            if ($search) {
                $sql .= " AND (NO_AGT LIKE ? OR NAMA LIKE ?)";
                $searchParam = '%' . $search . '%';
                $params[] = $searchParam;
                $params[] = $searchParam;
            }

            // Order by NO_AGT
            $sql .= " ORDER BY NO_AGT ASC";

            // Get total count (only when search provided for performance)
            $total = 0;
            if ($search) {
                $countSql = "SELECT COUNT(*) as TOTAL FROM ANGGOTA WHERE NO_AGT IS NOT NULL";
                $countSql .= " AND (NO_AGT LIKE ? OR NAMA LIKE ?)";
                $countStmt = $this->pdo->prepare($countSql);
                $countStmt->execute([$searchParam, $searchParam]);
                $totalRow = $countStmt->fetch(PDO::FETCH_ASSOC);
                $total = $totalRow['TOTAL'] ?? 0;
            }

            // Always limit results - max 100 per request
            $offset = ($page - 1) * $perPage;
            $from = $offset + 1;
            $to = $offset + $perPage;
            $sql .= " ROWS " . $from . " TO " . $to;

            // Execute query
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Normalize column names (Firebird returns uppercase)
            $normalizedData = array_map(function($row) {
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
                'page' => $page
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
            Log::error('Failed to fetch anggota list from Firebird: ' . $e->getMessage(), [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            
            $this->disconnect();
            
            throw new \Exception('Failed to fetch anggota list from Firebird: ' . $e->getMessage());
        }
    }
}