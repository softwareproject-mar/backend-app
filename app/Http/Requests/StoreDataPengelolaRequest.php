<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'ID_PENG' => ['required', 'string', 'max:12'],
            'NO_AGT' => ['nullable', 'string', 'max:15'],
            'NO_SK' => ['nullable', 'integer'],
        ];
    }
}
