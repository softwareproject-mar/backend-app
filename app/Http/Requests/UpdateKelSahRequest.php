<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKelSahRequest extends FormRequest
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
            'NAMA_KEL' => ['nullable', 'string', 'max:255'],
            'ID_KETUA' => ['nullable', 'string', 'max:12'],
            'ID_SEK' => ['nullable', 'string', 'max:12'],
            'ID_LO' => ['nullable', 'string', 'max:12'],
            'ID_AO' => ['nullable', 'string', 'max:12'],
            'ALAMAT' => ['nullable', 'string'],
            'STAT' => ['nullable', 'string', 'max:50'],
            'TGL_STAT' => ['nullable', 'string', 'max:50'],
            'ID_PENGELOLA' => ['nullable', 'string', 'max:50'],
        ];
    }
}
