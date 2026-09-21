<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Notifications</h4>
            <small class="text-muted">{{ $unreadCount }} unread</small>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group">
                <button type="button" class="btn btn-sm {{ $filter === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}" wire:click="setFilter('all')">All</button>
                <button type="button" class="btn btn-sm {{ $filter === 'unread' ? 'btn-dark' : 'btn-outline-secondary' }}" wire:click="setFilter('unread')">Unread</button>
            </div>
            @if($unreadCount > 0)
                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="markAllAsRead">
                    Mark all as read
                </button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                <div class="list-group-item d-flex justify-content-between align-items-start {{ $notification->isRead() ? '' : 'bg-light' }}" wire:key="notification-{{ $notification->id }}">
                    <div class="d-flex gap-3">
                        <span class="badge rounded-pill text-bg-{{ $notification->priority === 'high' ? 'danger' : ($notification->priority === 'low' ? 'secondary' : 'primary') }} align-self-start mt-1">
                            &nbsp;
                        </span>
                        <div>
                            <div class="fw-semibold">
                                {{ $notification->title }}
                                @if(! $notification->isRead())
                                    <span class="badge bg-primary ms-1" style="font-size:.6rem;">NEW</span>
                                @endif
                            </div>
                            <div class="text-muted small">{{ $notification->body }}</div>
                            @if($notification->action_url)
                                <a href="{{ $notification->action_url }}" class="small">View details</a>
                            @endif
                            <div class="text-muted" style="font-size:.75rem;">{{ $notification->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        @if(! $notification->isRead())
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="markAsRead({{ $notification->id }})">
                                Mark as read
                            </button>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                wire:click="deleteNotification({{ $notification->id }})"
                                wire:confirm="Delete this notification?">
                            Delete
                        </button>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-5">
                    No notifications to show.
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-3">
        {{ $notifications->links() }}
    </div>
</div>