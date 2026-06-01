<?php

namespace App\Http\Requests;

use App\Rules\NoAgtBelongsToMemberKelompok;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDataPenghasilanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Remove created_by from input (security)
        // NO_AGT can be updated by all roles now (it's just a regular field)
        $data = collect($this->all())->except('created_by')->all();

        $this->replace($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'NO_AGT' => ['sometimes', 'required', 'string', 'max:15', 'exists:anggota,NO_AGT', new NoAgtBelongsToMemberKelompok],
            'PENGHASILAN' => ['nullable', 'string', 'max:50'],
            'PENGELUARAN' => ['nullable', 'string', 'max:50'],
            'TGL_DATA' => ['nullable', 'string', 'max:50'],
        ];
    }
}
