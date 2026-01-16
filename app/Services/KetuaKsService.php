<?php

namespace App\Services;

use App\Models\KetuaKs;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class KetuaKsService
{
    use LogsActivity;
    public function __construct(
        private IdGeneratorService $idGenerator
    ) {
    }
    /**
     * Paginate ketua_ks with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = KetuaKs::query();

        $allowedFilters = ['ID_KET', 'NO_AGT', 'NAMA', 'STAT'];

        foreach ($allowedFilters as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): KetuaKs
    {
        if (! isset($data['ID_KET']) || empty($data['ID_KET'])) {
            $data['ID_KET'] = $this->idGenerator->generate('ketua-ks');
        }

        return $this->performWithLog('create', function() use ($data) {
            return KetuaKs::create($data);
        }, [
            'resource_type' => 'ketua_ks',
            'resource_id' => $data['ID_KET'] ?? null,
            'description' => 'Menambahkan ketua KS: ' . ($data['NAMA'] ?? 'Unknown'),
            'new_data' => $data,
        ]);
    }

    public function find(string $id): KetuaKs
    {
        return KetuaKs::findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): KetuaKs
    {
        $old = $this->find($id);
        
        return $this->performWithLog('update', function() use ($old, $data) {
            $old->update($data);
            return $old->fresh();
        }, [
            'resource_type' => 'ketua_ks',
            'resource_id' => $id,
            'description' => 'Mengupdate ketua KS: ' . ($old->NAMA ?? $id),
            'old_data' => $old->toArray(),
            'new_data' => $data,
        ]);
    }

    public function delete(string $id): void
    {
        $record = $this->find($id);
        
        $this->performWithLog('delete', function() use ($record) {
            $record->delete();
        }, [
            'resource_type' => 'ketua_ks',
            'resource_id' => $id,
            'description' => 'Menghapus ketua KS: ' . ($record->NAMA ?? $id),
            'old_data' => $record->toArray(),
        ]);
    }
}
