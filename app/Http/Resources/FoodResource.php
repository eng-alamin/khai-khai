<?php

namespace App\Http\Resources;

use App\Support\AssetUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Food */
class FoodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'restaurant_id' => $this->restaurant_id,
            'name'          => $this->name,
            'price'         => (float) $this->price,
            'compare_price' => $this->compare_price === null ? null : (float) $this->compare_price,
            'description'   => $this->description,
            'emoji'         => $this->emoji,
            'image_url'     => AssetUrl::make($this->image_url),
            'category'      => $this->whenLoaded('category', fn () => $this->category === null ? null : [
                'id'    => $this->category->id,
                'name'  => $this->category->name,
                'emoji' => $this->category->emoji,
            ]),
            'restaurant'    => $this->whenLoaded('restaurant', fn () => $this->restaurant === null ? null : [
                'id'   => $this->restaurant->id,
                'name' => $this->restaurant->name,
                'slug' => $this->restaurant->slug,
            ]),
        ];
    }
}
