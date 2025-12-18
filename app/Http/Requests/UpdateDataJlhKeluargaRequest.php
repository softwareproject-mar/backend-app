<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDataJlhKeluargaRequest extends FormRequest
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
            'JLH_AGT_KEL' => ['nullable', 'integer'],
            'TGL' => ['nullable', 'string', 'max:50'],
        ];
    }
}
