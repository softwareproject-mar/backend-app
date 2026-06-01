<?php

namespace App\Services;

use App\Models\KelSah;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class KelSahService
{
    use LogsActivity;

    public function __construct(
        private IdGeneratorService $idGenerator
    ) {}

    /**
     * Paginate kel_sah with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = KelSah::query();

        if (isset($filters['ID_KEL'])) {
            $query->where('ID_KEL', $filters['ID_KEL']);
        }

        if (isset($filters['ID_LO'])) {
            $query->where('ID_LO', $filters['ID_LO']);
        }

        if (isset($filters['ID_AO'])) {
            $query->where('ID_AO', $filters['ID_AO']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): KelSah
    {
        if (! isset($data['ID_KEL']) || empty($data['ID_KEL'])) {
            $data['ID_KEL'] = $this->idGenerator->generate('kel-sah');
        }

        return $this->performWithLog('create', function () use ($data) {
            return KelSah::create($data);
        }, [
            'resource_type' => 'kel_sah',
            'resource_id' => $data['ID_KEL'] ?? null,
            'description' => 'Menambahkan keluarga sejahtera: '.($data['ID_KEL'] ?? 'Unknown'),
            'new_data' => $data,
        ]);
    }

    public function find(string $id): KelSah
    {
        return KelSah::findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): KelSah
    {
        $old = $this->find($id);

        return $this->performWithLog('update', function () use ($old, $data) {
            $old->update($data);

            return $old->fresh();
        }, [
            'resource_type' => 'kel_sah',
            'resource_id' => $id,
            'description' => 'Mengupdate keluarga sejahtera: '.$id,
            'old_data' => $old->toArray(),
            'new_data' => $data,
        ]);
    }

    public function delete(string $id): void
    {
        $record = $this->find($id);

        $this->performWithLog('delete', function () use ($record) {
            $record->delete();
        }, [
            'resource_type' => 'kel_sah',
            'resource_id' => $record->ID_KEL,
            'description' => 'Menghapus keluarga sejahtera: '.$record->ID_KEL,
            'old_data' => $record->toArray(),
        ]);
    }
}
