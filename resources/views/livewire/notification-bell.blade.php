<div>
    @if ($role === 'guest')
        <div></div>
    @elseif ($role === 'customer')
        <a href="{{ route('customer.notifications') }}"
        class="topbar-btn position-relative"
        title="{{ $unreadCount > 0 ? $unreadCount . ' unread' : 'Notifications' }}"
        wire:poll.20s>
            <i class="fa fa-bell"></i>
            @if ($unreadCount > 0)
                <span class="dot"></span>
            @endif
        </a>
    @else
        @php $isVendor = $role === 'vendor'; @endphp

        <div wire:poll.20s>
            <div class="dropdown" wire:ignore.self>
                <button class="icon-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" wire:ignore.self>
                    <i class="fa fa-bell"></i>
                    @if ($unreadCount > 0)
                        <span class="notif-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </button>
                <ul class="dropdown-menu dropdown-menu-end notif-dropdown-menu" wire:ignore.self>
                    <li>
                        <div class="notif-header">
                            <h6>{{ $isVendor ? 'নোটিফিকেশন' : 'Notifications' }}</h6>
                        </div>
                    </li>

                    @forelse ($latest as $n)
                        <li wire:key="bell-{{ $n->id }}">
                            <a class="notif-item"
                            href="{{ $n->safeActionUrl() ?? '#' }}"
                            wire:click.prevent="open({{ $n->id }})"
                            style="{{ $n->isRead() ? 'opacity:.65' : '' }}">
                                <div class="notif-icon {{ $n->priority === 'high' ? 'cart' : 'podcast' }}">
                                    <span class="material-icons-round">{{ $n->iconName() }}</span>
                                </div>
                                <div class="notif-text">
                                    <strong>{{ $n->title }}</strong>
                                    <span>{{ $n->created_at->diffForHumans() }}</span>
                                </div>
                            </a>
                        </li>
                    @empty
                        <li>
                            <div class="notif-item text-muted">
                                {{ $isVendor ? 'কোনো নোটিফিকেশন নেই' : 'No notifications yet' }}
                            </div>
                        </li>
                    @endforelse

                    <li>
                        <div class="notif-footer d-flex justify-content-between align-items-center gap-2">
                            <a href="{{ $listUrl }}">{{ $isVendor ? 'সব দেখুন' : 'View all' }}</a>
                            @if ($unreadCount > 0)
                                <a href="#" wire:click.prevent="markAllAsRead">
                                    {{ $isVendor ? 'সব পড়া হয়েছে' : 'Mark all read' }}
                                </a>
                            @endif
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    @endif
</div>
