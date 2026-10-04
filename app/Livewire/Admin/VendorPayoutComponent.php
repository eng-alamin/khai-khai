<?php

namespace App\Livewire\Admin;

use App\Models\Payout;
use App\Models\Restaurant;
use App\Services\PaymentAccountService;
use App\Services\VendorPayoutService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/**
 * Admin screen for vendor (restaurant) payouts.
 * Route: /admin/vendor-payouts  (name: admin.vendor-payouts)
 */
class VendorPayoutComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // ── Filters ──────────────────────────────────────────────
    public string $search       = '';
    public string $statusFilter = 'pending';   // '' = all

    // ── Generate modal ───────────────────────────────────────
    public bool   $showGenerate = false;
    public string $periodStart  = '';
    public string $periodEnd    = '';
    public string $genRestaurantId = '';       // '' = all restaurants

    // ── Action modal (paid / failed) ─────────────────────────
    public bool   $showAction     = false;
    #[Locked]
    public ?int   $actionPayoutId = null;
    #[Locked]
    public string $actionType     = '';        // paid | failed
    public string $method         = 'bkash';
    public string $reference      = '';
    public string $reason         = '';

    protected $queryString = [
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => 'pending'],
    ];

    public function mount(): void
    {
        // Default period: the last 7 full days, ending yesterday.
        $this->periodEnd   = now()->subDay()->toDateString();
        $this->periodStart = now()->subDays(7)->toDateString();
    }

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
    }

    // ═════════════════════════════════════════════════════════
    // GENERATE
    // ═════════════════════════════════════════════════════════
    public function openGenerate(): void
    {
        $this->authorizeAdmin();
        $this->genRestaurantId = '';
        $this->resetValidation();
        $this->showGenerate = true;
    }

    public function closeGenerate(): void
    {
        $this->showGenerate = false;
        $this->resetValidation();
    }

    /** Dates are valid only when end is not after yesterday (no half-finished day). */
    private function parsedPeriod(): ?array
    {
        try {
            $start = Carbon::parse($this->periodStart)->startOfDay();
            $end   = Carbon::parse($this->periodEnd)->startOfDay();
        } catch (Throwable) {
            return null;
        }

        if ($start->gt($end) || $end->gte(today())) {
            return null;
        }

        return [$start, $end];
    }

    /** What would be created for the chosen dates (nothing is saved). */
    public function getPreviewProperty(): ?array
    {
        if (! $this->showGenerate || ($period = $this->parsedPeriod()) === null) {
            return null;
        }

        $restaurantId = $this->genRestaurantId !== '' ? (int) $this->genRestaurantId : null;

        $groups = app(VendorPayoutService::class)->preview($period[0], $period[1], $restaurantId);
        $names  = Restaurant::query()
            ->whereIn('id', $groups->pluck('restaurant_id'))
            ->pluck('name', 'id');

        return [
            'rows'      => $groups->map(fn ($g) => $g + ['name' => $names[$g['restaurant_id']] ?? '—'])->all(),
            'total_net' => (float) $groups->sum('net'),
            'orders'    => (int) $groups->sum('orders'),
        ];
    }

    public function generate(VendorPayoutService $service): void
    {
        $this->authorizeAdmin();

        $this->validate([
            'periodStart'     => ['required', 'date'],
            'periodEnd'       => ['required', 'date', 'after_or_equal:periodStart', 'before:today'],
            'genRestaurantId' => ['nullable', Rule::exists('restaurants', 'id')],
        ], [
            'periodEnd.before'            => 'The period must end yesterday or earlier (today is not finished yet).',
            'periodEnd.after_or_equal'    => 'The end date cannot be before the start date.',
        ]);

        $period = $this->parsedPeriod();

        if ($period === null) {
            return;
        }

        $restaurantId = $this->genRestaurantId !== '' ? (int) $this->genRestaurantId : null;

        $result = $service->generate($period[0], $period[1], Auth::user(), $restaurantId);

        $this->showGenerate = false;
        $this->statusFilter = 'pending';
        $this->resetPage();

        if ($result['created'] === 0) {
            session()->flash('error', 'Nothing to settle for this period. Every delivered order is already in a payout.');
            return;
        }

        session()->flash('success', $result['created'] . ' payout(s) created, total ৳' . number_format($result['total_net']) . '.');
    }

    // ═════════════════════════════════════════════════════════
    // MARK PAID / FAILED
    // ═════════════════════════════════════════════════════════
    public function openAction(int $payoutId, string $type): void
    {
        $this->authorizeAdmin();

        if (! in_array($type, ['paid', 'failed'], true)) {
            return;
        }

        Payout::findOrFail($payoutId);

        $this->actionPayoutId = $payoutId;
        $this->actionType     = $type;
        $this->method         = 'bkash';
        $this->reference      = '';
        $this->reason         = '';
        $this->resetValidation();
        $this->showAction     = true;
    }

    public function closeAction(): void
    {
        $this->showAction     = false;
        $this->actionPayoutId = null;
        $this->actionType     = '';
        $this->reference      = '';
        $this->reason         = '';
        $this->resetValidation();
    }

    public function getActionPayoutProperty(): ?Payout
    {
        return $this->actionPayoutId
            ? Payout::with('restaurant:id,name,owner_id,phone')->find($this->actionPayoutId)
            : null;
    }

    public function submitAction(VendorPayoutService $service): void
    {
        $this->authorizeAdmin();

        if ($this->actionPayoutId === null) {
            return;
        }

        if ($this->actionType === 'paid') {
            $this->validate([
                'method'    => ['required', Rule::in(Payout::METHODS)],
                'reference' => ['required_unless:method,cash', 'nullable', 'string', 'max:100'],
            ], [
                'reference.required_unless' => 'Please enter the transaction ID / reference of the transfer.',
            ]);

            $reference = trim($this->reference) !== '' ? trim($this->reference) : null;
            $done      = $service->markPaid($this->actionPayoutId, Auth::user(), $this->method, $reference);
        } elseif ($this->actionType === 'failed') {
            $this->validate([
                'reason' => ['required', 'string', 'max:500'],
            ], [
                'reason.required' => 'Please write the reason.',
            ]);

            $done = $service->markFailed($this->actionPayoutId, Auth::user(), trim($this->reason));
        } else {
            return;
        }

        $this->closeAction();

        if ($done) {
            session()->flash('success', 'Payout updated.');
        } else {
            session()->flash('error', 'This payout was already handled (maybe by another admin). The list has been refreshed.');
        }
    }

    // ═════════════════════════════════════════════════════════
    // RENDER
    // ═════════════════════════════════════════════════════════
    public function render()
    {
        $payouts = Payout::query()
            ->with('restaurant:id,name,phone,owner_id', 'restaurant.owner:id,name')
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';

                $q->where(function ($q) use ($term) {
                    $q->where('payout_number', 'like', $term)
                        ->orWhere('transaction_reference', 'like', $term)
                        ->orWhereHas('restaurant', fn ($r) => $r->where('name', 'like', $term));
                });
            })
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);

        $open = Payout::query()
            ->whereIn('status', Payout::OPEN_STATUSES)
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(net_amount), 0) as total_amount')
            ->first();

        // Money earned by vendors that is not in any payout yet.
        $unsettled = (float) DB::table('platform_transactions as pt')
            ->join('orders as o', 'o.id', '=', 'pt.order_id')
            ->whereNotNull('o.restaurant_id')
            ->where('pt.status', 'success')
            ->where('pt.settlement_status', 'pending')
            ->whereNull('pt.payout_id')
            ->sum('pt.vendor_amount');

        $stats = [
            'open_count'  => (int) ($open->total_count ?? 0),
            'open_amount' => (float) ($open->total_amount ?? 0),
            'unsettled'   => $unsettled,
            'paid_month'  => (float) Payout::query()
                ->where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('net_amount'),
        ];

        // Saved bKash / Nagad / bank accounts of the restaurant owners shown on this page
        // (and of the payout whose action modal is open).
        $ownerIds = $payouts->getCollection()
            ->map(fn ($p) => $p->restaurant?->owner_id)
            ->all();

        if ($this->showAction && $this->actionPayout?->restaurant?->owner_id) {
            $ownerIds[] = $this->actionPayout->restaurant->owner_id;
        }

        $accounts = app(PaymentAccountService::class)->allFor($ownerIds);

        return view('livewire.admin.vendor-payout-component', [
            'payouts'     => $payouts,
            'accounts'    => $accounts,
            'stats'       => $stats,
            'restaurants' => $this->showGenerate
                ? Restaurant::query()->where('is_approved', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
        ])->layout('layouts.admin', [
            'title'           => 'Vendor Payouts | KhaiKhai',
            'breadcrumbTitle' => 'Vendor Payouts',
        ]);
    }
}
