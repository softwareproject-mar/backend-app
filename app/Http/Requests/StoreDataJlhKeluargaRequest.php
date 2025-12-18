<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDataJlhKeluargaRequest extends FormRequest
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
            'JLH_AGT_KEL' => ['nullable', 'integer'],
            'TGL' => ['nullable', 'string', 'max:50'],
        ];
    }
}
