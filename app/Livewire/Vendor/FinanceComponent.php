<?php

namespace App\Livewire\Vendor;

use App\Models\Order;
use App\Models\Payout;
use App\Models\Restaurant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FinanceComponent extends Component
{
    // ── Commission rate (KhaiKhai cut), per-restaurant ─────
    public float $commissionRate = 0.0; // fraction, e.g. 0.15

    // ── Summary card data ──────────────────────────────────
    // "This month" is still live-calculated from `orders`, since the
    // current month's payout hasn't been generated/settled yet.
    public int $monthlyRevenue    = 0; // BDT, based on subtotal
    public int $monthlyCommission = 0;
    public int $monthlyNet        = 0;
    public string $nextPaymentDate = '';

    // ── Payment history, now sourced from `payouts` ─────────
    public array $paymentHistory = [];

    public int $historyLimit = 3;

    // ── Restaurant helpers ──────────────────────────────────
    private function restaurant(): Restaurant
    {
        return Auth::user()->restaurant;
    }

    private function restaurantId(): int
    {
        return $this->restaurant()->id;
    }

    public function mount(): void
    {
        $this->loadFinanceData();
    }

    // ── Core data loader ────────────────────────────────────
    private function loadFinanceData(): void
    {
        $restaurant   = $this->restaurant();
        $restaurantId = $restaurant->id;
        $now          = Carbon::now();

        // restaurants.commission_rate is a PERCENTAGE (e.g. 15.00 = 15%).
        // Kept as-is here since the view displays it directly as "15.0%".
        $this->commissionRate = ((float) $restaurant->commission_rate);

        // ── Current month live summary (orders not yet settled) ──
        // Commission is calculated on `subtotal` (food cost only), not
        // `total_amount`, since the delivery fee isn't part of the
        // restaurant's sales.
        $currentMonthSubtotalPaisa = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ])
            ->sum('subtotal');

        $revenue = (int) round($currentMonthSubtotalPaisa);

        // BUG FIX: commissionRate is a percentage (e.g. 15.0), so it must be
        // divided by 100 before being applied as a multiplier — otherwise
        // commission was being calculated as 15x revenue instead of 15% of it.
        $commission = (int) round($revenue * ($this->commissionRate / 100));

        $this->monthlyRevenue    = $revenue;
        $this->monthlyCommission = $commission;
        $this->monthlyNet        = $revenue - $commission;

        // ── Next payment date ──
        // Prefer the nearest upcoming scheduled payout if one exists;
        // otherwise fall back to the 5th of next month as a default cycle.
        $nextPayout = Payout::query()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('scheduled_at')
            ->first();

        $this->nextPaymentDate = $nextPayout?->scheduled_at
            ? $nextPayout->scheduled_at->translatedFormat('j F')
            : $now->copy()->addMonthNoOverflow()->day(5)->translatedFormat('j F');

        // ── Payment history, from real payouts ──
        $this->paymentHistory = Payout::query()
            ->where('restaurant_id', $restaurantId)
            ->orderByDesc('period_start')
            ->limit($this->historyLimit)
            ->get()
            ->map(function (Payout $payout) {
                return [
                    'date'       => $payout->paid_at
                        ? $payout->paid_at->translatedFormat('j F Y')
                        : $payout->period_start->translatedFormat('j F Y'),
                    'sales'      => (int) round($payout->gross_subtotal),
                    'commission' => (int) round($payout->commission_amount),
                    'net'        => (int) round($payout->net_amount),
                    'status'     => $payout->status, // pending | processing | paid | failed
                ];
            })
            ->toArray();
    }

    // ── Render ────────────────────────────────────────────
    public function render()
    {
        return view('livewire.vendor.finance-component')
            ->layout('layouts.vendor', [
                'title'           => 'Finance | KhaiKhai',
                'breadcrumbTitle' => 'Finance',
            ]);
    }
}