<?php

namespace App\Http\Requests;

use App\Rules\NoAgtBelongsToMemberKelompok;
use App\Support\MemberScope;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDataKunjunganRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return MemberScope::isRestrictedMemberUser($this->user());
    }

    protected function prepareForValidation(): void
    {
        // Remove created_by from input (security)
        // NO_AGT can be updated by all roles now (it's just a regular field)
        $data = collect($this->all())->except('created_by')->all();

        $this->replace($data);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'NO_AGT' => ['sometimes', 'required', 'string', 'max:15', 'exists:anggota,NO_AGT', new NoAgtBelongsToMemberKelompok],
            'ID_LO' => ['nullable', 'string', 'max:12'],
            'ID_KEL_SAH' => ['nullable', 'string', 'max:12'],
            'TGL_KUN' => ['nullable', 'string', 'max:50'],
            'KEGIATAN' => ['nullable', 'string', 'max:50'],
            'ID_PIC' => ['nullable', 'string', 'max:50'],
            'JLH_PESERTA' => ['nullable', 'integer'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:4096'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
