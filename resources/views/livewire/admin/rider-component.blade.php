<div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="rider-alert rider-alert-success">
            <span class="material-icons-round">check_circle</span>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="rider-alert-close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="rider-alert rider-alert-error">
            <span class="material-icons-round">error</span>
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="rider-alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="rider-topbar">
            <div class="rider-topbar-title">
                <span class="title-emoji">🏍️</span>
                Rider Management
            </div>
            <button class="btn-new-rider" wire:click="openAddModal">
                <span class="plus-icon">＋</span>
                Add Rider
            </button>
        </div>

        {{-- ── Stats Row ── --}}
        <div class="rider-stats">
            <div class="rider-stat-card">
                <div class="rider-stat-icon" style="background:rgba(232,63,140,.12);color:var(--pink)">
                    <span class="material-icons-round">group</span>
                </div>
                <div>
                    <div class="rider-stat-value">{{ $stats['total'] }}</div>
                    <div class="rider-stat-label">Total Riders</div>
                </div>
            </div>
            <div class="rider-stat-card">
                <div class="rider-stat-icon" style="background:rgba(34,197,94,.12);color:#16a34a">
                    <span class="material-icons-round" style="font-size:.85rem">circle</span>
                </div>
                <div>
                    <div class="rider-stat-value">{{ $stats['online'] }}</div>
                    <div class="rider-stat-label">Online Now</div>
                </div>
            </div>
            <div class="rider-stat-card">
                <div class="rider-stat-icon" style="background:rgba(245,158,11,.12);color:#f59e0b">
                    <span class="material-icons-round">hourglass_empty</span>
                </div>
                <div>
                    <div class="rider-stat-value">{{ $stats['pending'] }}</div>
                    <div class="rider-stat-label">Pending Approval</div>
                </div>
            </div>
            <div class="rider-stat-card">
                <div class="rider-stat-icon" style="background:rgba(239,68,68,.12);color:#ef4444">
                    <span class="material-icons-round">block</span>
                </div>
                <div>
                    <div class="rider-stat-value">{{ $stats['inactive'] }}</div>
                    <div class="rider-stat-label">Inactive</div>
                </div>
            </div>
        </div>

        {{-- ── Search ── --}}
        <div class="rider-search">
            <div class="rider-search-inner">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search name, phone or email...">
            </div>
        </div>

        {{-- ── Filter Chips ── --}}
        <div class="rider-filters">
            <label class="filter-chip {{ $statusFilter === '' ? 'active' : '' }}">
                <input type="radio" wire:model.live="statusFilter" value=""> All
            </label>
            <label class="filter-chip {{ $statusFilter === 'online' ? 'active' : '' }}">
                <input type="radio" wire:model.live="statusFilter" value="online"> 🟢 Online
            </label>
            <label class="filter-chip {{ $statusFilter === 'offline' ? 'active' : '' }}">
                <input type="radio" wire:model.live="statusFilter" value="offline"> ⚪ Offline
            </label>
            <label class="filter-chip {{ $statusFilter === 'pending' ? 'active' : '' }}">
                <input type="radio" wire:model.live="statusFilter" value="pending"> ⏳ Pending
            </label>
            <label class="filter-chip {{ $statusFilter === 'inactive' ? 'active' : '' }}">
                <input type="radio" wire:model.live="statusFilter" value="inactive"> 🔴 Inactive
            </label>

            <label class="filter-chip {{ $zoneFilter === '' ? 'active' : '' }}">
                <input type="radio" wire:model.live="zoneFilter" value=""> All Zones
            </label>
            @foreach($this->zones as $z)
                <label class="filter-chip {{ $zoneFilter === $z ? 'active' : '' }}">
                    <input type="radio" wire:model.live="zoneFilter" value="{{ $z }}">
                    {{ $z }}
                </label>
            @endforeach
        </div>

        {{-- ── Rider Cards ── --}}
        @forelse($riders as $rider)
            @php
                $p = $rider->riderProfile;
                $today = $todayCounts[$rider->id] ?? 0;
            @endphp

            <div class="rider-card" wire:key="rider-{{ $rider->id }}">
                {{-- Top Row --}}
                <div class="rider-card-top">
                    <div class="rider-card-thumb">
                        {{ strtoupper(substr($rider->name, 0, 2)) }}
                    </div>
                    <div class="rider-card-info">
                        <div class="rider-card-title">{{ $rider->name }}</div>
                        <div class="rider-card-desc">{{ $rider->phone }} @if($rider->email) · {{ $rider->email }} @endif</div>
                    </div>
                    <div class="rider-badges-col">
                        @if(! $p?->is_approved)
                            <span class="rider-status-badge pending">Pending</span>
                        @elseif($p?->is_online)
                            <span class="rider-status-badge available">Online</span>
                        @else
                            <span class="rider-status-badge offline">Offline</span>
                        @endif
                    </div>
                </div>

                {{-- Meta --}}
                <div class="rider-card-meta">
                    @if($p?->zone)
                        <span class="rider-zone-pill">
                            <span class="material-icons-round">place</span>
                            {{ $p->zone }}
                        </span>
                    @endif
                    @if($p?->vehicle_type)
                        <span class="rider-meta-item">
                            <span class="material-icons-round">two_wheeler</span>
                            {{ ucfirst(str_replace('_', ' ', $p->vehicle_type)) }}
                            @if($p->vehicle_plate) · {{ $p->vehicle_plate }} @endif
                        </span>
                    @endif
                    <span class="rider-meta-item">
                        <span class="material-icons-round">local_shipping</span>
                        {{ $today }} today
                    </span>
                    <span class="rider-meta-item">
                        <span class="material-icons-round">star</span>
                        {{ $p?->avg_rating ? number_format($p->avg_rating, 1) : 'N/A' }}
                    </span>
                </div>

                {{-- Bottom Row --}}
                <div class="rider-card-bottom">
                    <div style="display:flex;align-items:center;gap:8px;">
                        @if($rider->is_active)
                            <span class="rider-account-badge active">Active Account</span>
                        @else
                            <span class="rider-account-badge inactive">Inactive Account</span>
                        @endif
                    </div>
                    <div class="rider-card-actions">
                        <button class="rider-btn-view"
                            wire:click="viewRider({{ $rider->id }})">
                            <span class="material-icons-round">visibility</span>
                            View
                        </button>

                        @if(! $p?->is_approved)
                            <button class="rider-btn-approve"
                                wire:click="confirmApprove({{ $rider->id }})">
                                <span class="material-icons-round">check_circle_outline</span>
                                Approve
                            </button>
                        @endif

                        @if($rider->is_active)
                            <button class="rider-btn-block"
                                wire:click="confirmStatusChange({{ $rider->id }}, 'deactivate')" title="Deactivate">
                                <span class="material-icons-round">block</span>
                            </button>
                        @else
                            <button class="rider-btn-block unblock"
                                wire:click="confirmStatusChange({{ $rider->id }}, 'activate')" title="Activate">
                                <span class="material-icons-round">check_circle</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

        @empty
            <div class="rider-empty">
                <span class="material-icons-round rider-empty-icon">directions_bike</span>
                <p>No riders found.</p>
                <button class="btn-new-rider" wire:click="openAddModal">+ Add New Rider</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($riders->hasPages())
            <div class="rider-pagination">
                <small>Showing {{ $riders->firstItem() ?? 0 }}–{{ $riders->lastItem() ?? 0 }} of {{ $riders->total() }} total</small>
                {{ $riders->links() }}
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Add Rider Modal
    ══════════════════════════════════════ --}}
    @if($showAddModal)
        <div class="rider-modal-backdrop" wire:click.self="$set('showAddModal', false)">
            <div class="rider-modal">

                <div class="rider-modal-drag"></div>

                <div class="rider-modal-header">
                    <div class="rider-modal-title">🏍️ Add New Rider</div>
                    <button class="rider-modal-close" wire:click="$set('showAddModal', false)">✕</button>
                </div>

                <div class="rider-modal-body">

                    <div class="rider-section-label">Account Information</div>

                    <div class="rider-row">
                        <div class="rider-col">
                            <div class="rider-form-group">
                                <label class="rider-form-label">Full Name <span class="req">*</span></label>
                                <input type="text"
                                    class="rider-form-control @error('name') is-invalid @enderror"
                                    wire:model="name"
                                    placeholder="e.g. Karim Mia">
                                @error('name') <div class="rider-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="rider-col">
                            <div class="rider-form-group">
                                <label class="rider-form-label">Phone <span class="req">*</span></label>
                                <input type="text"
                                    class="rider-form-control @error('phone') is-invalid @enderror"
                                    wire:model="phone"
                                    placeholder="017XXXXXXXX">
                                @error('phone') <div class="rider-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="rider-row">
                        <div class="rider-col">
                            <div class="rider-form-group">
                                <label class="rider-form-label">Email</label>
                                <input type="email"
                                    class="rider-form-control @error('email') is-invalid @enderror"
                                    wire:model="email"
                                    placeholder="Optional">
                                @error('email') <div class="rider-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="rider-col">
                            <div class="rider-form-group">
                                <label class="rider-form-label">Password <span class="req">*</span></label>
                                <input type="password"
                                    class="rider-form-control @error('password') is-invalid @enderror"
                                    wire:model="password"
                                    placeholder="Min. 6 characters">
                                @error('password') <div class="rider-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="rider-section-label" style="margin-top:18px">Vehicle & Zone</div>

                    <div class="rider-row">
                        <div class="rider-col">
                            <div class="rider-form-group">
                                <label class="rider-form-label">Vehicle Type <span class="req">*</span></label>
                                <select class="rider-form-control @error('vehicle_type') is-invalid @enderror" wire:model="vehicle_type">
                                    <option value="">Select type…</option>
                                    <option value="bicycle">Bicycle</option>
                                    <option value="motorcycle">Motorcycle</option>
                                    <option value="scooter">Scooter</option>
                                    <option value="electric_bike">Electric Bike</option>
                                </select>
                                @error('vehicle_type') <div class="rider-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="rider-col">
                            <div class="rider-form-group">
                                <label class="rider-form-label">Vehicle Plate</label>
                                <input type="text"
                                    class="rider-form-control @error('vehicle_plate') is-invalid @enderror"
                                    wire:model="vehicle_plate"
                                    placeholder="e.g. DHA-1234">
                                @error('vehicle_plate') <div class="rider-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="rider-row">
                        <div class="rider-col">
                            <div class="rider-form-group">
                                <label class="rider-form-label">License Number</label>
                                <input type="text"
                                    class="rider-form-control @error('license_number') is-invalid @enderror"
                                    wire:model="license_number"
                                    placeholder="Optional">
                                @error('license_number') <div class="rider-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="rider-col">
                            <div class="rider-form-group">
                                <label class="rider-form-label">NID Number</label>
                                <input type="text"
                                    class="rider-form-control @error('nid_number') is-invalid @enderror"
                                    wire:model="nid_number"
                                    placeholder="Optional">
                                @error('nid_number') <div class="rider-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="rider-form-group">
                        <label class="rider-form-label">Zone / Area</label>
                        <input type="text"
                            class="rider-form-control @error('zone') is-invalid @enderror"
                            wire:model="zone"
                            placeholder="e.g. Dhaka-Metro">
                        @error('zone') <div class="rider-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>

                <div class="rider-modal-footer">
                    <button class="btn-rider-secondary"
                        wire:click="$set('showAddModal', false)">Cancel</button>
                    <button class="btn-rider-primary"
                        wire:click="saveRider"
                        wire:loading.attr="disabled">
                        <span wire:loading wire:target="saveRider" class="spinner-sm"></span>
                        Save Rider
                    </button>
                </div>

            </div>
        </div>
    @endif


    {{-- ══════════════════════════════════════
         View Rider Details Modal
    ══════════════════════════════════════ --}}
    @if($showView && $this->riderView)
        @php
            $r = $this->riderView;
            $p = $r->riderProfile;
            $totalEarningsPaisa = $r->riderEarnings?->sum('amount');
        @endphp
        <div class="rider-modal-backdrop" wire:click.self="$set('showView', false)">
            <div class="rider-modal">

                <div class="rider-modal-drag"></div>

                <div class="rider-modal-header">
                    <div class="rider-modal-title">🔍 Rider Details</div>
                    <button class="rider-modal-close" wire:click="$set('showView', false)">✕</button>
                </div>

                <div class="rider-modal-body">

                    {{-- Profile card --}}
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px;">
                        <div class="rider-card-thumb" style="width:64px;height:64px;font-size:1.25rem;">
                            {{ strtoupper(substr($r->name, 0, 2)) }}
                        </div>
                        <div style="flex:1">
                            <div style="font-size:1.05rem;font-weight:700;color:var(--dark)">{{ $r->name }}</div>
                            <div style="font-size:.8rem;color:var(--muted)">{{ $r->email ?? '—' }}</div>
                            <div style="font-size:.8rem;color:var(--muted)">{{ $r->phone }}</div>
                        </div>
                        <div style="text-align:right">
                            @if(! $p?->is_approved)
                                <span class="rider-status-badge pending">Pending</span>
                            @elseif($p?->is_online)
                                <span class="rider-status-badge available">Online</span>
                            @else
                                <span class="rider-status-badge offline">Offline</span>
                            @endif
                            <div style="margin-top:4px">
                                @if($r->is_active)
                                    <span class="rider-account-badge active">Active</span>
                                @else
                                    <span class="rider-account-badge inactive">Inactive</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Stats --}}
                    <div class="rider-view-stats">
                        <div class="rider-view-stat-item">
                            <div class="rider-view-stat-value" style="color:var(--pink)">{{ $p?->total_deliveries ?? 0 }}</div>
                            <div class="rider-view-stat-label">Total Deliveries</div>
                        </div>
                        <div class="rider-view-stat-item">
                            <div class="rider-view-stat-value" style="color:#6366f1">৳{{ number_format(($totalEarningsPaisa ?? 0) / 100, 2) }}</div>
                            <div class="rider-view-stat-label">Total Earnings</div>
                        </div>
                        <div class="rider-view-stat-item">
                            <div class="rider-view-stat-value" style="color:#f59e0b">
                                {{ $p?->avg_rating ? number_format($p->avg_rating, 1) : 'N/A' }}
                            </div>
                            <div class="rider-view-stat-label">Avg Rating</div>
                        </div>
                    </div>

                    {{-- Info table --}}
                    <div class="rider-section-label" style="margin-top:18px">Profile Information</div>
                    @php
                        $info = [
                            'Zone'           => $p?->zone ?? '—',
                            'Vehicle Type'   => $p?->vehicle_type ? ucfirst(str_replace('_', ' ', $p->vehicle_type)) : '—',
                            'Vehicle Plate'  => $p?->vehicle_plate  ?? '—',
                            'License Number' => $p?->license_number ?? '—',
                            'NID Number'     => $p?->nid_number     ?? '—',
                            'Approval'       => $p?->is_approved ? 'Approved' : 'Pending',
                            'Joined'         => $r->created_at?->format('d M Y') ?? '—',
                            'Last Updated'   => $p?->updated_at?->format('d M Y') ?? '—',
                        ];
                    @endphp
                    <div class="rider-detail-grid">
                        @foreach($info as $label => $value)
                            <div class="rider-detail-item">
                                <span class="rider-detail-label">{{ $label }}</span>
                                <span class="rider-detail-value">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>

                </div>

                <div class="rider-modal-footer">
                    <button class="btn-rider-secondary" wire:click="$set('showView', false)">Close</button>
                </div>

            </div>
        </div>
    @endif


    {{-- ══════════════════════════════════════
         Approve Confirmation Modal
    ══════════════════════════════════════ --}}
    @if($showApprove)
        <div class="rider-modal-backdrop">
            <div class="rider-confirm-modal">
                <div class="rider-confirm-icon" style="background:rgba(245,158,11,.12)">
                    <span class="material-icons-round" style="color:#d97706">check_circle_outline</span>
                </div>
                <h6>Approve This Rider?</h6>
                <p>The rider will be allowed to receive delivery orders.</p>
                <div class="rider-confirm-actions">
                    <button class="btn-cancel" wire:click="$set('showApprove', false)">Cancel</button>
                    <button class="btn-confirm-warning"
                        wire:click="approveRider"
                        wire:loading.attr="disabled">
                        <span wire:loading wire:target="approveRider" class="spinner-sm"></span>
                        Approve
                    </button>
                </div>
            </div>
        </div>
    @endif


    {{-- ══════════════════════════════════════
         Activate / Deactivate Confirmation Modal
    ══════════════════════════════════════ --}}
    @if($showStatus)
        <div class="rider-modal-backdrop">
            <div class="rider-confirm-modal">
                @if($statusAction === 'deactivate')
                    <div class="rider-confirm-icon" style="background:rgba(239,68,68,.12)">
                        <span class="material-icons-round" style="color:#ef4444">block</span>
                    </div>
                    <h6>Deactivate Rider?</h6>
                    <p>This rider's account will be disabled and they won't be able to log in.</p>
                    <div class="rider-confirm-actions">
                        <button class="btn-cancel" wire:click="$set('showStatus', false)">Cancel</button>
                        <button class="btn-confirm-delete"
                            wire:click="changeStatus"
                            wire:loading.attr="disabled">
                            <span wire:loading wire:target="changeStatus" class="spinner-sm"></span>
                            Deactivate
                        </button>
                    </div>
                @else
                    <div class="rider-confirm-icon" style="background:rgba(34,197,94,.12)">
                        <span class="material-icons-round" style="color:#16a34a">check_circle</span>
                    </div>
                    <h6>Activate Rider?</h6>
                    <p>This rider's account will be re-enabled.</p>
                    <div class="rider-confirm-actions">
                        <button class="btn-cancel" wire:click="$set('showStatus', false)">Cancel</button>
                        <button class="btn-confirm-success"
                            wire:click="changeStatus"
                            wire:loading.attr="disabled">
                            <span wire:loading wire:target="changeStatus" class="spinner-sm"></span>
                            Activate
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>

