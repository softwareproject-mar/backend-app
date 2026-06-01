<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveMemberRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $kel = $this->input('id_kel');
        $this->merge([
            'id_kel' => is_string($kel) && trim($kel) !== '' ? trim($kel) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id_kel' => [
                'nullable',
                'string',
                'max:12',
                Rule::when($this->filled('id_kel'), ['exists:kel_sah,ID_KEL']),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_kel.exists' => 'Kelompok sahabat tidak ditemukan.',
        ];
    }
}
