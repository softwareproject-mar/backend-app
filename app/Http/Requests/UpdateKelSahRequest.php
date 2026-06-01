<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKelSahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $idKel = $this->route('kel_sah');

        return [
            'NAMA_KEL' => ['nullable', 'string', 'max:255', Rule::unique('kel_sah', 'NAMA_KEL')->ignore($idKel, 'ID_KEL')],
            'ID_KETUA' => ['nullable', 'string', 'max:12', Rule::unique('kel_sah', 'ID_KETUA')->ignore($idKel, 'ID_KEL')],
            'ID_SEK' => ['nullable', 'string', 'max:12', Rule::unique('kel_sah', 'ID_SEK')->ignore($idKel, 'ID_KEL')],
            'ID_LO' => ['nullable', 'string', 'max:12', Rule::unique('kel_sah', 'ID_LO')->ignore($idKel, 'ID_KEL')],
            'ID_AO' => ['nullable', 'string', 'max:12', Rule::unique('kel_sah', 'ID_AO')->ignore($idKel, 'ID_KEL')],
            'ALAMAT' => ['nullable', 'string'],
            'STAT' => ['nullable', 'string', 'max:50'],
            'TGL_STAT' => ['nullable', 'string', 'max:50'],
            'ID_PENGELOLA' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ID_KETUA.unique' => 'Ketua ini sudah dipakai di kelompok lain.',
            'ID_SEK.unique' => 'Sekretaris ini sudah dipakai di kelompok lain.',
            'ID_LO.unique' => 'LO ini sudah dipakai di kelompok lain.',
            'ID_AO.unique' => 'AO ini sudah dipakai di kelompok lain.',
            'NAMA_KEL.unique' => 'Nama kelompok sudah dipakai.',
        ];
    }
}
