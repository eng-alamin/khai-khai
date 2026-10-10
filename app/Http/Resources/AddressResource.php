<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CustomerAddress */
class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'label'        => $this->label,
            'full_address' => $this->full_address,
            'city'         => $this->city,
            'postal_code'  => $this->postal_code,
            'latitude'     => $this->latitude === null ? null : (float) $this->latitude,
            'longitude'    => $this->longitude === null ? null : (float) $this->longitude,
            'is_default'   => (bool) $this->is_default,
        ];
    }
}
