<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Top-bar bell for vendor, rider and customer layouts (the admin layout keeps
 * its own bell). Shows only the logged-in user's own notifications, refreshes
 * every 20 seconds and links to the full list page for the user's role.
 */
class NotificationBell extends Component
{
    private const LATEST_LIMIT = 5;

    /** Mark one of MY notifications as read. */
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

    /** Full-list page for the current user's role. */
    private function listUrl(): string
    {
        if (! Auth::check()) {
            return '#';
        }

        return match (Auth::user()?->role) {
            'vendor' => route('vendor.notifications'),
            'rider'  => route('rider.notifications'),
            default  => route('customer.notifications'),
        };
    }

    public function render()
    {
        $userId = Auth::id();
        $role   = Auth::user()?->role;

        // The customer layout is also shown to guests: nothing to show them.
        if ($userId === null) {
            return view('livewire.notification-bell', [
                'role'        => 'guest',
                'unreadCount' => 0,
                'latest'      => collect(),
                'listUrl'     => '#',
            ]);
        }

        $unreadCount = Notification::query()
            ->forUser($userId)
            ->unread()
            ->count();

        // The customer top bar only shows a link with a dot, so the list is
        // loaded only for vendor and rider.
        $latest = $role === 'customer'
            ? collect()
            : Notification::query()
                ->forUser($userId)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(self::LATEST_LIMIT)
                ->get();

        return view('livewire.notification-bell', [
            'role'        => $role,
            'unreadCount' => $unreadCount,
            'latest'      => $latest,
            'listUrl'     => $this->listUrl(),
        ]);
    }
}
