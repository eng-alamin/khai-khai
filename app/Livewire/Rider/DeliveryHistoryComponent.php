<?php

namespace App\Livewire\Rider;

use App\Models\Order;
use App\Models\RiderEarning;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class DeliveryHistoryComponent extends Component
{
    use WithPagination;

    /** প্রতি পেজে কতটা রো দেখাবে */
    public int $perPage = 10;

    private const BANGLA_DIGITS = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];

    private const BANGLA_MONTHS = [
        1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ',     4 => 'এপ্রিল',
        5 => 'মে',        6 => 'জুন',        7 => 'জুলাই',     8 => 'আগস্ট',
        9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর',   11 => 'নভেম্বর',  12 => 'ডিসেম্বর',
    ];

    /* ── Computed: এই রাইডারের মোট সম্পন্ন ডেলিভারি সংখ্যা (top badge) ── */
    public function getTotalDeliveredProperty(): int
    {
        return Order::where('rider_id', Auth::id())
            ->where('status', 'delivered')
            ->count();
    }

    /* ── Computed: Paginated delivery history ── */
    public function getHistoryOrdersProperty()
    {
        return Order::with(['customer', 'restaurant'])
            ->where('rider_id', Auth::id())
            ->where('status', 'delivered')
            ->orderByDesc('delivered_at')
            ->paginate($this->perPage);
    }

    /* ── Helper: এই অর্ডারের জন্য rider earning (টাকায়) ── */
    private function earningFor(Order $order): ?int
    {
        $earning = RiderEarning::where('order_id', $order->id)->first();

        if (! $earning) {
            return null;
        }

        return $earning->amount;
    }

    /* ── Helper: তারিখ লেবেল — আজ / গতকাল / X দিন আগে / পূর্ণ তারিখ ── */
    private function dateLabel(?Carbon $time): string
    {
        if (! $time) {
            return '-';
        }

        if ($time->isToday()) {
            return 'আজ ' . $this->toBanglaNumber($time->format('g:i'));
        }

        if ($time->isYesterday()) {
            return 'গতকাল';
        }

        $days = (int) $time->diffInDays(now());

        if ($days < 7) {
            return $this->toBanglaNumber($days) . ' দিন আগে';
        }

        return $this->toBanglaNumber($time->day) . ' ' . self::BANGLA_MONTHS[$time->month] . ', ' . $this->toBanglaNumber($time->year);
    }

    public function toBanglaNumber(int|string $number): string
    {
        return strtr((string) $number, self::BANGLA_DIGITS);
    }

    public function render()
    {
        // প্রতিটি অর্ডারকে UI-friendly array তে রূপান্তর (pagination meta ঠিক রেখে)
        $rows = $this->historyOrders->through(function (Order $order) {
            $earning = $this->earningFor($order);

            return [
                'id'           => $order->id,
                'order_number' => $order->order_number,
                'customer'     => $order->customer->name ?? 'কাস্টমার',
                'restaurant'   => $order->restaurant->name ?? 'রেস্টুরেন্ট',
                'date_label'   => $this->dateLabel($order->delivered_at ?? $order->updated_at),
                'earning'      => $earning !== null ? '৳' . $earning : '-',
                // 🔶 ASSUMPTION: orders.rider_rating (nullable tinyint 1-5) — confirm করো
                'rating'       => $order->rider_rating,
            ];
        });

        return view('livewire.rider.delivery-history-component', [
            'rows'           => $rows,
            'totalDelivered' => $this->totalDelivered,
        ])->layout('layouts.rider', [
            'title'           => 'Delivery History | KhaiKhai',
            'breadcrumbTitle' => 'Delivery History',
        ]);
    }
}