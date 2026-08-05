<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Order;

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
    private function commissionRate(): float
    {
        return (float) \App\Models\AdminSetting::get('default_commission_rate', 0);
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

        $thisMonthRevenue = (int) Order::where('status', 'delivered')
            ->where('created_at', '>=', $startOfThisMonth)
            ->sum('total_amount');

        $lastMonthRevenue = (int) Order::where('status', 'delivered')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total_amount');

        $thisMonthTaka = intdiv($thisMonthRevenue, 100);
        $lastMonthTaka = intdiv($lastMonthRevenue, 100);

        $commission = $thisMonthTaka * ($this->commissionRate() / 100);

        $growth = $lastMonthTaka > 0
            ? round((($thisMonthTaka - $lastMonthTaka) / $lastMonthTaka) * 100)
            : ($thisMonthTaka > 0 ? 100 : 0);

        return [
            'total_revenue' => $this->toLakh($thisMonthTaka),
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

            $revenue = (int) Order::where('status', 'delivered')
                ->whereBetween('created_at', [$start, $end])
                ->sum('total_amount');

            $taka = intdiv($revenue, 100);
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