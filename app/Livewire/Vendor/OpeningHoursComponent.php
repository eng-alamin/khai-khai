<?php

namespace App\Livewire\Vendor;

use App\Models\RestaurantHour;
use App\Models\VendorSetting;
use App\Services\OpeningHoursService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Vendor sets the weekly opening hours of his own restaurant.
 * Route: opening-hours  (name: vendor.hours)
 */
class OpeningHoursComponent extends Component
{
    /** Bangladesh week order: Saturday first. */
    public const DISPLAY_ORDER = [6, 0, 1, 2, 3, 4, 5];

    public bool $useOpeningHours = false;

    /**
     * Keyed by day_of_week (0 = Sunday ... 6 = Saturday).
     * Each item: ['closed' => bool, 'opens' => 'H:i', 'closes' => 'H:i']
     *
     * @var array<int, array{closed: bool, opens: string, closes: string}>
     */
    public array $days = [];

    // ── Restaurant helper ─────────────────────────────────
    private function restaurant()
    {
        $restaurant = Auth::user()->restaurant;

        abort_if(
            ! $restaurant,
            403,
            'No restaurant is linked to your account yet. Please contact support.'
        );

        return $restaurant;
    }

    // ── Validation ────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'useOpeningHours'  => ['boolean'],
            'days'             => ['array', 'size:7'],
            'days.*.closed'    => ['boolean'],
            'days.*.opens'     => ['nullable', 'date_format:H:i'],
            'days.*.closes'    => ['nullable', 'date_format:H:i'],
        ];
    }

    // ── Mount ─────────────────────────────────────────────
    public function mount(): void
    {
        $restaurant = $this->restaurant();

        $setting = VendorSetting::firstOrCreate(['restaurant_id' => $restaurant->id]);
        $this->useOpeningHours = (bool) $setting->use_opening_hours;

        $saved = $restaurant->hours()->get()->keyBy('day_of_week');

        foreach (array_keys(RestaurantHour::DAY_NAMES) as $day) {
            $row = $saved->get($day);

            $this->days[$day] = [
                'closed' => $row ? (bool) $row->is_closed : false,
                'opens'  => $row && $row->opens_at  ? substr($row->opens_at, 0, 5)  : '10:00',
                'closes' => $row && $row->closes_at ? substr($row->closes_at, 0, 5) : '22:00',
            ];
        }
    }

    // ── Save ──────────────────────────────────────────────
    public function save(OpeningHoursService $hoursService): void
    {
        $this->validate();

        // Every open day needs both times, and they must differ
        // (closing earlier than opening means "after midnight").
        $hasError = false;

        foreach ($this->days as $day => $row) {
            if ($row['closed']) {
                continue;
            }

            if (($row['opens'] ?? '') === '' || ($row['closes'] ?? '') === '') {
                $this->addError("days.$day.closes", 'Please set both opening and closing time.');
                $hasError = true;
            } elseif ($row['opens'] === $row['closes']) {
                $this->addError("days.$day.closes", 'Opening and closing time cannot be the same.');
                $hasError = true;
            }
        }

        if ($hasError) {
            return;
        }

        $restaurant = $this->restaurant();

        DB::transaction(function () use ($restaurant, $hoursService) {
            foreach ($this->days as $day => $row) {
                RestaurantHour::updateOrCreate(
                    ['restaurant_id' => $restaurant->id, 'day_of_week' => (int) $day],
                    [
                        'is_closed' => (bool) $row['closed'],
                        'opens_at'  => $row['closed'] ? null : $row['opens'] . ':00',
                        'closes_at' => $row['closed'] ? null : $row['closes'] . ':00',
                    ]
                );
            }

            $setting = VendorSetting::firstOrCreate(['restaurant_id' => $restaurant->id]);
            $setting->update(['use_opening_hours' => $this->useOpeningHours]);

            if ($this->useOpeningHours) {
                // Apply the schedule right now instead of waiting for the next minute.
                $shouldBeOpen = $hoursService->isOpenAt($restaurant->hours()->get(), now());

                $restaurant->update(['is_open' => $shouldBeOpen]);
                $setting->forceFill(['scheduled_open_state' => $shouldBeOpen])->save();
            } else {
                $setting->forceFill(['scheduled_open_state' => null])->save();
            }

            activity()
                ->causedBy(Auth::user())
                ->performedOn($restaurant)
                ->withProperties(['use_opening_hours' => $this->useOpeningHours])
                ->log('Vendor updated opening hours');
        });

        session()->flash('success', 'Opening hours saved.');
    }

    // ── Render ────────────────────────────────────────────
    public function render()
    {
        return view('livewire.vendor.opening-hours-component', [
            'dayNames'     => RestaurantHour::DAY_NAMES,
            'displayOrder' => self::DISPLAY_ORDER,
            'isOpenNow'    => (bool) $this->restaurant()->is_open,
        ])->layout('layouts.vendor', [
            'title'           => 'Opening Hours | KhaiKhai',
            'breadcrumbTitle' => 'Opening Hours',
        ]);
    }
}
