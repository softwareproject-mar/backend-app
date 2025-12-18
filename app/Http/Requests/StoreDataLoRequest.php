<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'ID_LO' => ['required', 'string', 'max:12'],
            'NO_AGT' => ['nullable', 'string', 'max:15'],
            'ID_TP' => ['nullable', 'string', 'max:12'],
            'NAMA' => ['nullable', 'string', 'max:255'],
            'STAT' => ['nullable', 'string', 'max:50'],
            'TGL_STAT' => ['nullable', 'string', 'max:50'],
        ];
    }
}
