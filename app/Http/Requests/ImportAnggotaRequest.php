<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportAnggotaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add authorization logic if needed
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'no_agt' => 'required|string|max:15',
            'confirm_import' => 'required|boolean|accepted', // User must confirm import
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'no_agt.required' => 'NO_AGT is required.',
            'no_agt.string' => 'NO_AGT must be a string.',
            'no_agt.max' => 'NO_AGT cannot exceed 15 characters.',
            'confirm_import.required' => 'You must confirm the import.',
            'confirm_import.boolean' => 'Confirm import must be true or false.',
            'confirm_import.accepted' => 'You must accept to proceed with import.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'no_agt' => 'NO_AGT',
            'confirm_import' => 'Import Confirmation',
        ];
    }
}