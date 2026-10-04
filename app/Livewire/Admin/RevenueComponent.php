<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Order;
use App\Models\PlatformTransaction;
use App\Models\Refund;

class RevenueComponent extends Component
{
    // Number of months to show in the trend chart
    public int $trendMonths = 5;

    private array $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    // ── Helpers ──────────────────────────────────────────────
    /**
     * Commission earned on orders created from $from onwards (delivered orders only).
     * Read from platform_transactions, which store the restaurant's own rate.
     */
    private function commissionSince(\Illuminate\Support\Carbon $from): float
    {
        return (float) PlatformTransaction::query()
            ->where('status', 'success')
            ->whereHas('order', fn ($q) => $q
                ->where('status', 'delivered')
                ->where('created_at', '>=', $from))
            ->sum('platform_commission');
    }

    /**
     * Refunds already paid out for delivered orders created in the range.
     * They are matched to the month of the ORDER (not the refund), so a late
     * refund lowers the month that earned the money.
     */
    private function refundsBetween(\Illuminate\Support\Carbon $from, ?\Illuminate\Support\Carbon $to = null): float
    {
        return (float) Refund::query()
            ->where('status', 'paid')
            ->whereHas('order', fn ($q) => $q
                ->where('status', 'delivered')
                ->where('created_at', '>=', $from)
                ->when($to !== null, fn ($w) => $w->where('created_at', '<=', $to)))
            ->sum('amount');
    }

    private function toLakh(float $taka): float
    {
        return round($taka / 100000, 2);
    }

    // ── Computed: Stats ───────────────────────────────────────
    public function getStatsProperty(): array
    {
        $startOfThisMonth = now()->startOfMonth();
        $startOfLastMonth = now()->subMonthNoOverflow()->startOfMonth();
        $endOfLastMonth   = now()->subMonthNoOverflow()->endOfMonth();

        $thisMonthRefunds = $this->refundsBetween($startOfThisMonth);
        $lastMonthRefunds = $this->refundsBetween($startOfLastMonth, $endOfLastMonth);

        // Revenue = what customers paid, minus what was refunded to them.
        $thisMonthRevenue = (float) Order::where('status', 'delivered')
            ->where('created_at', '>=', $startOfThisMonth)
            ->sum('total_amount') - $thisMonthRefunds;

        $lastMonthRevenue = (float) Order::where('status', 'delivered')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total_amount') - $lastMonthRefunds;

        $thisMonthTaka = $thisMonthRevenue; // orders store Taka, not paisa
        $lastMonthTaka = $lastMonthRevenue;

        $commission = $this->commissionSince($startOfThisMonth);

        $growth = $lastMonthTaka > 0
            ? round((($thisMonthTaka - $lastMonthTaka) / $lastMonthTaka) * 100)
            : ($thisMonthTaka > 0 ? 100 : 0);

        return [
            'total_revenue' => $this->toLakh($thisMonthTaka),
            'refunds'       => $thisMonthRefunds,
            'commission'    => $this->toLakh($commission),
            'growth'        => $growth,
        ];
    }

    // ── Computed: Monthly Trend (last N months) ─────────────────
    public function getMonthlyTrendProperty(): array
    {
        $rows = [];
        $max  = 0;

        for ($i = $this->trendMonths - 1; $i >= 0; $i--) {
            $monthDate = now()->subMonthsNoOverflow($i);
            $start     = $monthDate->copy()->startOfMonth();
            $end       = $monthDate->copy()->endOfMonth();

            $revenue = (float) Order::where('status', 'delivered')
                ->whereBetween('created_at', [$start, $end])
                ->sum('total_amount') - $this->refundsBetween($start, $end);

            $taka = $revenue;
            $lakh = $this->toLakh($taka);

            $rows[] = [
                'label' => $this->months[$monthDate->month] . ' ' . $monthDate->year,
                'value' => $lakh,
            ];

            $max = max($max, $lakh);
        }

        foreach ($rows as &$row) {
            $row['percent'] = $max > 0 ? round(($row['value'] / $max) * 100, 1) : 0;
        }

        return $rows;
    }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.admin.revenue-component', [
            'stats' => $this->stats,
            'trend' => $this->monthlyTrend,
        ])->layout('layouts.admin', [
            'title'           => 'Revenue | KhaiKhai',
            'breadcrumbTitle' => 'Revenues',
        ]);
    }
}