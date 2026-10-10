<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Same rules as the website (HasAccountRegistrationValidation), except
     * the mobile app asks for a phone number instead of an email, so phone
     * is required here and email is optional.
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:3', 'max:100'],
            'phone'    => ['required', 'string', 'regex:/^01[3-9]\d{8}$/', 'unique:users,phone'],
            'email'    => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex'        => 'Please enter a valid Bangladeshi mobile number (01XXXXXXXXX).',
            'phone.unique'       => 'This mobile number is already registered.',
            'email.unique'       => 'This email is already in use.',
            'password.min'       => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The passwords do not match.',
        ];
    }
}
