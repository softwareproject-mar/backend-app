<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDataPengelolaRequest extends FormRequest
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
        $idPeng = $this->route('data_pengelola');

        return [
            'NO_AGT' => [
                'nullable',
                'string',
                'max:15',
                Rule::unique('data_pengelola', 'NO_AGT')->ignore($idPeng, 'ID_PENG'),
            ],
            'NO_SK' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'NO_AGT.unique' => 'Nomor anggota ini sudah dipakai sebagai pengelola.',
        ];
    }
}
