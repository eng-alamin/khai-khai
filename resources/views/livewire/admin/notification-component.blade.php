<div>

    {{-- ── Flash ── --}}
    @if (session('success'))
        <div class="alert alert-success">
            <i class="material-icons-round">check_circle</i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
        </div>
    @endif

    {{-- ── Top Bar ── --}}
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">Notifications</div>
            <span class="total-badge">{{ $unreadCount }} Unread</span>
        </div>
        <div class="topbar-right">
            <div class="filter-tabs">
                <button type="button" class="filter-tab {{ $filter === 'all' ? 'active' : '' }}" wire:click="setFilter('all')">All</button>
                <button type="button" class="filter-tab {{ $filter === 'unread' ? 'active' : '' }}" wire:click="setFilter('unread')">Unread</button>
            </div>
            @if($unreadCount > 0)
                <button type="button" class="btn-secondary" wire:click="markAllAsRead">
                    Mark all as read
                </button>
            @endif
        </div>
    </div>

    {{-- ── List ── --}}
    <div class="table-card">
        @forelse ($notifications as $notification)
            <div class="notif-row {{ $notification->isRead() ? '' : 'unread' }}" wire:key="notification-{{ $notification->id }}">
                <span class="notif-dot {{ $notification->priority === 'high' ? 'high' : ($notification->priority === 'low' ? 'low' : 'medium') }}"></span>
                <div class="notif-body">
                    <div class="notif-title">
                        {{ $notification->title }}
                        @if(! $notification->isRead())
                            <span class="status-badge new">NEW</span>
                        @endif
                    </div>
                    <div class="card-desc">{{ $notification->body }}</div>
                    @if($notification->action_url)
                        <a href="{{ $notification->action_url }}" class="notif-link">View details</a>
                    @endif
                    <div class="meta-item">{{ $notification->created_at->diffForHumans() }}</div>
                </div>
                <div class="notif-actions">
                    @if(! $notification->isRead())
                        <button type="button" class="icon-btn" title="Mark as read" wire:click="markAsRead({{ $notification->id }})">
                            <span class="material-icons-round">mark_email_read</span>
                        </button>
                    @endif
                    <button type="button" class="btn-delete" title="Delete"
                            wire:click="deleteNotification({{ $notification->id }})"
                            wire:confirm="Delete this notification?">
                        <span class="material-icons-round">delete</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="empty">
                <i class="material-icons-round empty-icon">notifications_none</i>
                <p>No notifications to show.</p>
            </div>
        @endforelse

        <div class="pagination">
            {{ $notifications->links() }}
        </div>
    </div>

</div>
