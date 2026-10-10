<?php

namespace App\Http\Resources;

use App\Models\Food;
use App\Support\AssetUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Field names match lib/models/order_models.dart (Order.fromJson).
 * Relations are optional: the list loads items + review, the detail view
 * also loads status logs and the rider.
 *
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $order = $this->resource;
        $live  = $order->status === 'picked_up';

        return [
            'id'                        => $order->id,
            'order_number'              => $order->order_number,
            'order_type'                => $order->order_type,
            'restaurant_id'             => $order->restaurant_id,
            'status'                    => $order->status,
            'payment_method'            => $order->payment_method,
            'payment_status'            => $order->payment_status,
            'subtotal'                  => (float) $order->subtotal,
            'delivery_fee'              => (float) $order->delivery_fee,
            'discount_amount'           => (float) $order->discount_amount,
            'total_amount'              => (float) $order->total_amount,
            'special_instructions'      => $order->special_instructions,
            'cancel_reason'             => $order->cancel_reason,
            'delivery_address_snapshot' => $order->delivery_address_snapshot,
            'estimated_delivery_at'     => $order->estimated_delivery_at?->toIso8601String(),
            'created_at'                => $order->created_at?->toIso8601String(),
            'updated_at'                => $order->updated_at?->toIso8601String(),

            // Admin "product" orders have no restaurant; the app only needs a name to show.
            'restaurant' => $order->restaurant
                ? [
                    'id'        => $order->restaurant->id,
                    'name'      => $order->restaurant->name,
                    'latitude'  => $order->restaurant->latitude === null ? null : (float) $order->restaurant->latitude,
                    'longitude' => $order->restaurant->longitude === null ? null : (float) $order->restaurant->longitude,
                ]
                : ['id' => null, 'name' => 'KhaiKhai', 'latitude' => null, 'longitude' => null],

            'items' => $this->whenLoaded('items', fn () => $order->items->map(fn ($i) => [
                'menu_item_id' => $i->orderable_type === Food::class ? $i->orderable_id : null,
                'item_name'    => $i->item_name,
                'item_price'   => (float) $i->item_price,
                'quantity'     => (int) $i->quantity,
                'line_total'   => (float) $i->line_total,
                'emoji'        => $i->emoji,
                'image_url'    => AssetUrl::make($i->item_image),
            ])->values()),

            'review' => $this->whenLoaded('review', fn () => $order->review === null ? null : [
                'food_rating' => (int) $order->review->food_rating,
                'comment'     => $order->review->comment,
            ]),

            'status_logs' => $this->whenLoaded('statusLogs', fn () => $order->statusLogs->map(fn ($l) => [
                'to_status'  => $l->to_status,
                'created_at' => $l->created_at?->toIso8601String(),
            ])->values()),

            // The rider's live position is only shared while the food is on its way.
            'rider' => $this->whenLoaded('rider', function () use ($order, $live) {
                $rider = $order->rider;

                if ($rider === null) {
                    return null;
                }

                $p = $rider->riderProfile;

                return [
                    'id'            => $rider->id,
                    'name'          => $rider->name,
                    'rider_profile' => [
                        'zone'                => $p?->zone,
                        'vehicle_type'        => $p?->vehicle_type,
                        'vehicle_plate'       => $p?->vehicle_plate,
                        'avg_rating'          => $p?->avg_rating === null ? null : (float) $p->avg_rating,
                        'current_lat'         => $live && $p?->current_lat !== null ? (float) $p->current_lat : null,
                        'current_lng'         => $live && $p?->current_lng !== null ? (float) $p->current_lng : null,
                        'location_updated_at' => $live ? $p?->location_updated_at?->toIso8601String() : null,
                    ],
                ];
            }),
        ];
    }
}
