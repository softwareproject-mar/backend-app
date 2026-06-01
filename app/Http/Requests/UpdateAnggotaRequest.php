<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnggotaRequest extends FormRequest
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
            'NAMA' => ['nullable', 'string', 'max:255'],
            'ID_KS' => ['nullable', 'string', 'max:12'],
            'ID_KS_ASL' => ['nullable', 'string', 'max:12'],
            'TGL_MTS' => ['nullable', 'string', 'max:50'],
            'TGL_AKTIF' => ['nullable', 'string', 'max:50'],
            'TGL_JA' => ['nullable', 'string', 'max:50'],
        ];
    }
}
