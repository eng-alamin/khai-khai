<?php

namespace App\Http\Requests\Api;

use App\Services\CheckoutService;
use Illuminate\Foundation\Http\FormRequest;

/** Shared item rules for /cart/quote and /orders. Only ids + quantities are accepted. */
class CartItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'        => ['required', 'array', 'min:1', 'max:30'],
            'items.*.id'   => ['required', 'integer', 'min:1'],
            'items.*.qty'  => ['required', 'integer', 'min:1', 'max:' . CheckoutService::MAX_QTY_PER_ITEM],
            'coupon'       => ['nullable', 'string', 'max:40'],
            'apply_coupon' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'  => 'আগে কার্টে আইটেম যোগ করুন।',
            'items.*.qty.max' => 'একটি আইটেম সর্বোচ্চ ' . CheckoutService::MAX_QTY_PER_ITEM . 'টি অর্ডার করা যাবে।',
        ];
    }
}
