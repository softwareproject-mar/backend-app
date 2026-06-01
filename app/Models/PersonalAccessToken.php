<?php

namespace App\Models;

use Illuminate\Database\QueryException;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    public function save(array $options = [])
    {
        try {
            return parent::save($options);
        } catch (QueryException $e) {
            if ($this->shouldIgnoreFirebirdLastUsedAtDeadlock($e)) {
                return true;
            }

            throw $e;
        }
    }

    private function shouldIgnoreFirebirdLastUsedAtDeadlock(QueryException $e): bool
    {
        $isFirebird = $this->getConnection()?->getDriverName() === 'firebird';
        if (! $isFirebird) {
            return false;
        }

        $message = $e->getMessage();
        $isDeadlock = str_contains($message, '-913') || str_contains(mb_strtolower($message), 'deadlock');
        if (! $isDeadlock) {
            return false;
        }

        $dirty = array_keys($this->getDirty());

        // Kasus yang ingin ditoleransi: Sanctum update last_used_at (+ updated_at).
        $allowedDirty = array_diff($dirty, ['last_used_at', 'updated_at']);

        return $dirty !== [] && $allowedDirty === [];
    }
}
