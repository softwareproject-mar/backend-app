<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDataAoRequest extends FormRequest
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
        $idAo = $this->route('data_ao');

        return [
            'NO_AGT' => [
                'nullable',
                'string',
                'max:15',
                Rule::unique('data_ao', 'NO_AGT')->ignore($idAo, 'ID_AO'),
            ],
            'NAMA' => ['nullable', 'string', 'max:255'],
            'STAT' => ['nullable', 'string', 'max:50'],
            'TGL_STAT' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'NO_AGT.unique' => 'Nomor anggota ini sudah dipakai sebagai AO.',
        ];
    }
}
