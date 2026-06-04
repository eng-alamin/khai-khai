{{-- resources/views/livewire/vendor/promotion-component.blade.php --}}
<div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="promo-alert promo-alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="promo-alert-close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="promo-alert promo-alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="promo-alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="promo-topbar">
            <div class="promo-topbar-title">
                <span class="title-emoji">🏷️</span>
                Promotions & Offers
            </div>
            <button class="btn-new-offer" wire:click="openCreate">
                <span class="plus-icon">＋</span>
                New Offer
            </button>
        </div>

        {{-- ── Search ── --}}
        <div class="promo-search">
            <div class="promo-search-inner">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search promotions...">
            </div>
        </div>

        {{-- ── Filter Chips ── --}}
        <div class="promo-filters">
            <label class="filter-chip {{ $filterType === '' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterType" value=""> All Types
            </label>
            <label class="filter-chip {{ $filterType === 'item_discount' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterType" value="item_discount"> 🏷️ Item Discount
            </label>
            <label class="filter-chip {{ $filterType === 'buy_x_get_y' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterType" value="buy_x_get_y"> 🎁 Buy X Get Y
            </label>
            <label class="filter-chip {{ $filterType === 'flash_deal' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterType" value="flash_deal"> ⚡ Flash Deal
            </label>
            <label class="filter-chip {{ $filterStatus === 'active' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterStatus" value="active"> ✅ Active
            </label>
            <label class="filter-chip {{ $filterStatus === 'inactive' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterStatus" value="inactive"> 🔴 Inactive
            </label>
        </div>

        {{-- ── Promo Cards ── --}}
        @forelse($promotions as $i => $promo)
            @php
                $expired  = $promo->is_expired;
                $upcoming = $promo->is_upcoming;
                $target   = match($promo->applies_to) {
                    'category'      => $promo->category,
                    'specific_item' => $promo->menuItem,
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

            <div class="promo-card">
                {{-- Top Row --}}
                <div class="promo-card-top">
                    <div class="promo-card-title">{{ $promo->title }}</div>
                    <span class="promo-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                {{-- Description --}}
                @if($promo->description)
                    <div class="promo-card-desc">{{ Str::limit($promo->description, 60) }}</div>
                @endif

                {{-- Meta --}}
                <div class="promo-card-meta">
                    <span class="promo-type-pill">
                        @if($promo->type === 'item_discount') 🏷️
                        @elseif($promo->type === 'buy_x_get_y') 🎁
                        @else ⚡
                        @endif
                        {{ $this->typeLabel($promo->type) }}
                    </span>
                    @if($target)
                        <span class="promo-meta-item">
                            <span class="material-icons-round">category</span>
                            {{ $target->emoji ?? '' }} {{ $target->name }}
                        </span>
                    @endif
                    @if($promo->starts_at || $promo->ends_at)
                        <span class="promo-meta-item">
                            <span class="material-icons-round">schedule</span>
                            {{ $promo->starts_at?->format('d M') ?? '∞' }} → {{ $promo->ends_at?->format('d M') ?? '∞' }}
                        </span>
                    @endif
                </div>

                {{-- Bottom Row --}}
                <div class="promo-card-bottom">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="promo-discount-badge">৳{{ number_format($promo->discount_value, 2) }}</span>
                        {{-- Toggle --}}
                        <label class="promo-toggle">
                            <input type="checkbox"
                                @checked($promo->is_active)
                                wire:click="toggleActive({{ $promo->id }})">
                            <span class="promo-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="promo-card-actions">
                        <button class="promo-btn-edit"
                            wire:click="openEdit({{ $promo->id }})">
                            <span class="material-icons-round">drive_file_rename_outline</span>
                            Edit
                        </button>
                        <button class="promo-btn-delete"
                            wire:click="confirmDeleteRecord({{ $promo->id }})">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </div>
                </div>
            </div>

        @empty
            <div class="promo-empty">
                <i class="bi bi-tag promo-empty-icon"></i>
                <p>No promotions found.</p>
                <button class="btn-new-offer" wire:click="openCreate">+ Create New Offer</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($promotions->hasPages())
            <div class="promo-pagination">
                <small>Showing {{ $promotions->firstItem() ?? 0 }}–{{ $promotions->lastItem() ?? 0 }} of {{ $promotions->total() }} total</small>
                {{ $promotions->links('pagination::custom') }}
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Create / Edit Modal
         ══════════════════════════════════════ --}}
    @if($showModal)
        <div class="promo-modal-backdrop" wire:ignore.self wire:click.self="$set('showModal', false)">
            <div class="promo-modal">

                <div class="promo-modal-drag"></div>

                <div class="promo-modal-header">
                    <div class="promo-modal-title">
                        {{ $editId ? '✏️ Edit Promotion' : '🏷️ Create New Promotion' }}
                    </div>
                    <button class="promo-modal-close" wire:click="$set('showModal', false)">✕</button>
                </div>

                <div class="promo-modal-body">

                    {{-- Title --}}
                    <div class="promo-form-group">
                        <label class="promo-form-label">Title <span class="req">*</span></label>
                        <input type="text"
                            class="promo-form-control @error('title') is-invalid @enderror"
                            wire:model.defer="title"
                            placeholder="e.g. Lunch Special, Eid Offer...">
                        @error('title') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Type + Discount --}}
                    <div class="promo-row">
                        <div class="promo-col">
                            <div class="promo-form-group">
                                <label class="promo-form-label">Type <span class="req">*</span></label>
                                <select class="promo-form-control @error('type') is-invalid @enderror"
                                    wire:model.defer="type">
                                    <option value="item_discount">🏷️ Item Discount</option>
                                    <option value="buy_x_get_y">🎁 Buy X Get Y</option>
                                    <option value="flash_deal">⚡ Flash Deal</option>
                                </select>
                                @error('type') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="promo-col">
                            <div class="promo-form-group">
                                <label class="promo-form-label">Discount (৳) <span class="req">*</span></label>
                                <div class="promo-input-prefix">
                                    <span class="promo-input-prefix-text">৳</span>
                                    <input type="number"
                                        class="promo-form-control @error('discount_value') is-invalid @enderror"
                                        wire:model.defer="discount_value"
                                        min="0.01" step="0.01" placeholder="0.00">
                                </div>
                                @error('discount_value') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Applies To --}}
                    <div class="promo-row">
                        <div class="promo-col">
                            <div class="promo-form-group">
                                <label class="promo-form-label">Applies To <span class="req">*</span></label>
                                <select class="promo-form-control @error('applies_to') is-invalid @enderror"
                                    wire:model.live="applies_to">
                                    <option value="all_items">All Items</option>
                                    <option value="category">Specific Category</option>
                                    <option value="specific_item">Specific Item</option>
                                </select>
                                @error('applies_to') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="promo-col">
                            @if($applies_to === 'category')
                                <div class="promo-form-group">
                                    <label class="promo-form-label">Category <span class="req">*</span></label>
                                    <select class="promo-form-control @error('target_id') is-invalid @enderror"
                                        wire:model.defer="target_id">
                                        <option value="">— Select —</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->emoji }} {{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('target_id') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @elseif($applies_to === 'specific_item')
                                <div class="promo-form-group">
                                    <label class="promo-form-label">Item <span class="req">*</span></label>
                                    <select class="promo-form-control @error('target_id') is-invalid @enderror"
                                        wire:model.defer="target_id">
                                        <option value="">— Select —</option>
                                        @foreach($menuItems as $item)
                                            <option value="{{ $item->id }}">{{ $item->emoji ?? '🍽️' }} {{ $item->name }} (৳{{ number_format($item->price / 100) }})</option>
                                        @endforeach
                                    </select>
                                    @error('target_id') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Dates --}}
                    <div class="promo-row">
                        <div class="promo-col">
                            <div class="promo-form-group">
                                <label class="promo-form-label">Start Date</label>
                                <input type="datetime-local"
                                    class="promo-form-control @error('starts_at') is-invalid @enderror"
                                    wire:model.defer="starts_at">
                                <div class="promo-form-hint">Leave empty to start immediately.</div>
                                @error('starts_at') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="promo-col">
                            <div class="promo-form-group">
                                <label class="promo-form-label">End Date</label>
                                <input type="datetime-local"
                                    class="promo-form-control @error('ends_at') is-invalid @enderror"
                                    wire:model.defer="ends_at">
                                <div class="promo-form-hint">Leave empty for no expiry.</div>
                                @error('ends_at') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="promo-form-group">
                        <label class="promo-form-label">Description</label>
                        <textarea class="promo-form-control @error('description') is-invalid @enderror"
                            wire:model.defer="description"
                            rows="3"
                            placeholder="Brief description of the promotion...">{{ $description }}</textarea>
                        <div class="char-count">{{ strlen($description) }} / 500</div>
                        @error('description') <div class="promo-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Active --}}
                    <label class="promo-switch-wrap">
                        <span class="promo-switch-label">Keep promotion active now</span>
                        <label class="promo-toggle">
                            <input type="checkbox" wire:model.defer="is_active" id="promoActive">
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
         ══════════════════════════════════════ --}}
    @if($confirmDelete)
        <div class="promo-modal-backdrop">
            <div class="promo-delete-modal">
                <div class="promo-delete-icon">⚠️</div>
                <h6>Delete Promotion?</h6>
                <p>This promotion will be permanently deleted.<br>This action cannot be undone.</p>
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
        /* ── Page Wrapper ── */
        .main-content {
            background: var(--bg);
            min-height: 100vh;
            padding: 0 0 80px;
            font-family: var(--font);
        }

        /* ── Top Header ── */
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
        .promo-topbar-title .title-emoji {
            font-size: 1.2rem;
        }

        /* ── New Offer Button ── */
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

        /* ── Filter Bar ── */
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

        /* ── Search ── */
        .promo-search {
            padding: 0 16px 12px;
        }
        .promo-search-inner {
            position: relative;
        }
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

        /* ── Section Label ── */
        .promo-section-label {
            padding: 4px 16px 8px;
            font-size: .72rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        /* ── Promo Card ── */
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

        /* card top row */
        .promo-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 6px;
        }
        .promo-card-title {
            font-size: .98rem;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.3;
            flex: 1;
        }
        .promo-status-badge {
            flex-shrink: 0;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: .68rem;
            font-weight: 600;
            font-family: var(--font);
        }
        .promo-status-badge.active {
            background: #E8FAF0;
            color: #1A9453;
        }
        .promo-status-badge.inactive {
            background: #FFF0F0;
            color: #E53935;
        }
        .promo-status-badge.upcoming {
            background: #EFF8FF;
            color: #1565C0;
        }
        .promo-status-badge.expired {
            background: #F5F5F5;
            color: var(--muted);
        }

        /* card description */
        .promo-card-desc {
            font-size: .8rem;
            color: var(--muted);
            margin-bottom: 10px;
            line-height: 1.5;
        }

        /* card meta row */
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
        .promo-meta-item .material-icons-round {
            font-size: .85rem;
        }

        /* card type pill */
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

        /* card bottom row */
        .promo-card-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
            gap: 8px;
        }

        .promo-discount-badge {
            font-size: .9rem;
            font-weight: 700;
            color: var(--pink);
        }

        /* toggle */
        .promo-toggle-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
        }
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
        .promo-btn-edit:hover {
            background: var(--pink-light);
            color: var(--pink);
        }
        .promo-btn-edit .material-icons-round { font-size: .95rem; }

        .promo-btn-delete {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #FFF0F0;
            border: none;
            border-radius: var(--radius-sm);
            padding: 7px 9px;
            font-size: .78rem;
            color: #E53935;
            cursor: pointer;
            transition: var(--transition);
        }
        .promo-btn-delete:hover { background: #FFD6D6; }
        .promo-btn-delete .material-icons-round { font-size: .95rem; }

        /* ── Empty State ── */
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

        /* ── Pagination ── */
        .promo-pagination {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .promo-pagination small { font-size: .74rem; color: var(--muted); }

        /* ── Alerts ── */
        .promo-alert {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 12px 16px;
            padding: 12px 14px;
            border-radius: var(--radius-md);
            font-size: .82rem;
        }
        .promo-alert span { flex: 1; }
        .promo-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
        .promo-alert-error { background: #FFF0F0; color: #E53935; border: 1px solid #FFBCBC; }
        .promo-alert-close {
            background: none; border: none; cursor: pointer;
            font-size: 1.1rem; color: inherit; padding: 0; line-height: 1;
        }

        /* ── Modal ── */
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

        /* on wider screens, center it */
        @media (min-width: 640px) {
            .promo-modal-backdrop { align-items: center; padding: 20px; }
            .promo-modal { border-radius: var(--radius-lg); max-height: 85vh; }
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

        /* drag indicator */
        .promo-modal-drag {
            width: 40px; height: 4px;
            background: var(--border);
            border-radius: 4px;
            margin: 10px auto 0;
            flex-shrink: 0;
        }

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

        /* Form elements */
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

        .promo-input-prefix {
            display: flex;
            align-items: stretch;
        }
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

        /* Buttons */
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

        /* ── Delete Modal ── */
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

        /* spinner */
        .spinner-sm {
            width: 14px; height: 14px;
            border: 2px solid rgba(255,255,255,.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* char counter */
        .char-count {
            text-align: right;
            font-size: .7rem;
            color: var(--muted);
            margin-top: 3px;
        }

        @media (max-width: 400px) {
            .promo-row { flex-direction: column; }
            .promo-topbar-title { font-size: 1rem; }
        }
    </style>
@endpush