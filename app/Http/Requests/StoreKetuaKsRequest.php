<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKetuaKsRequest extends FormRequest
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
            'ID_KET' => ['nullable', 'string', 'size:12', new \App\Rules\ValidIdFormat('ketua-ks'), 'unique:ketua_ks,ID_KET'],
            'NO_AGT' => ['required', 'string', 'max:15'],
            'NAMA' => ['nullable', 'string', 'max:50'],
            'STAT' => ['nullable', 'string', 'max:50'],
            'TGL_STAT' => ['nullable', 'string', 'max:50'],
            'NO_SK' => ['nullable', 'integer'],
        ];
    }
}
