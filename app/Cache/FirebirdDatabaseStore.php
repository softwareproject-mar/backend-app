<?php

namespace App\Cache;

use Illuminate\Cache\DatabaseStore;

/**
 * Driver cache database untuk Firebird: Illuminate\DatabaseStore memakai upsert()
 * pada put()/putMany(), sedangkan engine Firebird tidak mendukung upsert di grammar Laravel.
 * Mengganti dengan updateOrInsert (SELECT + INSERT/UPDATE) yang didukung.
 */
class FirebirdDatabaseStore extends DatabaseStore
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function putMany(array $values, $seconds): bool
    {
        if ($values === []) {
            return false;
        }

        $expiration = $this->getTime() + $seconds;

        foreach ($values as $key => $value) {
            $prefixedKey = $this->prefix.$key;
            $row = [
                'key' => $prefixedKey,
                'value' => $this->serialize($value),
                'expiration' => $expiration,
            ];

            $this->table()->updateOrInsert(
                ['key' => $prefixedKey],
                $row
            );
        }

        return true;
    }
}
