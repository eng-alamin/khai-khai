<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The phone's push (FCM) token. One row per token; a token belongs to ONE account. */
class DeviceController extends Controller
{
    /** POST /devices {token, platform} */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'     => ['required', 'string', 'max:255'],
            'platform'  => ['required', 'in:android,ios'],
            'device_id' => ['nullable', 'string', 'max:100'],
        ]);

        // The token column is UNIQUE. If someone else logged in on this phone
        // before, the token simply moves to the current customer (the previous
        // account must stop receiving this phone's pushes).
        DeviceToken::query()->updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id'      => $request->user()->id,
                'platform'     => $data['platform'],
                'device_id'    => $data['device_id'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return response()->json(['message' => 'Registered.'], 201);
    }

    /** POST /devices/unregister {token} — called on logout. Only the owner's row is removed. */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:255']]);

        DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $data['token'])
            ->delete();

        return response()->json(['message' => 'Removed.']);
    }
}
