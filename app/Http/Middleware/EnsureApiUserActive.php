<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API version of EnsureUserActive. A deactivated customer cannot use an
 * old token: the token is deleted and the app gets 401, so the Flutter
 * ApiClient.onUnauthorized hook sends the user back to the login screen.
 */
class EnsureApiUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Your account has been deactivated. Please contact support.',
            ], 401);
        }

        return $next($request);
    }
}
