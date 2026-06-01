<?php

namespace App\Models;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Model untuk tabel skema lama (kolom UPPER_SNAKE). PDO Firebird memakai CASE_LOWER,
 * sehingga hasil query punya kunci no_agt — dinormalisasi ke NO_AGT saat hydrate.
 * Jangan dipakai untuk model konvensional (User, ActivityLog) yang memakai snake_case.
 */
abstract class FirebirdLegacyModel extends Model
{
    /**
     * Alias API untuk primary key sebagai `id`.
     *
     * Penting: di PHP nama method case-insensitive — method ini sama dengan getIDAttribute().
     * Jangan memanggil getAttribute($pk) saat $pk === 'ID', itu memanggil accessor ini lagi (rekursi → 500).
     */
    public function getIdAttribute(): mixed
    {
        $pk = $this->getKeyName();

        if ($pk === 'id') {
            return $this->attributes['id'] ?? null;
        }

        $pk = (string) $pk;
        foreach ([$pk, strtoupper($pk), 'id', 'ID'] as $key) {
            if (array_key_exists($key, $this->attributes)) {
                return $this->attributes[$key];
            }
        }

        return null;
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (! $model->shouldGenerateIncrementingKeyOnFirebird()) {
                return;
            }

            $pk = $model->getKeyName();
            $nextId = (int) $model->newQuery()->max($pk) + 1;
            $model->setAttribute($pk, $nextId);
        });
    }

    public function newFromBuilder($attributes = [], $connection = null)
    {
        if (is_object($attributes)) {
            $attributes = (array) $attributes;
        }

        if (is_array($attributes) && $this->connectionIsFirebird($connection)) {
            // Jangan ikutkan 'id': PDO mengirim kunci lowercase `id`, sedangkan model PK = `ID`.
            // Kalau `id` tetap lowercase, getKey()/findOrFail gagal → WHERE ID = null (-HY105).
            $keepLower = ['created_by', 'updated_by', 'deleted_at'];
            $normalized = [];
            foreach ($attributes as $key => $value) {
                $k = (string) $key;
                if (in_array($k, $keepLower, true)) {
                    $normalized[$k] = $value;
                } else {
                    $normalized[strtoupper($k)] = $value;
                }
            }
            $attributes = $normalized;
        }

        return parent::newFromBuilder($attributes, $connection);
    }

    protected function connectionIsFirebird(null|string|Connection $connection): bool
    {
        if ($connection instanceof Connection) {
            return $connection->getDriverName() === 'firebird';
        }

        $name = $connection ?? (new static)->getConnectionName() ?? config('database.default');

        return config('database.connections.'.$name.'.driver') === 'firebird';
    }

    protected function shouldGenerateIncrementingKeyOnFirebird(): bool
    {
        if (! $this->connectionIsFirebird($this->getConnectionName())) {
            return false;
        }

        if (! $this->getIncrementing()) {
            return false;
        }

        $pk = $this->getKeyName();
        $connectionName = $this->getConnectionName() ?? config('database.default');
        if (! Schema::connection($connectionName)->hasColumn($this->getTable(), $pk)) {
            return false;
        }

        $current = $this->getAttribute($pk);
        if ($current !== null && $current !== '') {
            return false;
        }

        return in_array($this->getKeyType(), ['int', 'integer'], true);
    }
}
