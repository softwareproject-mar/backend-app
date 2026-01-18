<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListAnggotaRequest extends FormRequest
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
            'search' => 'nullable|string|min:3|max:100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'search.string' => 'Search must be a string.',
            'search.min' => 'Search must be at least 3 characters.',
            'search.max' => 'Search cannot exceed 100 characters.',
            'page.integer' => 'Page must be an integer.',
            'page.min' => 'Page must be at least 1.',
            'per_page.integer' => 'Per page must be an integer.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page cannot exceed 100.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'search' => 'Search query',
            'page' => 'Page number',
            'per_page' => 'Items per page',
        ];
    }

    /**
     * Get validated data with defaults
     */
    public function getValidatedData(): array
    {
        $validated = $this->validated();

        return [
            'search' => $validated['search'] ?? null,
            'page' => $validated['page'] ?? null,
            'per_page' => $validated['per_page'] ?? 100,
        ];
    }
}