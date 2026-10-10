<?php

namespace App\Http\Resources;

use App\Support\AssetUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'phone'       => $this->phone,
            'email'       => $this->email,
            'role'        => $this->role,
            'points'      => (int) $this->points,
            'is_verified' => (bool) $this->is_verified,
            'avatar'      => AssetUrl::make($this->avatar),
            'addresses'   => $this->whenLoaded(
                'addresses',
                fn () => AddressResource::collection($this->addresses)->resolve($request)
            ),
        ];
    }
}
