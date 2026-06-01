<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDataLoRequest extends FormRequest
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
            'ID_LO' => ['nullable', 'string', 'size:12', new \App\Rules\ValidIdFormat('data-lo'), 'unique:data_lo,ID_LO'],
            'NO_AGT' => ['nullable', 'string', 'max:15', Rule::unique('data_lo', 'NO_AGT')],
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
