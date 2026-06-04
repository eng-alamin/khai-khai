{{-- resources/views/livewire/vendor/menu/menu-item-component.blade.php --}}
<div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="item-alert item-alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="item-alert-close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="item-alert item-alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="item-alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="item-topbar">
            <div class="item-topbar-title">
                <span class="title-emoji">🍽️</span>
                Menu Items
            </div>
            <button class="btn-new-item" wire:click="openCreate">
                <span class="plus-icon">＋</span>
                Add Item
            </button>
        </div>

        {{-- ── Search ── --}}
        <div class="item-search">
            <div class="item-search-inner">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search items...">
            </div>
        </div>

        {{-- ── Filter Chips ── --}}
        <div class="item-filters">
            <label class="filter-chip {{ $filterStatus === '' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterStatus" value=""> All
            </label>
            <label class="filter-chip {{ $filterStatus === 'available' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterStatus" value="available"> ✅ Available
            </label>
            <label class="filter-chip {{ $filterStatus === 'unavailable' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterStatus" value="unavailable"> 🔴 Unavailable
            </label>

            {{-- Category filter chips --}}
            <label class="filter-chip {{ $filterCategory === '' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterCategory" value=""> All Categories
            </label>
            @foreach($categories as $cat)
                <label class="filter-chip {{ $filterCategory == $cat->id ? 'active' : '' }}">
                    <input type="radio" wire:model.live="filterCategory" value="{{ $cat->id }}">
                    {{ $cat->emoji }} {{ $cat->name }}
                </label>
            @endforeach
        </div>

        {{-- ── Item Cards ── --}}
        @forelse($items as $i => $item)
            @php
                $statusClass = $item->is_available ? 'available' : 'unavailable';
                $statusLabel = $item->is_available ? 'Available' : 'Unavailable';
            @endphp

            <div class="item-card">
                {{-- Top Row --}}
                <div class="item-card-top">
                    <div class="item-card-thumb">
                        @if($item->image_url)
                            <img src="{{ $item->image_url }}" alt="{{ $item->name }}">
                        @else
                            <span class="item-emoji-fallback">{{ $item->emoji ?? '🍽️' }}</span>
                        @endif
                    </div>
                    <div class="item-card-info">
                        <div class="item-card-title">{{ $item->name }}</div>
                        @if($item->description)
                            <div class="item-card-desc">{{ Str::limit($item->description, 55) }}</div>
                        @endif
                    </div>
                    <span class="item-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                {{-- Meta --}}
                <div class="item-card-meta">
                    @if($item->category)
                        <span class="item-cat-pill">
                            {{ $item->category->emoji }} {{ $item->category->name }}
                        </span>
                    @endif
                    <span class="item-meta-item">
                        <span class="material-icons-round">sort</span>
                        Order: {{ $item->sort_order }}
                    </span>
                </div>

                {{-- Bottom Row --}}
                <div class="item-card-bottom">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="item-price-badge">৳{{ number_format($item->price / 100) }}</span>
                        <label class="item-toggle">
                            <input type="checkbox"
                                @checked($item->is_available)
                                wire:click="toggleAvailability({{ $item->id }})">
                            <span class="item-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="item-card-actions">
                        <button class="item-btn-edit"
                            wire:click="openEdit({{ $item->id }})">
                            <span class="material-icons-round">drive_file_rename_outline</span>
                            Edit
                        </button>
                        <button class="item-btn-delete"
                            wire:click="confirmDeleteRecord({{ $item->id }})">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </div>
                </div>
            </div>

        @empty
            <div class="item-empty">
                <i class="bi bi-inbox item-empty-icon"></i>
                <p>No items found.</p>
                <button class="btn-new-item" wire:click="openCreate">+ Add New Item</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($items->hasPages())
            <div class="item-pagination">
                <small>Showing {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} of {{ $items->total() }} total</small>
                {{ $items->links('pagination::custom') }}
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Create / Edit Modal
         ══════════════════════════════════════ --}}
    @if($showModal)
        <div class="item-modal-backdrop" wire:ignore.self wire:click.self="$set('showModal', false)">
            <div class="item-modal">

                <div class="item-modal-drag"></div>

                <div class="item-modal-header">
                    <div class="item-modal-title">
                        {{ $editId ? '✏️ Edit Menu Item' : '🍽️ Add New Menu Item' }}
                    </div>
                    <button class="item-modal-close" wire:click="$set('showModal', false)">✕</button>
                </div>

                <div class="item-modal-body">

                    {{-- Category + Name --}}
                    <div class="item-row">
                        <div class="item-col">
                            <div class="item-form-group">
                                <label class="item-form-label">Category <span class="req">*</span></label>
                                <select class="item-form-control @error('category_id') is-invalid @enderror"
                                    wire:model.defer="category_id">
                                    <option value="0">— Select —</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->emoji }} {{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id') <div class="item-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="item-col">
                            <div class="item-form-group">
                                <label class="item-form-label">Item Name <span class="req">*</span></label>
                                <input type="text"
                                    class="item-form-control @error('name') is-invalid @enderror"
                                    wire:model.defer="name"
                                    placeholder="e.g. Chicken Burger, Rice + Fish">
                                @error('name') <div class="item-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Price + Emoji + Sort Order --}}
                    <div class="item-row">
                        <div class="item-col">
                            <div class="item-form-group">
                                <label class="item-form-label">Price (BDT) <span class="req">*</span></label>
                                <div class="item-input-prefix">
                                    <span class="item-input-prefix-text">৳</span>
                                    <input type="number"
                                        class="item-form-control @error('price') is-invalid @enderror"
                                        wire:model.defer="price"
                                        min="1" placeholder="0">
                                </div>
                                @error('price') <div class="item-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="item-col">
                            <div class="item-form-group">
                                <label class="item-form-label">Emoji</label>
                                <input type="text"
                                    class="item-form-control @error('emoji') is-invalid @enderror"
                                    wire:model.defer="emoji"
                                    placeholder="e.g. 🍚 🍔 🍕"
                                    maxlength="10">
                                @error('emoji') <div class="item-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="item-col">
                            <div class="item-form-group">
                                <label class="item-form-label">Sort Order</label>
                                <input type="number"
                                    class="item-form-control @error('sort_order') is-invalid @enderror"
                                    wire:model.defer="sort_order"
                                    min="0" placeholder="0">
                                <div class="item-form-hint">Lower number appears first.</div>
                                @error('sort_order') <div class="item-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="item-form-group">
                        <label class="item-form-label">Description</label>
                        <textarea class="item-form-control @error('description') is-invalid @enderror"
                            wire:model.defer="description"
                            rows="3"
                            placeholder="Brief description of the item...">{{ $description }}</textarea>
                        <div class="char-count">{{ strlen($description) }} / 500</div>
                        @error('description') <div class="item-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Image --}}
                    <div class="item-form-group">
                        <label class="item-form-label">Image</label>

                        {{-- Existing image preview --}}
                        @if($existingImage && !$image)
                            <div class="item-img-preview">
                                <img src="{{ $existingImage }}" alt="Current">
                                <div class="item-img-preview-info">
                                    <span>Current image</span>
                                    <button type="button" class="item-img-remove"
                                        wire:click="$set('existingImage', null)">
                                        <span class="material-icons-round">delete</span> Remove
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- New image preview --}}
                        @if($image)
                            <div class="item-img-preview">
                                <img src="{{ $image->temporaryUrl() }}" alt="Preview">
                                <div class="item-img-preview-info">
                                    <span>{{ $image->getClientOriginalName() }}</span>
                                    <button type="button" class="item-img-remove"
                                        wire:click="$set('image', null)">
                                        <span class="material-icons-round">close</span> Cancel
                                    </button>
                                </div>
                            </div>
                        @endif

                        <input type="file"
                            class="item-form-control @error('image') is-invalid @enderror"
                            wire:model="image" accept="image/*">
                        <div class="item-form-hint">JPG, PNG, WEBP — max 2 MB. Optional.</div>

                        <div wire:loading wire:target="image" class="item-upload-progress">
                            <div class="item-upload-bar"></div>
                            <small>Uploading...</small>
                        </div>

                        @error('image') <div class="item-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Available toggle --}}
                    <label class="item-switch-wrap">
                        <span class="item-switch-label">Item is currently available</span>
                        <label class="item-toggle">
                            <input type="checkbox" wire:model.defer="is_available" id="itemAvailable">
                            <span class="item-toggle-slider"></span>
                        </label>
                    </label>

                </div>

                <div class="item-modal-footer">
                    <button class="btn-item-secondary"
                        wire:click="$set('showModal', false)">Cancel</button>
                    <button class="btn-item-primary"
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
        <div class="item-modal-backdrop">
            <div class="item-delete-modal">
                <div class="item-delete-icon">⚠️</div>
                <h6>Delete Item?</h6>
                <p>The image and all data will be permanently removed.<br>This action cannot be undone.</p>
                <div class="item-delete-actions">
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

        /* ── Top Bar ── */
        .item-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 16px 12px;
            background: var(--bg);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .item-topbar-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.18rem;
            font-weight: 700;
            color: var(--dark);
        }
        .item-topbar-title .title-emoji { font-size: 1.2rem; }

        /* ── Add Item Button ── */
        .btn-new-item {
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
        .btn-new-item:hover {
            background: #e02d7a;
            box-shadow: 0 6px 24px rgba(255,61,139,.45);
            transform: translateY(-1px);
        }
        .btn-new-item .plus-icon { font-size: 1.1rem; font-weight: 400; line-height: 1; }

        /* ── Filter Bar ── */
        .item-filters {
            display: flex;
            gap: 8px;
            padding: 8px 16px 12px;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .item-filters::-webkit-scrollbar { display: none; }
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
        .item-search { padding: 0 16px 12px; }
        .item-search-inner { position: relative; }
        .item-search-inner .search-icon {
            position: absolute;
            left: 13px; top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: .95rem;
            pointer-events: none;
        }
        .item-search-inner input {
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
        .item-search-inner input:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 3px rgba(255,61,139,.1);
        }

        /* ── Item Card ── */
        .item-card {
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
        .item-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
            background: var(--pink);
            border-radius: 4px 0 0 4px;
            opacity: 0;
            transition: var(--transition);
        }
        .item-card:hover {
            box-shadow: var(--shadow-hover);
            border-color: rgba(255,61,139,.2);
            transform: translateY(-2px);
        }
        .item-card:hover::before { opacity: 1; }

        /* card top row */
        .item-card-top {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
        }
        .item-card-thumb {
            flex-shrink: 0;
            width: 52px; height: 52px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            background: var(--bg);
        }
        .item-card-thumb img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .item-emoji-fallback { font-size: 1.6rem; line-height: 1; }

        .item-card-info { flex: 1; min-width: 0; }
        .item-card-title {
            font-size: .98rem;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .item-card-desc {
            font-size: .78rem;
            color: var(--muted);
            margin-top: 2px;
            line-height: 1.4;
        }

        .item-status-badge {
            flex-shrink: 0;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: .68rem;
            font-weight: 600;
            font-family: var(--font);
        }
        .item-status-badge.available   { background: #E8FAF0; color: #1A9453; }
        .item-status-badge.unavailable { background: #FFF0F0; color: #E53935; }

        /* meta row */
        .item-card-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }
        .item-cat-pill {
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
        .item-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: .74rem;
            color: var(--muted);
        }
        .item-meta-item .material-icons-round { font-size: .85rem; }

        /* card bottom row */
        .item-card-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
            gap: 8px;
        }
        .item-price-badge {
            font-size: .9rem;
            font-weight: 700;
            color: var(--pink);
        }

        /* toggle */
        .item-toggle {
            position: relative;
            width: 40px; height: 22px;
        }
        .item-toggle input { opacity: 0; width: 0; height: 0; }
        .item-toggle-slider {
            position: absolute;
            inset: 0;
            background: #ddd;
            border-radius: 50px;
            cursor: pointer;
            transition: var(--transition);
        }
        .item-toggle-slider::before {
            content: '';
            position: absolute;
            width: 16px; height: 16px;
            left: 3px; top: 3px;
            background: #fff;
            border-radius: 50%;
            transition: var(--transition);
            box-shadow: 0 1px 4px rgba(0,0,0,.2);
        }
        .item-toggle input:checked + .item-toggle-slider { background: var(--pink); }
        .item-toggle input:checked + .item-toggle-slider::before { transform: translateX(18px); }

        /* action buttons */
        .item-card-actions { display: flex; align-items: center; gap: 6px; }
        .item-btn-edit {
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
        .item-btn-edit:hover { background: var(--pink-light); color: var(--pink); }
        .item-btn-edit .material-icons-round { font-size: .95rem; }

        .item-btn-delete {
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
        .item-btn-delete:hover { background: #FFD6D6; }
        .item-btn-delete .material-icons-round { font-size: .95rem; }

        /* ── Empty State ── */
        .item-empty { text-align: center; padding: 60px 20px; }
        .item-empty-icon {
            font-size: 3rem; opacity: .25;
            display: block; margin-bottom: 12px;
        }
        .item-empty p { color: var(--muted); font-size: .88rem; margin: 0 0 16px; }

        /* ── Pagination ── */
        .item-pagination {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .item-pagination small { font-size: .74rem; color: var(--muted); }

        /* ── Alerts ── */
        .item-alert {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 12px 16px;
            padding: 12px 14px;
            border-radius: var(--radius-md);
            font-size: .82rem;
        }
        .item-alert span { flex: 1; }
        .item-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
        .item-alert-error   { background: #FFF0F0; color: #E53935; border: 1px solid #FFBCBC; }
        .item-alert-close {
            background: none; border: none; cursor: pointer;
            font-size: 1.1rem; color: inherit; padding: 0; line-height: 1;
        }

        /* ── Modal ── */
        .item-modal-backdrop {
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

        .item-modal {
            background: var(--card-bg);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            width: 100%;
            max-width: 600px;
            max-height: 92vh;
            display: flex;
            flex-direction: column;
            animation: slideUp .22s cubic-bezier(.4,0,.2,1);
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        @media (min-width: 640px) {
            .item-modal-backdrop { align-items: center; padding: 20px; }
            .item-modal { border-radius: var(--radius-lg); max-height: 88vh; }
        }

        .item-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 18px 0;
            flex-shrink: 0;
        }
        .item-modal-title { font-size: 1rem; font-weight: 700; color: var(--dark); }
        .item-modal-close {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--bg);
            border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--soft-dark);
            font-size: 1rem;
            transition: var(--transition);
        }
        .item-modal-close:hover { background: var(--pink-light); color: var(--pink); }

        .item-modal-drag {
            width: 40px; height: 4px;
            background: var(--border);
            border-radius: 4px;
            margin: 10px auto 0;
            flex-shrink: 0;
        }

        .item-modal-body {
            overflow-y: auto;
            padding: 18px;
            flex: 1;
        }
        .item-modal-body::-webkit-scrollbar { width: 4px; }
        .item-modal-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

        .item-modal-footer {
            display: flex;
            gap: 10px;
            padding: 14px 18px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }

        /* Form */
        .item-form-group { margin-bottom: 14px; }
        .item-form-label {
            display: block;
            font-size: .8rem;
            font-weight: 600;
            color: var(--soft-dark);
            margin-bottom: 5px;
        }
        .item-form-label .req { color: var(--pink); margin-left: 2px; }
        .item-form-control {
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
        .item-form-control:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 3px rgba(255,61,139,.1);
            background: #fff;
        }
        .item-form-control.is-invalid { border-color: #E53935; }
        .item-invalid-feedback { color: #E53935; font-size: .74rem; margin-top: 4px; }
        .item-form-hint { font-size: .72rem; color: var(--muted); margin-top: 4px; }

        .item-input-prefix { display: flex; align-items: stretch; }
        .item-input-prefix-text {
            padding: 10px 11px;
            background: var(--border);
            border: 1.5px solid var(--border);
            border-right: none;
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
            font-size: .84rem;
            color: var(--soft-dark);
            font-weight: 600;
        }
        .item-input-prefix .item-form-control {
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
        }

        .item-row { display: flex; gap: 12px; }
        .item-col { flex: 1; min-width: 0; }

        /* Image preview */
        .item-img-preview {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            background: var(--bg);
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--border);
            margin-bottom: 10px;
        }
        .item-img-preview img {
            width: 56px; height: 56px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border);
            flex-shrink: 0;
        }
        .item-img-preview-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .item-img-preview-info span { font-size: .75rem; color: var(--muted); }
        .item-img-remove {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            background: #FFF0F0;
            border: none;
            border-radius: var(--radius-sm);
            padding: 4px 10px;
            font-size: .74rem;
            color: #E53935;
            cursor: pointer;
            font-family: var(--font);
            font-weight: 600;
            transition: var(--transition);
        }
        .item-img-remove:hover { background: #FFD6D6; }
        .item-img-remove .material-icons-round { font-size: .8rem; }

        /* Upload progress */
        .item-upload-progress { margin-top: 8px; }
        .item-upload-bar {
            height: 4px;
            border-radius: 99px;
            background: linear-gradient(90deg, var(--pink), #ff8fab);
            background-size: 200% 100%;
            animation: shimmer 1.2s infinite;
            margin-bottom: 4px;
        }
        @keyframes shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .item-upload-progress small { font-size: .72rem; color: var(--muted); }

        .item-switch-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: var(--bg);
            border-radius: var(--radius-sm);
            cursor: pointer;
        }
        .item-switch-label {
            font-size: .84rem;
            color: var(--soft-dark);
            font-weight: 500;
            flex: 1;
        }

        /* Buttons */
        .btn-item-primary {
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
        .btn-item-primary:hover { background: #e02d7a; }
        .btn-item-primary:disabled { opacity: .6; cursor: not-allowed; }

        .btn-item-secondary {
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
        .btn-item-secondary:hover { background: var(--border); }

        /* ── Delete Modal ── */
        .item-delete-modal {
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
        .item-delete-icon {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: #FFF0F0;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
            font-size: 1.6rem;
        }
        .item-delete-modal h6 {
            font-size: .98rem; font-weight: 700;
            color: var(--dark); margin: 0 0 6px;
        }
        .item-delete-modal p {
            font-size: .8rem; color: var(--muted);
            margin: 0 0 20px; line-height: 1.5;
        }
        .item-delete-actions { display: flex; gap: 10px; justify-content: center; }

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

        /* Spinner */
        .spinner-sm {
            width: 14px; height: 14px;
            border: 2px solid rgba(255,255,255,.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Char counter */
        .char-count {
            text-align: right;
            font-size: .7rem;
            color: var(--muted);
            margin-top: 3px;
        }

        @media (max-width: 400px) {
            .item-row { flex-direction: column; }
            .item-topbar-title { font-size: 1rem; }
        }

    </style>
@endpush