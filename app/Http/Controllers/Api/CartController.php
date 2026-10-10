<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CartItemsRequest;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function __construct(private readonly CheckoutService $checkout)
    {
    }

    /** POST /cart/quote — prices the cart (fee, coupon, total). Writes nothing. */
    public function quote(CartItemsRequest $request): JsonResponse
    {
        try {
            $quote = $this->checkout->quote(
                $request->user(),
                $request->validated('items'),
                $request->validated('coupon'),
                (bool) ($request->validated('apply_coupon') ?? false),
            );
        } catch (CheckoutException $e) {
            return response()->json($e->toPayload(), 422);
        }

        return response()->json(['data' => $quote]);
    }
}
