<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\CustomerAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const TOKEN_NAME = 'flutter-customer-app';

    /** Newest tokens kept per customer (several phones is fine, an endless pile is not). */
    private const MAX_TOKENS = 5;

    public function __construct(private readonly CustomerAddressService $addresses)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'uuid'        => (string) Str::uuid(),
                'name'        => $data['name'],
                'phone'       => $data['phone'],
                'email'       => $data['email'] ?? null,
                'password'    => $data['password'],
                'role'        => 'customer',
                'is_verified' => false,
                'is_active'   => true,
                'points'      => 0,
            ]);

            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->log('Customer account registered (mobile app)');

            return $user;
        });

        return $this->sessionResponse($user, 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $field      = $request->filled('phone') ? 'phone' : 'email';
        $identifier = trim((string) $request->input($field));
        $throttle   = Str::lower($field.':'.$identifier).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttle, 5)) {
            $seconds = RateLimiter::availableIn($throttle);

            return response()->json([
                'message' => "Too many attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        $user = User::query()->where($field, $identifier)->first();

        // Unknown account: do the same amount of hashing work as a real
        // check, so response time cannot reveal which numbers are registered.
        if (! $user) {
            Hash::make((string) $request->input('password'));
        }

        // One message for "no such user" and "wrong password" so nobody can
        // find out which phone numbers are registered.
        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            RateLimiter::hit($throttle, 60);

            throw ValidationException::withMessages([
                $field => ['The phone number or password is incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact support.',
            ], 403);
        }

        // This app is the customer app. Vendors, riders and admins keep
        // using the website dashboards.
        if (! $user->isCustomer()) {
            return response()->json([
                'message' => 'This app is for customers only. Please use the website for your account.',
            ], 403);
        }

        RateLimiter::clear($throttle);

        // Auth::attempt() fires the Login event that stores these values for
        // the website. Token login does not, so it is done here.
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_seen_at'  => now(),
        ])->save();

        return $this->sessionResponse($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): UserResource
    {
        $user = $request->user();
        $user->setRelation('addresses', $this->addresses->listFor($user));

        return new UserResource($user);
    }

    /** {token, user} - the exact shape AuthState._acceptSession() reads. */
    private function sessionResponse(User $user, int $status = 200): JsonResponse
    {
        $user->setRelation('addresses', $this->addresses->listFor($user));

        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        // Drop the oldest app tokens beyond the newest MAX_TOKENS.
        $stale = $user->tokens()
            ->where('name', self::TOKEN_NAME)
            ->orderByDesc('id')
            ->skip(self::MAX_TOKENS)
            ->take(100)
            ->pluck('id');

        if ($stale->isNotEmpty()) {
            $user->tokens()->whereIn('id', $stale)->delete();
        }

        return response()->json([
            'token' => $token,
            'user'  => (new UserResource($user))->resolve(),
        ], $status);
    }
}
