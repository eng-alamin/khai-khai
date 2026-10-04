<?php

namespace App\Livewire\Admin;

use App\Models\Refund;
use App\Services\RefundService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin screen: refunds of delivered orders.
 * Route: /admin/refunds  (name: admin.refunds)
 *
 * The support inbox links here with ?order=KK... &ticket=TK... so the form
 * opens already filled.
 */
class RefundComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search       = '';
    public string $statusFilter = 'pending';   // '' = all

    // ── New refund form ──────────────────────────────────────
    public bool   $showCreate   = false;
    public string $orderNumber  = '';
    public string $ticketNumber = '';
    public string $amount       = '';
    public string $reason       = 'food_quality';
    public string $bearer       = 'vendor';
    public string $note         = '';

    // ── Mark-paid / cancel modal ─────────────────────────────
    #[Locked]
    public ?int   $refundId   = null;
    #[Locked]
    public string $actionType = '';      // 'pay' | 'cancel'
    public string $method     = 'bkash';
    public string $reference  = '';

    protected $queryString = [
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => 'pending'],
    ];

    public function mount(): void
    {
        $order  = trim((string) request()->query('order', ''));
        $ticket = trim((string) request()->query('ticket', ''));

        if ($order !== '') {
            $this->orderNumber  = mb_substr($order, 0, 30);
            $this->ticketNumber = mb_substr($ticket, 0, 30);
            $this->showCreate   = true;
        }
    }

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
    }

    // ── Create ───────────────────────────────────────────────
    public function openCreate(): void
    {
        $this->authorizeAdmin();
        $this->reset(['orderNumber', 'ticketNumber', 'amount', 'note']);
        $this->reason = 'food_quality';
        $this->bearer = 'vendor';
        $this->resetErrorBag();
        $this->showCreate = true;
    }

    public function closeCreate(): void
    {
        $this->showCreate = false;
        $this->resetErrorBag();
    }

    public function createRefund(RefundService $service): void
    {
        $this->authorizeAdmin();

        $this->validate([
            'orderNumber'  => ['required', 'string', 'max:30'],
            'ticketNumber' => ['nullable', 'string', 'max:30'],
            'amount'       => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'reason'       => ['required', Rule::in(array_keys(Refund::REASONS))],
            'bearer'       => ['required', Rule::in(Refund::BEARERS)],
            'note'         => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $refund = $service->create(
                Auth::user(),
                $this->orderNumber,
                (float) $this->amount,
                $this->reason,
                $this->bearer,
                $this->note,
                $this->ticketNumber
            );
        } catch (DomainException $e) {
            $this->addError('orderNumber', $e->getMessage());
            return;
        }

        $this->closeCreate();
        $this->statusFilter = 'pending';
        session()->flash('success', "Refund {$refund->refund_number} created. Send the money, then mark it as paid.");
    }

    // ── Pay / cancel ─────────────────────────────────────────
    public function openAction(int $id, string $type): void
    {
        $this->authorizeAdmin();

        if (! in_array($type, ['pay', 'cancel'], true)) {
            return;
        }

        $refund = Refund::query()->findOrFail($id);

        if ($refund->status !== 'pending') {
            session()->flash('error', 'This refund was already handled.');
            return;
        }

        $this->refundId   = $refund->id;
        $this->actionType = $type;
        $this->method     = 'bkash';
        $this->reference  = '';
        $this->resetErrorBag();
    }

    public function closeAction(): void
    {
        $this->refundId   = null;
        $this->actionType = '';
        $this->reference  = '';
        $this->resetErrorBag();
    }

    public function getSelectedRefundProperty(): ?Refund
    {
        return $this->refundId
            ? Refund::query()->with(['order', 'customer', 'restaurant'])->find($this->refundId)
            : null;
    }

    public function confirmAction(RefundService $service): void
    {
        $this->authorizeAdmin();

        if ($this->refundId === null) {
            return;
        }

        if ($this->actionType === 'pay') {
            $this->validate([
                'method'    => ['required', Rule::in(Refund::METHODS)],
                'reference' => ['nullable', 'string', 'max:100'],
            ]);

            $done = $service->markPaid($this->refundId, Auth::user(), $this->method, $this->reference);
            $okMsg = 'Refund marked as paid. The customer has been notified.';
        } elseif ($this->actionType === 'cancel') {
            $done  = $service->cancel($this->refundId, Auth::user());
            $okMsg = 'Refund cancelled.';
        } else {
            return;
        }

        $this->closeAction();

        if ($done) {
            session()->flash('success', $okMsg);
        } else {
            session()->flash('error', 'This refund was already changed (maybe by another admin). The list has been refreshed.');
        }
    }

    // ── Render ───────────────────────────────────────────────
    public function render()
    {
        $term = trim($this->search);

        $refunds = Refund::query()
            ->with(['order:id,order_number,total_amount', 'customer:id,name,phone', 'restaurant:id,name'])
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($term !== '', function ($q) use ($term) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

                $q->where(function ($w) use ($like) {
                    $w->where('refund_number', 'like', $like)
                        ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', $like))
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like)->orWhere('phone', 'like', $like));
                });
            })
            ->latest()
            ->paginate(10);

        $counts = Refund::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.admin.refund-component', [
            'refunds' => $refunds,
            'counts'  => $counts,
        ])->layout('layouts.admin', [
            'title'           => 'Refunds | KhaiKhai',
            'breadcrumbTitle' => 'Refunds',
        ]);
    }
}
