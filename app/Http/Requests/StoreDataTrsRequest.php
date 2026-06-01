<?php

namespace App\Http\Requests;

use App\Rules\NoAgtBelongsToMemberKelompok;
use Illuminate\Foundation\Http\FormRequest;

class StoreDataTrsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->replace(collect($this->all())->except('created_by')->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'NO_AGT' => ['required', 'string', 'max:15', new NoAgtBelongsToMemberKelompok],
            'STR_SP' => ['nullable', 'string', 'max:50'],
            'STR_SW' => ['nullable', 'string', 'max:50'],
            'STR_SKA' => ['nullable', 'string', 'max:50'],
            'STR_SRI' => ['nullable', 'string', 'max:50'],
            'STR_SDK' => ['nullable', 'string', 'max:50'],
            'STR_PJM' => ['nullable', 'string', 'max:50'],
            'STR_BNG' => ['nullable', 'string', 'max:50'],
            'PJM_BARU' => ['nullable', 'string', 'max:50'],
            'STR_SHR' => ['nullable', 'string', 'max:50'],
            'STR_SBJ' => ['nullable', 'string', 'max:50'],
            'STR_SJP' => ['nullable', 'string', 'max:50'],
            'STR_SPD' => ['nullable', 'string', 'max:50'],
            'STR_SRY' => ['nullable', 'string', 'max:50'],
            'STR_SMD' => ['nullable', 'string', 'max:50'],
            'TGL_LAP' => ['nullable', 'string', 'max:50'],
        ];
    }
}
