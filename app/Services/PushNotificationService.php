<?php

namespace App\Services;

use App\Models\CustomerProfile;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications through Firebase Cloud Messaging (HTTP v1) using a
 * service-account JSON, with no extra Composer package: it signs the Google
 * OAuth JWT itself (openssl) and caches the access token.
 *
 * Never throws: a push failure must not break an order status change.
 */
class PushNotificationService
{
    public const CATEGORY_ORDER = 'order';

    public const CATEGORY_PROMO = 'promo';

    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    /** A user rarely has more than a couple of phones; this caps a fan-out. */
    private const MAX_TOKENS_PER_USER = 10;

    /**
     * Queue a push for after the HTTP response is sent, so the FCM call never
     * slows down the request that caused it (a rider tapping "delivered").
     *
     * @param array<string,scalar|null> $data
     */
    public function queue(int $userId, string $title, string $body, array $data = [], string $category = self::CATEGORY_ORDER): void
    {
        if (! $this->configured()) {
            return;
        }

        dispatch(static function () use ($userId, $title, $body, $data, $category) {
            app(PushNotificationService::class)->sendToUser($userId, $title, $body, $data, $category);
        })->afterResponse();
    }

    /** @param array<string,scalar|null> $data */
    public function sendToUser(int $userId, string $title, string $body, array $data = [], string $category = self::CATEGORY_ORDER): void
    {
        try {
            if (! $this->configured() || ! $this->allowed($userId, $category)) {
                return;
            }

            $tokens = DeviceToken::query()
                ->where('user_id', $userId)
                ->orderByDesc('last_used_at')
                ->orderByDesc('id')
                ->limit(self::MAX_TOKENS_PER_USER)
                ->pluck('token');

            foreach ($tokens as $token) {
                $this->sendToToken((string) $token, $title, $body, $data);
            }
        } catch (\Throwable $e) {
            Log::warning('Push failed: ' . $e->getMessage());
        }
    }

    /** Customers can switch order / promo pushes off in the app. */
    private function allowed(int $userId, string $category): bool
    {
        $profile = CustomerProfile::query()->where('customer_id', $userId)->first();

        if ($profile === null) {
            return true; // not a customer, or no settings saved yet: default is on
        }

        return $category === self::CATEGORY_PROMO
            ? (bool) $profile->push_promotions
            : (bool) $profile->push_order_updates;
    }

    /** @param array<string,scalar|null> $data */
    private function sendToToken(string $token, string $title, string $body, array $data): void
    {
        $access = $this->accessToken();

        if ($access === null) {
            return;
        }

        // FCM data values must all be strings.
        $payload = [];
        foreach ($data as $key => $value) {
            if ($value !== null) {
                $payload[(string) $key] = (string) $value;
            }
        }

        $response = Http::withToken($access)
            ->timeout(8)
            ->post('https://fcm.googleapis.com/v1/projects/' . $this->projectId() . '/messages:send', [
                'message' => [
                    'token'        => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data'         => (object) $payload,
                    'android'      => ['priority' => 'HIGH'],
                    'apns'         => ['payload' => ['aps' => ['sound' => 'default']]],
                ],
            ]);

        if ($response->successful()) {
            return;
        }

        // The phone uninstalled the app / the token expired: forget it.
        if ($response->status() === 404 || str_contains((string) $response->body(), 'UNREGISTERED')) {
            DeviceToken::query()->where('token', $token)->delete();

            return;
        }

        // Our own access token was rejected: fetch a fresh one next time.
        if ($response->status() === 401) {
            Cache::forget('fcm:access_token');
        }

        Log::warning('FCM rejected a push', ['status' => $response->status(), 'body' => $response->body()]);
    }

    private function accessToken(): ?string
    {
        return Cache::remember('fcm:access_token', 3300, function () {
            $cred = $this->credentials();

            if ($cred === null) {
                return null;
            }

            $now    = time();
            $claims = [
                'iss'   => $cred['client_email'],
                'scope' => self::SCOPE,
                'aud'   => $cred['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
            ];

            $unsigned = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])) . '.' . $this->b64(json_encode($claims));

            if (! openssl_sign($unsigned, $signature, $cred['private_key'], OPENSSL_ALGO_SHA256)) {
                Log::error('FCM: could not sign the OAuth JWT (bad private_key?).');

                return null;
            }

            $response = Http::asForm()->timeout(8)->post($claims['aud'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $unsigned . '.' . $this->b64($signature),
            ]);

            if (! $response->successful()) {
                Log::error('FCM: OAuth token request failed', ['status' => $response->status()]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    public function configured(): bool
    {
        return $this->credentials() !== null;
    }

    private function projectId(): string
    {
        return (string) (config('fcm.project_id') ?: ($this->credentials()['project_id'] ?? ''));
    }

    /** @return array<string,mixed>|null */
    private function credentials(): ?array
    {
        static $cached = false;
        static $value  = null;

        if ($cached) {
            return $value;
        }

        $cached = true;
        $path   = config('fcm.credentials');

        if (! $path || ! is_readable($path)) {
            return $value = null;
        }

        $json = json_decode((string) file_get_contents($path), true);

        if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            Log::error('FCM: the credentials file is not a valid service-account JSON.');

            return $value = null;
        }

        return $value = $json;
    }

    private function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
