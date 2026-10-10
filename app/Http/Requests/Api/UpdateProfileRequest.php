<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Same rules as the website's ProfileComponent. */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'phone'        => ['required', 'string', 'regex:/^01[3-9]\d{8}$/', Rule::unique('users', 'phone')->ignore($userId)],
            'email'        => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'full_address' => ['nullable', 'string', 'max:500'],
            // customer_addresses.city is NOT NULL, so an address needs a city.
            'city'         => ['nullable', 'required_with:full_address', 'string', 'max:60', Rule::in(config('delivery.cities', []))],
            'postal_code'  => ['nullable', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex'  => 'Please enter a valid Bangladeshi mobile number (01XXXXXXXXX).',
            'phone.unique' => 'This phone number is already registered with another account.',
            'email.unique' => 'This email is already registered with another account.',
        ];
    }
}
