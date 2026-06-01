<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class CaseInsensitiveSearch
{
    /**
     * Kolom ID/nomor: tiap kata (token) boleh cocok salah satu.
     *
     * @var list<string>
     */
    private const ID_STYLE_COLUMN_SUFFIXES = [
        'NO_AGT',
        'ID_KS',
        'ID_KS_ASL',
        'ID_KEL',
        'ID_KET',
        'ID_SEKRE',
        'ID_LO',
        'ID_AO',
        'ID_PENG',
        'ID_KEL_SAH',
    ];

    /** @var list<string> */
    private const DATE_COLUMN_PREFIXES = ['TGL_'];

    public static function escapeLike(string $term): string
    {
        return addcslashes(trim($term), '%_\\');
    }

    public static function normalizeTerm(string $term): string
    {
        $t = trim($term);
        if ($t === '') {
            return '';
        }

        return (string) preg_replace('/\s+/u', ' ', $t);
    }

    /**
     * @return list<string>
     */
    public static function tokenize(string $term): array
    {
        $normalized = self::normalizeTerm($term);
        if ($normalized === '') {
            return [];
        }

        $parts = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($parts)) {
            return [];
        }

        return array_values(array_filter(
            $parts,
            static fn (string $p): bool => $p !== ''
        ));
    }

    public static function likePattern(string $term): string
    {
        $escaped = self::escapeLike($term);
        if ($escaped === '') {
            return '%%';
        }

        return '%'.mb_strtoupper($escaped, 'UTF-8').'%';
    }

    public static function upperLikeExpression(string $column): string
    {
        $column = trim($column);
        if (str_contains($column, '.')) {
            [$table, $col] = explode('.', $column, 2);

            return 'UPPER('.self::quoteIdent($table).'.'.self::quoteIdent($col).')';
        }

        return 'UPPER('.self::quoteIdent($column).')';
    }

    public static function applyLikeContains(Builder $query, string $column, string $term, string $boolean = 'and'): void
    {
        $tokens = self::tokenize($term);
        if ($tokens === []) {
            return;
        }

        $useAnyToken = self::isAnyTokenColumn($column);
        $query->where(function (Builder $inner) use ($column, $tokens, $useAnyToken, $query): void {
            self::applyTokensOnColumn($inner, $column, $tokens, $useAnyToken, $query->getConnection()->getDriverName());
        }, null, null, $boolean);
    }

    /**
     * @param  list<string>  $columns
     * @param  list<string>  $anyTokenColumns
     */
    public static function applyOrLikeContainsGroup(
        Builder $query,
        array $columns,
        string $term,
        array $anyTokenColumns = [],
    ): void {
        $tokens = self::tokenize($term);
        if ($tokens === [] || $columns === []) {
            return;
        }

        $driver = $query->getConnection()->getDriverName();
        $anyTokenSet = array_fill_keys($anyTokenColumns, true);

        $query->where(function (Builder $q) use ($columns, $tokens, $anyTokenSet, $driver): void {
            foreach ($columns as $index => $column) {
                $useAnyToken = isset($anyTokenSet[$column]) || self::isAnyTokenColumn($column);
                $sqlWrap = function (Builder $inner) use ($column, $tokens, $useAnyToken, $driver): void {
                    self::applyTokensOnColumn($inner, $column, $tokens, $useAnyToken, $driver);
                };
                if ($index === 0) {
                    $q->where($sqlWrap);
                } else {
                    $q->orWhere($sqlWrap);
                }
            }
        });
    }

    /**
     * @param  list<string>  $tokens
     */
    private static function applyTokensOnColumn(
        Builder $query,
        string $column,
        array $tokens,
        bool $anyToken,
        string $driver,
    ): void {
        $expr = self::searchableExpression($column, $driver);
        if ($anyToken) {
            $query->where(function (Builder $q) use ($expr, $tokens): void {
                foreach ($tokens as $i => $token) {
                    $pattern = self::likePattern($token);
                    if ($i === 0) {
                        $q->whereRaw($expr.' LIKE ?', [$pattern]);
                    } else {
                        $q->orWhereRaw($expr.' LIKE ?', [$pattern]);
                    }
                }
                // Jangan LIKE frasa penuh di kolom ID/CHAR pendek (Firebird -303 truncation).
            });

            return;
        }

        foreach ($tokens as $token) {
            $query->whereRaw($expr.' LIKE ?', [self::likePattern($token)]);
        }
    }

    private static function searchableExpression(string $column, string $driver): string
    {
        $ref = self::quotedColumnReference($column);
        if (self::isStatusCodeColumn($column)) {
            // Samakan perilaku dengan label UI: A/B/N -> Aktif/Blokir/Non Aktif.
            return "UPPER(CASE {$ref} WHEN 'A' THEN 'AKTIF' WHEN 'B' THEN 'BLOKIR' WHEN 'N' THEN 'NON AKTIF' ELSE CAST({$ref} AS VARCHAR(32)) END)";
        }

        if ($driver === 'firebird') {
            // Firebird: kolom non-text (date/numeric) perlu cast agar LIKE tidak error -303.
            return 'UPPER(CAST('.$ref.' AS VARCHAR(64)))';
        }

        return self::upperLikeExpression($column);
    }

    private static function quotedColumnReference(string $column): string
    {
        $column = trim($column);
        if (str_contains($column, '.')) {
            [$table, $col] = explode('.', $column, 2);

            return self::quoteIdent($table).'.'.self::quoteIdent($col);
        }

        return self::quoteIdent($column);
    }

    private static function isAnyTokenColumn(string $column): bool
    {
        $bare = $column;
        if (str_contains($column, '.')) {
            $bare = substr($column, strrpos($column, '.') + 1);
        }
        $upper = strtoupper($bare);

        return in_array($upper, self::ID_STYLE_COLUMN_SUFFIXES, true);
    }

    private static function isDateColumn(string $column): bool
    {
        $bare = $column;
        if (str_contains($column, '.')) {
            $bare = substr($column, strrpos($column, '.') + 1);
        }
        $upper = strtoupper($bare);
        foreach (self::DATE_COLUMN_PREFIXES as $prefix) {
            if (str_starts_with($upper, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private static function isStatusCodeColumn(string $column): bool
    {
        $bare = $column;
        if (str_contains($column, '.')) {
            $bare = substr($column, strrpos($column, '.') + 1);
        }

        return strtoupper($bare) === 'STAT';
    }

    private static function quoteIdent(string $ident): string
    {
        return '"'.str_replace('"', '""', strtoupper(trim($ident))).'"';
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    public static function firebirdAnggotaSearchSql(string $term): array
    {
        $tokens = self::tokenize($term);
        if ($tokens === []) {
            return ['', []];
        }

        $params = [];
        $namaConds = [];
        foreach ($tokens as $token) {
            $namaConds[] = 'UPPER("NAMA") LIKE ?';
            $params[] = self::likePattern($token);
        }
        $namaSql = '('.implode(' AND ', $namaConds).')';

        $noAgtConds = [];
        foreach ($tokens as $token) {
            $noAgtConds[] = 'UPPER("NO_AGT") LIKE ?';
            $params[] = self::likePattern($token);
        }
        $noAgtSql = '('.implode(' OR ', $noAgtConds).')';

        return [' AND ('.$noAgtSql.' OR '.$namaSql.')', $params];
    }

    /**
     * Pencarian NO_AGT di query PDO Firebird (DATA_TRS, dll.).
     *
     * @return array{0: string, 1: list<string>}
     */
    public static function firebirdNoAgtSearchSql(string $term): array
    {
        $tokens = self::tokenize($term);
        if ($tokens === []) {
            return ['', []];
        }

        $conds = [];
        $params = [];
        foreach ($tokens as $token) {
            $conds[] = 'UPPER("NO_AGT") LIKE ?';
            $params[] = self::likePattern($token);
        }

        return [' AND ('.implode(' OR ', $conds).')', $params];
    }
}
