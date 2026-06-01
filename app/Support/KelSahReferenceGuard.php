<?php

namespace App\Support;

use App\Models\KelSah;
use Illuminate\Validation\ValidationException;

/**
 * Cegah hapus master (ketua, sekre, LO, AO, pengelola) bila masih direferensikan baris kel_sah.
 */
final class KelSahReferenceGuard
{
    /**
     * @throws ValidationException
     */
    public static function assertIdNotUsedInKelompok(string $kelSahColumn, string $referencedId): void
    {
        if ($referencedId === '') {
            return;
        }

        if (KelSah::query()->where($kelSahColumn, $referencedId)->exists()) {
            throw ValidationException::withMessages([
                'id' => ['Data masih digunakan dalam Kelompok Sahabat. Silakan ubah atau pindahkan data tersebut dari Kelompok Sahabat terlebih dahulu sebelum melakukan penghapusan.'],
            ]);
        }
    }
}
