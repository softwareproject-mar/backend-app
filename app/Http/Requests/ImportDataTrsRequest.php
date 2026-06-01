<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportDataTrsRequest extends FormRequest
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
            'no_agt' => ['required', 'string', 'max:15'],
            'confirm_import' => ['required', 'boolean', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'no_agt.required' => 'Nomor anggota wajib diisi.',
            'confirm_import.accepted' => 'Anda harus mengonfirmasi untuk melanjutkan impor.',
        ];
    }
}
