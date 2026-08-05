{{-- resources/views/livewire/admin/category-component.blade.php --}}
<div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="acat-alert acat-alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="acat-alert-close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="acat-alert acat-alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="acat-alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="acat-topbar">
            <div class="acat-topbar-title">
                <span class="title-emoji">🗂️</span>
                Categories
            </div>
            <button class="btn-new-acat" wire:click="openCreate">
                <span class="plus-icon">＋</span>
                Add Category
            </button>
        </div>

        {{-- ── Search ── --}}
        <div class="acat-search">
            <div class="acat-search-inner">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search categories...">
            </div>
        </div>

        {{-- ── Filter Chips: Status ── --}}
        <div class="acat-filters">
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

        {{-- ── Filter Chips: Type ── --}}
        <div class="acat-filters">
            <label class="filter-chip {{ $filterType === '' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterType" value=""> All Types
            </label>
            @foreach($categoryTypes as $value => $label)
                <label class="filter-chip {{ $filterType === $value ? 'active' : '' }}">
                    <input type="radio" wire:model.live="filterType" value="{{ $value }}"> {{ $label }}
                </label>
            @endforeach
        </div>

        {{-- ── Category Cards ── --}}
        @forelse($categories as $category)
            @php
                $statusClass = $category->is_active ? 'available' : 'unavailable';
                $statusLabel = $category->is_active ? 'Active' : 'Inactive';
            @endphp

            <div class="acat-card">
                {{-- Top Row --}}
                <div class="acat-card-top">
                    <div class="acat-card-thumb">
                        @if($category->image_url)
                            <img src="{{ $category->image_url }}" alt="{{ $category->name }}">
                        @else
                            <span class="acat-emoji-fallback">🗂️</span>
                        @endif
                    </div>
                    <div class="acat-card-info">
                        <div class="acat-card-title">{{ $category->name }}</div>
                        @php
                            $isFoodType = $category->type === \App\Models\Category::TYPE_FOOD;
                            $itemCount  = $isFoodType ? $category->foods_count : $category->products_count;
                        @endphp
                        <div class="acat-card-desc">
                            {{ $itemCount }} {{ $isFoodType ? 'food(s)' : 'product(s)' }}
                        </div>
                    </div>
                    <span class="acat-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                {{-- Meta --}}
                <div class="acat-card-meta">
                    <span class="acat-type-badge">{{ $categoryTypes[$category->type] ?? ucfirst($category->type) }}</span>
                    <span class="acat-meta-item">
                        <span class="material-icons-round">sort</span>
                        Order: {{ $category->sort_order }}
                    </span>
                </div>

                {{-- Bottom Row --}}
                <div class="acat-card-bottom">
                    <label class="acat-toggle">
                        <input type="checkbox"
                            @checked($category->is_active)
                            wire:click="toggleActive({{ $category->id }})">
                        <span class="acat-toggle-slider"></span>
                    </label>
                    <div class="acat-card-actions">
                        <button class="acat-btn-edit"
                            wire:click="openEdit({{ $category->id }})">
                            <span class="material-icons-round">drive_file_rename_outline</span>
                            Edit
                        </button>
                        <button class="acat-btn-delete"
                            wire:click="confirmDeleteRecord({{ $category->id }})">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </div>
                </div>
            </div>

        @empty
            <div class="acat-empty">
                <i class="bi bi-inbox acat-empty-icon"></i>
                <p>No categories found.</p>
                <button class="btn-new-acat" wire:click="openCreate">+ Add New Category</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($categories->hasPages())
            <div class="acat-pagination">
                <small>Showing {{ $categories->firstItem() ?? 0 }}–{{ $categories->lastItem() ?? 0 }} of {{ $categories->total() }} total</small>
                {{ $categories->links('pagination::custom') }}
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Create / Edit Modal
         ══════════════════════════════════════ --}}
    @if($showModal)
        <div class="acat-modal-backdrop" wire:ignore.self wire:click.self="$set('showModal', false)">
            <div class="acat-modal">

                <div class="acat-modal-drag"></div>

                <div class="acat-modal-header">
                    <div class="acat-modal-title">
                        {{ $editId ? '✏️ Edit Category' : '🗂️ Add New Category' }}
                    </div>
                    <button class="acat-modal-close" wire:click="$set('showModal', false)">✕</button>
                </div>

                <div class="acat-modal-body">

                    {{-- Type --}}
                    <div class="acat-form-group">
                        <label class="acat-form-label">Category Type <span class="req">*</span></label>
                        <select class="acat-form-control @error('type') is-invalid @enderror"
                            wire:model.defer="type">
                            @foreach($categoryTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <div class="acat-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Name + Sort Order --}}
                    <div class="acat-row">
                        <div class="acat-col">
                            <div class="acat-form-group">
                                <label class="acat-form-label">Category Name <span class="req">*</span></label>
                                <input type="text"
                                    class="acat-form-control @error('name') is-invalid @enderror"
                                    wire:model.defer="name"
                                    placeholder="e.g. Offers, Fruits, Vegetables">
                                @error('name') <div class="acat-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="acat-col">
                            <div class="acat-form-group">
                                <label class="acat-form-label">Sort Order</label>
                                <input type="number"
                                    class="acat-form-control @error('sort_order') is-invalid @enderror"
                                    wire:model.defer="sort_order"
                                    min="0" placeholder="0">
                                <div class="acat-form-hint">Lower number appears first.</div>
                                @error('sort_order') <div class="acat-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Image --}}
                    <div class="acat-form-group">
                        <label class="acat-form-label">Tile Image</label>

                        {{-- Existing image preview --}}
                        @if($existingImage && !$image)
                            <div class="acat-img-preview">
                                <img src="{{ $existingImage }}" alt="Current">
                                <div class="acat-img-preview-info">
                                    <span>Current image</span>
                                    <button type="button" class="acat-img-remove"
                                        wire:click="$set('existingImage', null)">
                                        <span class="material-icons-round">delete</span> Remove
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- New image preview --}}
                        @if($image)
                            <div class="acat-img-preview">
                                <img src="{{ $image->temporaryUrl() }}" alt="Preview">
                                <div class="acat-img-preview-info">
                                    <span>{{ $image->getClientOriginalName() }}</span>
                                    <button type="button" class="acat-img-remove"
                                        wire:click="$set('image', null)">
                                        <span class="material-icons-round">close</span> Cancel
                                    </button>
                                </div>
                            </div>
                        @endif

                        <input type="file"
                            class="acat-form-control @error('image') is-invalid @enderror"
                            wire:model="image" accept="image/*">
                        <div class="acat-form-hint">JPG, PNG, WEBP — max 2 MB. Shown as the homepage tile image.</div>

                        <div wire:loading wire:target="image" class="acat-upload-progress">
                            <div class="acat-upload-bar"></div>
                            <small>Uploading...</small>
                        </div>

                        @error('image') <div class="acat-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Active toggle --}}
                    <label class="acat-switch-wrap">
                        <span class="acat-switch-label">Category is currently active</span>
                        <label class="acat-toggle">
                            <input type="checkbox" wire:model.defer="is_active" id="acatActive">
                            <span class="acat-toggle-slider"></span>
                        </label>
                    </label>

                </div>

                <div class="acat-modal-footer">
                    <button class="btn-acat-secondary"
                        wire:click="$set('showModal', false)">Cancel</button>
                    <button class="btn-acat-primary"
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
        <div class="acat-modal-backdrop">
            <div class="acat-delete-modal">
                <div class="acat-delete-icon">⚠️</div>
                <h6>Delete Category?</h6>
                <p>The image and all data will be permanently removed.<br>This action cannot be undone.</p>
                <div class="acat-delete-actions">
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
        .acat-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 16px 12px;
            background: var(--bg);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .acat-topbar-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.18rem;
            font-weight: 700;
            color: var(--dark);
        }
        .acat-topbar-title .title-emoji { font-size: 1.2rem; }

        /* ── Add Category Button ── */
        .btn-new-acat {
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
        .btn-new-acat:hover {
            background: #e02d7a;
            box-shadow: 0 6px 24px rgba(255,61,139,.45);
            transform: translateY(-1px);
        }
        .btn-new-acat .plus-icon { font-size: 1.1rem; font-weight: 400; line-height: 1; }

        /* ── Filter Bar ── */
        .acat-filters {
            display: flex;
            gap: 8px;
            padding: 8px 16px 12px;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .acat-filters::-webkit-scrollbar { display: none; }
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
        .acat-search { padding: 0 16px 12px; }
        .acat-search-inner { position: relative; }
        .acat-search-inner .search-icon {
            position: absolute;
            left: 13px; top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: .95rem;
            pointer-events: none;
        }
        .acat-search-inner input {
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
        .acat-search-inner input:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 3px rgba(255,61,139,.1);
        }

        /* ── Category Card ── */
        .acat-card {
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
        .acat-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
            background: var(--pink);
            border-radius: 4px 0 0 4px;
            opacity: 0;
            transition: var(--transition);
        }
        .acat-card:hover {
            box-shadow: var(--shadow-hover);
            border-color: rgba(255,61,139,.2);
            transform: translateY(-2px);
        }
        .acat-card:hover::before { opacity: 1; }

        /* card top row */
        .acat-card-top {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
        }
        .acat-card-thumb {
            flex-shrink: 0;
            width: 52px; height: 52px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            background: var(--bg);
        }
        .acat-card-thumb img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .acat-emoji-fallback { font-size: 1.6rem; line-height: 1; }

        .acat-card-info { flex: 1; min-width: 0; }
        .acat-card-title {
            font-size: .98rem;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .acat-card-desc {
            font-size: .78rem;
            color: var(--muted);
            margin-top: 2px;
            line-height: 1.4;
        }

        .acat-status-badge {
            flex-shrink: 0;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: .68rem;
            font-weight: 600;
            font-family: var(--font);
        }
        .acat-status-badge.available   { background: #E8FAF0; color: #1A9453; }
        .acat-status-badge.unavailable { background: #FFF0F0; color: #E53935; }

        /* type badge */
        .acat-type-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: .68rem;
            font-weight: 600;
            font-family: var(--font);
            background: #EEF2FF;
            color: #4F46E5;
        }

        /* meta row */
        .acat-card-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }
        .acat-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: .74rem;
            color: var(--muted);
        }
        .acat-meta-item .material-icons-round { font-size: .85rem; }

        /* card bottom row */
        .acat-card-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
            gap: 8px;
        }

        /* toggle */
        .acat-toggle {
            position: relative;
            width: 40px; height: 22px;
        }
        .acat-toggle input { opacity: 0; width: 0; height: 0; }
        .acat-toggle-slider {
            position: absolute;
            inset: 0;
            background: #ddd;
            border-radius: 50px;
            cursor: pointer;
            transition: var(--transition);
        }
        .acat-toggle-slider::before {
            content: '';
            position: absolute;
            width: 16px; height: 16px;
            left: 3px; top: 3px;
            background: #fff;
            border-radius: 50%;
            transition: var(--transition);
            box-shadow: 0 1px 4px rgba(0,0,0,.2);
        }
        .acat-toggle input:checked + .acat-toggle-slider { background: var(--pink); }
        .acat-toggle input:checked + .acat-toggle-slider::before { transform: translateX(18px); }

        /* action buttons */
        .acat-card-actions { display: flex; align-items: center; gap: 6px; }
        .acat-btn-edit {
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
        .acat-btn-edit:hover { background: var(--pink-light); color: var(--pink); }
        .acat-btn-edit .material-icons-round { font-size: .95rem; }

        .acat-btn-delete {
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
        .acat-btn-delete:hover { background: #FFD6D6; }
        .acat-btn-delete .material-icons-round { font-size: .95rem; }

        /* ── Empty State ── */
        .acat-empty { text-align: center; padding: 60px 20px; }
        .acat-empty-icon {
            font-size: 3rem; opacity: .25;
            display: block; margin-bottom: 12px;
        }
        .acat-empty p { color: var(--muted); font-size: .88rem; margin: 0 0 16px; }

        /* ── Pagination ── */
        .acat-pagination {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .acat-pagination small { font-size: .74rem; color: var(--muted); }

        /* ── Alerts ── */
        .acat-alert {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 12px 16px;
            padding: 12px 14px;
            border-radius: var(--radius-md);
            font-size: .82rem;
        }
        .acat-alert span { flex: 1; }
        .acat-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
        .acat-alert-error   { background: #FFF0F0; color: #E53935; border: 1px solid #FFBCBC; }
        .acat-alert-close {
            background: none; border: none; cursor: pointer;
            font-size: 1.1rem; color: inherit; padding: 0; line-height: 1;
        }

        /* ── Modal ── */
        .acat-modal-backdrop {
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

        .acat-modal {
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
            .acat-modal-backdrop { align-items: center; padding: 20px; }
            .acat-modal { border-radius: var(--radius-lg); max-height: 88vh; }
        }

        .acat-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 18px 0;
            flex-shrink: 0;
        }
        .acat-modal-title { font-size: 1rem; font-weight: 700; color: var(--dark); }
        .acat-modal-close {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--bg);
            border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--soft-dark);
            font-size: 1rem;
            transition: var(--transition);
        }
        .acat-modal-close:hover { background: var(--pink-light); color: var(--pink); }

        .acat-modal-drag {
            width: 40px; height: 4px;
            background: var(--border);
            border-radius: 4px;
            margin: 10px auto 0;
            flex-shrink: 0;
        }

        .acat-modal-body {
            overflow-y: auto;
            padding: 18px;
            flex: 1;
        }
        .acat-modal-body::-webkit-scrollbar { width: 4px; }
        .acat-modal-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

        .acat-modal-footer {
            display: flex;
            gap: 10px;
            padding: 14px 18px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }

        /* Form */
        .acat-form-group { margin-bottom: 14px; }
        .acat-form-label {
            display: block;
            font-size: .8rem;
            font-weight: 600;
            color: var(--soft-dark);
            margin-bottom: 5px;
        }
        .acat-form-label .req { color: var(--pink); margin-left: 2px; }
        .acat-form-control {
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
        select.acat-form-control {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='%23888' d='M4 6l4 4 4-4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 14px;
            padding-right: 32px;
        }
        .acat-form-control:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 3px rgba(255,61,139,.1);
            background-color: #fff;
        }
        .acat-form-control.is-invalid { border-color: #E53935; }
        .acat-invalid-feedback { color: #E53935; font-size: .74rem; margin-top: 4px; }
        .acat-form-hint { font-size: .72rem; color: var(--muted); margin-top: 4px; }

        .acat-row { display: flex; gap: 12px; }
        .acat-col { flex: 1; min-width: 0; }

        /* Image preview */
        .acat-img-preview {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            background: var(--bg);
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--border);
            margin-bottom: 10px;
        }
        .acat-img-preview img {
            width: 56px; height: 56px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border);
            flex-shrink: 0;
        }
        .acat-img-preview-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .acat-img-preview-info span { font-size: .75rem; color: var(--muted); }
        .acat-img-remove {
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
        .acat-img-remove:hover { background: #FFD6D6; }
        .acat-img-remove .material-icons-round { font-size: .8rem; }

        /* Upload progress */
        .acat-upload-progress { margin-top: 8px; }
        .acat-upload-bar {
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
        .acat-upload-progress small { font-size: .72rem; color: var(--muted); }

        .acat-switch-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: var(--bg);
            border-radius: var(--radius-sm);
            cursor: pointer;
        }
        .acat-switch-label {
            font-size: .84rem;
            color: var(--soft-dark);
            font-weight: 500;
            flex: 1;
        }

        /* Buttons */
        .btn-acat-primary {
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
        .btn-acat-primary:hover { background: #e02d7a; }
        .btn-acat-primary:disabled { opacity: .6; cursor: not-allowed; }

        .btn-acat-secondary {
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
        .btn-acat-secondary:hover { background: var(--border); }

        /* ── Delete Modal ── */
        .acat-delete-modal {
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
        .acat-delete-icon {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: #FFF0F0;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
            font-size: 1.6rem;
        }
        .acat-delete-modal h6 {
            font-size: .98rem; font-weight: 700;
            color: var(--dark); margin: 0 0 6px;
        }
        .acat-delete-modal p {
            font-size: .8rem; color: var(--muted);
            margin: 0 0 20px; line-height: 1.5;
        }
        .acat-delete-actions { display: flex; gap: 10px; justify-content: center; }

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

        @media (max-width: 400px) {
            .acat-row { flex-direction: column; }
            .acat-topbar-title { font-size: 1rem; }
        }

    </style>
@endpush