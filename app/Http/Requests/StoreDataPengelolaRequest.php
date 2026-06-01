<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDataPengelolaRequest extends FormRequest
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
            'ID_PENG' => ['nullable', 'string', 'size:12', new \App\Rules\ValidIdFormat('data-pengelola'), 'unique:data_pengelola,ID_PENG'],
            'NO_AGT' => ['nullable', 'string', 'max:15', Rule::unique('data_pengelola', 'NO_AGT')],
            'NO_SK' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'NO_AGT.unique' => 'Nomor anggota ini sudah dipakai sebagai pengelola.',
        ];
    }
}
