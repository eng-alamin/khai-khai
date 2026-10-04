<?php

namespace App\Services;

use App\Models\RestaurantHour;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Answers one question: "does this weekly schedule say the restaurant is
 * open at this moment?" Pure logic, no database access, easy to test.
 */
class OpeningHoursService
{
    /**
     * @param Collection<int, RestaurantHour> $hours all rows of one restaurant
     */
    public function isOpenAt(Collection $hours, CarbonInterface $at): bool
    {
        $byDay = $hours->keyBy('day_of_week');

        $today     = (int) $at->dayOfWeek;
        $yesterday = ($today + 6) % 7;
        $time      = $at->format('H:i:s');

        // 1) Today's own slot.
        $slot = $byDay->get($today);

        if ($this->isUsable($slot)) {
            $opens  = $this->normalize($slot->opens_at);
            $closes = $this->normalize($slot->closes_at);

            if ($opens < $closes) {
                if ($time >= $opens && $time < $closes) {
                    return true;
                }
            } elseif ($time >= $opens) {
                // Slot runs past midnight: it is open from $opens until the end of today.
                return true;
            }
        }

        // 2) Yesterday's slot that ran past midnight into today.
        $previous = $byDay->get($yesterday);

        if ($this->isUsable($previous)) {
            $opens  = $this->normalize($previous->opens_at);
            $closes = $this->normalize($previous->closes_at);

            if ($closes < $opens && $time < $closes) {
                return true;
            }
        }

        return false;
    }

    private function isUsable(?RestaurantHour $slot): bool
    {
        return $slot !== null
            && ! $slot->is_closed
            && $slot->opens_at !== null
            && $slot->closes_at !== null;
    }

    /** "09:30" or "09:30:00" -> "09:30:00" so plain string comparison is safe. */
    private function normalize(string $time): string
    {
        return strlen($time) === 5 ? $time . ':00' : $time;
    }
}
