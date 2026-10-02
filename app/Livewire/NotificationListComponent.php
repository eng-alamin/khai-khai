<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Full notification list for vendor, rider and customer.
 * One component, three routes: the layout follows the user's role.
 * Only the logged-in user's OWN notifications are ever read or changed
 * (the admin keeps its own page, which also shows the shared admin rows).
 */
class NotificationListComponent extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $filter = 'all'; // all | unread

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['all', 'unread'], true) ? $filter : 'all';
        $this->resetPage();
    }

    public function markAsRead(int $id): void
    {
        Notification::query()
            ->forUser(Auth::id())
            ->whereKey($id)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /** Mark as read, then go to the page the notification points to. */
    public function open(int $id)
    {
        $notification = Notification::query()
            ->forUser(Auth::id())
            ->whereKey($id)
            ->first();

        if (! $notification) {
            return null;
        }

        if (! $notification->isRead()) {
            $notification->update(['read_at' => now()]);
        }

        $url = $notification->safeActionUrl();

        return $url ? $this->redirect($url) : null;
    }

    public function markAllAsRead(): void
    {
        Notification::query()
            ->forUser(Auth::id())
            ->unread()
            ->update(['read_at' => now()]);
    }

    public function deleteNotification(int $id): void
    {
        Notification::query()
            ->forUser(Auth::id())
            ->whereKey($id)
            ->delete();
    }

    /** layouts.<role>; customer is the fallback. */
    private function layoutName(): string
    {
        return match (Auth::user()?->role) {
            'vendor' => 'layouts.vendor',
            'rider'  => 'layouts.rider',
            default  => 'layouts.customer',
        };
    }

    public function render()
    {
        $query = Notification::query()
            ->forUser(Auth::id())
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($this->filter === 'unread') {
            $query->unread();
        }

        $unreadCount = Notification::query()
            ->forUser(Auth::id())
            ->unread()
            ->count();

        return view('livewire.notification-list-component', [
            'notifications' => $query->paginate(15),
            'unreadCount'   => $unreadCount,
        ])->layout($this->layoutName(), [
            'title'           => 'Notifications | KhaiKhai',
            'breadcrumbTitle' => 'Notifications',
        ]);
    }
}
