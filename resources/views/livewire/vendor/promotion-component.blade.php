{{-- resources/views/livewire/vendor/promotion-component.blade.php --}}
<div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="topbar">
            <div class="topbar-title">
                <span class="title-emoji">🏷️</span>
                Promotions & Offers
            </div>
            <button class="btn-new-adm" wire:click="openCreate">
                <span class="plus-icon">＋</span>
                New Offer
            </button>
        </div>

        {{-- ── Search (left) + Type/Status Select (right) ── --}}
        <div class="filterbar">
            <div class="search-inner filterbar-search">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search promotions...">
            </div>

            <div class="filterbar-selects">
                <select class="select" wire:model.live="filterType">
                    <option value="">All Types</option>
                    <option value="item_discount">🏷️ Item Discount</option>
                    <option value="buy_x_get_y">🎁 Buy X Get Y</option>
                    <option value="flash_deal">⚡ Flash Deal</option>
                </select>

                <select class="select" wire:model.live="filterStatus">
                    <option value="">All Status</option>
                    <option value="active">✅ Active</option>
                    <option value="inactive">🔴 Inactive</option>
                </select>
            </div>
        </div>

        {{-- ── Promo Cards ── --}}
        @forelse($promotions as $i => $promo)
            @php
                $expired  = $promo->is_expired;
                $upcoming = $promo->is_upcoming;
                $target   = match($promo->applies_to) {
                    'category'      => $promo->category,
                    'specific_food' => $promo->food,
                    default         => null,
                };
                if ($expired) {
                    $statusClass = 'expired'; $statusLabel = 'Expired';
                } elseif ($upcoming) {
                    $statusClass = 'upcoming'; $statusLabel = 'Upcoming';
                } elseif ($promo->is_active) {
                    $statusClass = 'active'; $statusLabel = 'Active';
                } else {
                    $statusClass = 'inactive'; $statusLabel = 'Inactive';
                }
            @endphp

            <div class="card" wire:key="promo-{{ $promo->id }}">
                {{-- Top Row --}}
                <div class="card-top">
                    <div class="offer-title">{{ $promo->title }}</div>
                    <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                {{-- Description --}}
                @if($promo->description)
                    <div class="offer-desc">{{ Str::limit($promo->description, 60) }}</div>
                @endif

                {{-- Meta --}}
                <div class="card-meta">
                    <span class="sec-pill">
                        @if($promo->type === 'item_discount') 🏷️
                        @elseif($promo->type === 'buy_x_get_y') 🎁
                        @else ⚡
                        @endif
                        {{ $this->typeLabel($promo->type) }}
                    </span>
                    @if($target)
                        <span class="meta-item">
                            <span class="material-icons-round">category</span>
                            {{ $target->emoji ?? '' }} {{ $target->name }}
                        </span>
                    @endif
                    @if($promo->starts_at || $promo->ends_at)
                        <span class="meta-item">
                            <span class="material-icons-round">schedule</span>
                            {{ $promo->starts_at?->format('d M') ?? '∞' }} → {{ $promo->ends_at?->format('d M') ?? '∞' }}
                        </span>
                    @endif
                </div>

                {{-- Bottom Row --}}
                <div class="card-bottom">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="price-badge">৳{{ number_format($promo->discount_value, 2) }}</span>
                        {{-- Toggle --}}
                        <label class="toggle">
                            <input type="checkbox"
                                @checked($promo->is_active)
                                wire:click="toggleActive({{ $promo->id }})">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="card-actions">
                        <button class="btn-edit"
                            wire:click="openEdit({{ $promo->id }})">
                            <span class="material-icons-round">drive_file_rename_outline</span>
                            Edit
                        </button>
                        <button class="btn-delete"
                            wire:click="confirmDeleteRecord({{ $promo->id }})">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </div>
                </div>
            </div>

        @empty
            <div class="empty">
                <i class="bi bi-tag empty-icon"></i>
                <p>No promotions found.</p>
                <button class="btn-new-adm" wire:click="openCreate">+ Create New Offer</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($promotions->hasPages())
            <div class="pagination">
                <small>Showing {{ $promotions->firstItem() ?? 0 }}–{{ $promotions->lastItem() ?? 0 }} of {{ $promotions->total() }} total</small>
                {{ $promotions->links('pagination::custom') }}
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Create / Edit Modal
         ══════════════════════════════════════ --}}
    @if($showModal)
        <div class="jara-modal-backdrop" wire:ignore.self wire:click.self="$set('showModal', false)">
            <div class="jara-modal">

                <div class="jara-modal-drag"></div>

                <div class="jara-modal-header">
                    <div class="jara-modal-title">
                        {{ $editId ? '✏️ Edit Promotion' : '🏷️ Create New Promotion' }}
                    </div>
                    <button class="jara-modal-close" wire:click="$set('showModal', false)">✕</button>
                </div>

                <div class="jara-modal-body">

                    {{-- Title --}}
                    <div class="form-group">
                        <label class="form-label">Title <span class="req">*</span></label>
                        <input type="text"
                            class="form-control @error('title') is-invalid @enderror"
                            wire:model.defer="title"
                            placeholder="e.g. Lunch Special, Eid Offer...">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Type + Discount --}}
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Type <span class="req">*</span></label>
                                <select class="form-control @error('type') is-invalid @enderror"
                                    wire:model.defer="type">
                                    <option value="item_discount">🏷️ Item Discount</option>
                                    <option value="buy_x_get_y">🎁 Buy X Get Y</option>
                                    <option value="flash_deal">⚡ Flash Deal</option>
                                </select>
                                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Discount (৳) <span class="req">*</span></label>
                                <div class="input-prefix">
                                    <span class="input-prefix-text">৳</span>
                                    <input type="number"
                                        class="form-control @error('discount_value') is-invalid @enderror"
                                        wire:model.defer="discount_value"
                                        min="0.01" step="0.01" placeholder="0.00">
                                </div>
                                @error('discount_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Applies To --}}
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Applies To <span class="req">*</span></label>
                                <select class="form-control @error('applies_to') is-invalid @enderror"
                                    wire:model.live="applies_to">
                                    <option value="all_items">All Items</option>
                                    <option value="category">Specific Category</option>
                                    <option value="specific_food">Specific Item</option>
                                </select>
                                @error('applies_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            @if($applies_to === 'category')
                                <div class="form-group">
                                    <label class="form-label">Category <span class="req">*</span></label>
                                    <select class="form-control @error('target_id') is-invalid @enderror"
                                        wire:model.defer="target_id">
                                        <option value="">— Select —</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->emoji }} {{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('target_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @elseif($applies_to === 'specific_food')
                                <div class="form-group">
                                    <label class="form-label">Item <span class="req">*</span></label>
                                    <select class="form-control @error('target_id') is-invalid @enderror"
                                        wire:model.defer="target_id">
                                        <option value="">— Select —</option>
                                        @foreach($foodItems as $item)
                                            <option value="{{ $item->id }}">{{ $item->emoji ?? '🍽️' }} {{ $item->name }} (৳{{ number_format($item->price , 0) }})</option>
                                        @endforeach
                                    </select>
                                    @error('target_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Dates --}}
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Start Date</label>
                                <input type="datetime-local"
                                    class="form-control @error('starts_at') is-invalid @enderror"
                                    wire:model.defer="starts_at">
                                <div class="form-hint">Leave empty to start immediately.</div>
                                @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">End Date</label>
                                <input type="datetime-local"
                                    class="form-control @error('ends_at') is-invalid @enderror"
                                    wire:model.defer="ends_at">
                                <div class="form-hint">Leave empty for no expiry.</div>
                                @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                            wire:model.defer="description"
                            rows="3"
                            placeholder="Brief description of the promotion...">{{ $description }}</textarea>
                        <div class="char-count">{{ strlen($description) }} / 500</div>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Active --}}
                    <label class="switch-wrap">
                        <span class="switch-label">Keep promotion active now</span>
                        <label class="toggle">
                            <input type="checkbox" wire:model.defer="is_active" id="promoActive">
                            <span class="toggle-slider"></span>
                        </label>
                    </label>

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
         Delete Confirmation Modal
         ══════════════════════════════════════ --}}
    @if($confirmDelete)
        <div class="jara-modal-backdrop">
            <div class="jara-delete-modal">
                <div class="delete-icon">⚠️</div>
                <h6>Delete Promotion?</h6>
                <p>This promotion will be permanently deleted.<br>This action cannot be undone.</p>
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