@push('styles')
<style>

    .main-content {
        background: var(--bg);
        min-height: 100vh;
        padding: 0 0 80px;
        font-family: var(--font);
    }

    /* ── Top Bar ── */
    .rider-topbar {
        display: flex; align-items: center; justify-content: space-between;
        padding: 20px 16px 12px;
        background: var(--bg);
        position: sticky; top: 0; z-index: 50;
    }
    .rider-topbar-title {
        display: flex; align-items: center; gap: 8px;
        font-size: 1.18rem; font-weight: 700; color: var(--dark);
    }

    .btn-new-rider {
        display: inline-flex; align-items: center; gap: 5px;
        background: var(--pink); color: #fff; border: none;
        border-radius: 50px; padding: 9px 18px;
        font-size: .82rem; font-weight: 600; font-family: var(--font);
        cursor: pointer; box-shadow: 0 4px 18px rgba(255,61,139,.35);
        transition: var(--transition); letter-spacing: .02em;
    }
    .btn-new-rider:hover {
        background: #e02d7a; box-shadow: 0 6px 24px rgba(255,61,139,.45);
        transform: translateY(-1px);
    }
    .btn-new-rider .plus-icon { font-size: 1.1rem; font-weight: 400; line-height: 1; }

    /* ── Stats Row ── */
    .rider-stats {
        display: grid; grid-template-columns: repeat(4, 1fr);
        gap: 10px; padding: 0 16px 12px;
    }
    .rider-stat-card {
        background: var(--card-bg); border: 1.5px solid var(--border);
        border-radius: var(--radius-lg); padding: 12px;
        display: flex; align-items: center; gap: 10px;
        box-shadow: var(--shadow-card);
    }
    .rider-stat-icon {
        width: 38px; height: 38px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .rider-stat-value { font-size: 1.05rem; font-weight: 700; color: var(--dark); line-height: 1.1; }
    .rider-stat-label { font-size: .68rem; color: var(--muted); margin-top: 2px; }

    @media (max-width: 560px) {
        .rider-stats { grid-template-columns: repeat(2, 1fr); }
    }

    /* ── Search ── */
    .rider-search { padding: 0 16px 12px; }
    .rider-search-inner { position: relative; }
    .rider-search-inner .search-icon {
        position: absolute; left: 13px; top: 50%;
        transform: translateY(-50%); color: var(--muted);
        font-size: .95rem; pointer-events: none;
    }
    .rider-search-inner input {
        width: 100%; padding: 10px 12px 10px 36px;
        border: 1.5px solid var(--border); border-radius: 50px;
        font-family: var(--font); font-size: .82rem; color: var(--dark);
        background: var(--card-bg); outline: none;
        transition: var(--transition); box-sizing: border-box;
    }
    .rider-search-inner input:focus {
        border-color: var(--pink); box-shadow: 0 0 0 3px rgba(255,61,139,.1);
    }

    /* ── Filter Chips ── */
    .rider-filters {
        display: flex; gap: 8px; padding: 8px 16px 12px;
        overflow-x: auto; scrollbar-width: none;
    }
    .rider-filters::-webkit-scrollbar { display: none; }
    .filter-chip {
        flex-shrink: 0; display: inline-flex; align-items: center; gap: 4px;
        padding: 6px 14px; border-radius: 50px;
        border: 1.5px solid var(--border); background: var(--card-bg);
        color: var(--soft-dark); font-size: .76rem; font-family: var(--font);
        cursor: pointer; transition: var(--transition);
        font-weight: 500; white-space: nowrap;
    }
    .filter-chip.active, .filter-chip:hover {
        border-color: var(--pink); background: var(--pink-light); color: var(--pink);
    }
    .filter-chip input[type="radio"] { display: none; }

    /* ── Rider Card ── */
    .rider-card {
        margin: 0 16px 12px;
        background: var(--card-bg); border-radius: var(--radius-lg);
        padding: 16px; box-shadow: var(--shadow-card);
        border: 1.5px solid var(--border); transition: var(--transition);
        position: relative; overflow: hidden;
    }
    .rider-card::before {
        content: ''; position: absolute; left: 0; top: 0; bottom: 0;
        width: 4px; background: var(--pink); border-radius: 4px 0 0 4px;
        opacity: 0; transition: var(--transition);
    }
    .rider-card:hover {
        box-shadow: var(--shadow-hover); border-color: rgba(255,61,139,.2);
        transform: translateY(-2px);
    }
    .rider-card:hover::before { opacity: 1; }

    .rider-card-top {
        display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px;
    }
    .rider-card-thumb {
        flex-shrink: 0; width: 48px; height: 48px;
        border-radius: 50%; background: rgba(255,61,139,.12);
        color: var(--pink); font-size: .92rem; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
    }
    .rider-card-info { flex: 1; min-width: 0; }
    .rider-card-title {
        font-size: .98rem; font-weight: 700; color: var(--dark);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .rider-card-desc { font-size: .78rem; color: var(--muted); margin-top: 2px; }

    .rider-badges-col { flex-shrink: 0; }

    .rider-status-badge {
        display: inline-block; padding: 3px 10px; border-radius: 50px;
        font-size: .68rem; font-weight: 600; font-family: var(--font);
    }
    .rider-status-badge.available { background: #E8FAF0; color: #1A9453; }
    .rider-status-badge.offline   { background: #F0F0F3; color: #6b7280; }
    .rider-status-badge.pending   { background: #FFF8E1; color: #F57F17; }

    .rider-account-badge {
        display: inline-block; padding: 2px 9px; border-radius: 50px;
        font-size: .66rem; font-weight: 600;
    }
    .rider-account-badge.active   { background: #E8FAF0; color: #1A9453; }
    .rider-account-badge.inactive { background: #FFF0F0; color: #E53935; }

    .rider-card-meta {
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        margin-bottom: 4px;
    }
    .rider-zone-pill {
        display: inline-flex; align-items: center; gap: 3px;
        padding: 3px 10px; border-radius: 50px; font-size: .7rem; font-weight: 600;
        background: #EEF2FF; color: #4F46E5;
    }
    .rider-zone-pill .material-icons-round { font-size: .8rem; }
    .rider-meta-item {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: .74rem; color: var(--muted);
    }
    .rider-meta-item .material-icons-round { font-size: .85rem; }

    .rider-card-bottom {
        display: flex; align-items: center; justify-content: space-between;
        margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border);
        gap: 8px; flex-wrap: wrap;
    }
    .rider-card-actions { display: flex; align-items: center; gap: 6px; }

    .rider-btn-view {
        display: inline-flex; align-items: center; gap: 5px;
        background: #F0F4FF; border: none; border-radius: var(--radius-sm);
        padding: 7px 14px; font-size: .78rem; font-family: var(--font);
        color: #3B5BDB; cursor: pointer; transition: var(--transition); font-weight: 600;
    }
    .rider-btn-view:hover { background: #D9E4FF; }
    .rider-btn-view .material-icons-round { font-size: .9rem; }

    .rider-btn-approve {
        display: inline-flex; align-items: center; gap: 5px;
        background: #FFF8E1; border: none; border-radius: var(--radius-sm);
        padding: 7px 14px; font-size: .78rem; font-family: var(--font);
        color: #d97706; cursor: pointer; transition: var(--transition); font-weight: 600;
    }
    .rider-btn-approve:hover { background: #FFEDBB; }
    .rider-btn-approve .material-icons-round { font-size: .9rem; }

    .rider-btn-block {
        display: inline-flex; align-items: center; justify-content: center;
        background: #FFF0F0; border: none; border-radius: var(--radius-sm);
        padding: 7px 9px; color: #E53935; cursor: pointer; transition: var(--transition);
    }
    .rider-btn-block.unblock { background: #E8FAF0; color: #1A9453; }
    .rider-btn-block:hover { opacity: .8; }
    .rider-btn-block .material-icons-round { font-size: .9rem; }

    /* ── Empty ── */
    .rider-empty { text-align: center; padding: 60px 20px; }
    .rider-empty-icon { font-size: 3rem; opacity: .25; display: block; margin-bottom: 12px; }
    .rider-empty p { color: var(--muted); font-size: .88rem; margin: 0 0 16px; }

    /* ── Pagination ── */
    .rider-pagination {
        padding: 12px 16px;
        display: flex; align-items: center; justify-content: space-between;
    }
    .rider-pagination small { font-size: .74rem; color: var(--muted); }

    /* ── Alerts ── */
    .rider-alert {
        display: flex; align-items: center; gap: 8px;
        margin: 12px 16px; padding: 12px 14px;
        border-radius: var(--radius-md); font-size: .82rem;
    }
    .rider-alert span:not(.material-icons-round) { flex: 1; }
    .rider-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
    .rider-alert-error   { background: #FFF0F0; color: #E53935; border: 1px solid #FFBCBC; }
    .rider-alert-close {
        background: none; border: none; cursor: pointer;
        font-size: 1.1rem; color: inherit; padding: 0; line-height: 1;
    }

    /* ── Modal ── */
    .rider-modal-backdrop {
        position: fixed; inset: 0;
        background: rgba(10,10,30,.55); z-index: 1000;
        display: flex; align-items: flex-end; justify-content: center;
        animation: fadeIn .18s ease;
    }
    @keyframes fadeIn { from { opacity: 0 } to { opacity: 1 } }

    .rider-modal {
        background: var(--card-bg);
        border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        width: 100%; max-width: 640px; max-height: 92vh;
        display: flex; flex-direction: column;
        animation: slideUp .22s cubic-bezier(.4,0,.2,1);
    }
    @keyframes slideUp {
        from { transform: translateY(30px); opacity: 0; }
        to   { transform: translateY(0);    opacity: 1; }
    }
    @media (min-width: 640px) {
        .rider-modal-backdrop { align-items: center; padding: 20px; }
        .rider-modal { border-radius: var(--radius-lg); max-height: 88vh; }
    }

    .rider-modal-drag {
        width: 40px; height: 4px; background: var(--border);
        border-radius: 4px; margin: 10px auto 0; flex-shrink: 0;
    }
    .rider-modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 18px 18px 0; flex-shrink: 0;
    }
    .rider-modal-title { font-size: 1rem; font-weight: 700; color: var(--dark); }
    .rider-modal-close {
        width: 32px; height: 32px; border-radius: 50%;
        background: var(--bg); border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        color: var(--soft-dark); font-size: 1rem; transition: var(--transition);
    }
    .rider-modal-close:hover { background: var(--pink-light); color: var(--pink); }

    .rider-modal-body {
        overflow-y: auto; padding: 18px; flex: 1;
    }
    .rider-modal-body::-webkit-scrollbar { width: 4px; }
    .rider-modal-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

    .rider-modal-footer {
        display: flex; gap: 10px; padding: 14px 18px;
        border-top: 1px solid var(--border); flex-shrink: 0;
    }

    /* ── Section Label ── */
    .rider-section-label {
        font-size: .78rem; font-weight: 700; color: var(--muted);
        text-transform: uppercase; letter-spacing: .06em;
        margin-bottom: 12px; padding-bottom: 6px;
        border-bottom: 1px solid var(--border);
    }

    /* ── Form ── */
    .rider-form-group { margin-bottom: 14px; }
    .rider-form-label {
        display: block; font-size: .8rem; font-weight: 600;
        color: var(--soft-dark); margin-bottom: 5px;
    }
    .rider-form-label .req { color: var(--pink); margin-left: 2px; }
    .rider-form-control {
        width: 100%; padding: 10px 12px;
        border: 1.5px solid var(--border); border-radius: var(--radius-sm);
        font-family: var(--font); font-size: .84rem; color: var(--dark);
        background: var(--bg); outline: none; transition: var(--transition);
        box-sizing: border-box;
    }
    select.rider-form-control {
        appearance: none; -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='%23888' d='M4 6l4 4 4-4z'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 12px center;
        background-size: 14px; padding-right: 32px;
    }
    .rider-form-control:focus {
        border-color: var(--pink); box-shadow: 0 0 0 3px rgba(255,61,139,.1); background: #fff;
    }
    .rider-form-control.is-invalid { border-color: #E53935; }
    .rider-invalid-feedback { color: #E53935; font-size: .74rem; margin-top: 4px; }

    .rider-row { display: flex; gap: 12px; }
    .rider-col { flex: 1; min-width: 0; }

    /* ── View Modal Stats ── */
    .rider-view-stats {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;
        margin-bottom: 6px;
    }
    .rider-view-stat-item {
        text-align: center; padding: 14px 8px;
        background: var(--bg); border-radius: var(--radius-md);
        border: 1px solid var(--border);
    }
    .rider-view-stat-value { font-size: 1.15rem; font-weight: 700; line-height: 1.1; }
    .rider-view-stat-label { font-size: .72rem; color: var(--muted); margin-top: 4px; }

    /* ── Detail Grid ── */
    .rider-detail-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
    }
    .rider-detail-item {
        background: var(--bg); border-radius: var(--radius-sm);
        padding: 10px 12px; border: 1px solid var(--border);
    }
    .rider-detail-label {
        display: block; font-size: .7rem; font-weight: 600;
        color: var(--muted); text-transform: uppercase;
        letter-spacing: .04em; margin-bottom: 3px;
    }
    .rider-detail-value { font-size: .84rem; font-weight: 600; color: var(--dark); }

    /* ── Buttons ── */
    .btn-rider-primary {
        flex: 1; padding: 11px; background: var(--pink); color: #fff;
        border: none; border-radius: var(--radius-md); font-family: var(--font);
        font-size: .88rem; font-weight: 700; cursor: pointer; transition: var(--transition);
        display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .btn-rider-primary:hover { background: #e02d7a; }
    .btn-rider-primary:disabled { opacity: .6; cursor: not-allowed; }

    .btn-rider-secondary {
        padding: 11px 20px; background: var(--bg); color: var(--soft-dark);
        border: 1.5px solid var(--border); border-radius: var(--radius-md);
        font-family: var(--font); font-size: .88rem; font-weight: 600;
        cursor: pointer; transition: var(--transition);
    }
    .btn-rider-secondary:hover { background: var(--border); }

    /* ── Confirm Modal (approve/status) ── */
    .rider-confirm-modal {
        background: var(--card-bg); border-radius: var(--radius-lg);
        max-width: 320px; width: calc(100% - 32px);
        padding: 28px 20px 20px; text-align: center;
        animation: scaleIn .18s cubic-bezier(.4,0,.2,1);
    }
    @keyframes scaleIn {
        from { transform: scale(.9); opacity: 0; }
        to   { transform: scale(1);  opacity: 1; }
    }
    .rider-confirm-icon {
        width: 56px; height: 56px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 14px; font-size: 1.6rem;
    }
    .rider-confirm-modal h6 { font-size: .98rem; font-weight: 700; color: var(--dark); margin: 0 0 6px; }
    .rider-confirm-modal p  { font-size: .8rem; color: var(--muted); margin: 0 0 20px; line-height: 1.5; }
    .rider-confirm-actions  { display: flex; gap: 10px; justify-content: center; }

    .btn-cancel {
        padding: 9px 20px; background: var(--bg); border: 1.5px solid var(--border);
        border-radius: var(--radius-sm); font-family: var(--font); font-size: .82rem;
        font-weight: 600; color: var(--soft-dark); cursor: pointer; transition: var(--transition);
    }
    .btn-cancel:hover { background: var(--border); }

    .btn-confirm-delete {
        padding: 9px 20px; background: #E53935; border: none;
        border-radius: var(--radius-sm); font-family: var(--font); font-size: .82rem;
        font-weight: 600; color: #fff; cursor: pointer; transition: var(--transition);
        display: flex; align-items: center; gap: 5px;
    }
    .btn-confirm-delete:hover { background: #c62828; }

    .btn-confirm-warning {
        padding: 9px 20px; background: #f59e0b; border: none;
        border-radius: var(--radius-sm); font-family: var(--font); font-size: .82rem;
        font-weight: 600; color: #fff; cursor: pointer; transition: var(--transition);
        display: flex; align-items: center; gap: 5px;
    }
    .btn-confirm-warning:hover { background: #d97706; }

    .btn-confirm-success {
        padding: 9px 20px; background: #16a34a; border: none;
        border-radius: var(--radius-sm); font-family: var(--font); font-size: .82rem;
        font-weight: 600; color: #fff; cursor: pointer; transition: var(--transition);
        display: flex; align-items: center; gap: 5px;
    }
    .btn-confirm-success:hover { background: #15803d; }

    /* ── Spinner ── */
    .spinner-sm {
        width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.4);
        border-top-color: #fff; border-radius: 50%;
        animation: spin .6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 400px) {
        .rider-row { flex-direction: column; }
        .rider-detail-grid { grid-template-columns: 1fr; }
        .rider-view-stats { grid-template-columns: 1fr 1fr 1fr; gap: 6px; }
    }

</style>
@endpush