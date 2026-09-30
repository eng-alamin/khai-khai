{{-- resources/views/livewire/vendor/coupon-component.blade.php --}}
<div>

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="topbar">
            <div class="topbar-title">
                <span class="title-emoji">🎟️</span>
                Coupons
            </div>
            <button class="btn-new-adm" wire:click="openCreate">
                <span class="plus-icon">＋</span>
                New Coupon
            </button>
        </div>

        {{-- ── Search (left) + Type/Status Select (right) ── --}}
        <div class="filterbar">
            <div class="search-inner filterbar-search">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by code or description...">
            </div>

            <div class="filterbar-selects">
                <select class="select" wire:model.live="filterType">
                    <option value="">All Types</option>
                    <option value="percentage">💯 Percentage</option>
                    <option value="fixed_amount">৳ Fixed Amount</option>
                    <option value="free_delivery">🛵 Free Delivery</option>
                </select>

                <select class="select" wire:model.live="filterStatus">
                    <option value="">All Status</option>
                    <option value="active">✅ Active</option>
                    <option value="inactive">🔴 Inactive</option>
                    <option value="expired">⏰ Expired</option>
                </select>
            </div>
        </div>

        {{-- ── Coupon Cards ── --}}
        @forelse($coupons as $coupon)
            @php
                $expired   = $coupon->is_expired;
                $upcoming  = $coupon->is_upcoming;
                $exhausted = $coupon->is_exhausted;

                if ($expired) {
                    $statusClass = 'expired';  $statusLabel = 'Expired';
                } elseif ($exhausted) {
                    $statusClass = 'expired';  $statusLabel = 'Exhausted';
                } elseif ($upcoming) {
                    $statusClass = 'upcoming'; $statusLabel = 'Upcoming';
                } elseif ($coupon->is_active) {
                    $statusClass = 'active';   $statusLabel = 'Active';
                } else {
                    $statusClass = 'inactive'; $statusLabel = 'Inactive';
                }
            @endphp

            <div class="card" wire:key="coupon-{{ $coupon->id }}">

                {{-- Top Row --}}
                <div class="card-top">
                    <div class="coupon-top-left">
                        <span class="coupon-code-badge">{{ $coupon->code }}</span>
                        <span class="sec-pill">
                            {{ $this->typeEmoji($coupon->type) }} {{ $this->typeLabel($coupon->type) }}
                        </span>
                    </div>
                    <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                {{-- Description --}}
                <div class="coupon-desc">{{ Str::limit($coupon->description, 80) }}</div>

                {{-- Value Row --}}
                <div class="coupon-value-row">
                    <span class="coupon-discount-value">
                        @if($coupon->type === 'percentage')
                            {{ $coupon->value }}% off
                            @if($coupon->max_discount)
                                <span class="coupon-cap">(max ৳{{ $coupon->max_discount_taka }})</span>
                            @endif
                        @elseif($coupon->type === 'fixed_amount')
                            ৳{{ number_format($coupon->value, 0) }} off
                        @else
                            Free Delivery
                        @endif
                    </span>
                    @if($coupon->min_order_amount)
                        <span class="coupon-min-order">
                            <span class="material-icons-round">shopping_bag</span>
                            Min ৳{{ $coupon->min_order_taka }}
                        </span>
                    @endif
                </div>

                {{-- Meta ── usage + dates --}}
                <div class="card-meta">
                    <span class="meta-item">
                        <span class="material-icons-round">confirmation_number</span>
                        {{ $coupon->used_count }}{{ $coupon->usage_limit ? '/' . $coupon->usage_limit : '' }} used
                    </span>
                    @if($coupon->per_user_limit)
                        <span class="meta-item">
                            <span class="material-icons-round">person</span>
                            {{ $coupon->per_user_limit }}x/user
                        </span>
                    @endif
                    @if($coupon->valid_from || $coupon->valid_until)
                        <span class="meta-item">
                            <span class="material-icons-round">schedule</span>
                            {{ $coupon->valid_from?->format('d M') ?? '∞' }} → {{ $coupon->valid_until?->format('d M') ?? '∞' }}
                        </span>
                    @endif
                </div>

                {{-- Usage Progress Bar --}}
                @if($coupon->usage_limit)
                    @php $pct = min(100, round($coupon->used_count / $coupon->usage_limit)); @endphp
                    <div class="coupon-progress-wrap">
                        <div class="coupon-progress-bar">
                            <div class="coupon-progress-fill {{ $pct >= 90 ? 'danger' : ($pct >= 60 ? 'warning' : '') }}"
                                style="width:{{ $pct }}%"></div>
                        </div>
                        <span class="coupon-progress-label">{{ $pct }}%</span>
                    </div>
                @endif

                {{-- Bottom Row --}}
                <div class="card-bottom">
                    <label class="toggle">
                        <input type="checkbox"
                            @checked($coupon->is_active)
                            wire:click="toggleActive({{ $coupon->id }})">
                        <span class="toggle-slider"></span>
                    </label>
                    <div class="card-actions">
                        <button class="btn-edit" wire:click="openEdit({{ $coupon->id }})">
                            <span class="material-icons-round">drive_file_rename_outline</span>
                            Edit
                        </button>
                        <button class="btn-delete"
                            wire:click="confirmDeleteRecord({{ $coupon->id }})">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </div>
                </div>

            </div>
        @empty
            <div class="empty">
                <i class="bi bi-ticket-perforated empty-icon"></i>
                <p>No coupons found.</p>
                <button class="btn-new-adm" wire:click="openCreate">+ Create New Coupon</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($coupons->hasPages())
            <div class="pagination">
                <small>Showing {{ $coupons->firstItem() ?? 0 }}–{{ $coupons->lastItem() ?? 0 }} of {{ $coupons->total() }} total</small>
                {{ $coupons->links('pagination::custom') }}
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Create / Edit Modal
    ══════════════════════════════════════════ --}}
    @if($showModal)
        <div class="jara-modal-backdrop" wire:ignore.self wire:click.self="$set('showModal', false)">
            <div class="jara-modal">

                <div class="jara-modal-drag"></div>

                <div class="jara-modal-header">
                    <div class="jara-modal-title">
                        {{ $editId ? '✏️ Edit Coupon' : '🎟️ Create New Coupon' }}
                    </div>
                    <button class="jara-modal-close" wire:click="$set('showModal', false)">✕</button>
                </div>

                <div class="jara-modal-body">

                    {{-- Code + Generate --}}
                    <div class="form-group">
                        <label class="form-label">Coupon Code <span class="req">*</span></label>
                        <div class="coupon-code-input-wrap">
                            <input type="text"
                                class="form-control @error('code') is-invalid @enderror"
                                wire:model="code"
                                placeholder="e.g. EID20, SAVE50"
                                style="text-transform:uppercase; letter-spacing:.08em; font-weight:700;">
                            <button type="button" class="btn-generate-code" wire:click="generateCode">
                                <span class="material-icons-round">autorenew</span>
                                Generate
                            </button>
                        </div>
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Description --}}
                    <div class="form-group">
                        <label class="form-label">Description <span class="req">*</span></label>
                        <input type="text"
                            class="form-control @error('description') is-invalid @enderror"
                            wire:model="description"
                            placeholder="e.g. Eid Special 20% off on all items">
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Type + Value --}}
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Type <span class="req">*</span></label>
                                <select class="form-control @error('type') is-invalid @enderror"
                                    wire:model.live="type">
                                    <option value="percentage">💯 Percentage</option>
                                    <option value="fixed_amount">৳ Fixed Amount</option>
                                    <option value="free_delivery">🛵 Free Delivery</option>
                                </select>
                                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            @if($type !== 'free_delivery')
                                <div class="form-group">
                                    <label class="form-label">
                                        {{ $type === 'percentage' ? 'Discount (%)' : 'Discount (৳)' }}
                                        <span class="req">*</span>
                                    </label>
                                    <div class="input-prefix">
                                        <span class="input-prefix-text">
                                            {{ $type === 'percentage' ? '%' : '৳' }}
                                        </span>
                                        <input type="number"
                                            class="form-control @error('value') is-invalid @enderror"
                                            wire:model="value"
                                            min="0.01"
                                            max="{{ $type === 'percentage' ? '100' : '' }}"
                                            step="{{ $type === 'percentage' ? '1' : '0.01' }}"
                                            placeholder="{{ $type === 'percentage' ? '20' : '100' }}">
                                    </div>
                                    @error('value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @else
                                <div class="form-group">
                                    <label class="form-label">Value</label>
                                    <input type="number" class="form-control" value="0" disabled>
                                    <div class="form-hint">Free delivery — no value needed.</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Max Discount Cap (percentage only) --}}
                    @if($type === 'percentage')
                        <div class="form-group">
                            <label class="form-label">
                                Max Discount Cap (৳)
                                <span class="form-hint" style="display:inline; font-weight:400;">(optional)</span>
                            </label>
                            <div class="input-prefix">
                                <span class="input-prefix-text">৳</span>
                                <input type="number"
                                    class="form-control @error('max_discount') is-invalid @enderror"
                                    wire:model="max_discount"
                                    min="0" step="1"
                                    placeholder="e.g. 200">
                            </div>
                            <div class="form-hint">Percentage discount এর সর্বোচ্চ টাকার সীমা।</div>
                            @error('max_discount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    {{-- Min Order + Usage Limit --}}
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Min Order (৳)</label>
                                <div class="input-prefix">
                                    <span class="input-prefix-text">৳</span>
                                    <input type="number"
                                        class="form-control @error('min_order_amount') is-invalid @enderror"
                                        wire:model="min_order_amount"
                                        min="0" step="1"
                                        placeholder="e.g. 300">
                                </div>
                                <div class="form-hint">Leave empty = no minimum.</div>
                                @error('min_order_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Total Usage Limit</label>
                                <input type="number"
                                    class="form-control @error('usage_limit') is-invalid @enderror"
                                    wire:model="usage_limit"
                                    min="1" step="1"
                                    placeholder="Unlimited">
                                <div class="form-hint">Leave empty = unlimited.</div>
                                @error('usage_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Per User Limit --}}
                    <div class="form-group">
                        <label class="form-label">Per User Limit</label>
                        <input type="number"
                            class="form-control @error('per_user_limit') is-invalid @enderror"
                            wire:model="per_user_limit"
                            min="1" step="1"
                            placeholder="e.g. 1  (one-time use per user)">
                        <div class="form-hint">Leave empty = unlimited per user.</div>
                        @error('per_user_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Dates --}}
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Valid From</label>
                                <input type="datetime-local"
                                    class="form-control @error('valid_from') is-invalid @enderror"
                                    wire:model="valid_from">
                                <div class="form-hint">Leave empty = starts immediately.</div>
                                @error('valid_from') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Valid Until</label>
                                <input type="datetime-local"
                                    class="form-control @error('valid_until') is-invalid @enderror"
                                    wire:model="valid_until">
                                <div class="form-hint">Leave empty = no expiry.</div>
                                @error('valid_until') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Active Toggle --}}
                    <label class="switch-wrap">
                        <span class="switch-label">Keep coupon active now</span>
                        <label class="toggle">
                            <input type="checkbox" wire:model="is_active">
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
    ══════════════════════════════════════════ --}}
    @if($confirmDelete)
        <div class="jara-modal-backdrop">
            <div class="jara-delete-modal">
                <div class="delete-icon">⚠️</div>
                <h6>Delete Coupon?</h6>
                <p>এই কুপন স্থায়ীভাবে মুছে যাবে।<br>এই কাজ পূর্বাবস্থায় ফেরানো যাবে না।</p>
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
