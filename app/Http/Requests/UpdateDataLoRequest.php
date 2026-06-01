<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDataLoRequest extends FormRequest
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
        $idLo = $this->route('data_lo');

        return [
            'NO_AGT' => [
                'nullable',
                'string',
                'max:15',
                Rule::unique('data_lo', 'NO_AGT')->ignore($idLo, 'ID_LO'),
            ],
            'ID_TP' => ['nullable', 'string', 'max:12'],
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
            'NO_AGT.unique' => 'Nomor anggota ini sudah dipakai sebagai LO.',
        ];
    }
}
