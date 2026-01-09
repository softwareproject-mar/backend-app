<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDataAoRequest extends FormRequest
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
            'ID_AO' => ['nullable', 'string', 'size:12', new \App\Rules\ValidIdFormat('data-ao'), 'unique:data_ao,ID_AO'],
            'NO_AGT' => ['nullable', 'string', 'max:15'],
            'NAMA' => ['nullable', 'string', 'max:255'],
            'STAT' => ['nullable', 'string', 'max:50'],
            'TGL_STAT' => ['nullable', 'string', 'max:50'],
        ];
    }
}
