<?php

namespace App\Database\Query\Processors;

use Danidoble\Firebird\Query\Processors\FirebirdProcessor;
use Illuminate\Database\Query\Builder;

/**
 * pdo_firebird tidak mendukung lastInsertId().
 * INSERT ... RETURNING dijalankan via PDO::prepare()->execute() langsung,
 * bukan lewat selectFromWriteConnection() yang membuka transaksi implisit dan
 * menyebabkan "There is already an active transaction" di Firebird 2.5.
 */
class FirebirdReturningProcessor extends FirebirdProcessor
{
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $connection = $query->getConnection();
        $connection->recordsHaveBeenModified();

        // Jalankan INSERT ... RETURNING langsung via PDO tanpa membuka transaksi baru
        $pdo = $connection->getPdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($values));

        $row = $stmt->fetch(\PDO::FETCH_OBJ);

        $sequence = $sequence ?: 'id';

        if (! $row) {
            return 0;
        }

        $lower = strtolower((string) $sequence);
        $upper = strtoupper((string) $sequence);

        return (int) (
            (isset($row->{$sequence}) ? $row->{$sequence} : null)
            ?? (isset($row->{$lower}) ? $row->{$lower} : null)
            ?? (isset($row->{$upper}) ? $row->{$upper} : null)
            ?? 0
        );
    }
}
