<?php

namespace App\Rules;

use App\Models\Anggota;
use App\Support\MemberScope;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoAgtBelongsToMemberKelompok implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('obormas.strict_member_no_agt_same_kelompok')) {
            return;
        }

        $user = auth()->user();
        if (! MemberScope::isRestrictedMemberUser($user)) {
            return;
        }

        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $idKel = MemberScope::memberKelompokId($user);
        if ($idKel === null) {
            $fail('Akun belum terhubung ke kelompok (nomor anggota / data anggota).');

            return;
        }

        $rowIdKs = Anggota::query()->where('NO_AGT', trim($value))->value('ID_KS');
        if ($rowIdKs === null || $rowIdKs === '') {
            $fail('Nomor anggota tidak ditemukan di data anggota.');

            return;
        }

        if (trim((string) $rowIdKs) !== $idKel) {
            $fail('Nomor anggota harus dari kelompok yang sama dengan akun Anda.');
        }
    }
}
