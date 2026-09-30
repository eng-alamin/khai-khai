<?php

namespace App\Livewire\Vendor;

use App\Models\Food;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Restaurant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DashboardComponent extends Component
{
    // ── KPI cards ────────────────────────────────────────
    public int $todayOrdersCount = 0;

    public float $ordersChangePercent = 0;

    public float $todaySales = 0;

    public float $salesChangePercent = 0;

    public int $avgPrepMinutes = 0;

    public string $prepTimeNote = '';

    public float $avgRating = 0;

    public int $totalReviews = 0;

    // ── Weekly sales chart ───────────────────────────────
    public array $weekLabels = [];

    public array $weekSales = [];

    // ── Live orders / top items ─────────────────────────
    public array $liveOrders = [];

    public array $topItems = [];

    public int $liveOrdersPendingCount = 0;

    // ── Menu overview (read-only stats) ──────────────────
    public int $totalMenuItems = 0;

    public int $activeMenuItems = 0;

    public int $inactiveMenuItems = 0;

    private ?Restaurant $restaurant = null;

    public function mount(): void
    {
        $this->restaurant = $this->restaurant();

        $this->loadKpis();
        $this->loadWeeklyChart();
        $this->loadLiveOrders();
        $this->loadTopItems();
        $this->loadMenuOverview();
    }

    private function restaurant(): Restaurant
    {
        if ($this->restaurant) {
            return $this->restaurant;
        }

        return $this->restaurant = Auth::user()->restaurant;
    }

    private function restaurantId(): int
    {
        return $this->restaurant()->id;
    }

    // ── KPI CARD DATA ────────────────────────────────────
    private function loadKpis(): void
    {
        $restaurantId = $this->restaurantId();
        $today        = Carbon::today();
        $yesterday    = Carbon::yesterday();

        // Orders count
        $todayCount     = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', $today)
            ->count();

        $yesterdayCount = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', $yesterday)
            ->count();

        $this->todayOrdersCount    = $todayCount;
        $this->ordersChangePercent = $this->percentChange($todayCount, $yesterdayCount);

        // FIX: total_amount decimal(15,2) column e taka-e store hoy, paisa-te
        // na — tai /100 division bad dilam, age eta sales 100x kom dekhachhilo.
        $todaySalesTaka     = Order::where('restaurant_id', $restaurantId)
            ->where('payment_status', 'paid')
            ->whereDate('created_at', $today)
            ->sum('total_amount');

        $yesterdaySalesTaka = Order::where('restaurant_id', $restaurantId)
            ->where('payment_status', 'paid')
            ->whereDate('created_at', $yesterday)
            ->sum('total_amount');

        $this->todaySales         = round($todaySalesTaka);
        $this->salesChangePercent = $this->percentChange($todaySalesTaka, $yesterdaySalesTaka);

        // Average preparation time: confirmed -> picked_up, for today's orders
        $logs = OrderStatusLog::whereHas('order', function ($q) use ($restaurantId) {
                $q->where('restaurant_id', $restaurantId);
            })
            ->whereIn('to_status', ['confirmed', 'picked_up'])
            ->whereDate('created_at', $today)
            ->get()
            ->groupBy('order_id');

        $prepMinutes = [];
        foreach ($logs as $group) {
            $confirmed = $group->firstWhere('to_status', 'confirmed');
            $pickedUp  = $group->firstWhere('to_status', 'picked_up');

            if ($confirmed && $pickedUp) {
                $prepMinutes[] = $pickedUp->created_at->diffInMinutes($confirmed->created_at);
            }
        }

        $this->avgPrepMinutes = count($prepMinutes)
            ? (int) round(array_sum($prepMinutes) / count($prepMinutes))
            : 0;

        $this->prepTimeNote = count($prepMinutes) ? 'Fastest today' : 'No completed orders yet';

        // Rating, straight from the restaurants table
        $this->avgRating    = (float) ($this->restaurant()->avg_rating ?? 0);
        $this->totalReviews = (int) ($this->restaurant()->total_reviews ?? 0);
    }

    private function percentChange(int|float $current, int|float $previous): float
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 1);
        }

        return $current > 0 ? 100.0 : 0.0;
    }

    // ── WEEKLY SALES CHART ───────────────────────────────
    private function loadWeeklyChart(): void
    {
        $restaurantId = $this->restaurantId();
        $labels       = [];
        $sales        = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);

            $daySalesTaka = Order::where('restaurant_id', $restaurantId)
                ->where('payment_status', 'paid')
                ->whereDate('created_at', $date)
                ->sum('total_amount');

            $labels[] = $date->translatedFormat('D');
            $sales[]  = round($daySalesTaka);
        }

        $this->weekLabels = $labels;
        $this->weekSales  = $sales;
    }

    // ── LIVE ORDERS (read-only) ──────────────────────────
    private function loadLiveOrders(): void
    {
        $restaurantId = $this->restaurantId();

        $orders = Order::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['pending', 'confirmed', 'preparing'])
            ->with(['customer', 'items'])
            ->latest()
            ->limit(5)
            ->get();

        $this->liveOrdersPendingCount = $orders->count();

        $this->liveOrders = $orders->map(function (Order $order) {
            $customerName = $order->customer_snapshot['name']
                ?? $order->customer?->name
                ?? 'Customer';

            return [
                'number'     => $order->order_number,
                'customer'   => $customerName,
                'summary'    => $order->items_summary,
                'total'      => number_format($order->total_amount),
                'status'     => $order->status,
                'statusText' => ucwords(str_replace('_', ' ', $order->status)),
                'timeAgo'    => $order->created_at->diffForHumans(),
            ];
        })->toArray();
    }

    // ── TOP SELLING FOOD ITEMS (last 30 days, read-only) ─
    private function loadTopItems(): void
    {
        $restaurantId = $this->restaurantId();

        // order_items ekhon polymorphic (orderable_type/orderable_id) —
        // vendor er dashboard e shudhu Food type item gulo dekhabo.
        $rows = OrderItem::select('orderable_id', DB::raw('SUM(quantity) as total_sold'))
            ->where('orderable_type', Food::class)
            ->whereHas('order', function ($q) use ($restaurantId) {
                $q->where('restaurant_id', $restaurantId)
                    ->where('created_at', '>=', Carbon::now()->subDays(30));
            })
            ->whereNotNull('orderable_id')
            ->groupBy('orderable_id')
            ->orderByDesc('total_sold')
            ->with('orderable')
            ->limit(6)
            ->get()
            ->filter(fn ($row) => $row->orderable !== null);

        $maxSold = $rows->max('total_sold') ?: 1;

        $this->topItems = $rows->map(function ($row) use ($maxSold) {
            $item = $row->orderable;

            return [
                'name'     => $item->name,
                'price'    => number_format($item->price),
                'image'    => $item->image_url,
                'sold'     => (int) $row->total_sold,
                'progress' => (int) round(($row->total_sold / $maxSold) * 100),
            ];
        })->toArray();
    }

    // ── MENU OVERVIEW (counts only, no listing/actions) ──
    private function loadMenuOverview(): void
    {
        $restaurantId = $this->restaurantId();

        $this->totalMenuItems    = Food::where('restaurant_id', $restaurantId)->count();
        $this->activeMenuItems   = Food::where('restaurant_id', $restaurantId)->where('is_available', true)->count();
        $this->inactiveMenuItems = $this->totalMenuItems - $this->activeMenuItems;
    }

    public function render()
    {
        return view('livewire.vendor.dashboard-component', [
            'restaurant' => $this->restaurant(),
        ])->layout('layouts.vendor', [
            'title'           => 'Dashboard | KhaiKhai',
            'breadcrumbTitle' => 'Dashboard',
        ]);
    }
}