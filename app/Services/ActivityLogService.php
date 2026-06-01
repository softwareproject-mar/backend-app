<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Anggota;
use App\Models\KelSah;

class ActivityLogService
{
    public const EMPTY_LABEL = 'Tanpa nama';

    /**
     * Log activity with explicit user (e.g. for registration where user just created).
     *
     * @param  array<string, mixed>  $context
     */
    public static function log(
        int $userId,
        string $userName,
        string $actionType,
        string $resourceType,
        ?string $resourceId,
        string $description,
        string $status = 'success',
        ?string $errorMessage = null,
        ?array $oldData = null,
        ?array $newData = null
    ): void {
        ActivityLog::create([
            'user_id' => $userId,
            'user_name' => $userName,
            'action_type' => $actionType,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'description' => $description,
            'status' => $status,
            'error_message' => $errorMessage,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public static function kelompokSahabatLabel(?string $idKel, ?string $namaKel = null): string
    {
        $name = trim((string) ($namaKel ?? ''));
        if ($name !== '') {
            return $name;
        }

        $id = trim((string) ($idKel ?? ''));
        if ($id === '') {
            return self::EMPTY_LABEL;
        }

        $record = KelSah::query()->where('ID_KEL', $id)->first(['NAMA_KEL']);
        $dbName = trim((string) ($record?->NAMA_KEL ?? ''));

        return $dbName !== '' ? $dbName : self::EMPTY_LABEL;
    }

    public static function anggotaNamaByNoAgt(?string $noAgt): ?string
    {
        $no = trim((string) ($noAgt ?? ''));
        if ($no === '') {
            return null;
        }

        $anggota = Anggota::query()->where('NO_AGT', $no)->first(['NAMA']);
        $name = trim((string) ($anggota?->NAMA ?? ''));

        return $name !== '' ? $name : null;
    }

    public static function anggotaLabelByNoAgt(?string $noAgt, ?string $fallbackName = null): string
    {
        $fallback = trim((string) ($fallbackName ?? ''));
        if ($fallback !== '') {
            return $fallback;
        }

        $name = self::anggotaNamaByNoAgt($noAgt);

        return $name ?? self::EMPTY_LABEL;
    }
}
