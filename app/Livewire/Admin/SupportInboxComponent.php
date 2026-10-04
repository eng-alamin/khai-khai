<?php

namespace App\Livewire\Admin;

use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin screen: customer support tickets - read them and answer.
 * Route: /admin/support  (name: admin.support)
 */
class SupportInboxComponent extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search       = '';
    public string $statusFilter = 'open';   // '' = all

    // ── Ticket modal ─────────────────────────────────────────
    #[Locked]
    public ?int   $ticketId = null;
    public string $reply    = '';
    public string $status   = 'resolved';

    protected $queryString = [
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => 'open'],
    ];

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
    }

    // ── Open / close ─────────────────────────────────────────
    public function openTicket(int $id): void
    {
        $this->authorizeAdmin();

        $ticket = SupportTicket::query()->findOrFail($id);

        $this->ticketId = $ticket->id;
        $this->reply    = ''; // every answer is a new message in the thread
        $this->status   = in_array($ticket->status, SupportTicketService::SETTABLE, true)
            ? $ticket->status
            : 'resolved';
        $this->resetErrorBag();
    }

    public function closeTicket(): void
    {
        $this->ticketId = null;
        $this->reply    = '';
        $this->status   = 'resolved';
        $this->resetErrorBag();
    }

    public function getSelectedTicketProperty(): ?SupportTicket
    {
        return $this->ticketId
            ? SupportTicket::query()->with(['customer', 'order', 'messages.sender'])->find($this->ticketId)
            : null;
    }

    // ── Actions ──────────────────────────────────────────────
    public function sendReply(SupportTicketService $service): void
    {
        $this->authorizeAdmin();

        if ($this->ticketId === null) {
            return;
        }

        $this->validate([
            'reply'  => ['required', 'string', 'min:3', 'max:2000'],
            'status' => ['required', Rule::in(SupportTicketService::SETTABLE)],
        ], [
            'reply.required' => 'Please write the reply. The customer will see it.',
        ]);

        $done = $service->reply($this->ticketId, Auth::user(), trim($this->reply), $this->status);

        $this->closeTicket();

        if ($done) {
            session()->flash('success', 'Reply saved. The customer has been notified.');
        } else {
            session()->flash('error', 'This ticket is closed (or was already handled). The list has been refreshed.');
        }
    }

    public function changeStatus(SupportTicketService $service): void
    {
        $this->authorizeAdmin();

        if ($this->ticketId === null) {
            return;
        }

        $this->validate(['status' => ['required', Rule::in(SupportTicketService::SETTABLE)]]);

        $done = $service->setStatus($this->ticketId, Auth::user(), $this->status);

        $this->closeTicket();

        if ($done) {
            session()->flash('success', 'Ticket status updated.');
        } else {
            session()->flash('error', 'Status was not changed (the ticket is closed or already has this status).');
        }
    }

    // ── Render ───────────────────────────────────────────────
    public function render()
    {
        $term = trim($this->search);

        $tickets = SupportTicket::query()
            ->withCount('messages')
            ->with('customer:id,name,phone')
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($term !== '', function ($q) use ($term) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

                $q->where(function ($w) use ($like) {
                    $w->where('ticket_number', 'like', $like)
                        ->orWhere('subject', 'like', $like)
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like)->orWhere('phone', 'like', $like));
                });
            })
            ->latest()
            ->paginate(10);

        $counts = SupportTicket::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.admin.support-inbox-component', [
            'tickets' => $tickets,
            'counts'  => $counts,
        ])->layout('layouts.admin', [
            'title'           => 'Support Inbox | KhaiKhai',
            'breadcrumbTitle' => 'Support Inbox',
        ]);
    }
}
