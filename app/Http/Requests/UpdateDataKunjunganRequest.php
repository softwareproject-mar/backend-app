<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDataKunjunganRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ID_LO' => ['nullable', 'string', 'max:12'],
            'NO_AGT' => ['nullable', 'string', 'max:15'],
            'ID_KEL_SAH' => ['nullable', 'string', 'max:12'],
            'TGL_KUN' => ['nullable', 'string', 'max:50'],
            'KEGIATAN' => ['nullable', 'string', 'max:50'],
            'ID_PIC' => ['nullable', 'string', 'max:50'],
            'JLH_PESERTA' => ['nullable', 'integer'],
        ];
    }
}
