<?php

namespace App\Http\Resources;

use App\Support\AssetUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'category_id'   => $this->category_id,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'price'         => (float) $this->price,
            'compare_price' => $this->compare_price === null ? null : (float) $this->compare_price,
            'emoji'         => $this->emoji,
            'image_url'     => AssetUrl::make($this->image_url),
            'section'       => $this->section,
        ];
    }
}
