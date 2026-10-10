<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Create and update share the same rules (as AddressComponent does). */
class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'        => ['required', Rule::in(['home', 'office', 'other'])],
            'full_address' => ['required', 'string', 'max:500'],
            'city'         => ['required', 'string', 'max:60', Rule::in(config('delivery.cities', []))],
            'postal_code'  => ['nullable', 'string', 'max:10'],
            'latitude'     => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude'    => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_address.required' => 'The full address field is required.',
            'city.in'              => 'We do not deliver to the selected city yet.',
        ];
    }
}
