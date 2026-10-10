<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class PlaceOrderRequest extends CartItemsRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'special_instructions' => ['nullable', 'string', 'max:300'],
            // orders.order_source only accepts web|android|ios|admin.
            'platform'             => ['nullable', Rule::in(['android', 'ios'])],
        ];
    }
}
