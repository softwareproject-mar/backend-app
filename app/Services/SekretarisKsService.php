<?php

namespace App\Services;

use App\Models\SekretarisKs;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SekretarisKsService
{
    use LogsActivity;
    public function __construct(
        private IdGeneratorService $idGenerator
    ) {
    }
    /**
     * Paginate sekre_ks with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SekretarisKs::query();

        $allowedFilters = ['ID_SEKRE', 'NO_AGT', 'NAMA', 'STAT'];

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
    public function create(array $data): SekretarisKs
    {
        if (! isset($data['ID_SEKRE']) || empty($data['ID_SEKRE'])) {
            $data['ID_SEKRE'] = $this->idGenerator->generate('sekre-ks');
        }

        return $this->performWithLog('create', function() use ($data) {
            return SekretarisKs::create($data);
        }, [
            'resource_type' => 'sekretaris_ks',
            'resource_id' => $data['ID_SEKRE'] ?? null,
            'description' => 'Menambahkan sekretaris KS: ' . ($data['NAMA'] ?? 'Unknown'),
            'new_data' => $data,
        ]);
    }

    public function find(string $id): SekretarisKs
    {
        return SekretarisKs::findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): SekretarisKs
    {
        $old = $this->find($id);
        
        return $this->performWithLog('update', function() use ($old, $data) {
            $old->update($data);
            return $old->fresh();
        }, [
            'resource_type' => 'sekretaris_ks',
            'resource_id' => $id,
            'description' => 'Mengupdate sekretaris KS: ' . ($old->NAMA ?? $id),
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
            'resource_type' => 'sekretaris_ks',
            'resource_id' => $id,
            'description' => 'Menghapus sekretaris KS: ' . ($record->NAMA ?? $id),
            'old_data' => $record->toArray(),
        ]);
    }
}
