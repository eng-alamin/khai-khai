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

    private const MONTH_LABELS = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar',  4 => 'Apr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul',  8 => 'Aug',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    /* ── This month's total earnings (in Taka) ── */
    public function getMonthlyEarningsProperty(): int
    {
        $total = RiderEarning::where('rider_id', Auth::id())
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return $total;
    }

    /* ── This month's total delivery count ── */
    public function getMonthlyDeliveriesProperty(): int
    {
        return Order::where('rider_id', Auth::id())
            ->where('status', 'delivered')
            ->whereMonth('delivered_at', now()->month)
            ->whereYear('delivered_at', now()->year)
            ->count();
    }

    /* ── Total sent to bank (all-time sum of paid payouts) ── */
    public function getTotalPayoutProperty(): int
    {
        $total = RiderPayout::where('rider_id', Auth::id())
            ->where('status', 'paid')
            ->sum('amount');

        return $total;
    }

    /* ── Paginated payment history ── */
    public function getPayoutHistoryProperty()
    {
        return RiderPayout::where('rider_id', Auth::id())
            ->orderByRaw('COALESCE(resolved_at, created_at) DESC')
            ->paginate($this->perPage);
    }

    private function formatDate(Carbon $date): string
    {
        return $date->day . ' ' .
               self::MONTH_LABELS[$date->month] . ' ' .
               $date->year;
    }

    public function render()
    {
        $rows = $this->payoutHistory->through(function (RiderPayout $payout) {
            return [
                'id'         => $payout->id,
                'date_label' => $payout->resolved_at
                    ? $this->formatDate($payout->resolved_at)
                    : $this->formatDate($payout->created_at),
                'method'     => $payout->method ?? 'bKash',   // e.g. bKash, Nagad, Bank
                'amount'     => 'Tk ' . $payout->amount,
                'status'     => $payout->status,              // pending|approved|processing|paid|rejected|failed
            ];
        });

        return view('livewire.rider.finance-component', [
            'rows'              => $rows,
            'monthlyEarnings'   => 'Tk ' . number_format($this->monthlyEarnings),
            'monthlyDeliveries' => $this->monthlyDeliveries,
            'totalPayout'       => 'Tk ' . number_format($this->totalPayout),
        ])->layout('layouts.rider', [
            'title'           => 'Finance | KhaiKhai',
            'breadcrumbTitle' => 'Finance',
        ]);
    }
}