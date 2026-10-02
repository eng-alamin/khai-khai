{{-- resources/views/livewire/admin/rider-component.blade.php --}}
{{-- Styles: resources/css/blade.css (shared "" classes, Bootstrap 5 required) --}}
<div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="alert alert-success">
            <i class="material-icons-round">check_circle</i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">
            <i class="material-icons-round">error</i>
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="topbar">
            <div class="topbar-title">
                <span class="title-emoji">🏍️</span>
                Rider Management
            </div>
            <button class="btn-new-adm" wire:click="openAddModal">
                <span class="plus-icon">＋</span>
                Add Rider
            </button>
        </div>

        {{-- ── Stats Row ── --}}
        <div class="mini-stats">
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(232,63,140,.12);color:var(--pink)">
                    <span class="material-icons-round">group</span>
                </div>
                <div>
                    <div class="mini-stat-value">{{ $stats['total'] }}</div>
                    <div class="mini-stat-label">Total Riders</div>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(34,197,94,.12);color:#16a34a">
                    <span class="material-icons-round" style="font-size:.85rem">circle</span>
                </div>
                <div>
                    <div class="mini-stat-value">{{ $stats['online'] }}</div>
                    <div class="mini-stat-label">Online Now</div>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(245,158,11,.12);color:#f59e0b">
                    <span class="material-icons-round">hourglass_empty</span>
                </div>
                <div>
                    <div class="mini-stat-value">{{ $stats['pending'] }}</div>
                    <div class="mini-stat-label">Pending Approval</div>
                </div>
            </div>
            <div class="mini-stat-card">
                <div class="mini-stat-icon" style="background:rgba(239,68,68,.12);color:#ef4444">
                    <span class="material-icons-round">block</span>
                </div>
                <div>
                    <div class="mini-stat-value">{{ $stats['inactive'] }}</div>
                    <div class="mini-stat-label">Inactive</div>
                </div>
            </div>
        </div>

        {{-- ── Search (left) + Status/Zone Select (right) ── --}}
        <div class="filterbar">
            <div class="search-inner filterbar-search">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search name, phone or email...">
            </div>

            <div class="filterbar-selects">
                <select class="select" wire:model.live="statusFilter">
                    <option value="">All Status</option>
                    <option value="online">🟢 Online</option>
                    <option value="offline">⚪ Offline</option>
                    <option value="pending">⏳ Pending</option>
                    <option value="inactive">🔴 Inactive</option>
                </select>

                <select class="select" wire:model.live="zoneFilter">
                    <option value="">All Zones</option>
                    @foreach($this->zones as $z)
                        <option value="{{ $z }}">{{ $z }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ── Rider Cards ── --}}
        @forelse($riders as $rider)
            @php
                $p = $rider->riderProfile;
                $today = $todayCounts[$rider->id] ?? 0;
            @endphp

            <div class="card" wire:key="{{ $rider->id }}">
                {{-- Top Row --}}
                <div class="card-top">
                    <div class="card-thumb">
                        {{ strtoupper(substr($rider->name, 0, 1)) }}
                    </div>
                    <div class="card-info">
                        <div class="card-title">{{ $rider->name }}</div>
                        <div class="card-desc">{{ $rider->phone }} @if($rider->email) · {{ $rider->email }} @endif</div>
                    </div>
                    <div class="badges-col">
                        @if(! $p?->is_approved)
                            <span class="status-badge pending">Pending</span>
                        @elseif($p?->is_online)
                            <span class="status-badge available">Online</span>
                        @else
                            <span class="status-badge offline">Offline</span>
                        @endif
                    </div>
                </div>

                {{-- Meta --}}
                <div class="card-meta">
                    @if($p?->zone)
                        <span class="cat-pill">
                            <span class="material-icons-round">place</span>
                            {{ $p->zone }}
                        </span>
                    @endif
                    @if($p?->vehicle_type)
                        <span class="meta-item">
                            <span class="material-icons-round">two_wheeler</span>
                            {{ ucfirst(str_replace('_', ' ', $p->vehicle_type)) }}
                            @if($p->vehicle_plate) · {{ $p->vehicle_plate }} @endif
                        </span>
                    @endif
                    <span class="meta-item">
                        <span class="material-icons-round">local_shipping</span>
                        {{ $today }} today
                    </span>
                    <span class="meta-item">
                        <span class="material-icons-round">star</span>
                        {{ $p?->avg_rating ? number_format($p->avg_rating, 1) : 'N/A' }}
                    </span>
                </div>

                {{-- Bottom Row --}}
                <div class="card-bottom">
                    <div style="display:flex;align-items:center;gap:8px;">
                        @if($rider->is_active)
                            <span class="account-badge active">Active Account</span>
                        @else
                            <span class="account-badge inactive">Inactive Account</span>
                        @endif
                    </div>
                    <div class="card-actions">
                        <button class="btn-view"
                            wire:click="viewRider({{ $rider->id }})">
                            <span class="material-icons-round">visibility</span>
                            View
                        </button>

                        @if(! $p?->is_approved)
                            <button class="btn-approve"
                                wire:click="confirmApprove({{ $rider->id }})">
                                <span class="material-icons-round">check_circle_outline</span>
                                Approve
                            </button>
                        @endif

                        @if($rider->is_active)
                            <button class="btn-block"
                                wire:click="confirmStatusChange({{ $rider->id }}, 'deactivate')" title="Deactivate">
                                <span class="material-icons-round">block</span>
                            </button>
                        @else
                            <button class="btn-block unblock"
                                wire:click="confirmStatusChange({{ $rider->id }}, 'activate')" title="Activate">
                                <span class="material-icons-round">check_circle</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

        @empty
            <div class="empty">
                <span class="material-icons-round empty-icon">directions_bike</span>
                <p>No riders found.</p>
                <button class="btn-new-adm" wire:click="openAddModal">+ Add New Rider</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($riders->hasPages())
            <div class="pagination">
                <small>Showing {{ $riders->firstItem() ?? 0 }}–{{ $riders->lastItem() ?? 0 }} of {{ $riders->total() }} total</small>
                {{ $riders->links() }}
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Add Rider Modal
    ══════════════════════════════════════ --}}
    @if($showAddModal)
        <div class="jara-modal-backdrop" wire:click.self="$set('showAddModal', false)">
            <div class="jara-modal">

                <div class="jara-modal-drag"></div>

                <div class="jara-modal-header">
                    <div class="jara-modal-title">🏍️ Add New Rider</div>
                    <button class="jara-modal-close" wire:click="$set('showAddModal', false)">✕</button>
                </div>

                <div class="jara-modal-body">

                    <div class="section-label">Account Information</div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Full Name <span class="req">*</span></label>
                                <input type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    wire:model="name"
                                    placeholder="e.g. Karim Mia">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Phone <span class="req">*</span></label>
                                <input type="text"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    wire:model="phone"
                                    placeholder="017XXXXXXXX">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    wire:model="email"
                                    placeholder="Optional">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Password <span class="req">*</span></label>
                                <input type="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    wire:model="password"
                                    placeholder="Min. 6 characters">
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="section-label" style="margin-top:18px">Vehicle & Zone</div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Vehicle Type <span class="req">*</span></label>
                                <select class="form-control @error('vehicle_type') is-invalid @enderror" wire:model="vehicle_type">
                                    <option value="">Select type…</option>
                                    <option value="bicycle">Bicycle</option>
                                    <option value="motorcycle">Motorcycle</option>
                                    <option value="scooter">Scooter</option>
                                    <option value="electric_bike">Electric Bike</option>
                                </select>
                                @error('vehicle_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Vehicle Plate</label>
                                <input type="text"
                                    class="form-control @error('vehicle_plate') is-invalid @enderror"
                                    wire:model="vehicle_plate"
                                    placeholder="e.g. DHA-1234">
                                @error('vehicle_plate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">License Number</label>
                                <input type="text"
                                    class="form-control @error('license_number') is-invalid @enderror"
                                    wire:model="license_number"
                                    placeholder="Optional">
                                @error('license_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">NID Number</label>
                                <input type="text"
                                    class="form-control @error('nid_number') is-invalid @enderror"
                                    wire:model="nid_number"
                                    placeholder="Optional">
                                @error('nid_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Zone / Area</label>
                        <input type="text"
                            class="form-control @error('zone') is-invalid @enderror"
                            wire:model="zone"
                            placeholder="e.g. Dhaka-Metro">
                        @error('zone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>

                <div class="jara-modal-footer">
                    <button class="btn-secondary"
                        wire:click="$set('showAddModal', false)">Cancel</button>
                    <button class="btn-primary"
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
        <div class="jara-modal-backdrop" wire:click.self="$set('showView', false)">
            <div class="jara-modal">

                <div class="jara-modal-drag"></div>

                <div class="jara-modal-header">
                    <div class="jara-modal-title">🔍 Rider Details</div>
                    <button class="jara-modal-close" wire:click="$set('showView', false)">✕</button>
                </div>

                <div class="jara-modal-body">

                    {{-- Profile card --}}
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px;">
                        <div class="card-thumb" style="width:64px;height:64px;font-size:1.25rem;">
                            {{ strtoupper(substr($r->name, 0, 2)) }}
                        </div>
                        <div style="flex:1">
                            <div style="font-size:1.05rem;font-weight:700;color:var(--dark)">{{ $r->name }}</div>
                            <div style="font-size:.8rem;color:var(--muted)">{{ $r->email ?? '—' }}</div>
                            <div style="font-size:.8rem;color:var(--muted)">{{ $r->phone }}</div>
                        </div>
                        <div style="text-align:right">
                            @if(! $p?->is_approved)
                                <span class="status-badge pending">Pending</span>
                            @elseif($p?->is_online)
                                <span class="status-badge available">Online</span>
                            @else
                                <span class="status-badge offline">Offline</span>
                            @endif
                            <div style="margin-top:4px">
                                @if($r->is_active)
                                    <span class="account-badge active">Active</span>
                                @else
                                    <span class="account-badge inactive">Inactive</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Stats --}}
                    <div class="view-stats">
                        <div class="view-stat-item">
                            <div class="view-stat-value" style="color:var(--pink)">{{ $p?->total_deliveries ?? 0 }}</div>
                            <div class="view-stat-label">Total Deliveries</div>
                        </div>
                        <div class="view-stat-item">
                            <div class="view-stat-value" style="color:#6366f1">৳{{ number_format((float) ($totalEarningsPaisa ?? 0), 2) }}</div>
                            <div class="view-stat-label">Total Earnings</div>
                        </div>
                        <div class="view-stat-item">
                            <div class="view-stat-value" style="color:#f59e0b">
                                {{ $p?->avg_rating ? number_format($p->avg_rating, 1) : 'N/A' }}
                            </div>
                            <div class="view-stat-label">Avg Rating</div>
                        </div>
                    </div>

                    {{-- Info table --}}
                    <div class="section-label" style="margin-top:18px">Profile Information</div>
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
                    <div class="detail-grid">
                        @foreach($info as $label => $value)
                            <div class="detail-item">
                                <span class="detail-label">{{ $label }}</span>
                                <span class="detail-value">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>

                </div>

                <div class="jara-modal-footer">
                    <button class="btn-secondary" wire:click="$set('showView', false)">Close</button>
                </div>

            </div>
        </div>
    @endif


    {{-- ══════════════════════════════════════
         Approve Confirmation Modal
    ══════════════════════════════════════ --}}
    @if($showApprove)
        <div class="jara-modal-backdrop">
            <div class="jara-delete-modal">
                <div class="delete-icon" style="background:rgba(245,158,11,.12)">
                    <span class="material-icons-round" style="color:#d97706">check_circle_outline</span>
                </div>
                <h6>Approve This Rider?</h6>
                <p>The rider will be allowed to receive delivery orders.</p>
                <div class="delete-actions">
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
        <div class="jara-modal-backdrop">
            <div class="jara-delete-modal">
                @if($statusAction === 'deactivate')
                    <div class="delete-icon" style="background:rgba(239,68,68,.12)">
                        <span class="material-icons-round" style="color:#ef4444">block</span>
                    </div>
                    <h6>Deactivate Rider?</h6>
                    <p>This rider's account will be disabled and they won't be able to log in.</p>
                    <div class="delete-actions">
                        <button class="btn-cancel" wire:click="$set('showStatus', false)">Cancel</button>
                        <button class="btn-confirm-delete"
                            wire:click="changeStatus"
                            wire:loading.attr="disabled">
                            <span wire:loading wire:target="changeStatus" class="spinner-sm"></span>
                            Deactivate
                        </button>
                    </div>
                @else
                    <div class="delete-icon" style="background:rgba(34,197,94,.12)">
                        <span class="material-icons-round" style="color:#16a34a">check_circle</span>
                    </div>
                    <h6>Activate Rider?</h6>
                    <p>This rider's account will be re-enabled.</p>
                    <div class="delete-actions">
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

