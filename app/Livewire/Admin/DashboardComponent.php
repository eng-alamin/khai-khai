<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Order;
use App\Models\PlatformTransaction;
use App\Models\User;
use App\Models\Restaurant;
use Carbon\Carbon;

class DashboardComponent extends Component
{
    // ── Computed: Stat Cards ────────────────────────────────────
    public function getStatsProperty(): array
    {
        $today = Carbon::today();

        $ordersToday = Order::whereDate('created_at', $today)->count();
        $ordersYesterday = Order::whereDate('created_at', $today->copy()->subDay())->count();
        $orderGrowth = $ordersYesterday > 0
            ? round((($ordersToday - $ordersYesterday) / $ordersYesterday) * 100)
            : 0;

        $revenueToday = Order::whereDate('created_at', $today)
            ->where('status', 'delivered')
            ->sum('total_amount');
        $revenueYesterday = Order::whereDate('created_at', $today->copy()->subDay())
            ->where('status', 'delivered')
            ->sum('total_amount');
        $revenueGrowth = $revenueYesterday > 0
            ? round((($revenueToday - $revenueYesterday) / $revenueYesterday) * 100)
            : 0;

        $activeVendors = Restaurant::where('is_active', true)->count();
        $newVendorsThisWeek = Restaurant::where('created_at', '>=', now()->subDays(7))->count();

        $activeRiders = User::where('role', 'rider')->where('last_seen_at', '>=', now()->subMinutes(5)) ->count();

        $totalCustomers = User::where('role', 'customer')->count();
        $customersThisMonth = User::where('role', 'customer')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        // Commission comes from platform_transactions (each restaurant's own rate).
        $commissionToday     = $this->commissionOn($today);
        $commissionYesterday = $this->commissionOn($today->copy()->subDay());
        $commissionGrowth = $commissionYesterday > 0
            ? round((($commissionToday - $commissionYesterday) / $commissionYesterday) * 100)
            : 0;

        return [
            'orders_today'         => number_format($ordersToday),
            'order_growth'         => $orderGrowth,
            'revenue_today'        => (int) round((float) $revenueToday),
            'revenue_growth'       => $revenueGrowth,
            'active_vendors'       => number_format($activeVendors),
            'new_vendors_week'     => $newVendorsThisWeek,
            'active_riders'        => number_format($activeRiders),
            'total_customers'      => number_format($totalCustomers),
            'customers_growth'     => $customersThisMonth,
            'commission_today'     => (int) round($commissionToday),
            'commission_growth'    => $commissionGrowth,
        ];
    }

    /** Platform commission on delivered orders created on the given day (Taka). */
    private function commissionOn(Carbon $day): float
    {
        return (float) PlatformTransaction::query()
            ->where('status', 'success')
            ->whereHas('order', fn ($q) => $q
                ->where('status', 'delivered')
                ->whereDate('created_at', $day))
            ->sum('platform_commission');
    }

    // ── Computed: Weekly Orders Chart ───────────────────────────
    public function getWeeklyOrdersProperty(): array
    {
        $days = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            $count = Order::whereDate('created_at', $date)->count();

            return [
                'label' => $date->format('D'),
                'value' => $count,
            ];
        });

        $max = $days->max('value') ?: 1;

        return $days->map(fn($d) => [
            'label'   => $d['label'],
            'value'   => $d['value'],
            'percent' => round(($d['value'] / $max) * 100),
        ])->toArray();
    }

    // ── Computed: Orders By City ────────────────────────────────
    public function getOrdersByCityProperty(): array
    {
        $rows = Order::query()
            ->join('customer_addresses', 'customer_addresses.id', '=', 'orders.delivery_address_id')
            ->selectRaw('customer_addresses.city as city, COUNT(*) as total')
            ->groupBy('customer_addresses.city')
            ->orderByDesc('total')
            ->limit(4)
            ->get();

        $grandTotal = $rows->sum('total') ?: 1;

        return $rows->map(fn($row) => [
            'city'    => $row->city,
            'percent' => round(($row->total / $grandTotal) * 100),
        ])->toArray();
    }

    // ── Computed: Recent Activity ───────────────────────────────
    public function getRecentActivityProperty(): array
    {
        $activities = collect();

        // New vendor registrations pending approval
        Restaurant::where('is_approved', false)
            ->latest()
            ->limit(3)
            ->get()
            ->each(function ($vendor) use ($activities) {
                $activities->push([
                    'icon'  => 'storefront',
                    'color' => 'green',
                    'text'  => "New vendor \"{$vendor->name}\" pending approval",
                    'time'  => $vendor->created_at,
                ]);
            });

        // Riders who recently came online
        User::where('role', 'rider')
            // ->where('is_online', true)
            ->latest('updated_at')
            ->limit(3)
            ->get()
            ->each(function ($rider) use ($activities) {
                $activities->push([
                    'icon'  => 'sports_motorsports',
                    'color' => 'blue',
                    'text'  => "Rider {$rider->name} #R-{$rider->id} came online",
                    'time'  => $rider->updated_at,
                ]);
            });

        // Orders stuck too long in a non-final status
        Order::whereNotIn('status', ['delivered', 'cancelled', 'rejected'])
            ->where('created_at', '<=', now()->subMinutes(30))
            ->latest()
            ->limit(3)
            ->get()
            ->each(function ($order) use ($activities) {
                $activities->push([
                    'icon'  => 'warning',
                    'color' => 'orange',
                    'text'  => "Order #KK{$order->id} stuck for " . $order->created_at->diffInMinutes(now()) . " minutes",
                    'time'  => $order->created_at,
                ]);
            });

        // Recent vendor payouts
        if (class_exists(\App\Models\Payout::class)) {
            \App\Models\Payout::query()
                ->with('restaurant:id,name')
                ->where('status', 'paid')
                ->latest('paid_at')
                ->limit(3)
                ->get()
                ->each(function ($payout) use ($activities) {
                    $activities->push([
                        'icon'  => 'payments',
                        'color' => 'pink',
                        'text'  => 'Payout of ৳' . number_format((int) round((float) $payout->net_amount))
                            . ' sent to ' . ($payout->restaurant->name ?? 'a vendor'),
                        'time'  => $payout->paid_at ?? $payout->created_at,
                    ]);
                });
        }

        return $activities
            ->sortByDesc('time')
            ->take(6)
            ->map(fn($a) => [
                'icon'      => $a['icon'],
                'color'     => $a['color'],
                'text'      => $a['text'],
                'time_ago'  => $a['time']->diffForHumans(),
            ])
            ->values()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.admin.dashboard-component', [
            'stats'          => $this->stats,
            'weeklyOrders'   => $this->weeklyOrders,
            'ordersByCity'   => $this->ordersByCity,
            'recentActivity' => $this->recentActivity,
        ])->layout('layouts.admin', [
            'title'           => 'Dashboard | KhaiKhai',
            'breadcrumbTitle' => 'Dashboard',
        ]);
    }
}