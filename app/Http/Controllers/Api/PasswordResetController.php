<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * "Forgot password" for the mobile app, by email.
 *
 * The web flow emails a link; an app cannot open that, so the app gets a
 * 6-digit code instead. The code is kept (hashed) in the cache for
 * CODE_MINUTES, NOT in password_reset_tokens, so it can never be used on the
 * website's reset page. Each code allows MAX_WRONG wrong guesses, and a new
 * code can be requested only a few times per hour, so guessing is hopeless.
 *
 * The answer to "send code" is identical whether or not the email belongs to
 * an account (no way to find out who is registered).
 */
class PasswordResetController extends Controller
{
    private const CODE_MINUTES = 30;

    private const MAX_WRONG = 5;

    /** POST /auth/forgot-password {email} */
    public function send(Request $request): JsonResponse
    {
        $data  = $request->validate(['email' => ['required', 'email', 'max:150']]);
        $email = Str::lower(trim($data['email']));

        // Same limits whether or not the account exists. By IP and by address
        // (the latter stops someone mail-bombing a person).
        foreach ([
            ['pwd-forgot-ip:' . $request->ip(), 10, 900],
            ['pwd-forgot-email:' . sha1($email), 3, 3600],
        ] as [$key, $max, $decay]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return $this->tooMany(RateLimiter::availableIn($key));
            }
            RateLimiter::hit($key, $decay);
        }

        $user = $this->customerByEmail($email);

        if ($user) {
            $code = (string) random_int(100000, 999999);

            // A new code replaces the old one (and its wrong-guess count).
            Cache::put($this->cacheKey($user), [
                'hash'       => Hash::make($code),
                'wrong'      => 0,
                'expires_at' => now()->addMinutes(self::CODE_MINUTES)->timestamp,
            ], now()->addMinutes(self::CODE_MINUTES));

            $to   = $user->email;
            $name = $user->name;

            // Sent after the response, so a slow mail server cannot reveal
            // (through response time) that the account exists.
            dispatch(function () use ($to, $name, $code) {
                try {
                    Mail::to($to)->send(new PasswordResetCodeMail($name, $code, self::CODE_MINUTES));
                } catch (\Throwable $e) {
                    Log::error('Password reset mail failed: ' . $e->getMessage());
                }
            })->afterResponse();
        }

        return response()->json([
            'message' => 'If an account exists with that email, we have sent a 6-digit code. It is valid for '
                . self::CODE_MINUTES . ' minutes.',
        ]);
    }

    /** POST /auth/reset-password {email, code, password, password_confirmation} */
    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email', 'max:150'],
            'code'     => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'code.digits'        => 'The code must be 6 digits.',
            'password.min'       => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The passwords do not match.',
        ]);

        $email = Str::lower(trim($data['email']));
        $user  = $this->customerByEmail($email);
        $state = $user ? Cache::get($this->cacheKey($user)) : null;

        $valid = is_array($state)
            && ($state['expires_at'] ?? 0) > now()->timestamp
            && ($state['wrong'] ?? self::MAX_WRONG) < self::MAX_WRONG;

        // Always do one hash check, so timing is the same in every case.
        $matches = Hash::check($data['code'], $valid ? $state['hash'] : Hash::make('no-such-code'));

        if (! $valid || ! $matches) {
            if ($user && is_array($state) && $valid) {
                $state['wrong']++;
                $left = max(1, (int) $state['expires_at'] - now()->timestamp);

                if ($state['wrong'] >= self::MAX_WRONG) {
                    Cache::forget($this->cacheKey($user)); // burned: ask for a new code
                } else {
                    Cache::put($this->cacheKey($user), $state, $left);
                }
            }

            if ($user && is_array($state) && ($state['wrong'] ?? 0) >= self::MAX_WRONG) {
                return response()->json([
                    'message' => 'Too many wrong attempts. Please request a new code.',
                    'code'    => 'too_many_attempts',
                ], 422);
            }

            return response()->json([
                'message' => 'The code is wrong or has expired.',
                'code'    => 'invalid_code',
            ], 422);
        }

        // The 'hashed' cast on User hashes this.
        $user->forceFill(['password' => $data['password']])->save();

        // Whoever knew the old password is signed out everywhere.
        $user->tokens()->delete();
        Cache::forget($this->cacheKey($user));

        activity()->causedBy($user)->performedOn($user)->log('Customer reset password by email code (mobile app)');

        return response()->json(['message' => 'Your password has been changed. Please log in.']);
    }

    private function customerByEmail(string $email): ?User
    {
        return User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('role', 'customer')
            ->where('is_active', true)
            ->first();
    }

    private function cacheKey(User $user): string
    {
        return 'pwd-reset-code:' . $user->id;
    }

    private function tooMany(int $seconds): JsonResponse
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return response()->json([
            'message' => 'Too many attempts. Please try again in ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '') . '.',
            'code'    => 'too_many_attempts',
        ], 429);
    }
}
