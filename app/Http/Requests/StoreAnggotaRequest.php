<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnggotaRequest extends FormRequest
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
            'NO_AGT' => ['required', 'string', 'max:15', Rule::unique('anggota', 'NO_AGT')],
            'NAMA' => ['nullable', 'string', 'max:255'],
            'ID_KS' => ['nullable', 'string', 'max:12'],
            'ID_KS_ASL' => ['nullable', 'string', 'max:12'],
            'TGL_MTS' => ['nullable', 'string', 'max:50'],
            'TGL_AKTIF' => ['nullable', 'string', 'max:50'],
            'TGL_JA' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'NO_AGT.unique' => 'Nomor anggota sudah ada di sistem. Gunakan nomor lain atau ubah data yang sudah ada.',
        ];
    }
}
