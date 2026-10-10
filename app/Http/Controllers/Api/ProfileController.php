<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\CustomerAddressService;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /** PUT /me - phone, email and the default address (profile screen). */
    public function update(UpdateProfileRequest $request, CustomerAddressService $addresses): UserResource
    {
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $data, $addresses) {
            $user->update([
                'phone' => $data['phone'],
                'email' => ($data['email'] ?? null) ?: null,
            ]);

            if (! empty($data['full_address'])) {
                $addresses->saveDefaultFromProfile($user, $data);
            }

            activity()->causedBy($user)->performedOn($user)->log('Customer updated profile (mobile app)');
        });

        $user->setRelation('addresses', $addresses->listFor($user));

        return new UserResource($user);
    }
}
