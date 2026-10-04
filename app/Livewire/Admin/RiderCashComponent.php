<?php

namespace App\Livewire\Admin;

use App\Models\RiderCashDeposit;
use App\Models\User;
use App\Services\RiderBalanceService;
use App\Services\RiderCashService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin screen: how much Cash on Delivery money each rider still holds,
 * and recording the cash a rider hands over.
 * Route: /admin/rider-cash  (name: admin.rider-cash)
 */
class RiderCashComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';
    public bool   $onlyHolding = true;      // hide riders who hold no cash

    // ── Deposit modal ────────────────────────────────────────
    public bool   $showDeposit = false;
    #[Locked]
    public ?int   $riderId     = null;
    public $amount             = '';
    public string $method      = 'cash';
    public string $reference   = '';
    public string $note        = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch(): void      { $this->resetPage(); }
    public function updatingOnlyHolding(): void { $this->resetPage(); }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
    }

    // ── Open / close ─────────────────────────────────────────
    public function openDeposit(int $riderId, RiderCashService $cash): void
    {
        $this->authorizeAdmin();

        User::query()->where('role', 'rider')->findOrFail($riderId);

        $this->riderId   = $riderId;
        $this->amount    = max(0.0, $cash->cashInHand($riderId)) ?: '';
        $this->method    = 'cash';
        $this->reference = '';
        $this->note      = '';
        $this->resetValidation();
        $this->showDeposit = true;
    }

    public function closeDeposit(): void
    {
        $this->showDeposit = false;
        $this->riderId     = null;
        $this->amount      = '';
        $this->reference   = '';
        $this->note        = '';
        $this->resetValidation();
    }

    public function getDepositRiderProperty(): ?array
    {
        if (! $this->riderId) {
            return null;
        }

        $rider = User::query()->select('id', 'name', 'phone')->find($this->riderId);

        if (! $rider) {
            return null;
        }

        return [
            'name'    => $rider->name,
            'phone'   => $rider->phone,
            'in_hand' => app(RiderCashService::class)->cashInHand($rider->id),
        ];
    }

    // ── Save ─────────────────────────────────────────────────
    public function saveDeposit(RiderCashService $cash): void
    {
        $this->authorizeAdmin();

        if (! $this->riderId) {
            return;
        }

        $this->validate([
            'amount'    => ['required', 'numeric', 'min:0.01'],
            'method'    => ['required', Rule::in(RiderCashDeposit::METHODS)],
            'reference' => ['required_unless:method,cash', 'nullable', 'string', 'max:100'],
            'note'      => ['nullable', 'string', 'max:255'],
        ], [
            'amount.required'           => 'Please enter the amount you received.',
            'reference.required_unless' => 'Please enter the transaction ID / slip number.',
        ]);

        $rider = User::query()->where('role', 'rider')->findOrFail($this->riderId);

        $deposit = $cash->recordDeposit(
            $rider,
            (float) $this->amount,
            $this->method,
            trim($this->reference) !== '' ? trim($this->reference) : null,
            trim($this->note) !== '' ? trim($this->note) : null,
            Auth::user()
        );

        if ($deposit === null) {
            $this->addError('amount', 'The amount is more than the cash this rider holds (৳' . number_format(max(0, $cash->cashInHand($rider->id)), 2) . ').');
            return;
        }

        $this->closeDeposit();
        session()->flash('success', 'Deposit recorded. The rider has been notified.');
    }

    // ── Render ───────────────────────────────────────────────
    public function render(RiderBalanceService $balance, RiderCashService $cash)
    {
        $riders = User::query()
            ->where('role', 'rider')
            ->select('id', 'name', 'phone')
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('phone', 'like', $term));
            })
            ->orderBy('name')
            ->get();

        // Money picture of every matching rider (few queries), then filter + page in PHP.
        $summaries = $balance->summaries($riders->pluck('id')->all());

        if ($this->onlyHolding) {
            $riders = $riders->filter(fn ($r) => ($summaries[$r->id]['in_hand'] ?? 0) > 0.0)->values();
        }

        $page    = max(1, (int) $this->getPage());
        $perPage = 10;
        $paged   = new \Illuminate\Pagination\LengthAwarePaginator(
            $riders->forPage($page, $perPage)->values(),
            $riders->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.admin.rider-cash-component', [
            'riders'    => $paged,
            'summaries' => $summaries,
            'totals'    => $cash->platformTotals(),
            'recent'    => RiderCashDeposit::query()
                ->with('rider:id,name', 'recordedBy:id,name')
                ->latest('deposited_at')
                ->limit(8)
                ->get(),
            'netting'   => $balance->nettingEnabled(),
        ])->layout('layouts.admin', [
            'title'           => 'Rider Cash | KhaiKhai',
            'breadcrumbTitle' => 'Rider Cash',
        ]);
    }
}
