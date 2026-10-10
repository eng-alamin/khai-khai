<?php

namespace App\Http\Resources;

use App\Support\AssetUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * MySQL returns decimals as strings and booleans as 0/1. Dart's
 * "(x).toDouble()" crashes on a String, and a Dart "bool" crashes on 1,
 * so every value is cast to its real type here.
 *
 * @mixin \App\Models\Restaurant
 */
class RestaurantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'category'      => $this->category,
            'emoji'         => $this->emoji,
            'logo_url'      => AssetUrl::make($this->logo_url),
            'banner_url'    => AssetUrl::make($this->banner_url),
            'city'          => $this->city,
            'avg_rating'    => (float) ($this->avg_rating ?? 0),
            'total_reviews' => (int) ($this->total_reviews ?? 0),
            'tag'           => $this->tag,
            'is_open'       => (bool) $this->is_open,
        ];
    }
}
