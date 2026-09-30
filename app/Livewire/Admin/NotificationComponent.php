<?php

namespace App\Livewire\Admin;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationComponent extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $filter = 'all'; // all | unread

    public function markAsRead(int $id): void
    {
        $notification = Notification::query()
            ->where(function ($q) {
                $q->whereNull('user_id')->orWhere('user_id', Auth::id());
            })
            ->findOrFail($id);

        if (! $notification->isRead()) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function markAllAsRead(): void
    {
        Notification::query()
            ->where(function ($q) {
                $q->whereNull('user_id')->orWhere('user_id', Auth::id());
            })
            ->unread()
            ->update(['read_at' => now()]);

        session()->flash('success', 'All notifications marked as read.');
    }

    public function deleteNotification(int $id): void
    {
        $notification = Notification::query()
            ->where(function ($q) {
                $q->whereNull('user_id')->orWhere('user_id', Auth::id());
            })
            ->findOrFail($id);

        $notification->delete();

        session()->flash('success', 'Notification deleted.');
    }

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['all', 'unread'], true) ? $filter : 'all';
        $this->resetPage();
    }

    public function render()
    {
        $query = Notification::query()
            ->where(function ($q) {
                $q->whereNull('user_id')->orWhere('user_id', Auth::id());
            })
            ->latest();

        if ($this->filter === 'unread') {
            $query->unread();
        }

        $notifications = $query->paginate(15);

        $unreadCount = Notification::query()
            ->where(function ($q) {
                $q->whereNull('user_id')->orWhere('user_id', Auth::id());
            })
            ->unread()
            ->count();

        return view('livewire.admin.notification-component', [
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
        ])->layout('layouts.admin', [
            'title'           => 'Notifications | KhaiKhai',
            'breadcrumbTitle' => 'Notifications',
        ]);
    }
}