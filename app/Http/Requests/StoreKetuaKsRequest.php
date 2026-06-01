<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'NO_AGT' => ['required', 'string', 'max:15', Rule::unique('ketua_ks', 'NO_AGT')],
            'NAMA' => ['nullable', 'string', 'max:50'],
            'STAT' => ['nullable', 'string', 'max:50'],
            'TGL_STAT' => ['nullable', 'string', 'max:50'],
            'NO_SK' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'NO_AGT.unique' => 'Nomor anggota ini sudah dipakai sebagai ketua kelompok.',
        ];
    }
}
