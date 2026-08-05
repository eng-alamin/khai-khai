<div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="vendor-alert vendor-alert-success">
            <i class="material-icons-round">check_circle</i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="vendor-alert-close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="vendor-alert vendor-alert-error">
            <i class="material-icons-round">error</i>
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="vendor-alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="vendor-topbar">
            <div class="vendor-topbar-title">
                <span class="title-emoji">🏪</span>
                Vendor Management
            </div>
            <button class="btn-new-vendor" wire:click="openCreate">
                <span class="plus-icon">＋</span>
                Add Vendor
            </button>
        </div>

        {{-- ── Search ── --}}
        <div class="vendor-search">
            <div class="vendor-search-inner">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by name, category or city...">
            </div>
        </div>

        {{-- ── Filter Chips ── --}}
        <div class="vendor-filters">
            <label class="filter-chip {{ $statusFilter === '' ? 'active' : '' }}">
                <input type="radio" wire:model.live="statusFilter" value=""> All
            </label>
            <label class="filter-chip {{ $statusFilter === 'active' ? 'active' : '' }}">
                <input type="radio" wire:model.live="statusFilter" value="active"> ✅ Active
            </label>
            <label class="filter-chip {{ $statusFilter === 'pending' ? 'active' : '' }}">
                <input type="radio" wire:model.live="statusFilter" value="pending"> ⏳ Pending
            </label>
            <label class="filter-chip {{ $statusFilter === 'blocked' ? 'active' : '' }}">
                <input type="radio" wire:model.live="statusFilter" value="blocked"> 🔴 Blocked
            </label>
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

            <div class="vendor-card">
                {{-- Top Row --}}
                <div class="vendor-card-top">
                    <div class="vendor-card-thumb">
                        @if($vendor->logo_url)
                            <img src="{{ $vendor->logo_url }}" alt="{{ $vendor->name }}">
                        @elseif($vendor->emoji)
                            <span class="vendor-emoji-fallback">{{ $vendor->emoji }}</span>
                        @else
                            <span class="material-icons-round vendor-icon-fallback">store</span>
                        @endif
                    </div>
                    <div class="vendor-card-info">
                        <div class="vendor-card-title">{{ $vendor->name }}</div>
                        <div class="vendor-card-desc">{{ $vendor->category }} · {{ $vendor->city }}</div>
                    </div>
                    <span class="vendor-status-badge {{ $statusConfig['class'] }}">
                        {{ $statusConfig['label'] }}
                    </span>
                </div>

                {{-- Stats Row --}}
                <div class="vendor-card-meta">
                    <span class="vendor-meta-item">
                        <span class="material-icons-round">star</span>
                        {{ $rating }}
                    </span>
                    <span class="vendor-meta-item">
                        <span class="material-icons-round">receipt_long</span>
                        {{ $vendor->orders_count }} orders
                    </span>
                    <span class="vendor-meta-item">
                        <span class="material-icons-round">person</span>
                        {{ $vendor->owner->name ?? '—' }}
                    </span>
                    @if($vendor->tag)
                        <span class="vendor-meta-item">
                            <span class="material-icons-round">sell</span>
                            {{ $vendor->tag }}
                        </span>
                    @endif
                </div>

                {{-- Bottom Actions --}}
                <div class="vendor-card-bottom">
                    <div class="vendor-card-actions">
                        @if($status === 'pending')
                            <button class="vendor-btn-approve"
                                wire:click="approveVendor({{ $vendor->id }})">
                                <span class="material-icons-round">check</span> Approve
                            </button>
                            <button class="vendor-btn-reject"
                                wire:click="rejectVendor({{ $vendor->id }})">
                                <span class="material-icons-round">close</span> Reject
                            </button>
                        @else
                            <button class="vendor-btn-view"
                                wire:click="openView({{ $vendor->id }})">
                                <span class="material-icons-round">visibility</span> View
                            </button>
                            <button class="vendor-btn-edit"
                                wire:click="openEdit({{ $vendor->id }})">
                                <span class="material-icons-round">drive_file_rename_outline</span> Edit
                            </button>
                            <button class="vendor-btn-block {{ $status === 'blocked' ? 'unblock' : '' }}"
                                wire:click="toggleBlock({{ $vendor->id }})">
                                <span class="material-icons-round">{{ $status === 'blocked' ? 'lock_open' : 'block' }}</span>
                            </button>
                        @endif
                        <button class="vendor-btn-delete"
                            wire:click="confirmDeleteRecord({{ $vendor->id }})">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="vendor-empty">
                <span class="material-icons-round vendor-empty-icon">store_mall_directory</span>
                <p>No vendors found.</p>
                <button class="btn-new-vendor" wire:click="openCreate">+ Add New Vendor</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($vendors->hasPages())
            <div class="vendor-pagination">
                <small>Showing {{ $vendors->firstItem() ?? 0 }}–{{ $vendors->lastItem() ?? 0 }} of {{ $vendors->total() }} total</small>
                {{ $vendors->links() }}
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Create / Edit Modal
    ══════════════════════════════════════ --}}
    @if($showModal)
        <div class="vendor-modal-backdrop" wire:click.self="$set('showModal', false)">
            <div class="vendor-modal">

                <div class="vendor-modal-drag"></div>

                <div class="vendor-modal-header">
                    <div class="vendor-modal-title">
                        {{ $editId ? '✏️ Edit Vendor' : '🏪 Add New Vendor' }}
                    </div>
                    <button class="vendor-modal-close" wire:click="$set('showModal', false)">✕</button>
                </div>

                <div class="vendor-modal-body">

                    {{-- Section: Owner Info --}}
                    <div class="vendor-section-label">👤 Owner Information</div>

                    <div class="vendor-row">
                        <div class="vendor-col">
                            <div class="vendor-form-group">
                                <label class="vendor-form-label">Owner Name <span class="req">*</span></label>
                                <input type="text"
                                    class="vendor-form-control @error('owner_name') is-invalid @enderror"
                                    wire:model.defer="owner_name"
                                    placeholder="Full name">
                                @error('owner_name') <div class="vendor-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="vendor-col">
                            <div class="vendor-form-group">
                                <label class="vendor-form-label">Owner Email <span class="req">*</span></label>
                                <input type="email"
                                    class="vendor-form-control @error('owner_email') is-invalid @enderror"
                                    wire:model.defer="owner_email"
                                    placeholder="email@example.com">
                                @error('owner_email') <div class="vendor-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="vendor-form-group">
                        <label class="vendor-form-label">
                            Password {{ $editId ? '(leave blank to keep current)' : '' }}
                            @if(!$editId) <span class="req">*</span> @endif
                        </label>
                        <input type="password"
                            class="vendor-form-control @error('owner_password') is-invalid @enderror"
                            wire:model.defer="owner_password"
                            placeholder="{{ $editId ? 'New password (optional)' : 'Min 6 characters' }}">
                        @error('owner_password') <div class="vendor-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Section: Restaurant Info --}}
                    <div class="vendor-section-label" style="margin-top:18px">🏪 Restaurant Information</div>

                    <div class="vendor-row">
                        <div class="vendor-col">
                            <div class="vendor-form-group">
                                <label class="vendor-form-label">Restaurant Name <span class="req">*</span></label>
                                <input type="text"
                                    class="vendor-form-control @error('name') is-invalid @enderror"
                                    wire:model.defer="name"
                                    placeholder="e.g. Ma's Kitchen">
                                @error('name') <div class="vendor-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="vendor-col">
                            <div class="vendor-form-group">
                                <label class="vendor-form-label">Category <span class="req">*</span></label>
                                <input type="text"
                                    class="vendor-form-control @error('category') is-invalid @enderror"
                                    wire:model.defer="category"
                                    placeholder="e.g. Bangla Food, Fast Food">
                                @error('category') <div class="vendor-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="vendor-row">
                        <div class="vendor-col">
                            <div class="vendor-form-group">
                                <label class="vendor-form-label">City <span class="req">*</span></label>
                                <input type="text"
                                    class="vendor-form-control @error('city') is-invalid @enderror"
                                    wire:model.defer="city"
                                    placeholder="e.g. Dhaka">
                                @error('city') <div class="vendor-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="vendor-col">
                            <div class="vendor-form-group">
                                <label class="vendor-form-label">Phone</label>
                                <input type="text"
                                    class="vendor-form-control @error('phone') is-invalid @enderror"
                                    wire:model.defer="phone"
                                    placeholder="01XXXXXXXXX">
                                @error('phone') <div class="vendor-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="vendor-form-group">
                        <label class="vendor-form-label">Address <span class="req">*</span></label>
                        <textarea class="vendor-form-control @error('address') is-invalid @enderror"
                            wire:model.defer="address"
                            rows="2"
                            placeholder="Full restaurant address...">{{ $address }}</textarea>
                        @error('address') <div class="vendor-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Section: Business Settings --}}
                    <div class="vendor-section-label" style="margin-top:18px">⚙️ Business Settings</div>

                    <div class="vendor-row">
                        <div class="vendor-col">
                            <div class="vendor-form-group">
                                <label class="vendor-form-label">Commission (%) <span class="req">*</span></label>
                                <input type="number"
                                    class="vendor-form-control @error('commission_rate') is-invalid @enderror"
                                    wire:model.defer="commission_rate"
                                    min="0" max="100" step="0.01">
                                @error('commission_rate') <div class="vendor-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="vendor-col">
                            <div class="vendor-form-group">
                                <label class="vendor-form-label">Tag</label>
                                <input type="text"
                                    class="vendor-form-control @error('tag') is-invalid @enderror"
                                    wire:model.defer="tag"
                                    placeholder="e.g. সেরা, জনপ্রিয়" maxlength="40">
                                @error('tag') <div class="vendor-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="vendor-form-group">
                        <label class="vendor-form-label">Emoji</label>
                        <input type="text"
                            class="vendor-form-control"
                            wire:model.defer="emoji"
                            placeholder="🍔"
                            maxlength="10">
                    </div>

                    {{-- Logo Upload --}}
                    <div class="vendor-form-group">
                        <label class="vendor-form-label">Logo</label>

                        @if($existingLogo && !$logo)
                            <div class="vendor-img-preview">
                                <img src="{{ $existingLogo }}" alt="Current Logo">
                                <div class="vendor-img-preview-info">
                                    <span>Current logo</span>
                                    <button type="button" class="vendor-img-remove"
                                        wire:click="$set('existingLogo', null)">
                                        <span class="material-icons-round">delete</span> Remove
                                    </button>
                                </div>
                            </div>
                        @endif

                        @if($logo)
                            <div class="vendor-img-preview">
                                <img src="{{ $logo->temporaryUrl() }}" alt="Preview">
                                <div class="vendor-img-preview-info">
                                    <span>{{ $logo->getClientOriginalName() }}</span>
                                    <button type="button" class="vendor-img-remove"
                                        wire:click="$set('logo', null)">
                                        <span class="material-icons-round">close</span> Cancel
                                    </button>
                                </div>
                            </div>
                        @endif

                        <input type="file"
                            class="vendor-form-control @error('logo') is-invalid @enderror"
                            wire:model="logo" accept="image/*">
                        <div class="vendor-form-hint">JPG, PNG, WEBP — max 2 MB</div>

                        <div wire:loading wire:target="logo" class="vendor-upload-progress">
                            <div class="vendor-upload-bar"></div>
                            <small>Uploading...</small>
                        </div>

                        @error('logo') <div class="vendor-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Banner Upload --}}
                    <div class="vendor-form-group">
                        <label class="vendor-form-label">Banner</label>

                        @if($existingBanner && !$banner)
                            <div class="vendor-img-preview">
                                <img src="{{ $existingBanner }}" alt="Current Banner">
                                <div class="vendor-img-preview-info">
                                    <span>Current banner</span>
                                    <button type="button" class="vendor-img-remove"
                                        wire:click="$set('existingBanner', null)">
                                        <span class="material-icons-round">delete</span> Remove
                                    </button>
                                </div>
                            </div>
                        @endif

                        @if($banner)
                            <div class="vendor-img-preview">
                                <img src="{{ $banner->temporaryUrl() }}" alt="Preview">
                                <div class="vendor-img-preview-info">
                                    <span>{{ $banner->getClientOriginalName() }}</span>
                                    <button type="button" class="vendor-img-remove"
                                        wire:click="$set('banner', null)">
                                        <span class="material-icons-round">close</span> Cancel
                                    </button>
                                </div>
                            </div>
                        @endif

                        <input type="file"
                            class="vendor-form-control @error('banner') is-invalid @enderror"
                            wire:model="banner" accept="image/*">
                        <div class="vendor-form-hint">JPG, PNG, WEBP — max 4 MB</div>

                        <div wire:loading wire:target="banner" class="vendor-upload-progress">
                            <div class="vendor-upload-bar"></div>
                            <small>Uploading...</small>
                        </div>

                        @error('banner') <div class="vendor-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Toggles --}}
                    <label class="vendor-switch-wrap">
                        <span class="vendor-switch-label">Restaurant is currently open</span>
                        <label class="vendor-toggle">
                            <input type="checkbox" wire:model.defer="is_open">
                            <span class="vendor-toggle-slider"></span>
                        </label>
                    </label>

                    @if($editId)
                        <label class="vendor-switch-wrap" style="margin-top:8px">
                            <span class="vendor-switch-label">Approved</span>
                            <label class="vendor-toggle">
                                <input type="checkbox" wire:model.defer="is_approved">
                                <span class="vendor-toggle-slider"></span>
                            </label>
                        </label>
                        <label class="vendor-switch-wrap" style="margin-top:8px">
                            <span class="vendor-switch-label">Active (uncheck to block)</span>
                            <label class="vendor-toggle">
                                <input type="checkbox" wire:model.defer="is_active">
                                <span class="vendor-toggle-slider"></span>
                            </label>
                        </label>
                    @endif

                </div>

                <div class="vendor-modal-footer">
                    <button class="btn-vendor-secondary"
                        wire:click="$set('showModal', false)">Cancel</button>
                    <button class="btn-vendor-primary"
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
        <div class="vendor-modal-backdrop" wire:click.self="$set('showView', false)">
            <div class="vendor-modal">

                <div class="vendor-modal-drag"></div>

                <div class="vendor-modal-header">
                    <div class="vendor-modal-title">🔍 Vendor Details</div>
                    <button class="vendor-modal-close" wire:click="$set('showView', false)">✕</button>
                </div>

                <div class="vendor-modal-body">
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
                        <div class="vendor-card-thumb" style="width:64px;height:64px;">
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
                            <span class="vendor-status-badge {{ $vcfg['class'] }}" style="margin-top:4px;display:inline-block">{{ $vcfg['label'] }}</span>
                        </div>
                    </div>

                    @if($viewVendor->banner_url)
                        <div style="margin-bottom:16px;border-radius:var(--radius-md);overflow:hidden;border:1px solid var(--border);">
                            <img src="{{ $viewVendor->banner_url }}" alt="Banner" style="width:100%;height:120px;object-fit:cover;display:block;">
                        </div>
                    @endif

                    {{-- Details Grid --}}
                    <div class="vendor-detail-grid">
                        <div class="vendor-detail-item">
                            <span class="vendor-detail-label">Owner</span>
                            <span class="vendor-detail-value">{{ $viewVendor->owner->name ?? '—' }}</span>
                        </div>
                        <div class="vendor-detail-item">
                            <span class="vendor-detail-label">Email</span>
                            <span class="vendor-detail-value">{{ $viewVendor->owner->email ?? '—' }}</span>
                        </div>
                        <div class="vendor-detail-item">
                            <span class="vendor-detail-label">Phone</span>
                            <span class="vendor-detail-value">{{ $viewVendor->phone ?? '—' }}</span>
                        </div>
                        <div class="vendor-detail-item">
                            <span class="vendor-detail-label">Address</span>
                            <span class="vendor-detail-value">{{ $viewVendor->address }}</span>
                        </div>
                        <div class="vendor-detail-item">
                            <span class="vendor-detail-label">Rating</span>
                            <span class="vendor-detail-value">{{ $viewVendor->avg_rating ? number_format($viewVendor->avg_rating, 1) . ' ★' : 'N/A' }}</span>
                        </div>
                        <div class="vendor-detail-item">
                            <span class="vendor-detail-label">Total Reviews</span>
                            <span class="vendor-detail-value">{{ $viewVendor->total_reviews }}</span>
                        </div>
                        <div class="vendor-detail-item">
                            <span class="vendor-detail-label">Total Orders</span>
                            <span class="vendor-detail-value">{{ $viewVendor->orders_count }}</span>
                        </div>
                        <div class="vendor-detail-item">
                            <span class="vendor-detail-label">Commission</span>
                            <span class="vendor-detail-value">{{ $viewVendor->commission_rate }}%</span>
                        </div>
                        <div class="vendor-detail-item">
                            <span class="vendor-detail-label">Tag</span>
                            <span class="vendor-detail-value">{{ $viewVendor->tag ?? '—' }}</span>
                        </div>
                        <div class="vendor-detail-item">
                            <span class="vendor-detail-label">Joined</span>
                            <span class="vendor-detail-value">{{ $viewVendor->created_at->format('d M Y') }}</span>
                        </div>
                    </div>
                </div>

                <div class="vendor-modal-footer">
                    <button class="btn-vendor-secondary" wire:click="$set('showView', false)">Close</button>
                    <button class="btn-vendor-primary" wire:click="openEdit({{ $viewVendor->id }}); $set('showView', false)">
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
        <div class="vendor-modal-backdrop">
            <div class="vendor-delete-modal">
                <div class="vendor-delete-icon">⚠️</div>
                <h6>Delete Vendor?</h6>
                <p>The logo, banner and all restaurant data will be permanently removed.<br>This action cannot be undone.</p>
                <div class="vendor-delete-actions">
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

