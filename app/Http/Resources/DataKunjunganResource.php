<?php

namespace App\Http\Resources;

use App\Models\Anggota;
use App\Models\KelSah;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DataKunjunganResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fotoPath = $this->FOTO_PATH;
        $fotoUrl = null;
        $fotoApiUrl = null;

        if ($fotoPath) {
            $relativeStorageUrl = Storage::disk('public')->url($fotoPath); // /storage/...
            $appUrl = (string) config('app.url', '');
            $appPath = (string) (parse_url($appUrl, PHP_URL_PATH) ?? '');
            $normalizedAppPath = trim($appPath, '/');
            $baseUrl = rtrim(
                $request->getSchemeAndHttpHost()
                .($normalizedAppPath !== '' ? '/'.$normalizedAppPath : ''),
                '/'
            );
            $fotoUrl = str_starts_with($relativeStorageUrl, 'http')
                ? $relativeStorageUrl
                : $baseUrl.'/'.ltrim($relativeStorageUrl, '/');
            if ($this->NO_URT !== null) {
                $fotoApiUrl = route('data-kunjungan.photo', ['id' => $this->NO_URT]);
            }
        }

        return [
            'NO_URT' => $this->NO_URT,
            'NO_AGT' => $this->NO_AGT,
            'ID_LO' => $this->ID_LO,
            'ID_KEL_SAH' => $this->ID_KEL_SAH,
            'nama_kelompok' => $this->resolvedNamaKelompok(),
            'nama_anggota' => $this->resolvedNamaAnggota(),
            'TGL_KUN' => $this->TGL_KUN,
            'KEGIATAN' => $this->KEGIATAN,
            'ID_PIC' => $this->ID_PIC,
            'JLH_PESERTA' => $this->JLH_PESERTA,
            'foto_path' => $fotoPath,
            'foto_url' => $fotoUrl,
            'foto_api_url' => $fotoApiUrl,
            'latitude' => $this->attributeFromModelCaseInsensitive('LATITUDE'),
            'longitude' => $this->attributeFromModelCaseInsensitive('LONGITUDE'),
        ];
    }

    /**
     * Baca atribut model tanpa bergantung pada casing kunci (Firebird / join).
     */
    private function attributeFromModelCaseInsensitive(string $logicalName): mixed
    {
        foreach ($this->resource->getAttributes() as $key => $value) {
            if (strcasecmp((string) $key, $logicalName) === 0) {
                return $value;
            }
        }

        return null;
    }

    private function resolvedNamaKelompok(): ?string
    {
        $fromJoin = $this->firstNonEmptyStringAttribute([
            'JOIN_NAMA_KELOMPOK',
            'join_nama_kelompok',
        ]);
        if ($fromJoin !== null) {
            return $fromJoin;
        }

        $idKel = $this->ID_KEL_SAH;
        if ($idKel === null || trim((string) $idKel) === '') {
            return null;
        }

        $nama = KelSah::query()->where('ID_KEL', trim((string) $idKel))->value('NAMA_KEL');

        return $nama !== null && trim((string) $nama) !== '' ? trim((string) $nama) : null;
    }

    private function resolvedNamaAnggota(): ?string
    {
        $fromJoin = $this->firstNonEmptyStringAttribute([
            'JOIN_NAMA_ANGGOTA',
            'join_nama_anggota',
        ]);
        if ($fromJoin !== null) {
            return $fromJoin;
        }

        $noAgt = $this->NO_AGT;
        if ($noAgt === null || trim((string) $noAgt) === '') {
            return null;
        }

        $nama = Anggota::query()->where('NO_AGT', trim((string) $noAgt))->value('NAMA');

        return $nama !== null && trim((string) $nama) !== '' ? trim((string) $nama) : null;
    }

    /**
     * @param  list<string>  $keys
     */
    private function firstNonEmptyStringAttribute(array $keys): ?string
    {
        foreach ($keys as $key) {
            $v = $this->resource->getAttribute($key);
            if ($v !== null && trim((string) $v) !== '') {
                return trim((string) $v);
            }
        }

        return null;
    }
}
