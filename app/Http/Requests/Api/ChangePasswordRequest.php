<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** The new password follows the same rule as registration (min 8). */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Please enter your current password.',
            'password.min'              => 'The password must be at least 8 characters.',
            'password.confirmed'        => 'The passwords do not match.',
            'password.different'        => 'The new password must be different from the current one.',
        ];
    }
}