@push('styles')
<style>
    .main-content {
        background: var(--bg);
        min-height: 100vh;
        padding: 0 0 80px;
        font-family: var(--font);
    }

    /* ── Top Bar ── */
    .vendor-topbar {
        display: flex; align-items: center; justify-content: space-between;
        padding: 20px 16px 12px;
        background: var(--bg);
        position: sticky; top: 0; z-index: 50;
    }
    .vendor-topbar-title {
        display: flex; align-items: center; gap: 8px;
        font-size: 1.18rem; font-weight: 700; color: var(--dark);
    }

    .btn-new-vendor {
        display: inline-flex; align-items: center; gap: 5px;
        background: var(--pink); color: #fff; border: none;
        border-radius: 50px; padding: 9px 18px;
        font-size: .82rem; font-weight: 600; font-family: var(--font);
        cursor: pointer; box-shadow: 0 4px 18px rgba(255,61,139,.35);
        transition: var(--transition); letter-spacing: .02em;
    }
    .btn-new-vendor:hover {
        background: #e02d7a; box-shadow: 0 6px 24px rgba(255,61,139,.45);
        transform: translateY(-1px);
    }

    /* ── Search ── */
    .vendor-search { padding: 0 16px 12px; }
    .vendor-search-inner { position: relative; }
    .vendor-search-inner .search-icon {
        position: absolute; left: 13px; top: 50%;
        transform: translateY(-50%); color: var(--muted);
        font-size: .95rem; pointer-events: none;
    }
    .vendor-search-inner input {
        width: 100%; padding: 10px 12px 10px 36px;
        border: 1.5px solid var(--border); border-radius: 50px;
        font-family: var(--font); font-size: .82rem; color: var(--dark);
        background: var(--card-bg); outline: none;
        transition: var(--transition); box-sizing: border-box;
    }
    .vendor-search-inner input:focus {
        border-color: var(--pink); box-shadow: 0 0 0 3px rgba(255,61,139,.1);
    }

    /* ── Filter Chips ── */
    .vendor-filters {
        display: flex; gap: 8px; padding: 8px 16px 12px;
        overflow-x: auto; scrollbar-width: none;
    }
    .vendor-filters::-webkit-scrollbar { display: none; }
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

    /* ── Vendor Card ── */
    .vendor-card {
        margin: 0 16px 12px;
        background: var(--card-bg); border-radius: var(--radius-lg);
        padding: 16px; box-shadow: var(--shadow-card);
        border: 1.5px solid var(--border); transition: var(--transition);
        position: relative; overflow: hidden;
    }
    .vendor-card::before {
        content: ''; position: absolute; left: 0; top: 0; bottom: 0;
        width: 4px; background: var(--pink); border-radius: 4px 0 0 4px;
        opacity: 0; transition: var(--transition);
    }
    .vendor-card:hover {
        box-shadow: var(--shadow-hover); border-color: rgba(255,61,139,.2);
        transform: translateY(-2px);
    }
    .vendor-card:hover::before { opacity: 1; }

    .vendor-card-top {
        display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px;
    }
    .vendor-card-thumb {
        flex-shrink: 0; width: 52px; height: 52px;
        border-radius: 10px; border: 1.5px solid var(--border);
        overflow: hidden; display: flex; align-items: center;
        justify-content: center; background: var(--bg);
    }
    .vendor-card-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .vendor-emoji-fallback { font-size: 1.6rem; line-height: 1; }
    .vendor-icon-fallback  { font-size: 1.6rem; color: var(--muted); }

    .vendor-card-info { flex: 1; min-width: 0; }
    .vendor-card-title {
        font-size: .98rem; font-weight: 700; color: var(--dark);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .vendor-card-desc { font-size: .78rem; color: var(--muted); margin-top: 2px; }

    .vendor-status-badge {
        flex-shrink: 0; padding: 3px 10px; border-radius: 50px;
        font-size: .68rem; font-weight: 600; font-family: var(--font);
    }
    .vendor-status-badge.available   { background: #E8FAF0; color: #1A9453; }
    .vendor-status-badge.unavailable { background: #FFF0F0; color: #E53935; }
    .vendor-status-badge.pending     { background: #FFF8E1; color: #F57F17; }

    .vendor-card-meta {
        display: flex; align-items: center; gap: 12px;
        flex-wrap: wrap; margin-bottom: 10px;
    }
    .vendor-meta-item {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: .74rem; color: var(--muted);
    }
    .vendor-meta-item .material-icons-round { font-size: .85rem; }

    .vendor-card-bottom {
        padding-top: 10px; border-top: 1px solid var(--border);
    }
    .vendor-card-actions { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

    .vendor-btn-approve {
        display: inline-flex; align-items: center; gap: 4px;
        background: #E8FAF0; border: none; border-radius: var(--radius-sm);
        padding: 7px 14px; font-size: .78rem; font-family: var(--font);
        color: #1A9453; cursor: pointer; transition: var(--transition); font-weight: 600;
    }
    .vendor-btn-approve:hover { background: #C6F2D8; }
    .vendor-btn-approve .material-icons-round { font-size: .9rem; }

    .vendor-btn-reject {
        display: inline-flex; align-items: center; gap: 4px;
        background: #FFF0F0; border: none; border-radius: var(--radius-sm);
        padding: 7px 14px; font-size: .78rem; font-family: var(--font);
        color: #E53935; cursor: pointer; transition: var(--transition); font-weight: 600;
    }
    .vendor-btn-reject:hover { background: #FFD6D6; }
    .vendor-btn-reject .material-icons-round { font-size: .9rem; }

    .vendor-btn-view {
        display: inline-flex; align-items: center; gap: 4px;
        background: #F0F4FF; border: none; border-radius: var(--radius-sm);
        padding: 7px 14px; font-size: .78rem; font-family: var(--font);
        color: #3B5BDB; cursor: pointer; transition: var(--transition); font-weight: 600;
    }
    .vendor-btn-view:hover { background: #D9E4FF; }
    .vendor-btn-view .material-icons-round { font-size: .9rem; }

    .vendor-btn-edit {
        display: inline-flex; align-items: center; gap: 4px;
        background: #F5F5FB; border: none; border-radius: var(--radius-sm);
        padding: 7px 14px; font-size: .78rem; font-family: var(--font);
        color: var(--soft-dark); cursor: pointer; transition: var(--transition); font-weight: 600;
    }
    .vendor-btn-edit:hover { background: var(--pink-light); color: var(--pink); }
    .vendor-btn-edit .material-icons-round { font-size: .9rem; }

    .vendor-btn-block {
        display: inline-flex; align-items: center; justify-content: center;
        background: #FFF0F0; border: none; border-radius: var(--radius-sm);
        padding: 7px 9px; color: #E53935; cursor: pointer; transition: var(--transition);
    }
    .vendor-btn-block.unblock { background: #E8FAF0; color: #1A9453; }
    .vendor-btn-block:hover { opacity: .8; }
    .vendor-btn-block .material-icons-round { font-size: .9rem; }

    .vendor-btn-delete {
        display: inline-flex; align-items: center; justify-content: center;
        background: #FFF0F0; border: none; border-radius: var(--radius-sm);
        padding: 7px 9px; color: #E53935; cursor: pointer; transition: var(--transition);
    }
    .vendor-btn-delete:hover { background: #FFD6D6; }
    .vendor-btn-delete .material-icons-round { font-size: .9rem; }

    /* ── Empty ── */
    .vendor-empty { text-align: center; padding: 60px 20px; }
    .vendor-empty-icon { font-size: 3rem; opacity: .25; display: block; margin-bottom: 12px; }
    .vendor-empty p { color: var(--muted); font-size: .88rem; margin: 0 0 16px; }

    /* ── Pagination ── */
    .vendor-pagination {
        padding: 12px 16px;
        display: flex; align-items: center; justify-content: space-between;
    }
    .vendor-pagination small { font-size: .74rem; color: var(--muted); }

    /* ── Alerts ── */
    .vendor-alert {
        display: flex; align-items: center; gap: 8px;
        margin: 12px 16px; padding: 12px 14px;
        border-radius: var(--radius-md); font-size: .82rem;
    }
    .vendor-alert span { flex: 1; }
    .vendor-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
    .vendor-alert-error   { background: #FFF0F0; color: #E53935; border: 1px solid #FFBCBC; }
    .vendor-alert-close {
        background: none; border: none; cursor: pointer;
        font-size: 1.1rem; color: inherit; padding: 0; line-height: 1;
    }

    /* ── Modal ── */
    .vendor-modal-backdrop {
        position: fixed; inset: 0;
        background: rgba(10,10,30,.55); z-index: 1000;
        display: flex; align-items: flex-end; justify-content: center;
        animation: fadeIn .18s ease;
    }
    @keyframes fadeIn { from { opacity: 0 } to { opacity: 1 } }

    .vendor-modal {
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
        .vendor-modal-backdrop { align-items: center; padding: 20px; }
        .vendor-modal { border-radius: var(--radius-lg); max-height: 88vh; }
    }

    .vendor-modal-drag {
        width: 40px; height: 4px; background: var(--border);
        border-radius: 4px; margin: 10px auto 0; flex-shrink: 0;
    }
    .vendor-modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 18px 18px 0; flex-shrink: 0;
    }
    .vendor-modal-title { font-size: 1rem; font-weight: 700; color: var(--dark); }
    .vendor-modal-close {
        width: 32px; height: 32px; border-radius: 50%;
        background: var(--bg); border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        color: var(--soft-dark); font-size: 1rem; transition: var(--transition);
    }
    .vendor-modal-close:hover { background: var(--pink-light); color: var(--pink); }

    .vendor-modal-body {
        overflow-y: auto; padding: 18px; flex: 1;
    }
    .vendor-modal-body::-webkit-scrollbar { width: 4px; }
    .vendor-modal-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

    .vendor-modal-footer {
        display: flex; gap: 10px; padding: 14px 18px;
        border-top: 1px solid var(--border); flex-shrink: 0;
    }

    /* ── Section Label ── */
    .vendor-section-label {
        font-size: .78rem; font-weight: 700; color: var(--muted);
        text-transform: uppercase; letter-spacing: .06em;
        margin-bottom: 12px; padding-bottom: 6px;
        border-bottom: 1px solid var(--border);
    }

    /* ── Form ── */
    .vendor-form-group { margin-bottom: 14px; }
    .vendor-form-label {
        display: block; font-size: .8rem; font-weight: 600;
        color: var(--soft-dark); margin-bottom: 5px;
    }
    .vendor-form-label .req { color: var(--pink); margin-left: 2px; }
    .vendor-form-control {
        width: 100%; padding: 10px 12px;
        border: 1.5px solid var(--border); border-radius: var(--radius-sm);
        font-family: var(--font); font-size: .84rem; color: var(--dark);
        background: var(--bg); outline: none; transition: var(--transition);
        box-sizing: border-box;
    }
    .vendor-form-control:focus {
        border-color: var(--pink); box-shadow: 0 0 0 3px rgba(255,61,139,.1); background: #fff;
    }
    .vendor-form-control.is-invalid { border-color: #E53935; }
    .vendor-invalid-feedback { color: #E53935; font-size: .74rem; margin-top: 4px; }
    .vendor-form-hint { font-size: .72rem; color: var(--muted); margin-top: 4px; }

    .vendor-row { display: flex; gap: 12px; }
    .vendor-col { flex: 1; min-width: 0; }

    .vendor-input-prefix { display: flex; align-items: stretch; }
    .vendor-input-prefix-text {
        padding: 10px 11px; background: var(--border);
        border: 1.5px solid var(--border); border-right: none;
        border-radius: var(--radius-sm) 0 0 var(--radius-sm);
        font-size: .84rem; color: var(--soft-dark); font-weight: 600;
    }
    .vendor-input-prefix .vendor-form-control {
        border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
    }

    /* ── Image Preview ── */
    .vendor-img-preview {
        display: flex; align-items: center; gap: 12px;
        padding: 10px; background: var(--bg);
        border-radius: var(--radius-sm); border: 1.5px solid var(--border);
        margin-bottom: 10px;
    }
    .vendor-img-preview img {
        width: 56px; height: 56px; object-fit: cover;
        border-radius: 8px; border: 1px solid var(--border); flex-shrink: 0;
    }
    .vendor-img-preview-info { display: flex; flex-direction: column; gap: 4px; }
    .vendor-img-preview-info span { font-size: .75rem; color: var(--muted); }
    .vendor-img-remove {
        display: inline-flex; align-items: center; gap: 3px;
        background: #FFF0F0; border: none; border-radius: var(--radius-sm);
        padding: 4px 10px; font-size: .74rem; color: #E53935;
        cursor: pointer; font-family: var(--font); font-weight: 600; transition: var(--transition);
    }
    .vendor-img-remove:hover { background: #FFD6D6; }
    .vendor-img-remove .material-icons-round { font-size: .8rem; }

    /* ── Upload Progress ── */
    .vendor-upload-progress { margin-top: 8px; }
    .vendor-upload-bar {
        height: 4px; border-radius: 99px;
        background: linear-gradient(90deg, var(--pink), #ff8fab);
        background-size: 200% 100%;
        animation: shimmer 1.2s infinite; margin-bottom: 4px;
    }
    @keyframes shimmer {
        0%   { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    .vendor-upload-progress small { font-size: .72rem; color: var(--muted); }

    /* ── Toggle ── */
    .vendor-switch-wrap {
        display: flex; align-items: center; gap: 10px;
        padding: 12px; background: var(--bg); border-radius: var(--radius-sm); cursor: pointer;
    }
    .vendor-switch-label { font-size: .84rem; color: var(--soft-dark); font-weight: 500; flex: 1; }
    .vendor-toggle { position: relative; width: 40px; height: 22px; }
    .vendor-toggle input { opacity: 0; width: 0; height: 0; }
    .vendor-toggle-slider {
        position: absolute; inset: 0; background: #ddd;
        border-radius: 50px; cursor: pointer; transition: var(--transition);
    }
    .vendor-toggle-slider::before {
        content: ''; position: absolute; width: 16px; height: 16px;
        left: 3px; top: 3px; background: #fff; border-radius: 50%;
        transition: var(--transition); box-shadow: 0 1px 4px rgba(0,0,0,.2);
    }
    .vendor-toggle input:checked + .vendor-toggle-slider { background: var(--pink); }
    .vendor-toggle input:checked + .vendor-toggle-slider::before { transform: translateX(18px); }

    /* ── Buttons ── */
    .btn-vendor-primary {
        flex: 1; padding: 11px; background: var(--pink); color: #fff;
        border: none; border-radius: var(--radius-md); font-family: var(--font);
        font-size: .88rem; font-weight: 700; cursor: pointer; transition: var(--transition);
        display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .btn-vendor-primary:hover { background: #e02d7a; }
    .btn-vendor-primary:disabled { opacity: .6; cursor: not-allowed; }

    .btn-vendor-secondary {
        padding: 11px 20px; background: var(--bg); color: var(--soft-dark);
        border: 1.5px solid var(--border); border-radius: var(--radius-md);
        font-family: var(--font); font-size: .88rem; font-weight: 600;
        cursor: pointer; transition: var(--transition);
    }
    .btn-vendor-secondary:hover { background: var(--border); }

    /* ── View Detail Grid ── */
    .vendor-detail-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
    }
    .vendor-detail-item {
        background: var(--bg); border-radius: var(--radius-sm);
        padding: 10px 12px; border: 1px solid var(--border);
    }
    .vendor-detail-label {
        display: block; font-size: .7rem; font-weight: 600;
        color: var(--muted); text-transform: uppercase;
        letter-spacing: .04em; margin-bottom: 3px;
    }
    .vendor-detail-value { font-size: .84rem; font-weight: 600; color: var(--dark); }

    /* ── Delete Modal ── */
    .vendor-delete-modal {
        background: var(--card-bg); border-radius: var(--radius-lg);
        max-width: 320px; width: calc(100% - 32px);
        padding: 28px 20px 20px; text-align: center;
        animation: scaleIn .18s cubic-bezier(.4,0,.2,1);
    }
    @keyframes scaleIn {
        from { transform: scale(.9); opacity: 0; }
        to   { transform: scale(1);  opacity: 1; }
    }
    .vendor-delete-icon {
        width: 56px; height: 56px; border-radius: 50%;
        background: #FFF0F0; display: flex; align-items: center;
        justify-content: center; margin: 0 auto 14px; font-size: 1.6rem;
    }
    .vendor-delete-modal h6 { font-size: .98rem; font-weight: 700; color: var(--dark); margin: 0 0 6px; }
    .vendor-delete-modal p  { font-size: .8rem; color: var(--muted); margin: 0 0 20px; line-height: 1.5; }
    .vendor-delete-actions  { display: flex; gap: 10px; justify-content: center; }

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

    /* ── Spinner ── */
    .spinner-sm {
        width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.4);
        border-top-color: #fff; border-radius: 50%;
        animation: spin .6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 400px) {
        .vendor-row { flex-direction: column; }
        .vendor-detail-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush