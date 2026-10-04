<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Small helper for rate limiting a Livewire action.
 *
 * Every call counts as one attempt. When the limit is already used up it
 * returns the seconds left to wait (> 0) and the caller must stop. Otherwise it
 * records the attempt and returns 0.
 */
trait ThrottlesActions
{
    protected function throttleHit(string $name, string $key, int $maxAttempts, int $decaySeconds): int
    {
        $bucket = $name . ':' . $key;

        if (RateLimiter::tooManyAttempts($bucket, $maxAttempts)) {
            return max(1, RateLimiter::availableIn($bucket));
        }

        RateLimiter::hit($bucket, $decaySeconds);

        return 0;
    }

    protected function throttleClear(string $name, string $key): void
    {
        RateLimiter::clear($name . ':' . $key);
    }

    /** Human friendly Bangla wait time, e.g. "২ মিনিট". */
    protected function throttleWaitText(int $seconds): string
    {
        $value = $seconds < 60 ? $seconds : (int) ceil($seconds / 60);
        $unit  = $seconds < 60 ? 'সেকেন্ড' : 'মিনিট';

        $digits = ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯'];

        // Array form of strtr: the three-argument form works on bytes and would
        // break multi-byte Bengali digits.
        return strtr((string) $value, $digits) . ' ' . $unit;
    }

    protected function throttleMessage(int $seconds): string
    {
        return 'অনেকবার চেষ্টা করা হয়েছে। ' . $this->throttleWaitText($seconds) . ' পর আবার চেষ্টা করুন।';
    }
}
