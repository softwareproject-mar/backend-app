<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
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
            'email' => [
                'required',
                'email',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $email = is_string($value) ? trim($value) : '';
                    if ($email === '') {
                        return;
                    }
                    $user = User::query()->where('email', $email)->first();
                    if ($user === null) {
                        return;
                    }
                    if ($user->role === 'user' && $user->registration_status === User::REGISTRATION_REJECTED) {
                        return;
                    }
                    $fail('Email ini sudah dipakai. Gunakan email lain atau tunggu persetujuan jika masih mendaftar.');
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
        ];
    }
}
