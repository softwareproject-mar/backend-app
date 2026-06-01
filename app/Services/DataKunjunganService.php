<?php

namespace App\Services;

use App\Models\Anggota;
use App\Models\DataKunjungan;
use App\Models\KelSah;
use App\Models\User;
use App\Support\CaseInsensitiveSearch;
use App\Support\MemberScope;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class DataKunjunganService
{
    use LogsActivity;

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        $table = $query->getModel()->getTable();

        $allowedFilters = [
            'ID_LO',
            'NO_AGT',
            'ID_KEL_SAH',
            'TGL_KUN',
            'KEGIATAN',
            'ID_PIC',
        ];

        foreach ($allowedFilters as $field) {
            if (isset($filters[$field])) {
                // Wajib pakai prefix tabel: paginate() memakai join ke kel_sah/anggota;
                // Firebird error "Ambiguous field name" jika NO_AGT / ID_LO tidak di-qualify.
                $query->where("{$table}.{$field}", $filters[$field]);
            }
        }

        if (isset($filters['created_by'])) {
            $query->where("{$table}.created_by", (int) $filters['created_by']);
        }
    }

    /**
     * Pencarian nama kelompok / nama anggota (butuh join kel_sah + anggota — hanya dipanggil dari paginate()).
     *
     * @param  array<string, mixed>  $filters
     */
    protected function applyListNameSearchFilter(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $w) use ($search) {
            CaseInsensitiveSearch::applyOrLikeContainsGroup(
                $w,
                [
                    'kel_sah.ID_KEL',
                    'kel_sah.NAMA_KEL',
                    'data_kunjungan.ID_LO',
                    'data_kunjungan.NO_AGT',
                    'anggota.NAMA',
                    'data_kunjungan.ID_KEL_SAH',
                    'data_kunjungan.TGL_KUN',
                    'data_kunjungan.KEGIATAN',
                    'data_kunjungan.ID_PIC',
                ],
                $search,
                ['kel_sah.ID_KEL', 'data_kunjungan.ID_LO', 'data_kunjungan.NO_AGT', 'data_kunjungan.ID_KEL_SAH', 'data_kunjungan.ID_PIC'],
            );
        });
    }

    /**
     * Filter baris kunjungan untuk agregasi laporan admin (tanpa join — pakai whereExists).
     *
     * @param  array<string, mixed>  $filters
     */
    protected function applyKunjunganReportLineFilters(Builder $query, array $filters): void
    {
        $t = $query->getModel()->getTable();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $outer) use ($search, $t) {
            $outer->whereExists(function ($sub) use ($search, $t) {
                $sub->selectRaw('1')
                    ->from('kel_sah')
                    ->whereColumn('kel_sah.ID_KEL', "{$t}.ID_KEL_SAH");
                CaseInsensitiveSearch::applyOrLikeContainsGroup(
                    $sub,
                    ['kel_sah.ID_KEL', 'kel_sah.NAMA_KEL'],
                    $search,
                    ['kel_sah.ID_KEL'],
                );
            })->orWhereExists(function ($sub) use ($search, $t) {
                $sub->selectRaw('1')
                    ->from('anggota')
                    ->whereColumn('anggota.NO_AGT', "{$t}.NO_AGT");
                CaseInsensitiveSearch::applyLikeContains($sub, 'anggota.NAMA', $search);
            })->orWhere(function (Builder $self) use ($search, $t) {
                CaseInsensitiveSearch::applyOrLikeContainsGroup(
                    $self,
                    ["{$t}.ID_LO", "{$t}.NO_AGT", "{$t}.ID_KEL_SAH", "{$t}.TGL_KUN", "{$t}.KEGIATAN", "{$t}.ID_PIC"],
                    $search,
                    ["{$t}.ID_LO", "{$t}.NO_AGT", "{$t}.ID_KEL_SAH", "{$t}.ID_PIC"],
                );
            });
        });
    }

    /**
     * Baca kolom hasil selectRaw/aggregate: FirebirdLegacyModel menormalisasi kunci atribut ke UPPERCASE,
     * sehingga alias "frekuensi" jadi FREKUENSI — akses properti camel/lowercase mengembalikan null.
     */
    private function rawAggregateColumn(Model $row, string $logicalName): mixed
    {
        foreach ($row->getAttributes() as $key => $value) {
            if (strcasecmp((string) $key, $logicalName) === 0) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Paginate data kunjungan with optional filters and ownership validation.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15, ?User $user = null): LengthAwarePaginator
    {
        // Apply ownership filtering for user role
        $filters = MemberScope::mergeOwnershipFilterForCrud($user, $filters);

        $query = DataKunjungan::query()
            ->select('data_kunjungan.*')
            ->leftJoin('kel_sah', 'data_kunjungan.ID_KEL_SAH', '=', 'kel_sah.ID_KEL')
            ->leftJoin('anggota', 'data_kunjungan.NO_AGT', '=', 'anggota.NO_AGT')
            ->addSelect([
                'kel_sah.NAMA_KEL as join_nama_kelompok',
                'anggota.NAMA as join_nama_anggota',
            ]);

        $this->applyFilters($query, $filters);
        $this->applyListNameSearchFilter($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Ringkasan laporan admin: frekuensi kunjungan per kelompok (ID_KEL_SAH terisi).
     *
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Support\Collection<int, array{id_kel_sah: string, nama_kelompok: string, frekuensi: int}>
     */
    public function reportGroupSummaryRows(array $filters = []): Collection
    {
        $q = DataKunjungan::query()
            ->selectRaw('data_kunjungan.ID_KEL_SAH as rep_id_kel, COUNT(*) as frekuensi')
            ->whereNotNull('data_kunjungan.ID_KEL_SAH')
            ->where('data_kunjungan.ID_KEL_SAH', '!=', '');

        $this->applyKunjunganReportLineFilters($q, $filters);

        $aggregates = $q->groupBy('data_kunjungan.ID_KEL_SAH')
            ->orderBy('data_kunjungan.ID_KEL_SAH')
            ->get();

        if ($aggregates->isEmpty()) {
            return collect();
        }

        $ids = $aggregates
            ->map(fn ($row) => trim((string) ($row->rep_id_kel ?? $row->REP_ID_KEL ?? '')))
            ->filter()
            ->unique()
            ->values();
        // Jangan pakai pluck(..., 'ID_KEL'): hasil mentah stdClass dari driver bisa
        // memakai kunci lowercase (id_kel) sehingga $row->ID_KEL meledak di Firebird/PDO.
        $namaById = KelSah::query()
            ->whereIn('ID_KEL', $ids->all())
            ->get()
            ->mapWithKeys(function (KelSah $row): array {
                $id = trim((string) $row->getKey());
                if ($id === '') {
                    return [];
                }

                return [$id => trim((string) ($row->NAMA_KEL ?? ''))];
            });

        return $aggregates->map(function ($row) use ($namaById) {
            $id = trim((string) ($row->rep_id_kel ?? $row->REP_ID_KEL ?? ''));

            return [
                'id_kel_sah' => $id,
                'nama_kelompok' => (string) ($namaById[$id] ?? ''),
                'frekuensi' => (int) ($this->rawAggregateColumn($row, 'frekuensi') ?? 0),
            ];
        })->sortBy('nama_kelompok', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    /**
     * Ringkasan per anggota dalam satu kelompok (untuk laporan admin Tingkat B).
     *
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Support\Collection<int, array{no_agt: string, nama_anggota: string, frekuensi: int, tanggal_terakhir: string|null}>
     */
    public function reportAnggotaSummaryForKelompok(string $idKelSah, array $filters = []): Collection
    {
        $idKelSah = trim($idKelSah);
        if ($idKelSah === '') {
            return collect();
        }

        $q = DataKunjungan::query()
            ->where('data_kunjungan.ID_KEL_SAH', $idKelSah)
            ->whereNotNull('data_kunjungan.NO_AGT')
            ->where('data_kunjungan.NO_AGT', '!=', '');

        $this->applyKunjunganReportLineFilters($q, $filters);

        $aggregates = $q
            ->selectRaw('data_kunjungan.NO_AGT as rep_no_agt, COUNT(*) as frekuensi, MAX(data_kunjungan.TGL_KUN) as tanggal_terakhir')
            ->groupBy('data_kunjungan.NO_AGT')
            ->orderBy('data_kunjungan.NO_AGT')
            ->get();

        if ($aggregates->isEmpty()) {
            return collect();
        }

        $noList = $aggregates
            ->map(fn ($row) => trim((string) ($row->rep_no_agt ?? $row->REP_NO_AGT ?? '')))
            ->filter()
            ->unique()
            ->values();
        $namaByNo = Anggota::query()
            ->whereIn('NO_AGT', $noList->all())
            ->get()
            ->mapWithKeys(function (Anggota $row): array {
                $no = trim((string) $row->getKey());
                if ($no === '') {
                    return [];
                }

                return [$no => trim((string) ($row->NAMA ?? ''))];
            });

        return $aggregates->map(function ($row) use ($namaByNo) {
            $no = trim((string) ($row->rep_no_agt ?? $row->REP_NO_AGT ?? ''));
            $tgl = $this->rawAggregateColumn($row, 'tanggal_terakhir');

            return [
                'no_agt' => $no,
                'nama_anggota' => (string) ($namaByNo[$no] ?? ''),
                'frekuensi' => (int) ($this->rawAggregateColumn($row, 'frekuensi') ?? 0),
                'tanggal_terakhir' => $tgl !== null && trim((string) $tgl) !== ''
                    ? trim((string) $tgl)
                    : null,
            ];
        })->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, DataKunjungan>
     */
    public function listForExport(array $filters, int $limit, ?User $user = null): Collection
    {
        // Apply ownership filtering for user role
        $filters = MemberScope::mergeOwnershipFilterForCrud($user, $filters);

        $query = DataKunjungan::query();
        $this->applyFilters($query, $filters);

        return $query->orderBy('NO_URT')->limit($limit)->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $photo = null, ?User $user = null): DataKunjungan
    {
        // Auto-inject NO_AGT for user role
        $data = MemberScope::injectNoAgtForUser($user, $data);
        if (MemberScope::isRestrictedMemberUser($user)) {
            $data['created_by'] = (int) $user->id;
        }

        if (MemberScope::isRestrictedMemberUser($user)) {
            $memberKelompokId = MemberScope::memberKelompokId($user);

            if ($memberKelompokId === null) {
                abort(422, 'Akun belum ditautkan ke kelompok.');
            }

            $noAgtInput = isset($data['NO_AGT']) ? trim((string) $data['NO_AGT']) : '';
            if ($noAgtInput === '') {
                abort(422, 'Nomor anggota wajib diisi.');
            }

            $rowIdKs = Anggota::query()->where('NO_AGT', $noAgtInput)->value('ID_KS');
            if ($rowIdKs === null || $rowIdKs === '') {
                abort(422, 'Nomor anggota tidak ditemukan di data anggota.');
            }

            if (trim((string) $rowIdKs) !== $memberKelompokId) {
                abort(403, 'Nomor anggota harus dari kelompok yang sama dengan akun Anda.');
            }

            $data['NO_AGT'] = $noAgtInput;
            $data['ID_KEL_SAH'] = $memberKelompokId;
            $data['ID_LO'] = null;
        }

        // validated() bisa menyertakan key 'photo' (UploadedFile); jangan mass-assign / jangan masuk activity log JSON.
        unset($data['photo']);

        if ($photo) {
            $path = $photo->store('data_kunjungan', 'public');
            $data['FOTO_PATH'] = $path;
        }

        if (isset($data['latitude'])) {
            $data['LATITUDE'] = $data['latitude'];
            unset($data['latitude']);
        }

        if (isset($data['longitude'])) {
            $data['LONGITUDE'] = $data['longitude'];
            unset($data['longitude']);
        }

        return $this->performWithLog('create', function () use ($data) {
            return DataKunjungan::create($data);
        }, [
            'resource_type' => 'data_kunjungan',
            'resource_id' => null,
            'description' => 'Menambahkan data kunjungan: '.ActivityLogService::kelompokSahabatLabel(
                $data['ID_KEL_SAH'] ?? null
            ),
            'new_data' => $data,
        ]);
    }

    public function find(int $id, ?User $user = null): DataKunjungan
    {
        $record = DataKunjungan::findOrFail($id);

        // Validate ownership for user role (check NO_AGT field atau created_by)
        MemberScope::validateOwnershipForCrud($user, $record->NO_AGT, $record->created_by);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, ?UploadedFile $photo = null, ?User $user = null): DataKunjungan
    {
        $old = $this->find($id, $user); // This already validates ownership

        // Ensure NO_AGT cannot be changed by user role
        if (MemberScope::isRestrictedMemberUser($user)) {
            unset($data['NO_AGT']); // Remove NO_AGT from update data for security
            unset($data['ID_LO']); // ID_LO diset null saat create (tidak diedit dari aplikasi)
            unset($data['ID_KEL_SAH']); // Kelompok mengikuti data user
        }

        unset($data['photo']);

        if ($photo) {
            $path = $photo->store('data_kunjungan', 'public');
            $data['FOTO_PATH'] = $path;
        }

        if (isset($data['latitude'])) {
            $data['LATITUDE'] = $data['latitude'];
            unset($data['latitude']);
        }

        if (isset($data['longitude'])) {
            $data['LONGITUDE'] = $data['longitude'];
            unset($data['longitude']);
        }

        return $this->performWithLog('update', function () use ($old, $data) {
            $old->update($data);

            return $this->reloadModelAfterUpdate($old);
        }, [
            'resource_type' => 'data_kunjungan',
            'resource_id' => (string) $id,
            'description' => 'Mengupdate data kunjungan: '.ActivityLogService::kelompokSahabatLabel(
                $old->ID_KEL_SAH ?? null
            ),
            'old_data' => $old->toArray(),
            'new_data' => $data,
        ]);
    }

    public function delete(int $id, ?User $user = null): void
    {
        $record = $this->find($id, $user); // This already validates ownership

        $this->performWithLog('delete', function () use ($record) {
            $record->delete();
        }, [
            'resource_type' => 'data_kunjungan',
            'resource_id' => (string) $record->NO_URT,
            'description' => 'Menghapus data kunjungan: '.ActivityLogService::kelompokSahabatLabel(
                $record->ID_KEL_SAH ?? null
            ),
            'old_data' => $record->toArray(),
        ]);
    }
}
