<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    /**
     * PUT /me/password {current_password, password, password_confirmation}
     *
     * A wrong current password answers 422 (never 401), because the app signs
     * the customer out on every 401. All OTHER devices are signed out: if the
     * password was changed because someone else knew it, their token dies too.
     */
    public function update(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check((string) $request->validated('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        // The 'hashed' cast on User hashes this.
        $user->forceFill(['password' => $request->validated('password')])->save();

        $current = $user->currentAccessToken();
        $user->tokens()
            ->when($current?->id, fn ($q, $id) => $q->where('id', '!=', $id))
            ->delete();

        activity()->causedBy($user)->performedOn($user)->log('Customer changed password (mobile app)');

        return response()->json(['message' => 'Password changed.']);
    }
}
