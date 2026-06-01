<?php

namespace App\Database\Query\Grammars;

use Danidoble\Firebird\Query\Grammars\FirebirdGrammar as BaseFirebirdGrammar;
use Illuminate\Database\Query\Builder;

/**
 * Firebird 2.5 stores unquoted identifiers in uppercase. Laravel quotes "email" which
 * does not match column EMAIL. Uppercase all wrapped segments so Eloquent can use
 * snake_case attribute names while the database uses UPPER_SNAKE / UPPERCASE columns.
 */
class FirebirdUppercaseGrammar extends BaseFirebirdGrammar
{
    /**
     * Firebird 2.1+: INSERT ... RETURNING — required because PDO Firebird has no lastInsertId().
     */
    public function compileInsertGetId(Builder $query, $values, $sequence)
    {
        return $this->compileInsert($query, $values).' returning '.$this->wrap($sequence ?: 'id');
    }

    protected function wrapValue($value)
    {
        if ($value !== '*') {
            return '"'.str_replace('"', '""', strtoupper($value)).'"';
        }

        return $value;
    }
}
