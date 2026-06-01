<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKelSahRequest extends FormRequest
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
        return [
            'ID_KEL' => ['nullable', 'string', 'size:12', new \App\Rules\ValidIdFormat('kel-sah'), 'unique:kel_sah,ID_KEL'],
            'NAMA_KEL' => ['nullable', 'string', 'max:255', Rule::unique('kel_sah', 'NAMA_KEL')],
            'ID_KETUA' => ['nullable', 'string', 'max:12', Rule::unique('kel_sah', 'ID_KETUA')],
            'ID_SEK' => ['nullable', 'string', 'max:12', Rule::unique('kel_sah', 'ID_SEK')],
            'ID_LO' => ['nullable', 'string', 'max:12', Rule::unique('kel_sah', 'ID_LO')],
            'ID_AO' => ['nullable', 'string', 'max:12', Rule::unique('kel_sah', 'ID_AO')],
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
