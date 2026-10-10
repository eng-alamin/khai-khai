<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The Flutter app sends "phone". "email" is also accepted so a customer
     * who registered on the website with only an email can use the app too.
     */
    public function rules(): array
    {
        return [
            'phone'    => ['required_without:email', 'nullable', 'string', 'max:15'],
            'email'    => ['required_without:phone', 'nullable', 'email', 'max:150'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required_without' => 'The phone field is required.',
            'password.required'      => 'The password field is required.',
        ];
    }
}
