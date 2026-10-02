@php $useFa = auth()->user()?->role === 'customer'; @endphp
<div class="kk-notif-page">
    {{-- Colors are neutral/transparent on purpose, so the same page looks right
         in the vendor, rider and customer layouts (and in dark mode). --}}
    <style>
        .kk-notif-page { max-width: 760px; margin: 0 auto; padding: 16px; }
        .kk-notif-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
        .kk-notif-head h5 { margin: 0; font-weight: 700; }
        .kk-notif-tabs { display: inline-flex; gap: 6px; }
        .kk-notif-btn { border: 1px solid rgba(128,128,128,.35); background: transparent; color: inherit; border-radius: 8px; padding: 5px 12px; font-size: 13px; cursor: pointer; }
        .kk-notif-btn.active { background: rgba(255,107,53,.15); border-color: rgba(255,107,53,.6); font-weight: 600; }
        .kk-notif-row { display: flex; gap: 12px; align-items: flex-start; padding: 12px; border: 1px solid rgba(128,128,128,.25); border-radius: 12px; margin-bottom: 8px; }
        .kk-notif-row.unread { background: rgba(255,107,53,.08); border-color: rgba(255,107,53,.35); }
        .kk-notif-icon { flex: 0 0 auto; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(128,128,128,.15); }
        .kk-notif-icon.high { background: rgba(255,107,53,.2); }
        .kk-notif-main { flex: 1 1 auto; min-width: 0; }
        .kk-notif-title { font-weight: 600; display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .kk-notif-new { font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 99px; background: rgba(255,107,53,.9); color: #fff; }
        .kk-notif-body { font-size: 13px; opacity: .85; margin-top: 2px; word-break: break-word; }
        .kk-notif-time { font-size: 12px; opacity: .6; margin-top: 4px; }
        .kk-notif-actions { display: flex; gap: 6px; flex: 0 0 auto; }
        .kk-notif-actions button { border: none; background: transparent; color: inherit; opacity: .7; cursor: pointer; padding: 4px; border-radius: 8px; }
        .kk-notif-actions button:hover { opacity: 1; background: rgba(128,128,128,.15); }
        .kk-notif-empty { text-align: center; padding: 40px 10px; opacity: .6; }
    </style>

    <div class="kk-notif-head">
        <h5>Notifications @if ($unreadCount > 0)<span class="kk-notif-new">{{ $unreadCount }} unread</span>@endif</h5>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="kk-notif-tabs">
                <button type="button" class="kk-notif-btn {{ $filter === 'all' ? 'active' : '' }}" wire:click="setFilter('all')">All</button>
                <button type="button" class="kk-notif-btn {{ $filter === 'unread' ? 'active' : '' }}" wire:click="setFilter('unread')">Unread</button>
            </div>
            @if ($unreadCount > 0)
                <button type="button" class="kk-notif-btn" wire:click="markAllAsRead">Mark all as read</button>
            @endif
        </div>
    </div>

    @forelse ($notifications as $n)
        <div class="kk-notif-row {{ $n->isRead() ? '' : 'unread' }}" wire:key="notif-{{ $n->id }}">
            <div class="kk-notif-icon {{ $n->priority === 'high' ? 'high' : '' }}">
                @if ($useFa)
                    <i class="fa {{ $n->faIconClass() }}"></i>
                @else
                    <span class="material-icons-round" style="font-size:20px">{{ $n->iconName() }}</span>
                @endif
            </div>

            <div class="kk-notif-main">
                <div class="kk-notif-title">
                    {{ $n->title }}
                    @if (! $n->isRead())<span class="kk-notif-new">NEW</span>@endif
                </div>
                <div class="kk-notif-body">{{ $n->body }}</div>
                <div class="kk-notif-time">{{ $n->created_at->diffForHumans() }}</div>
                @if ($n->safeActionUrl())
                    <a href="{{ $n->safeActionUrl() }}" wire:click.prevent="open({{ $n->id }})" class="small">View details</a>
                @endif
            </div>

            <div class="kk-notif-actions">
                @if (! $n->isRead())
                    <button type="button" title="Mark as read" wire:click="markAsRead({{ $n->id }})">
                        @if ($useFa)<i class="fa fa-check"></i>@else<span class="material-icons-round" style="font-size:20px">mark_email_read</span>@endif
                    </button>
                @endif
                <button type="button" title="Delete" wire:click="deleteNotification({{ $n->id }})" wire:confirm="Delete this notification?">
                    @if ($useFa)<i class="fa fa-trash"></i>@else<span class="material-icons-round" style="font-size:20px">delete</span>@endif
                </button>
            </div>
        </div>
    @empty
        <div class="kk-notif-empty">
            @if ($useFa)<i class="fa fa-bell-slash" style="font-size:36px"></i>@else<span class="material-icons-round" style="font-size:42px">notifications_none</span>@endif
            <p class="mb-0">No notifications to show.</p>
        </div>
    @endforelse

    <div class="mt-3">{{ $notifications->links() }}</div>
</div>
