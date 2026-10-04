<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\VendorSetting;
use App\Services\OpeningHoursService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Runs every minute (routes/console.php). For restaurants whose vendor
 * switched on "open/close automatically", it applies the weekly schedule
 * to restaurants.is_open.
 *
 * It only acts when the scheduled state CHANGES (vendor_settings.
 * scheduled_open_state), so a manual open/close toggle by the vendor stays
 * in place until the next scheduled opening or closing time. A missed run
 * is caught up on the next one.
 */
class SyncRestaurantOpeningHours extends Command
{
    protected $signature = 'restaurants:sync-opening-hours';

    protected $description = 'Open/close restaurants according to their weekly opening hours';

    public function handle(OpeningHoursService $hoursService): int
    {
        $now     = now();
        $changed = 0;

        VendorSetting::query()
            ->where('use_opening_hours', true)
            ->whereHas('restaurant', fn ($q) => $q->where('is_approved', true)->where('is_active', true))
            ->with('restaurant.hours')
            ->chunkById(100, function ($settings) use ($hoursService, $now, &$changed) {
                foreach ($settings as $setting) {
                    $restaurant = $setting->restaurant;

                    // No hours saved yet: never close a restaurant by accident.
                    if ($restaurant->hours->isEmpty()) {
                        continue;
                    }

                    $shouldBeOpen = $hoursService->isOpenAt($restaurant->hours, $now);

                    if ($setting->scheduled_open_state === $shouldBeOpen) {
                        continue;
                    }

                    DB::transaction(function () use ($restaurant, $setting, $shouldBeOpen) {
                        $restaurant->update(['is_open' => $shouldBeOpen]);
                        $setting->forceFill(['scheduled_open_state' => $shouldBeOpen])->save();
                    });

                    $changed++;
                }
            });

        if ($changed > 0) {
            $this->info("Opening hours applied to {$changed} restaurant(s).");
            Log::info('Opening hours sync', ['changed' => $changed]);
        }

        return self::SUCCESS;
    }
}
