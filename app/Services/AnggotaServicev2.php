<?php

namespace App\Services;

use App\Models\Anggota;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AnggotaService
{
    use LogsActivity;

    /**
     * Paginate anggota with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Anggota::query();

        if (isset($filters['NO_AGT'])) {
            $query->where('NO_AGT', $filters['NO_AGT']);
        }

        if (isset($filters['ID_KS'])) {
            $query->where('ID_KS', $filters['ID_KS']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Anggota
    {
        return $this->performWithLog('create', function () use ($data) {
            return Anggota::create($data);
        }, [
            'resource_type' => 'anggota',
            'resource_id' => $data['NO_AGT'] ?? null,
            'description' => 'Menambahkan anggota: '.($data['NAMA'] ?? 'Unknown'),
            'new_data' => $data,
        ]);
    }

    public function find(string $id): Anggota
    {
        return Anggota::findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Anggota
    {
        $old = $this->find($id);

        return $this->performWithLog('update', function () use ($old, $data) {
            $old->update($data);

            return $old->fresh();
        }, [
            'resource_type' => 'anggota',
            'resource_id' => $id,
            'description' => 'Mengupdate anggota: '.($old->NAMA ?? $id),
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
            'resource_type' => 'anggota',
            'resource_id' => $id,
            'description' => 'Menghapus anggota: '.($record->NAMA ?? $id),
            'old_data' => $record->toArray(),
        ]);
    }
}
