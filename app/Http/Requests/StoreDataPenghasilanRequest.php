<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDataPenghasilanRequest extends FormRequest
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
            'NO_AGT' => ['required', 'string', 'max:15'],
            'PENGHASILAN' => ['nullable', 'string', 'max:50'],
            'PENGELUARAN' => ['nullable', 'string', 'max:50'],
            'TGL_DATA' => ['nullable', 'string', 'max:50'],
        ];
    }
}
