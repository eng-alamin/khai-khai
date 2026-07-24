<?php

namespace App\Livewire\Rider;

use App\Models\Order;
use App\Models\RiderEarning;
use App\Models\RiderPayout;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class FinanceComponent extends Component
{
    use WithPagination;

    public int $perPage = 10;

    private const BANGLA_DIGITS = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];

    private const BANGLA_MONTHS = [
        1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ',     4 => 'এপ্রিল',
        5 => 'মে',        6 => 'জুন',        7 => 'জুলাই',     8 => 'আগস্ট',
        9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর',   11 => 'নভেম্বর',  12 => 'ডিসেম্বর',
    ];

    /* ── এই মাসের মোট আয় (টাকায়) ── */
    public function getMonthlyEarningsProperty(): int
    {
        $total = RiderEarning::where('rider_id', Auth::id())
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return $total;
    }

    /* ── এই মাসের মোট ডেলিভারি সংখ্যা ── */
    public function getMonthlyDeliveriesProperty(): int
    {
        return Order::where('rider_id', Auth::id())
            ->where('status', 'delivered')
            ->whereMonth('delivered_at', now()->month)
            ->whereYear('delivered_at', now()->year)
            ->count();
    }

    /* ── ব্যাংকে পাঠানো মোট (সর্বকালীন paid payout সমষ্টি) ── */
    public function getTotalPayoutProperty(): int
    {
        $total = RiderPayout::where('rider_id', Auth::id())
            ->where('status', 'paid')
            ->sum('amount');

        return $total;
    }

    /* ── Paginated পেমেন্ট ইতিহাস ── */
    public function getPayoutHistoryProperty()
    {
        return RiderPayout::where('rider_id', Auth::id())
            ->orderByDesc('paid_at')
            ->paginate($this->perPage);
    }

    public function toBanglaNumber(int|string $number): string
    {
        return strtr((string) $number, self::BANGLA_DIGITS);
    }

    private function formatMonthYear(Carbon $date): string
    {
        return $this->toBanglaNumber($date->day) . ' ' .
               self::BANGLA_MONTHS[$date->month] . ' ' .
               $this->toBanglaNumber($date->year);
    }

    public function render()
    {
        $rows = $this->payoutHistory->through(function (RiderPayout $payout) {
            return [
                'id'         => $payout->id,
                'date_label' => $payout->paid_at
                    ? $this->formatMonthYear($payout->paid_at)
                    : $this->formatMonthYear($payout->created_at),
                'method'     => $payout->method ?? 'bKash',   // e.g. bKash, Nagad, Bank
                'amount'     => '৳' . $payout->amount,
                'status'     => $payout->status,              // 'paid' | 'pending' | 'processing'
            ];
        });

        return view('livewire.rider.finance-component', [
            'rows'              => $rows,
            'monthlyEarnings'   => '৳' . $this->toBanglaNumber($this->monthlyEarnings),
            'monthlyDeliveries' => $this->toBanglaNumber($this->monthlyDeliveries),
            'totalPayout'       => '৳' . $this->toBanglaNumber($this->totalPayout),
        ])->layout('layouts.rider', [
            'title'           => 'Finance | KhaiKhai',
            'breadcrumbTitle' => 'Finance',
        ]);
    }
}