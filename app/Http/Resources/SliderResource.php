<?php

namespace App\Http\Resources;

use App\Support\AssetUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Slider */
class SliderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'image' => AssetUrl::make($this->image) ?? '',
            'url'   => $this->url,
        ];
    }
}
