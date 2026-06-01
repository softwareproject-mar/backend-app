<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            // Wajib untuk role `user` (aplikasi). Admin/super_admin: opsional (web tanpa device_id; aplikasi dengan device_id untuk ikat perangkat).
            'device_id' => ['nullable', 'string', 'max:191'],
        ];
    }
}
