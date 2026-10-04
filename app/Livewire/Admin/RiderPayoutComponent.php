<?php

namespace App\Livewire\Admin;

use App\Models\RiderPayout;
use App\Services\RiderPayoutService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin screen for rider payout requests.
 * Route: /admin/rider-payouts  (name: admin.rider-payouts)
 */
class RiderPayoutComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    private const ACTIONS = ['approve', 'reject', 'paid', 'failed'];

    // ── Filters ──────────────────────────────────────────────
    public string $search       = '';
    public string $statusFilter = 'pending';   // '' = all

    // ── Action modal ─────────────────────────────────────────
    public bool    $showAction      = false;
    #[Locked]
    public ?int    $actionPayoutId  = null;
    #[Locked]
    public string  $actionType      = '';      // approve | reject | paid | failed
    public string  $note            = '';
    public string  $transactionId   = '';

    protected $queryString = [
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => 'pending'],
    ];

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
    }

    // ── Open / close the action modal ────────────────────────
    public function openAction(int $payoutId, string $type): void
    {
        $this->authorizeAdmin();

        if (! in_array($type, self::ACTIONS, true)) {
            return;
        }

        RiderPayout::findOrFail($payoutId);

        $this->actionPayoutId = $payoutId;
        $this->actionType     = $type;
        $this->note           = '';
        $this->transactionId  = '';
        $this->resetValidation();
        $this->showAction     = true;
    }

    public function closeAction(): void
    {
        $this->showAction     = false;
        $this->actionPayoutId = null;
        $this->actionType     = '';
        $this->note           = '';
        $this->transactionId  = '';
        $this->resetValidation();
    }

    public function getActionPayoutProperty(): ?RiderPayout
    {
        if (! $this->actionPayoutId) {
            return null;
        }

        return RiderPayout::with('rider:id,name,phone')->find($this->actionPayoutId);
    }

    // ── Submit ───────────────────────────────────────────────
    public function submitAction(RiderPayoutService $service): void
    {
        $this->authorizeAdmin();

        $rules = [
            'note'          => in_array($this->actionType, ['reject', 'failed'], true)
                ? ['required', 'string', 'max:500']
                : ['nullable', 'string', 'max:500'],
            'transactionId' => $this->actionType === 'paid'
                ? ['required', 'string', 'max:100']
                : ['nullable'],
        ];

        $this->validate($rules, [
            'note.required'          => 'Please write a reason. The rider will see it.',
            'transactionId.required' => 'Please enter the transaction ID of the transfer.',
        ]);

        if (! $this->actionPayoutId || ! in_array($this->actionType, self::ACTIONS, true)) {
            return;
        }

        $admin = Auth::user();
        $note  = trim($this->note) !== '' ? trim($this->note) : null;

        $done = match ($this->actionType) {
            'approve' => $service->approve($this->actionPayoutId, $admin, $note),
            'reject'  => $service->reject($this->actionPayoutId, $admin, (string) $note),
            'paid'    => $service->markPaid($this->actionPayoutId, $admin, trim($this->transactionId), $note),
            'failed'  => $service->markFailed($this->actionPayoutId, $admin, (string) $note),
        };

        $this->closeAction();

        if ($done) {
            session()->flash('success', 'Payout request updated. The rider has been notified.');
        } else {
            session()->flash('error', 'This request was already changed (maybe by another admin). The list has been refreshed.');
        }
    }

    // ── Render ───────────────────────────────────────────────
    public function render()
    {
        $payouts = RiderPayout::query()
            ->with('rider:id,name,phone')
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';

                $q->where(function ($q) use ($term) {
                    $q->where('account_number', 'like', $term)
                        ->orWhere('transaction_id', 'like', $term)
                        ->orWhereHas('rider', fn ($r) => $r->where('name', 'like', $term)
                            ->orWhere('phone', 'like', $term));
                });
            })
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);

        $byStatus = RiderPayout::query()
            ->selectRaw('status, COUNT(*) as total_count, COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $stats = [
            'pending_count'   => (int) ($byStatus['pending']->total_count ?? 0),
            'pending_amount'  => (float) ($byStatus['pending']->total_amount ?? 0),
            'approved_count'  => (int) ($byStatus['approved']->total_count ?? 0),
            'approved_amount' => (float) ($byStatus['approved']->total_amount ?? 0),
            'paid_month'      => (float) RiderPayout::query()
                ->where('status', 'paid')
                ->whereMonth('resolved_at', now()->month)
                ->whereYear('resolved_at', now()->year)
                ->sum('amount'),
        ];

        return view('livewire.admin.rider-payout-component', [
            'payouts' => $payouts,
            'stats'   => $stats,
        ])->layout('layouts.admin', [
            'title'           => 'Rider Payouts | KhaiKhai',
            'breadcrumbTitle' => 'Rider Payouts',
        ]);
    }
}
