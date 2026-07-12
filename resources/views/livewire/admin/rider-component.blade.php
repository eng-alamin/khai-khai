<div>

    {{-- ── Page Header ── --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color:var(--pink)">Rider Management</h4>
            <p class="text-muted small mb-0">Manage all delivery riders on the platform</p>
        </div>
        <button class="btn btn-pink px-4" wire:click="openAddModal">
            <span class="material-icons-round" style="font-size:1rem;vertical-align:-2px">add</span> Add Rider
        </button>
    </div>

    {{-- ── Flash ── --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm" role="alert">
            <span class="material-icons-round" style="font-size:1rem;vertical-align:-3px">check_circle</span>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ── Stats Row ── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body py-3 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width:42px;height:42px;background:rgba(232,63,140,.12)">
                            <span class="material-icons-round" style="color:var(--pink)">group</span>
                        </div>
                        <div>
                            <div class="fw-bold fs-5 lh-1">{{ $stats['total'] }}</div>
                            <div class="text-muted small">Total Riders</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body py-3 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width:42px;height:42px;background:rgba(34,197,94,.12)">
                            <span class="material-icons-round" style="color:#16a34a;font-size:.85rem">circle</span>
                        </div>
                        <div>
                            <div class="fw-bold fs-5 lh-1">{{ $stats['online'] }}</div>
                            <div class="text-muted small">Online Now</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body py-3 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width:42px;height:42px;background:rgba(245,158,11,.12)">
                            <span class="material-icons-round" style="color:#f59e0b">hourglass_empty</span>
                        </div>
                        <div>
                            <div class="fw-bold fs-5 lh-1">{{ $stats['pending'] }}</div>
                            <div class="text-muted small">Pending Approval</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body py-3 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width:42px;height:42px;background:rgba(239,68,68,.12)">
                            <span class="material-icons-round" style="color:#ef4444">block</span>
                        </div>
                        <div>
                            <div class="fw-bold fs-5 lh-1">{{ $stats['inactive'] }}</div>
                            <div class="text-muted small">Inactive</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Filters ── --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body py-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <span class="material-icons-round" style="font-size:1rem">search</span>
                        </span>
                        <input type="text"
                               class="form-control border-start-0 ps-0"
                               placeholder="Search name, phone or email…"
                               wire:model.live.debounce.400ms="search">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="">All Status</option>
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                        <option value="pending">Pending Approval</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="zoneFilter">
                        <option value="">All Zones</option>
                        @foreach($this->zones as $z)
                            <option value="{{ $z }}">{{ $z }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-outline-secondary w-100"
                            wire:click="$set('search',''); $set('statusFilter',''); $set('zoneFilter','')">
                        <span class="material-icons-round" style="font-size:1rem;vertical-align:-3px">close</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Table ── --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="table-header-row">
                            <th class="px-4 py-3">#</th>
                            <th class="py-3">Name</th>
                            <th class="py-3">Phone</th>
                            <th class="py-3">Zone</th>
                            <th class="py-3 text-center">Vehicle</th>
                            <th class="py-3 text-center">Today</th>
                            <th class="py-3 text-center">Rating</th>
                            <th class="py-3 text-center">Online</th>
                            <th class="py-3 text-center">Account</th>
                            <th class="py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riders as $rider)
                        @php $p = $rider->riderProfile; @endphp
                        <tr>
                            <td class="px-4 text-muted small">{{ $riders->firstItem() + $loop->index }}</td>

                            {{-- Name --}}
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rider-avatar">
                                        {{ strtoupper(substr($rider->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold lh-sm">{{ $rider->name }}</div>
                                        <div class="text-muted" style="font-size:.75rem">{{ $rider->email ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Phone --}}
                            <td class="text-muted small">{{ $rider->phone }}</td>

                            {{-- Zone --}}
                            <td>
                                @if($p?->zone)
                                    <span class="badge rounded-pill zone-badge">{{ $p->zone }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Vehicle --}}
                            <td class="text-center">
                                @if($p?->vehicle_type)
                                    <span class="small text-muted">
                                        {{ ucfirst(str_replace('_', ' ', $p->vehicle_type)) }}
                                        @if($p->vehicle_plate)
                                            <br><span class="fw-semibold text-dark">{{ $p->vehicle_plate }}</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Today's deliveries --}}
                            <td class="text-center">
                                <span class="badge rounded-pill px-3 py-2 today-badge">
                                    {{ $todayCounts[$rider->id] ?? 0 }}
                                </span>
                            </td>

                            {{-- Rating --}}
                            <td class="text-center fw-semibold" style="color:#f59e0b">
                                @if($p?->avg_rating)
                                    {{ number_format($p->avg_rating, 1) }}
                                    <span class="material-icons-round" style="font-size:.85rem;vertical-align:-1px">star</span>
                                @else
                                    <span class="text-muted small">N/A</span>
                                @endif
                            </td>

                            {{-- Online status --}}
                            <td class="text-center">
                                @if(! $p?->is_approved)
                                    <span class="badge rounded-pill px-3 py-2" style="background:rgba(245,158,11,.12);color:#d97706">Pending</span>
                                @elseif($p?->is_online)
                                    <span class="badge rounded-pill px-3 py-2" style="background:rgba(34,197,94,.12);color:#16a34a">Online</span>
                                @else
                                    <span class="badge rounded-pill px-3 py-2 bg-light text-secondary">Offline</span>
                                @endif
                            </td>

                            {{-- Account active --}}
                            <td class="text-center">
                                @if($rider->is_active)
                                    <span class="badge rounded-pill px-3 py-2" style="background:rgba(34,197,94,.12);color:#16a34a">Active</span>
                                @else
                                    <span class="badge rounded-pill px-3 py-2" style="background:rgba(239,68,68,.12);color:#ef4444">Inactive</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    {{-- View --}}
                                    <button class="action-btn" wire:click="viewRider({{ $rider->id }})" title="View Details">
                                        <span class="material-icons-round" style="font-size:.9rem">visibility</span>
                                    </button>

                                    {{-- Approve (if pending) --}}
                                    @if(! $p?->is_approved)
                                        <button class="action-btn text-warning" wire:click="confirmApprove({{ $rider->id }})" title="Approve Rider">
                                            <span class="material-icons-round" style="font-size:.9rem">check_circle_outline</span>
                                        </button>
                                    @endif

                                    {{-- Activate / Deactivate --}}
                                    @if($rider->is_active)
                                        <button class="action-btn text-danger" wire:click="confirmStatusChange({{ $rider->id }}, 'deactivate')" title="Deactivate">
                                            <span class="material-icons-round" style="font-size:.9rem">block</span>
                                        </button>
                                    @else
                                        <button class="action-btn text-success" wire:click="confirmStatusChange({{ $rider->id }}, 'activate')" title="Activate">
                                            <span class="material-icons-round" style="font-size:.9rem">check_circle</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <span class="material-icons-round d-block mb-2 opacity-25" style="font-size:2.5rem">directions_bike</span>
                                No riders found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($riders->hasPages())
            <div class="px-4 py-3 border-top">
                {{ $riders->links() }}
            </div>
            @endif
        </div>
    </div>


    {{-- ══════════════════════════════════════════
         MODAL: Add Rider
    ══════════════════════════════════════════ --}}
    <div class="modal fade" id="addRiderModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold">Add New Rider</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pt-3">

                    {{-- Account Info --}}
                    <p class="section-label">Account Information</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control rounded-3 @error('name') is-invalid @enderror"
                                   wire:model="name"
                                   placeholder="e.g. Karim Mia">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control rounded-3 @error('phone') is-invalid @enderror"
                                   wire:model="phone"
                                   placeholder="017XXXXXXXX">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email"
                                   class="form-control rounded-3 @error('email') is-invalid @enderror"
                                   wire:model="email"
                                   placeholder="Optional">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password"
                                   class="form-control rounded-3 @error('password') is-invalid @enderror"
                                   wire:model="password"
                                   placeholder="Min. 6 characters">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Vehicle & Zone --}}
                    <p class="section-label mt-4">Vehicle & Zone</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Vehicle Type <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3 @error('vehicle_type') is-invalid @enderror" wire:model="vehicle_type">
                                <option value="">Select type…</option>
                                <option value="bicycle">Bicycle</option>
                                <option value="motorcycle">Motorcycle</option>
                                <option value="scooter">Scooter</option>
                                <option value="electric_bike">Electric Bike</option>
                            </select>
                            @error('vehicle_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Vehicle Plate</label>
                            <input type="text"
                                   class="form-control rounded-3 @error('vehicle_plate') is-invalid @enderror"
                                   wire:model="vehicle_plate"
                                   placeholder="e.g. DHA-1234">
                            @error('vehicle_plate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">License Number</label>
                            <input type="text"
                                   class="form-control rounded-3 @error('license_number') is-invalid @enderror"
                                   wire:model="license_number"
                                   placeholder="Optional">
                            @error('license_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">NID Number</label>
                            <input type="text"
                                   class="form-control rounded-3 @error('nid_number') is-invalid @enderror"
                                   wire:model="nid_number"
                                   placeholder="Optional">
                            @error('nid_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Zone / Area</label>
                            <input type="text"
                                   class="form-control rounded-3 @error('zone') is-invalid @enderror"
                                   wire:model="zone"
                                   placeholder="e.g. Dhaka-Metro">
                            @error('zone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-0 px-4 pb-4 gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-pink rounded-3 px-4"
                            wire:click="saveRider"
                            wire:loading.attr="disabled">
                        <span wire:loading wire:target="saveRider" class="spinner-border spinner-border-sm me-1"></span>
                        Save Rider
                    </button>
                </div>
            </div>
        </div>
    </div>


    {{-- ══════════════════════════════════════════
         MODAL: View Rider Details
    ══════════════════════════════════════════ --}}
    <div class="modal fade" id="viewRiderModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content rounded-4 border-0 shadow">
                @if($this->riderView)
                @php
                    $r = $this->riderView;
                    $p = $r->riderProfile;
                    $totalEarningsPaisa = $r->riderEarnings?->sum('amount');
                @endphp
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold">Rider Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">

                    {{-- Profile card --}}
                    <div class="d-flex align-items-center gap-3 p-3 rounded-4 mb-4" style="background:var(--bg,#f8f9fc)">
                        <div class="rider-avatar-lg">{{ strtoupper(substr($r->name, 0, 2)) }}</div>
                        <div class="flex-grow-1">
                            <div class="fw-bold fs-5 lh-sm">{{ $r->name }}</div>
                            <div class="text-muted small">{{ $r->email ?? '—' }}</div>
                            <div class="text-muted small">{{ $r->phone }}</div>
                        </div>
                        <div class="text-end">
                            @if(! $p?->is_approved)
                                <span class="badge rounded-pill px-3 py-2" style="background:rgba(245,158,11,.15);color:#d97706">Pending Approval</span>
                            @elseif($p?->is_online)
                                <span class="badge rounded-pill px-3 py-2" style="background:rgba(34,197,94,.15);color:#16a34a">
                                    <span class="material-icons-round" style="font-size:.6rem;vertical-align:1px">circle</span> Online
                                </span>
                            @else
                                <span class="badge rounded-pill px-3 py-2 bg-light text-secondary">Offline</span>
                            @endif
                            <div class="mt-1">
                                @if($r->is_active)
                                    <span class="badge rounded-pill px-3 py-1" style="background:rgba(34,197,94,.1);color:#16a34a;font-size:.72rem">Active Account</span>
                                @else
                                    <span class="badge rounded-pill px-3 py-1" style="background:rgba(239,68,68,.1);color:#ef4444;font-size:.72rem">Inactive Account</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Stats --}}
                    <div class="row g-3 mb-4">
                        <div class="col-4">
                            <div class="text-center p-3 rounded-4" style="background:var(--bg,#f8f9fc)">
                                <div class="fw-bold fs-4 lh-1" style="color:var(--pink)">{{ $p?->total_deliveries ?? 0 }}</div>
                                <div class="text-muted small mt-1">Total Deliveries</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-3 rounded-4" style="background:var(--bg,#f8f9fc)">
                                <div class="fw-bold fs-5 lh-1" style="color:#6366f1">
                                    ৳{{ number_format($totalEarningsPaisa / 100, 2) }}
                                </div>
                                <div class="text-muted small mt-1">Total Earnings</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-3 rounded-4" style="background:var(--bg,#f8f9fc)">
                                <div class="fw-bold fs-4 lh-1" style="color:#f59e0b">
                                    {{ $p?->avg_rating ? number_format($p->avg_rating, 1) : 'N/A' }}
                                    @if($p?->avg_rating)
                                        <span class="material-icons-round" style="font-size:.95rem;vertical-align:-1px">star</span>
                                    @endif
                                </div>
                                <div class="text-muted small mt-1">Avg Rating</div>
                            </div>
                        </div>
                    </div>

                    {{-- Info table --}}
                    <p class="section-label">Profile Information</p>
                    <div class="row g-2">
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
                        @foreach($info as $label => $value)
                        <div class="col-6">
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted small">{{ $label }}</span>
                                <span class="small fw-semibold">{{ $value }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>

                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
                </div>
                @endif
            </div>
        </div>
    </div>


    {{-- ══════════════════════════════════════════
         MODAL: Approve Confirm
    ══════════════════════════════════════════ --}}
    <div class="modal fade" id="approveModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content rounded-4 border-0 shadow text-center p-4">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width:56px;height:56px;background:rgba(245,158,11,.12)">
                    <span class="material-icons-round fs-4" style="color:#d97706">check_circle_outline</span>
                </div>
                <h6 class="fw-bold mb-1">Approve This Rider?</h6>
                <p class="text-muted small mb-4">The rider will be allowed to receive delivery orders.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-warning rounded-3 px-4 text-white"
                            wire:click="approveRider"
                            wire:loading.attr="disabled">
                        <span wire:loading wire:target="approveRider" class="spinner-border spinner-border-sm me-1"></span>
                        Approve
                    </button>
                </div>
            </div>
        </div>
    </div>


    {{-- ══════════════════════════════════════════
         MODAL: Activate / Deactivate Confirm
    ══════════════════════════════════════════ --}}
    <div class="modal fade" id="statusModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content rounded-4 border-0 shadow text-center p-4">
                @if($statusAction === 'deactivate')
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3"
                         style="width:56px;height:56px;background:rgba(239,68,68,.12)">
                        <span class="material-icons-round fs-4" style="color:#ef4444">block</span>
                    </div>
                    <h6 class="fw-bold mb-1">Deactivate Rider?</h6>
                    <p class="text-muted small mb-4">This rider's account will be disabled and they won't be able to log in.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-danger rounded-3 px-4"
                                wire:click="changeStatus"
                                wire:loading.attr="disabled">
                            <span wire:loading wire:target="changeStatus" class="spinner-border spinner-border-sm me-1"></span>
                            Deactivate
                        </button>
                    </div>
                @else
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3"
                         style="width:56px;height:56px;background:rgba(34,197,94,.12)">
                        <span class="material-icons-round fs-4" style="color:#16a34a">check_circle</span>
                    </div>
                    <h6 class="fw-bold mb-1">Activate Rider?</h6>
                    <p class="text-muted small mb-4">This rider's account will be re-enabled.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-success rounded-3 px-4"
                                wire:click="changeStatus"
                                wire:loading.attr="disabled">
                            <span wire:loading wire:target="changeStatus" class="spinner-border spinner-border-sm me-1"></span>
                            Activate
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

@push('styles')
<style>
    .btn-pink {
        background: var(--pink, #e83f8c);
        color: #fff;
        border: none;
    }
    .btn-pink:hover { background: #d6337f; color: #fff; }

    .table-header-row th {
        background: var(--bg, #f8f9fc);
        color: #6b7280;
        font-size: .78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .03em;
        border-bottom: 1px solid var(--border, #e5e7eb);
    }
    .table > :not(caption) > * > * { padding-top: .85rem; padding-bottom: .85rem; }
    .table tbody tr { border-bottom: 1px solid var(--border, #f0f0f0); }
    .table tbody tr:last-child { border-bottom: none; }

    .rider-avatar {
        width: 38px; height: 38px;
        border-radius: 50%;
        background: rgba(232, 63, 140, .12);
        color: var(--pink, #e83f8c);
        font-size: .82rem;
        font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .rider-avatar-lg {
        width: 58px; height: 58px;
        border-radius: 50%;
        background: rgba(232, 63, 140, .12);
        color: var(--pink, #e83f8c);
        font-size: 1.25rem;
        font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .zone-badge {
        background: rgba(99, 102, 241, .12);
        color: #6366f1;
        font-weight: 500;
    }
    .today-badge {
        background: rgba(99, 102, 241, .15);
        color: #6366f1;
        font-size: .8rem;
    }
    .action-btn {
        width: 32px; height: 32px;
        border-radius: 50%;
        border: 1px solid var(--border, #e5e7eb);
        background: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .8rem;
        color: #6b7280;
        cursor: pointer;
        transition: all .15s;
    }
    .action-btn:hover { background: var(--bg, #f8f9fc); border-color: #d1d5db; }
    .action-btn.text-danger { color: #ef4444 !important; }
    .action-btn.text-success { color: #16a34a !important; }
    .action-btn.text-warning { color: #d97706 !important; }

    .section-label {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #9ca3af;
        margin-bottom: .75rem;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {

        Livewire.on('open-modal', ({ modal }) => {
            const el = document.getElementById(modal);
            if (el) bootstrap.Modal.getOrCreateInstance(el).show();
        });

        Livewire.on('close-modal', ({ modal }) => {
            const el = document.getElementById(modal);
            if (el) bootstrap.Modal.getOrCreateInstance(el).hide();
        });

        document.getElementById('addRiderModal')?.addEventListener('hidden.bs.modal', () => {
            @this.set('showAddModal', false);
            // @this.resetValidation?.();
        });

        document.getElementById('viewRiderModal')?.addEventListener('hidden.bs.modal', () => {
            @this.set('viewRiderId', null);
        });

        document.getElementById('statusModal')?.addEventListener('hidden.bs.modal', () => {
            @this.set('statusRiderId', null);
            @this.set('statusAction', '');
        });

        document.getElementById('approveModal')?.addEventListener('hidden.bs.modal', () => {
            @this.set('approveRiderId', null);
        });

    });
</script>
@endpush