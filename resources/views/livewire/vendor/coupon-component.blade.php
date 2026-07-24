{{-- resources/views/livewire/vendor/coupon-component.blade.php --}}
<div>

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="promo-topbar">
            <div class="promo-topbar-title">
                <span class="title-emoji">🎟️</span>
                Coupons
            </div>
            <button class="btn-new-offer" wire:click="openCreate">
                <span class="plus-icon">＋</span>
                New Coupon
            </button>
        </div>

        {{-- ── Search ── --}}
        <div class="promo-search">
            <div class="promo-search-inner">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by code or description...">
            </div>
        </div>

        {{-- ── Filter Chips ── --}}
        <div class="promo-filters">
            <label class="filter-chip {{ $filterType === '' && $filterStatus === '' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterType" value=""
                    wire:click="$set('filterStatus', '')"> All
            </label>
            <label class="filter-chip {{ $filterType === 'percentage' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterType" value="percentage"> 💯 Percentage
            </label>
            <label class="filter-chip {{ $filterType === 'fixed_amount' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterType" value="fixed_amount"> ৳ Fixed Amount
            </label>
            <label class="filter-chip {{ $filterType === 'free_delivery' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterType" value="free_delivery"> 🛵 Free Delivery
            </label>
            <label class="filter-chip {{ $filterStatus === 'active' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterStatus" value="active"
                    wire:click="$set('filterType', '')"> ✅ Active
            </label>
            <label class="filter-chip {{ $filterStatus === 'inactive' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterStatus" value="inactive"
                    wire:click="$set('filterType', '')"> 🔴 Inactive
            </label>
            <label class="filter-chip {{ $filterStatus === 'expired' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterStatus" value="expired"
                    wire:click="$set('filterType', '')"> ⏰ Expired
            </label>
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

            <div class="promo-card coupon-card" wire:key="coupon-{{ $coupon->id }}">

                {{-- Top Row --}}
                <div class="promo-card-top">
                    <div class="coupon-top-left">
                        <span class="coupon-code-badge">{{ $coupon->code }}</span>
                        <span class="promo-type-pill">
                            {{ $this->typeEmoji($coupon->type) }} {{ $this->typeLabel($coupon->type) }}
                        </span>
                    </div>
                    <span class="promo-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                {{-- Description --}}
                <div class="promo-card-desc" style="margin-top:6px;">{{ Str::limit($coupon->description, 80) }}</div>

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
                            <span class="material-icons-round" style="font-size:.8rem;">shopping_bag</span>
                            Min ৳{{ $coupon->min_order_taka }}
                        </span>
                    @endif
                </div>

                {{-- Meta ── usage + dates --}}
                <div class="promo-card-meta" style="margin-top:8px;">
                    <span class="promo-meta-item">
                        <span class="material-icons-round">confirmation_number</span>
                        {{ $coupon->used_count }}{{ $coupon->usage_limit ? '/' . $coupon->usage_limit : '' }} used
                    </span>
                    @if($coupon->per_user_limit)
                        <span class="promo-meta-item">
                            <span class="material-icons-round">person</span>
                            {{ $coupon->per_user_limit }}x/user
                        </span>
                    @endif
                    @if($coupon->valid_from || $coupon->valid_until)
                        <span class="promo-meta-item">
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
                <div class="promo-card-bottom">
                    <label class="promo-toggle">
                        <input type="checkbox"
                            @checked($coupon->is_active)
                            wire:click="toggleActive({{ $coupon->id }})">
                        <span class="promo-toggle-slider"></span>
                    </label>
                    <div class="promo-card-actions">
                        <button class="promo-btn-edit" wire:click="openEdit({{ $coupon->id }})">
                            <span class="material-icons-round">drive_file_rename_outline</span>
                            Edit
                        </button>
                        <button class="promo-btn-delete"
                            wire:click="confirmDeleteRecord({{ $coupon->id }})">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </div>
                </div>

            </div>
        @empty
            <div class="promo-empty">
                <i class="bi bi-ticket-perforated promo-empty-icon"></i>
                <p>No coupons found.</p>
                <button class="btn-new-offer" wire:click="openCreate">+ Create New Coupon</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($coupons->hasPages())
            <div class="promo-pagination">
                <small>Showing {{ $coupons->firstItem() ?? 0 }}–{{ $coupons->lastItem() ?? 0 }} of {{ $coupons->total() }} total</small>
                {{ $coupons->links('pagination::custom') }}
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Create / Edit Modal
    ══════════════════════════════════════════ --}}
    @if($showModal)
        <div class="promo-modal-backdrop" wire:ignore.self wire:click.self="$set('showModal', false)">
            <div class="promo-modal">

                <div class="promo-modal-drag"></div>

                <div class="promo-modal-header">
                    <div class="promo-modal-title">
                        {{ $editId ? '✏️ Edit Coupon' : '🎟️ Create New Coupon' }}
                    </div>
                    <button class="promo-modal-close" wire:click="$set('showModal', false)">✕</button>
                </div>

                <div class="promo-modal-body">

                    {{-- Code + Generate --}}
                    <div class="promo-form-group">
                        <label class="promo-form-label">Coupon Code <span class="req">*</span></label>
                        <div class="coupon-code-input-wrap">
                            <input type="text"
                                class="promo-form-control @error('code') is-invalid @enderror"
                                wire:model="code"
                                placeholder="e.g. EID20, SAVE50"
                                style="text-transform:uppercase; letter-spacing:.08em; font-weight:700;">
                            <button type="button" class="btn-generate-code" wire:click="generateCode">
                                <span class="material-icons-round">autorenew</span>
                                Generate
                            </button>
                        </div>
                        @error('code') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Description --}}
                    <div class="promo-form-group">
                        <label class="promo-form-label">Description <span class="req">*</span></label>
                        <input type="text"
                            class="promo-form-control @error('description') is-invalid @enderror"
                            wire:model="description"
                            placeholder="e.g. Eid Special 20% off on all items">
                        @error('description') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Type + Value --}}
                    <div class="promo-row">
                        <div class="promo-col">
                            <div class="promo-form-group">
                                <label class="promo-form-label">Type <span class="req">*</span></label>
                                <select class="promo-form-control @error('type') is-invalid @enderror"
                                    wire:model.live="type">
                                    <option value="percentage">💯 Percentage</option>
                                    <option value="fixed_amount">৳ Fixed Amount</option>
                                    <option value="free_delivery">🛵 Free Delivery</option>
                                </select>
                                @error('type') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="promo-col">
                            @if($type !== 'free_delivery')
                                <div class="promo-form-group">
                                    <label class="promo-form-label">
                                        {{ $type === 'percentage' ? 'Discount (%)' : 'Discount (৳)' }}
                                        <span class="req">*</span>
                                    </label>
                                    <div class="promo-input-prefix">
                                        <span class="promo-input-prefix-text">
                                            {{ $type === 'percentage' ? '%' : '৳' }}
                                        </span>
                                        <input type="number"
                                            class="promo-form-control @error('value') is-invalid @enderror"
                                            wire:model="value"
                                            min="0.01"
                                            max="{{ $type === 'percentage' ? '100' : '' }}"
                                            step="{{ $type === 'percentage' ? '1' : '0.01' }}"
                                            placeholder="{{ $type === 'percentage' ? '20' : '100' }}">
                                    </div>
                                    @error('value') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @else
                                <div class="promo-form-group">
                                    <label class="promo-form-label">Value</label>
                                    <input type="number" class="promo-form-control" value="0" disabled
                                        style="opacity:.5; cursor:not-allowed;">
                                    <div class="promo-form-hint">Free delivery — no value needed.</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Max Discount Cap (percentage only) --}}
                    @if($type === 'percentage')
                        <div class="promo-form-group">
                            <label class="promo-form-label">
                                Max Discount Cap (৳)
                                <span class="promo-form-hint" style="display:inline; font-weight:400;">(optional)</span>
                            </label>
                            <div class="promo-input-prefix">
                                <span class="promo-input-prefix-text">৳</span>
                                <input type="number"
                                    class="promo-form-control @error('max_discount') is-invalid @enderror"
                                    wire:model="max_discount"
                                    min="0" step="1"
                                    placeholder="e.g. 200">
                            </div>
                            <div class="promo-form-hint">Percentage discount এর সর্বোচ্চ টাকার সীমা।</div>
                            @error('max_discount') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    {{-- Min Order + Usage Limit --}}
                    <div class="promo-row">
                        <div class="promo-col">
                            <div class="promo-form-group">
                                <label class="promo-form-label">Min Order (৳)</label>
                                <div class="promo-input-prefix">
                                    <span class="promo-input-prefix-text">৳</span>
                                    <input type="number"
                                        class="promo-form-control @error('min_order_amount') is-invalid @enderror"
                                        wire:model="min_order_amount"
                                        min="0" step="1"
                                        placeholder="e.g. 300">
                                </div>
                                <div class="promo-form-hint">Leave empty = no minimum.</div>
                                @error('min_order_amount') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="promo-col">
                            <div class="promo-form-group">
                                <label class="promo-form-label">Total Usage Limit</label>
                                <input type="number"
                                    class="promo-form-control @error('usage_limit') is-invalid @enderror"
                                    wire:model="usage_limit"
                                    min="1" step="1"
                                    placeholder="Unlimited">
                                <div class="promo-form-hint">Leave empty = unlimited.</div>
                                @error('usage_limit') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Per User Limit --}}
                    <div class="promo-form-group">
                        <label class="promo-form-label">Per User Limit</label>
                        <input type="number"
                            class="promo-form-control @error('per_user_limit') is-invalid @enderror"
                            wire:model="per_user_limit"
                            min="1" step="1"
                            placeholder="e.g. 1  (one-time use per user)">
                        <div class="promo-form-hint">Leave empty = unlimited per user.</div>
                        @error('per_user_limit') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Dates --}}
                    <div class="promo-row">
                        <div class="promo-col">
                            <div class="promo-form-group">
                                <label class="promo-form-label">Valid From</label>
                                <input type="datetime-local"
                                    class="promo-form-control @error('valid_from') is-invalid @enderror"
                                    wire:model="valid_from">
                                <div class="promo-form-hint">Leave empty = starts immediately.</div>
                                @error('valid_from') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="promo-col">
                            <div class="promo-form-group">
                                <label class="promo-form-label">Valid Until</label>
                                <input type="datetime-local"
                                    class="promo-form-control @error('valid_until') is-invalid @enderror"
                                    wire:model="valid_until">
                                <div class="promo-form-hint">Leave empty = no expiry.</div>
                                @error('valid_until') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Active Toggle --}}
                    <label class="promo-switch-wrap">
                        <span class="promo-switch-label">Keep coupon active now</span>
                        <label class="promo-toggle">
                            <input type="checkbox" wire:model="is_active">
                            <span class="promo-toggle-slider"></span>
                        </label>
                    </label>

                </div>

                <div class="promo-modal-footer">
                    <button class="btn-promo-secondary"
                        wire:click="$set('showModal', false)">Cancel</button>
                    <button class="btn-promo-primary"
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
        <div class="promo-modal-backdrop">
            <div class="promo-delete-modal">
                <div class="promo-delete-icon">⚠️</div>
                <h6>Delete Coupon?</h6>
                <p>এই কুপন স্থায়ীভাবে মুছে যাবে।<br>এই কাজ পূর্বাবস্থায় ফেরানো যাবে না।</p>
                <div class="promo-delete-actions">
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
    /* ══════════════════════════════════════════
       PAGE WRAPPER
    ══════════════════════════════════════════ */
    .main-content {
        background: var(--bg);
        min-height: 100vh;
        padding: 0 0 80px;
        font-family: var(--font);
    }

    /* ══════════════════════════════════════════
       TOP HEADER
    ══════════════════════════════════════════ */
    .promo-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 16px 12px;
        background: var(--bg);
        position: sticky;
        top: 0;
        z-index: 50;
    }
    .promo-topbar-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 1.18rem;
        font-weight: 700;
        color: var(--dark);
    }
    .promo-topbar-title .title-emoji { font-size: 1.2rem; }

    /* ══════════════════════════════════════════
       NEW COUPON BUTTON
    ══════════════════════════════════════════ */
    .btn-new-offer {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: var(--pink);
        color: #fff;
        border: none;
        border-radius: 50px;
        padding: 9px 18px;
        font-size: .82rem;
        font-weight: 600;
        font-family: var(--font);
        cursor: pointer;
        box-shadow: 0 4px 18px rgba(255,61,139,.35);
        transition: var(--transition);
        letter-spacing: .02em;
    }
    .btn-new-offer:hover {
        background: #e02d7a;
        box-shadow: 0 6px 24px rgba(255,61,139,.45);
        transform: translateY(-1px);
    }
    .btn-new-offer .plus-icon {
        font-size: 1.1rem;
        font-weight: 400;
        line-height: 1;
    }

    /* ══════════════════════════════════════════
       SEARCH
    ══════════════════════════════════════════ */
    .promo-search { padding: 0 16px 12px; }
    .promo-search-inner { position: relative; }
    .promo-search-inner .search-icon {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--muted);
        font-size: .95rem;
        pointer-events: none;
    }
    .promo-search-inner input {
        width: 100%;
        padding: 10px 12px 10px 36px;
        border: 1.5px solid var(--border);
        border-radius: 50px;
        font-family: var(--font);
        font-size: .82rem;
        color: var(--dark);
        background: var(--card-bg);
        outline: none;
        transition: var(--transition);
        box-sizing: border-box;
    }
    .promo-search-inner input:focus {
        border-color: var(--pink);
        box-shadow: 0 0 0 3px rgba(255,61,139,.1);
    }

    /* ══════════════════════════════════════════
       FILTER CHIPS
    ══════════════════════════════════════════ */
    .promo-filters {
        display: flex;
        gap: 8px;
        padding: 8px 16px 12px;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .promo-filters::-webkit-scrollbar { display: none; }
    .filter-chip {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 14px;
        border-radius: 50px;
        border: 1.5px solid var(--border);
        background: var(--card-bg);
        color: var(--soft-dark);
        font-size: .76rem;
        font-family: var(--font);
        cursor: pointer;
        transition: var(--transition);
        font-weight: 500;
        white-space: nowrap;
    }
    .filter-chip.active,
    .filter-chip:hover {
        border-color: var(--pink);
        background: var(--pink-light);
        color: var(--pink);
    }
    .filter-chip input[type="radio"] { display: none; }

    /* ══════════════════════════════════════════
       COUPON CARD
    ══════════════════════════════════════════ */
    .promo-card {
        margin: 0 16px 12px;
        background: var(--card-bg);
        border-radius: var(--radius-lg);
        padding: 16px;
        box-shadow: var(--shadow-card);
        border: 1.5px solid var(--border);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }
    .promo-card::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 4px;
        background: var(--pink);
        border-radius: 4px 0 0 4px;
        opacity: 0;
        transition: var(--transition);
    }
    .promo-card:hover {
        box-shadow: var(--shadow-hover);
        border-color: rgba(255,61,139,.2);
        transform: translateY(-2px);
    }
    .promo-card:hover::before { opacity: 1; }

    /* card top */
    .promo-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 4px;
    }
    .coupon-top-left {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        flex: 1;
    }

    /* code badge */
    .coupon-code-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        background: var(--pink-soft, #fff0f6);
        color: var(--pink);
        border: 1.5px dashed var(--pink);
        border-radius: 6px;
        font-size: .82rem;
        font-weight: 800;
        letter-spacing: .12em;
        font-family: 'Courier New', monospace;
    }

    /* type pill */
    .promo-type-pill {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 3px 10px;
        border-radius: 50px;
        font-size: .7rem;
        font-weight: 600;
        background: var(--pink-light);
        color: var(--pink);
    }

    /* status badge */
    .promo-status-badge {
        flex-shrink: 0;
        padding: 3px 10px;
        border-radius: 50px;
        font-size: .68rem;
        font-weight: 600;
        font-family: var(--font);
    }
    .promo-status-badge.active   { background: #E8FAF0; color: #1A9453; }
    .promo-status-badge.inactive { background: #FFF0F0; color: #E53935; }
    .promo-status-badge.upcoming { background: #EFF8FF; color: #1565C0; }
    .promo-status-badge.expired  { background: #F5F5F5; color: var(--muted); }

    /* description */
    .promo-card-desc {
        font-size: .8rem;
        color: var(--muted);
        line-height: 1.5;
    }

    /* value row */
    .coupon-value-row {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 8px;
    }
    .coupon-discount-value {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--pink);
    }
    .coupon-cap {
        font-size: .75rem;
        font-weight: 500;
        color: var(--muted);
    }
    .coupon-min-order {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: .76rem;
        color: var(--muted);
        background: var(--bg);
        padding: 3px 8px;
        border-radius: 50px;
        border: 1px solid var(--border);
    }

    /* meta row */
    .promo-card-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .promo-meta-item {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: .74rem;
        color: var(--muted);
    }
    .promo-meta-item .material-icons-round { font-size: .85rem; }

    /* progress bar */
    .coupon-progress-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 10px;
    }
    .coupon-progress-bar {
        flex: 1;
        height: 5px;
        background: var(--border);
        border-radius: 50px;
        overflow: hidden;
    }
    .coupon-progress-fill {
        height: 100%;
        background: var(--pink);
        border-radius: 50px;
        transition: width .4s ease;
    }
    .coupon-progress-fill.warning { background: #F59E0B; }
    .coupon-progress-fill.danger  { background: #EF4444; }
    .coupon-progress-label {
        font-size: .7rem;
        color: var(--muted);
        font-weight: 600;
        min-width: 30px;
        text-align: right;
    }

    /* card bottom */
    .promo-card-bottom {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid var(--border);
        gap: 8px;
    }

    /* toggle */
    .promo-toggle {
        position: relative;
        width: 40px;
        height: 22px;
    }
    .promo-toggle input { opacity: 0; width: 0; height: 0; }
    .promo-toggle-slider {
        position: absolute;
        inset: 0;
        background: #ddd;
        border-radius: 50px;
        cursor: pointer;
        transition: var(--transition);
    }
    .promo-toggle-slider::before {
        content: '';
        position: absolute;
        width: 16px; height: 16px;
        left: 3px; top: 3px;
        background: #fff;
        border-radius: 50%;
        transition: var(--transition);
        box-shadow: 0 1px 4px rgba(0,0,0,.2);
    }
    .promo-toggle input:checked + .promo-toggle-slider { background: var(--pink); }
    .promo-toggle input:checked + .promo-toggle-slider::before { transform: translateX(18px); }

    /* action buttons */
    .promo-card-actions {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .promo-btn-edit {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #F5F5FB;
        border: none;
        border-radius: var(--radius-sm);
        padding: 7px 14px;
        font-size: .78rem;
        font-family: var(--font);
        color: var(--soft-dark);
        cursor: pointer;
        transition: var(--transition);
        font-weight: 600;
    }
    .promo-btn-edit:hover { background: var(--pink-light); color: var(--pink); }
    .promo-btn-edit .material-icons-round { font-size: .95rem; }

    .promo-btn-delete {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #FFF0F0;
        border: none;
        border-radius: var(--radius-sm);
        padding: 7px 9px;
        color: #E53935;
        cursor: pointer;
        transition: var(--transition);
    }
    .promo-btn-delete:hover { background: #FFD6D6; }
    .promo-btn-delete .material-icons-round { font-size: .95rem; }

    /* ══════════════════════════════════════════
       EMPTY STATE
    ══════════════════════════════════════════ */
    .promo-empty {
        text-align: center;
        padding: 60px 20px;
    }
    .promo-empty-icon {
        font-size: 3rem;
        opacity: .25;
        display: block;
        margin-bottom: 12px;
    }
    .promo-empty p { color: var(--muted); font-size: .88rem; margin: 0 0 16px; }

    /* ══════════════════════════════════════════
       PAGINATION
    ══════════════════════════════════════════ */
    .promo-pagination {
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .promo-pagination small { font-size: .74rem; color: var(--muted); }

    /* ══════════════════════════════════════════
       MODAL
    ══════════════════════════════════════════ */
    .promo-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(10,10,30,.55);
        z-index: 1000;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        animation: fadeIn .18s ease;
    }
    @keyframes fadeIn { from { opacity: 0 } to { opacity: 1 } }

    .promo-modal {
        background: var(--card-bg);
        border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        width: 100%;
        max-width: 600px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        animation: slideUp .22s cubic-bezier(.4,0,.2,1);
    }
    @keyframes slideUp {
        from { transform: translateY(30px); opacity: 0; }
        to   { transform: translateY(0);    opacity: 1; }
    }
    @media (min-width: 640px) {
        .promo-modal-backdrop { align-items: center; padding: 20px; }
        .promo-modal { border-radius: var(--radius-lg); max-height: 85vh; }
    }

    .promo-modal-drag {
        width: 40px; height: 4px;
        background: var(--border);
        border-radius: 4px;
        margin: 10px auto 0;
        flex-shrink: 0;
    }
    .promo-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 18px 0;
        flex-shrink: 0;
    }
    .promo-modal-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--dark);
    }
    .promo-modal-close {
        width: 32px; height: 32px;
        border-radius: 50%;
        background: var(--bg);
        border: none;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        color: var(--soft-dark);
        font-size: 1rem;
        transition: var(--transition);
    }
    .promo-modal-close:hover { background: var(--pink-light); color: var(--pink); }

    .promo-modal-body {
        overflow-y: auto;
        padding: 18px;
        flex: 1;
    }
    .promo-modal-body::-webkit-scrollbar { width: 4px; }
    .promo-modal-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

    .promo-modal-footer {
        display: flex;
        gap: 10px;
        padding: 14px 18px;
        border-top: 1px solid var(--border);
        flex-shrink: 0;
    }

    /* ══════════════════════════════════════════
       FORM ELEMENTS
    ══════════════════════════════════════════ */
    .promo-form-group { margin-bottom: 14px; }
    .promo-form-label {
        display: block;
        font-size: .8rem;
        font-weight: 600;
        color: var(--soft-dark);
        margin-bottom: 5px;
    }
    .promo-form-label .req { color: var(--pink); margin-left: 2px; }

    .promo-form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-sm);
        font-family: var(--font);
        font-size: .84rem;
        color: var(--dark);
        background: var(--bg);
        outline: none;
        transition: var(--transition);
        box-sizing: border-box;
    }
    .promo-form-control:focus {
        border-color: var(--pink);
        box-shadow: 0 0 0 3px rgba(255,61,139,.1);
        background: #fff;
    }
    .promo-form-control.is-invalid { border-color: #E53935; }

    .promo-invalid-feedback { color: #E53935; font-size: .74rem; margin-top: 4px; }
    .promo-form-hint { font-size: .72rem; color: var(--muted); margin-top: 4px; }

    .promo-input-prefix { display: flex; align-items: stretch; }
    .promo-input-prefix-text {
        padding: 10px 11px;
        background: var(--border);
        border: 1.5px solid var(--border);
        border-right: none;
        border-radius: var(--radius-sm) 0 0 var(--radius-sm);
        font-size: .84rem;
        color: var(--soft-dark);
        font-weight: 600;
    }
    .promo-input-prefix .promo-form-control {
        border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
    }

    .promo-row { display: flex; gap: 12px; }
    .promo-col { flex: 1; min-width: 0; }

    .promo-switch-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        background: var(--bg);
        border-radius: var(--radius-sm);
        cursor: pointer;
    }
    .promo-switch-label {
        font-size: .84rem;
        color: var(--soft-dark);
        font-weight: 500;
        flex: 1;
    }

    /* code input + generate button */
    .coupon-code-input-wrap {
        display: flex;
        gap: 8px;
        align-items: stretch;
    }
    .coupon-code-input-wrap .promo-form-control { flex: 1; }
    .btn-generate-code {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0 14px;
        background: var(--bg);
        border: 1.5px solid var(--border);
        border-radius: var(--radius-sm);
        font-family: var(--font);
        font-size: .76rem;
        font-weight: 600;
        color: var(--soft-dark);
        cursor: pointer;
        white-space: nowrap;
        transition: var(--transition);
        flex-shrink: 0;
    }
    .btn-generate-code:hover {
        border-color: var(--pink);
        color: var(--pink);
        background: var(--pink-light);
    }
    .btn-generate-code .material-icons-round { font-size: .9rem; }

    /* ══════════════════════════════════════════
       MODAL BUTTONS
    ══════════════════════════════════════════ */
    .btn-promo-primary {
        flex: 1;
        padding: 11px;
        background: var(--pink);
        color: #fff;
        border: none;
        border-radius: var(--radius-md);
        font-family: var(--font);
        font-size: .88rem;
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition);
        display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .btn-promo-primary:hover { background: #e02d7a; }
    .btn-promo-primary:disabled { opacity: .6; cursor: not-allowed; }

    .btn-promo-secondary {
        padding: 11px 20px;
        background: var(--bg);
        color: var(--soft-dark);
        border: 1.5px solid var(--border);
        border-radius: var(--radius-md);
        font-family: var(--font);
        font-size: .88rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }
    .btn-promo-secondary:hover { background: var(--border); }

    /* ══════════════════════════════════════════
       DELETE CONFIRM MODAL
    ══════════════════════════════════════════ */
    .promo-delete-modal {
        background: var(--card-bg);
        border-radius: var(--radius-lg);
        max-width: 320px;
        width: calc(100% - 32px);
        padding: 28px 20px 20px;
        text-align: center;
        animation: scaleIn .18s cubic-bezier(.4,0,.2,1);
    }
    @keyframes scaleIn {
        from { transform: scale(.9); opacity: 0; }
        to   { transform: scale(1);  opacity: 1; }
    }
    .promo-delete-icon {
        width: 56px; height: 56px;
        border-radius: 50%;
        background: #FFF0F0;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 14px;
        font-size: 1.6rem;
    }
    .promo-delete-modal h6 {
        font-size: .98rem;
        font-weight: 700;
        color: var(--dark);
        margin: 0 0 6px;
    }
    .promo-delete-modal p {
        font-size: .8rem;
        color: var(--muted);
        margin: 0 0 20px;
        line-height: 1.5;
    }
    .promo-delete-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    .btn-cancel {
        padding: 9px 20px;
        background: var(--bg);
        border: 1.5px solid var(--border);
        border-radius: var(--radius-sm);
        font-family: var(--font);
        font-size: .82rem;
        font-weight: 600;
        color: var(--soft-dark);
        cursor: pointer;
        transition: var(--transition);
    }
    .btn-cancel:hover { background: var(--border); }

    .btn-confirm-delete {
        padding: 9px 20px;
        background: #E53935;
        border: none;
        border-radius: var(--radius-sm);
        font-family: var(--font);
        font-size: .82rem;
        font-weight: 600;
        color: #fff;
        cursor: pointer;
        transition: var(--transition);
        display: flex; align-items: center; gap: 5px;
    }
    .btn-confirm-delete:hover { background: #c62828; }

    /* ══════════════════════════════════════════
       SPINNER
    ══════════════════════════════════════════ */
    .spinner-sm {
        width: 14px; height: 14px;
        border: 2px solid rgba(255,255,255,.4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin .6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ══════════════════════════════════════════
       RESPONSIVE
    ══════════════════════════════════════════ */
    @media (max-width: 400px) {
        .promo-row { flex-direction: column; }
        .promo-topbar-title { font-size: 1rem; }
        .coupon-code-input-wrap { flex-direction: column; }
        .btn-generate-code { padding: 10px; justify-content: center; }
    }
</style>
@endpush