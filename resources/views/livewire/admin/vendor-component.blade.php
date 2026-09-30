{{-- resources/views/livewire/admin/vendor-component.blade.php --}}
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
                <span class="title-emoji">🏪</span>
                Vendor Management
            </div>
            <button class="btn-new-adm" wire:click="openCreate">
                <span class="plus-icon">＋</span>
                Add Vendor
            </button>
        </div>

        {{-- ── Search (left) + Status Select (right) ── --}}
        <div class="filterbar">
            <div class="search-inner filterbar-search">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by name, category or city...">
            </div>

            <div class="filterbar-selects">
                <select class="select" wire:model.live="statusFilter">
                    <option value="">All Status</option>
                    <option value="active">✅ Active</option>
                    <option value="pending">⏳ Pending</option>
                    <option value="blocked">🔴 Blocked</option>
                </select>
            </div>
        </div>

        {{-- ── Vendor Cards ── --}}
        @forelse($vendors as $vendor)
            @php
                $status = $this->statusOf($vendor);
                $statusConfig = match($status) {
                    'active'  => ['label' => 'Active',  'class' => 'available'],
                    'pending' => ['label' => 'Pending', 'class' => 'pending'],
                    'blocked' => ['label' => 'Blocked', 'class' => 'unavailable'],
                    default   => ['label' => 'Unknown', 'class' => ''],
                };
                $rating = $vendor->avg_rating ? number_format($vendor->avg_rating, 1) . '★' : 'N/A';
            @endphp

            <div class="card">
                {{-- Top Row --}}
                <div class="card-top">
                    <div class="card-thumb">
                        @if($vendor->logo_url)
                            <img src="{{ $vendor->logo_url }}" alt="{{ $vendor->name }}">
                        @else
                            <span class="material-icons-round icon-fallback">image</span>
                        @endif
                    </div>
                    <div class="card-info">
                        <div class="card-title">{{ $vendor->name }}</div>
                        <div class="card-desc">{{ $vendor->category }} · {{ $vendor->city }}</div>
                    </div>
                    <span class="status-badge {{ $statusConfig['class'] }}">
                        {{ $statusConfig['label'] }}
                    </span>
                </div>

                {{-- Stats Row --}}
                <div class="card-meta">
                    <span class="meta-item">
                        <span class="material-icons-round">star</span>
                        {{ $rating }}
                    </span>
                    <span class="meta-item">
                        <span class="material-icons-round">receipt_long</span>
                        {{ $vendor->orders_count }} orders
                    </span>
                    <span class="meta-item">
                        <span class="material-icons-round">person</span>
                        {{ $vendor->owner->name ?? '—' }}
                    </span>
                    @if($vendor->tag)
                        <span class="meta-item">
                            <span class="material-icons-round">sell</span>
                            {{ $vendor->tag }}
                        </span>
                    @endif
                </div>

                {{-- Bottom Actions --}}
                <div class="card-bottom">
                    <div class="card-actions">
                        @if($status === 'pending')
                            <button class="btn-approve"
                                wire:click="approveVendor({{ $vendor->id }})">
                                <span class="material-icons-round">check</span> Approve
                            </button>
                            <button class="btn-reject"
                                wire:click="rejectVendor({{ $vendor->id }})">
                                <span class="material-icons-round">close</span> Reject
                            </button>
                        @else
                            <button class="btn-view"
                                wire:click="openView({{ $vendor->id }})">
                                <span class="material-icons-round">visibility</span> View
                            </button>
                            <button class="btn-edit"
                                wire:click="openEdit({{ $vendor->id }})">
                                <span class="material-icons-round">drive_file_rename_outline</span> Edit
                            </button>
                            <button class="btn-block {{ $status === 'blocked' ? 'unblock' : '' }}"
                                wire:click="toggleBlock({{ $vendor->id }})">
                                <span class="material-icons-round">{{ $status === 'blocked' ? 'lock_open' : 'block' }}</span>
                            </button>
                        @endif
                        <button class="btn-delete"
                            wire:click="confirmDeleteRecord({{ $vendor->id }})">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="empty">
                <span class="material-icons-round empty-icon">store_mall_directory</span>
                <p>No vendors found.</p>
                <button class="btn-new-adm" wire:click="openCreate">+ Add New Vendor</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($vendors->hasPages())
            <div class="pagination">
                <small>Showing {{ $vendors->firstItem() ?? 0 }}–{{ $vendors->lastItem() ?? 0 }} of {{ $vendors->total() }} total</small>
                {{ $vendors->links() }}
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Create / Edit Modal
    ══════════════════════════════════════ --}}
    @if($showModal)
        <div class="jara-modal-backdrop" wire:click.self="$set('showModal', false)">
            <div class="jara-modal">

                <div class="jara-modal-drag"></div>

                <div class="jara-modal-header">
                    <div class="jara-modal-title">
                        {{ $editId ? '✏️ Edit Vendor' : '🏪 Add New Vendor' }}
                    </div>
                    <button class="jara-modal-close" wire:click="$set('showModal', false)">✕</button>
                </div>

                <div class="jara-modal-body">

                    {{-- Section: Owner Info --}}
                    <div class="section-label">👤 Owner Information</div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Owner Name <span class="req">*</span></label>
                                <input type="text"
                                    class="form-control @error('owner_name') is-invalid @enderror"
                                    wire:model.defer="owner_name"
                                    placeholder="Full name">
                                @error('owner_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Owner Email <span class="req">*</span></label>
                                <input type="email"
                                    class="form-control @error('owner_email') is-invalid @enderror"
                                    wire:model.defer="owner_email"
                                    placeholder="email@example.com">
                                @error('owner_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Password {{ $editId ? '(leave blank to keep current)' : '' }}
                            @if(!$editId) <span class="req">*</span> @endif
                        </label>
                        <input type="password"
                            class="form-control @error('owner_password') is-invalid @enderror"
                            wire:model.defer="owner_password"
                            placeholder="{{ $editId ? 'New password (optional)' : 'Min 6 characters' }}">
                        @error('owner_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Section: Restaurant Info --}}
                    <div class="section-label" style="margin-top:18px">🏪 Restaurant Information</div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Restaurant Name <span class="req">*</span></label>
                                <input type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    wire:model.defer="name"
                                    placeholder="e.g. Ma's Kitchen">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Category <span class="req">*</span></label>
                                <input type="text"
                                    class="form-control @error('category') is-invalid @enderror"
                                    wire:model.defer="category"
                                    placeholder="e.g. Bangla Food, Fast Food">
                                @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">City <span class="req">*</span></label>
                                <input type="text"
                                    class="form-control @error('city') is-invalid @enderror"
                                    wire:model.defer="city"
                                    placeholder="e.g. Dhaka">
                                @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="text"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    wire:model.defer="phone"
                                    placeholder="01XXXXXXXXX">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Address <span class="req">*</span></label>
                        <textarea class="form-control @error('address') is-invalid @enderror"
                            wire:model.defer="address"
                            rows="2"
                            placeholder="Full restaurant address...">{{ $address }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Section: Business Settings --}}
                    <div class="section-label" style="margin-top:18px">⚙️ Business Settings</div>

                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Commission (%) <span class="req">*</span></label>
                                <input type="number"
                                    class="form-control @error('commission_rate') is-invalid @enderror"
                                    wire:model.defer="commission_rate"
                                    min="0" max="100" step="0.01">
                                @error('commission_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Tag</label>
                                <input type="text"
                                    class="form-control @error('tag') is-invalid @enderror"
                                    wire:model.defer="tag"
                                    placeholder="e.g. সেরা, জনপ্রিয়" maxlength="40">
                                @error('tag') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Emoji</label>
                        <input type="text"
                            class="form-control"
                            wire:model.defer="emoji"
                            placeholder="🍔"
                            maxlength="10">
                    </div>

                    {{-- Logo Upload --}}
                    <div class="form-group">
                        <label class="form-label">Logo</label>

                        @if($existingLogo && !$logo)
                            <div class="img-preview">
                                <img src="{{ $existingLogo }}" alt="Current Logo">
                                <div class="img-preview-info">
                                    <span>Current logo</span>
                                    <button type="button" class="img-remove"
                                        wire:click="$set('existingLogo', null)">
                                        <span class="material-icons-round">delete</span> Remove
                                    </button>
                                </div>
                            </div>
                        @endif

                        @if($logo)
                            <div class="img-preview">
                                <img src="{{ $logo->temporaryUrl() }}" alt="Preview">
                                <div class="img-preview-info">
                                    <span>{{ $logo->getClientOriginalName() }}</span>
                                    <button type="button" class="img-remove"
                                        wire:click="$set('logo', null)">
                                        <span class="material-icons-round">close</span> Cancel
                                    </button>
                                </div>
                            </div>
                        @endif

                        <input type="file"
                            class="form-control @error('logo') is-invalid @enderror"
                            wire:model="logo" accept="image/*">
                        <div class="form-hint">JPG, PNG, WEBP — max 2 MB</div>

                        <div wire:loading wire:target="logo" class="upload-progress">
                            <div class="upload-bar"></div>
                            <small>Uploading...</small>
                        </div>

                        @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Banner Upload --}}
                    <div class="form-group">
                        <label class="form-label">Banner</label>

                        @if($existingBanner && !$banner)
                            <div class="img-preview">
                                <img src="{{ $existingBanner }}" alt="Current Banner">
                                <div class="img-preview-info">
                                    <span>Current banner</span>
                                    <button type="button" class="img-remove"
                                        wire:click="$set('existingBanner', null)">
                                        <span class="material-icons-round">delete</span> Remove
                                    </button>
                                </div>
                            </div>
                        @endif

                        @if($banner)
                            <div class="img-preview">
                                <img src="{{ $banner->temporaryUrl() }}" alt="Preview">
                                <div class="img-preview-info">
                                    <span>{{ $banner->getClientOriginalName() }}</span>
                                    <button type="button" class="img-remove"
                                        wire:click="$set('banner', null)">
                                        <span class="material-icons-round">close</span> Cancel
                                    </button>
                                </div>
                            </div>
                        @endif

                        <input type="file"
                            class="form-control @error('banner') is-invalid @enderror"
                            wire:model="banner" accept="image/*">
                        <div class="form-hint">JPG, PNG, WEBP — max 4 MB</div>

                        <div wire:loading wire:target="banner" class="upload-progress">
                            <div class="upload-bar"></div>
                            <small>Uploading...</small>
                        </div>

                        @error('banner') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Toggles --}}
                    <label class="switch-wrap">
                        <span class="switch-label">Restaurant is currently open</span>
                        <label class="toggle">
                            <input type="checkbox" wire:model.defer="is_open">
                            <span class="toggle-slider"></span>
                        </label>
                    </label>

                    @if($editId)
                        <label class="switch-wrap" style="margin-top:8px">
                            <span class="switch-label">Approved</span>
                            <label class="toggle">
                                <input type="checkbox" wire:model.defer="is_approved">
                                <span class="toggle-slider"></span>
                            </label>
                        </label>
                        <label class="switch-wrap" style="margin-top:8px">
                            <span class="switch-label">Active (uncheck to block)</span>
                            <label class="toggle">
                                <input type="checkbox" wire:model.defer="is_active">
                                <span class="toggle-slider"></span>
                            </label>
                        </label>
                    @endif

                </div>

                <div class="jara-modal-footer">
                    <button class="btn-secondary"
                        wire:click="$set('showModal', false)">Cancel</button>
                    <button class="btn-primary"
                        wire:click="save"
                        wire:loading.attr="disabled">
                        <span wire:loading wire:target="save" class="spinner-sm"></span>
                        {{ $editId ? 'Update' : 'Create' }}
                    </button>
                </div>

            </div>
        </div>
    @endif


    {{-- ══════════════════════════════════════
         View Details Modal
    ══════════════════════════════════════ --}}
    @if($showView && $viewVendor)
        <div class="jara-modal-backdrop" wire:click.self="$set('showView', false)">
            <div class="jara-modal">

                <div class="jara-modal-drag"></div>

                <div class="jara-modal-header">
                    <div class="jara-modal-title">🔍 Vendor Details</div>
                    <button class="jara-modal-close" wire:click="$set('showView', false)">✕</button>
                </div>

                <div class="jara-modal-body">
                    @php
                        $vs = $this->statusOf($viewVendor);
                        $vcfg = match($vs) {
                            'active'  => ['label' => 'Active',  'class' => 'available'],
                            'pending' => ['label' => 'Pending', 'class' => 'pending'],
                            'blocked' => ['label' => 'Blocked', 'class' => 'unavailable'],
                            default   => ['label' => '—', 'class' => ''],
                        };
                    @endphp

                    {{-- Header --}}
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px;">
                        <div class="card-thumb" style="width:64px;height:64px;">
                            @if($viewVendor->logo_url)
                                <img src="{{ $viewVendor->logo_url }}" alt="{{ $viewVendor->name }}">
                            @elseif($viewVendor->emoji)
                                <span style="font-size:2rem">{{ $viewVendor->emoji }}</span>
                            @else
                                <span class="material-icons-round" style="font-size:2rem;color:var(--muted)">store</span>
                            @endif
                        </div>
                        <div>
                            <div style="font-size:1.05rem;font-weight:700;color:var(--dark)">{{ $viewVendor->name }}</div>
                            <div style="font-size:.8rem;color:var(--muted)">{{ $viewVendor->category }} · {{ $viewVendor->city }}</div>
                            <span class="status-badge {{ $vcfg['class'] }}" style="margin-top:4px;display:inline-block">{{ $vcfg['label'] }}</span>
                        </div>
                    </div>

                    @if($viewVendor->banner_url)
                        <div style="margin-bottom:16px;border-radius:var(--radius-md);overflow:hidden;border:1px solid var(--border);">
                            <img src="{{ $viewVendor->banner_url }}" alt="Banner" style="width:100%;height:120px;object-fit:cover;display:block;">
                        </div>
                    @endif

                    {{-- Details Grid --}}
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Owner</span>
                            <span class="detail-value">{{ $viewVendor->owner->name ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email</span>
                            <span class="detail-value">{{ $viewVendor->owner->email ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone</span>
                            <span class="detail-value">{{ $viewVendor->phone ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Address</span>
                            <span class="detail-value">{{ $viewVendor->address }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Rating</span>
                            <span class="detail-value">{{ $viewVendor->avg_rating ? number_format($viewVendor->avg_rating, 1) . ' ★' : 'N/A' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Reviews</span>
                            <span class="detail-value">{{ $viewVendor->total_reviews }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Orders</span>
                            <span class="detail-value">{{ $viewVendor->orders_count }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Commission</span>
                            <span class="detail-value">{{ $viewVendor->commission_rate }}%</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Tag</span>
                            <span class="detail-value">{{ $viewVendor->tag ?? '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Joined</span>
                            <span class="detail-value">{{ $viewVendor->created_at->format('d M Y') }}</span>
                        </div>
                    </div>
                </div>

                <div class="jara-modal-footer">
                    <button class="btn-secondary" wire:click="$set('showView', false)">Close</button>
                    <button class="btn-primary" wire:click="openEdit({{ $viewVendor->id }}); $set('showView', false)">
                        <span class="material-icons-round" style="font-size:16px">edit</span> Edit
                    </button>
                </div>

            </div>
        </div>
    @endif


    {{-- ══════════════════════════════════════
         Delete Confirmation Modal
    ══════════════════════════════════════ --}}
    @if($confirmDelete)
        <div class="jara-modal-backdrop">
            <div class="jara-delete-modal">
                <div class="delete-icon">⚠️</div>
                <h6>Delete Vendor?</h6>
                <p>The logo, banner and all restaurant data will be permanently removed.<br>This action cannot be undone.</p>
                <div class="delete-actions">
                    <button class="btn-cancel"
                        wire:click="$set('confirmDelete', false)">Cancel</button>
                    <button class="btn-confirm-delete"
                        wire:click="deleteRecord">
                        <span wire:loading wire:target="deleteRecord" class="spinner-sm"></span>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>

