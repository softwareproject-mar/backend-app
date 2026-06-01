<?php

namespace App\Http\Requests;

use App\Rules\NoAgtBelongsToMemberKelompok;
use Illuminate\Foundation\Http\FormRequest;

class StoreDataJlhKeluargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Remove created_by from input (security)
        $data = collect($this->all())->except('created_by')->all();

        // NO_AGT is now a regular input field - no auto-injection
        // Users must input NO_AGT manually

        $this->replace($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'NO_AGT' => ['required', 'string', 'max:15', 'exists:anggota,NO_AGT', new NoAgtBelongsToMemberKelompok],
            'JLH_AGT_KEL' => ['nullable', 'integer'],
            'TGL' => ['nullable', 'string', 'max:50'],
        ];
    }
}
