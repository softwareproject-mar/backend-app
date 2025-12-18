<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKetuaKsRequest extends FormRequest
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
            'NO_AGT' => ['sometimes', 'string', 'max:15'],
            'NAMA' => ['nullable', 'string', 'max:50'],
            'STAT' => ['nullable', 'string', 'max:50'],
            'TGL_STAT' => ['nullable', 'string', 'max:50'],
            'NO_SK' => ['nullable', 'integer'],
        ];
    }
}
