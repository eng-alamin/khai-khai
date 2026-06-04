{{-- resources/views/livewire/vendor/menu-category-component.blade.php --}}
<div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="cat-alert cat-alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="cat-alert-close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="cat-alert cat-alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="cat-alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="cat-topbar">
            <div class="cat-topbar-title">
                <span class="title-emoji">🗂️</span>
                Menu Categories
            </div>
            <button class="btn-new-cat" wire:click="openCreate">
                <span class="plus-icon">＋</span>
                Add Category
            </button>
        </div>

        {{-- ── Search ── --}}
        <div class="cat-search">
            <div class="cat-search-inner">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search categories...">
            </div>
        </div>

        {{-- ── Filter Chips ── --}}
        <div class="cat-filters">
            <label class="filter-chip {{ $filterStatus === '' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterStatus" value=""> All
            </label>
            <label class="filter-chip {{ $filterStatus === 'active' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterStatus" value="active"> ✅ Active
            </label>
            <label class="filter-chip {{ $filterStatus === 'inactive' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterStatus" value="inactive"> 🔴 Inactive
            </label>
        </div>

        {{-- ── Category Cards ── --}}
        @forelse($categories as $i => $category)
            @php
                $statusClass = $category->is_active ? 'active' : 'inactive';
                $statusLabel = $category->is_active ? 'Active' : 'Inactive';
            @endphp

            <div class="cat-card">
                {{-- Top Row --}}
                <div class="cat-card-top">
                    <div class="cat-card-title">
                        @if($category->emoji)
                            <span class="cat-emoji">{{ $category->emoji }}</span>
                        @endif
                        {{ $category->name }}
                    </div>
                    <span class="cat-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                {{-- Meta --}}
                <div class="cat-card-meta">
                    <span class="cat-meta-item">
                        <span class="material-icons-round">restaurant_menu</span>
                        {{ $category->menu_items_count }} items
                    </span>
                    <span class="cat-meta-item">
                        <span class="material-icons-round">sort</span>
                        Order: {{ $category->sort_order }}
                    </span>
                </div>

                {{-- Bottom Row --}}
                <div class="cat-card-bottom">
                    <div style="display:flex;align-items:center;gap:10px;">
                        {{-- Toggle --}}
                        <label class="cat-toggle">
                            <input type="checkbox"
                                @checked($category->is_active)
                                wire:click="toggleActive({{ $category->id }})">
                            <span class="cat-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="cat-card-actions">
                        <button class="cat-btn-edit"
                            wire:click="openEdit({{ $category->id }})">
                            <span class="material-icons-round">drive_file_rename_outline</span>
                            Edit
                        </button>
                        <button class="cat-btn-delete"
                            wire:click="confirmDeleteRecord({{ $category->id }})">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </div>
                </div>
            </div>

        @empty
            <div class="cat-empty">
                <i class="bi bi-grid cat-empty-icon"></i>
                <p>No categories found.</p>
                <button class="btn-new-cat" wire:click="openCreate">+ Create New Category</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($categories->hasPages())
            <div class="cat-pagination">
                <small>Showing {{ $categories->firstItem() ?? 0 }}–{{ $categories->lastItem() ?? 0 }} of {{ $categories->total() }} total</small>
                {{ $categories->links('pagination::custom') }}
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Create / Edit Modal
         ══════════════════════════════════════ --}}
    @if($showModal)
        <div class="cat-modal-backdrop" wire:ignore.self wire:click.self="$set('showModal', false)">
            <div class="cat-modal">

                <div class="cat-modal-drag"></div>

                <div class="cat-modal-header">
                    <div class="cat-modal-title">
                        {{ $editId ? '✏️ Edit Category' : '🗂️ Create New Category' }}
                    </div>
                    <button class="cat-modal-close" wire:click="$set('showModal', false)">✕</button>
                </div>

                <div class="cat-modal-body">

                    {{-- Name --}}
                    <div class="cat-form-group">
                        <label class="cat-form-label">Category Name <span class="req">*</span></label>
                        <input type="text"
                            class="cat-form-control @error('name') is-invalid @enderror"
                            wire:model.defer="name"
                            placeholder="e.g. Rice, Burger, Dessert...">
                        @error('name') <div class="cat-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Emoji + Sort Order --}}
                    <div class="cat-row">
                        <div class="cat-col">
                            <div class="cat-form-group">
                                <label class="cat-form-label">Emoji</label>
                                <input type="text"
                                    class="cat-form-control @error('emoji') is-invalid @enderror"
                                    wire:model.defer="emoji"
                                    placeholder="e.g. 🍚 🍔 🍰"
                                    maxlength="10">
                                <div class="cat-form-hint">Optional — shown next to the category name.</div>
                                @error('emoji') <div class="cat-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="cat-col">
                            <div class="cat-form-group">
                                <label class="cat-form-label">Sort Order <span class="req">*</span></label>
                                <input type="number"
                                    class="cat-form-control @error('sort_order') is-invalid @enderror"
                                    wire:model.defer="sort_order"
                                    min="0" placeholder="0">
                                <div class="cat-form-hint">Lower number appears first.</div>
                                @error('sort_order') <div class="cat-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Active --}}
                    <label class="cat-switch-wrap">
                        <span class="cat-switch-label">Keep category active</span>
                        <label class="cat-toggle">
                            <input type="checkbox" wire:model.defer="is_active" id="catActive">
                            <span class="cat-toggle-slider"></span>
                        </label>
                    </label>

                </div>

                <div class="cat-modal-footer">
                    <button class="btn-cat-secondary"
                        wire:click="$set('showModal', false)">Cancel</button>
                    <button class="btn-cat-primary"
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
        <div class="cat-modal-backdrop">
            <div class="cat-delete-modal">
                <div class="cat-delete-icon">⚠️</div>
                <h6>Delete Category?</h6>
                <p>This category will be permanently deleted.<br>This action cannot be undone.</p>
                <div class="cat-delete-actions">
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
        .cat-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 16px 12px;
            background: var(--bg);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .cat-topbar-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.18rem;
            font-weight: 700;
            color: var(--dark);
        }
        .cat-topbar-title .title-emoji { font-size: 1.2rem; }

        /* ── Add Category Button ── */
        .btn-new-cat {
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
        .btn-new-cat:hover {
            background: #e02d7a;
            box-shadow: 0 6px 24px rgba(255,61,139,.45);
            transform: translateY(-1px);
        }
        .btn-new-cat .plus-icon {
            font-size: 1.1rem;
            font-weight: 400;
            line-height: 1;
        }

        /* ── Filter Bar ── */
        .cat-filters {
            display: flex;
            gap: 8px;
            padding: 8px 16px 12px;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .cat-filters::-webkit-scrollbar { display: none; }
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
        .cat-search { padding: 0 16px 12px; }
        .cat-search-inner { position: relative; }
        .cat-search-inner .search-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: .95rem;
            pointer-events: none;
        }
        .cat-search-inner input {
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
        .cat-search-inner input:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 3px rgba(255,61,139,.1);
        }

        /* ── Category Card ── */
        .cat-card {
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
        .cat-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
            background: var(--pink);
            border-radius: 4px 0 0 4px;
            opacity: 0;
            transition: var(--transition);
        }
        .cat-card:hover {
            box-shadow: var(--shadow-hover);
            border-color: rgba(255,61,139,.2);
            transform: translateY(-2px);
        }
        .cat-card:hover::before { opacity: 1; }

        /* card top row */
        .cat-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }
        .cat-card-title {
            font-size: .98rem;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.3;
            flex: 1;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .cat-emoji { font-size: 1.1rem; }

        .cat-status-badge {
            flex-shrink: 0;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: .68rem;
            font-weight: 600;
            font-family: var(--font);
        }
        .cat-status-badge.active  { background: #E8FAF0; color: #1A9453; }
        .cat-status-badge.inactive { background: #FFF0F0; color: #E53935; }

        /* card meta row */
        .cat-card-meta {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }
        .cat-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: .74rem;
            color: var(--muted);
        }
        .cat-meta-item .material-icons-round { font-size: .85rem; }

        /* card bottom row */
        .cat-card-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
            gap: 8px;
        }

        /* toggle */
        .cat-toggle {
            position: relative;
            width: 40px;
            height: 22px;
        }
        .cat-toggle input { opacity: 0; width: 0; height: 0; }
        .cat-toggle-slider {
            position: absolute;
            inset: 0;
            background: #ddd;
            border-radius: 50px;
            cursor: pointer;
            transition: var(--transition);
        }
        .cat-toggle-slider::before {
            content: '';
            position: absolute;
            width: 16px; height: 16px;
            left: 3px; top: 3px;
            background: #fff;
            border-radius: 50%;
            transition: var(--transition);
            box-shadow: 0 1px 4px rgba(0,0,0,.2);
        }
        .cat-toggle input:checked + .cat-toggle-slider { background: var(--pink); }
        .cat-toggle input:checked + .cat-toggle-slider::before { transform: translateX(18px); }

        /* action buttons */
        .cat-card-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .cat-btn-edit {
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
        .cat-btn-edit:hover { background: var(--pink-light); color: var(--pink); }
        .cat-btn-edit .material-icons-round { font-size: .95rem; }

        .cat-btn-delete {
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
        .cat-btn-delete:hover { background: #FFD6D6; }
        .cat-btn-delete .material-icons-round { font-size: .95rem; }

        /* ── Empty State ── */
        .cat-empty {
            text-align: center;
            padding: 60px 20px;
        }
        .cat-empty-icon {
            font-size: 3rem;
            opacity: .25;
            display: block;
            margin-bottom: 12px;
        }
        .cat-empty p { color: var(--muted); font-size: .88rem; margin: 0 0 16px; }

        /* ── Pagination ── */
        .cat-pagination {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cat-pagination small { font-size: .74rem; color: var(--muted); }

        /* ── Alerts ── */
        .cat-alert {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 12px 16px;
            padding: 12px 14px;
            border-radius: var(--radius-md);
            font-size: .82rem;
        }
        .cat-alert span { flex: 1; }
        .cat-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
        .cat-alert-error   { background: #FFF0F0; color: #E53935; border: 1px solid #FFBCBC; }
        .cat-alert-close {
            background: none; border: none; cursor: pointer;
            font-size: 1.1rem; color: inherit; padding: 0; line-height: 1;
        }

        /* ── Modal ── */
        .cat-modal-backdrop {
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

        .cat-modal {
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
            .cat-modal-backdrop { align-items: center; padding: 20px; }
            .cat-modal { border-radius: var(--radius-lg); max-height: 85vh; }
        }

        .cat-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 18px 0;
            flex-shrink: 0;
        }
        .cat-modal-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--dark);
        }
        .cat-modal-close {
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
        .cat-modal-close:hover { background: var(--pink-light); color: var(--pink); }

        .cat-modal-drag {
            width: 40px; height: 4px;
            background: var(--border);
            border-radius: 4px;
            margin: 10px auto 0;
            flex-shrink: 0;
        }

        .cat-modal-body {
            overflow-y: auto;
            padding: 18px;
            flex: 1;
        }
        .cat-modal-body::-webkit-scrollbar { width: 4px; }
        .cat-modal-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

        .cat-modal-footer {
            display: flex;
            gap: 10px;
            padding: 14px 18px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }

        /* Form elements */
        .cat-form-group { margin-bottom: 14px; }
        .cat-form-label {
            display: block;
            font-size: .8rem;
            font-weight: 600;
            color: var(--soft-dark);
            margin-bottom: 5px;
        }
        .cat-form-label .req { color: var(--pink); margin-left: 2px; }
        .cat-form-control {
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
        .cat-form-control:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 3px rgba(255,61,139,.1);
            background: #fff;
        }
        .cat-form-control.is-invalid { border-color: #E53935; }
        .cat-invalid-feedback { color: #E53935; font-size: .74rem; margin-top: 4px; }
        .cat-form-hint { font-size: .72rem; color: var(--muted); margin-top: 4px; }

        .cat-row { display: flex; gap: 12px; }
        .cat-col { flex: 1; min-width: 0; }

        .cat-switch-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: var(--bg);
            border-radius: var(--radius-sm);
            cursor: pointer;
        }
        .cat-switch-label {
            font-size: .84rem;
            color: var(--soft-dark);
            font-weight: 500;
            flex: 1;
        }

        /* Buttons */
        .btn-cat-primary {
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
        .btn-cat-primary:hover { background: #e02d7a; }
        .btn-cat-primary:disabled { opacity: .6; cursor: not-allowed; }

        .btn-cat-secondary {
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
        .btn-cat-secondary:hover { background: var(--border); }

        /* ── Delete Modal ── */
        .cat-delete-modal {
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
        .cat-delete-icon {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: #FFF0F0;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
            font-size: 1.6rem;
        }
        .cat-delete-modal h6 {
            font-size: .98rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 6px;
        }
        .cat-delete-modal p {
            font-size: .8rem;
            color: var(--muted);
            margin: 0 0 20px;
            line-height: 1.5;
        }
        .cat-delete-actions {
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

        @media (max-width: 400px) {
            .cat-row { flex-direction: column; }
            .cat-topbar-title { font-size: 1rem; }
        }

    </style>
@endpush