{{-- resources/views/livewire/admin/product-component.blade.php --}}
<div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="aprod-alert aprod-alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="aprod-alert-close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="aprod-alert aprod-alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="aprod-alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="aprod-topbar">
            <div class="aprod-topbar-title">
                <span class="title-emoji">🛍️</span>
                Products
            </div>
            <button class="btn-new-aprod" wire:click="openCreate">
                <span class="plus-icon">＋</span>
                Add Product
            </button>
        </div>

        {{-- ── Search ── --}}
        <div class="aprod-search">
            <div class="aprod-search-inner">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search products...">
            </div>
        </div>

        {{-- ── Filter Chips ── --}}
        <div class="aprod-filters">
            <label class="filter-chip {{ $filterStatus === '' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterStatus" value=""> All
            </label>
            <label class="filter-chip {{ $filterStatus === 'active' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterStatus" value="active"> ✅ Active
            </label>
            <label class="filter-chip {{ $filterStatus === 'inactive' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterStatus" value="inactive"> 🔴 Inactive
            </label>

            {{-- Section filter chips --}}
            <label class="filter-chip {{ $filterSection === '' ? 'active' : '' }}">
                <input type="radio" wire:model.live="filterSection" value=""> All Sections
            </label>
            @foreach($sections as $sec)
                <label class="filter-chip {{ $filterSection === $sec ? 'active' : '' }}">
                    <input type="radio" wire:model.live="filterSection" value="{{ $sec }}">
                    {{ ucwords(str_replace('_', ' ', $sec)) }}
                </label>
            @endforeach
        </div>

        {{-- ── Product Cards ── --}}
        @forelse($products as $product)
            @php
                $statusClass = $product->is_active ? 'available' : 'unavailable';
                $statusLabel = $product->is_active ? 'Active' : 'Inactive';
            @endphp

            <div class="aprod-card">
                {{-- Top Row --}}
                <div class="aprod-card-top">
                    <div class="aprod-card-thumb">
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                        @else
                            <span class="material-icons-round aprod-icon-fallback">shopping_bag</span>
                        @endif
                    </div>
                    <div class="aprod-card-info">
                        <div class="aprod-card-title">{{ $product->name }}</div>
                        @if($product->description)
                            <div class="aprod-card-desc">{{ Str::limit($product->description, 55) }}</div>
                        @endif
                    </div>
                    <span class="aprod-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                {{-- Meta --}}
                <div class="aprod-card-meta">
                    <span class="aprod-sec-pill">
                        {{ ucwords(str_replace('_', ' ', $product->section)) }}
                    </span>
                    @if($product->category)
                        <span class="aprod-cat-pill">
                            <span class="material-icons-round">category</span>
                            {{ $product->category->name }}
                        </span>
                    @endif
                    <span class="aprod-meta-item">
                        <span class="material-icons-round">sort</span>
                        Order: {{ $product->sort_order }}
                    </span>
                </div>

                {{-- Bottom Row --}}
                <div class="aprod-card-bottom">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="aprod-price-badge">৳{{ number_format($product->price, 0) }}</span>
                        @if($product->compare_price)
                            <span class="aprod-compare-price">৳{{ number_format($product->compare_price, 0) }}</span>
                        @endif
                        <label class="aprod-toggle">
                            <input type="checkbox"
                                @checked($product->is_active)
                                wire:click="toggleActive({{ $product->id }})">
                            <span class="aprod-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="aprod-card-actions">
                        <button class="aprod-btn-edit"
                            wire:click="openEdit({{ $product->id }})">
                            <span class="material-icons-round">drive_file_rename_outline</span>
                            Edit
                        </button>
                        <button class="aprod-btn-delete"
                            wire:click="confirmDeleteRecord({{ $product->id }})">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </div>
                </div>
            </div>

        @empty
            <div class="aprod-empty">
                <i class="bi bi-inbox aprod-empty-icon"></i>
                <p>No products found.</p>
                <button class="btn-new-aprod" wire:click="openCreate">+ Add New Product</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($products->hasPages())
            <div class="aprod-pagination">
                <small>Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }} total</small>
                {{ $products->links('pagination::custom') }}
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Create / Edit Modal
         ══════════════════════════════════════ --}}
    @if($showModal)
        <div class="aprod-modal-backdrop" wire:ignore.self wire:click.self="$set('showModal', false)">
            <div class="aprod-modal">

                <div class="aprod-modal-drag"></div>

                <div class="aprod-modal-header">
                    <div class="aprod-modal-title">
                        {{ $editId ? '✏️ Edit Product' : '🛍️ Add New Product' }}
                    </div>
                    <button class="aprod-modal-close" wire:click="$set('showModal', false)">✕</button>
                </div>

                <div class="aprod-modal-body">

                    {{-- Section + Category --}}
                    <div class="aprod-row">
                        <div class="aprod-col">
                            <div class="aprod-form-group">
                                <label class="aprod-form-label">Homepage Section <span class="req">*</span></label>
                                <select class="aprod-form-control @error('section') is-invalid @enderror"
                                    wire:model.defer="section">
                                    <option value="">— Select —</option>
                                    @foreach($sections as $sec)
                                        <option value="{{ $sec }}">{{ ucwords(str_replace('_', ' ', $sec)) }}</option>
                                    @endforeach
                                </select>
                                @error('section') <div class="aprod-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="aprod-col">
                            <div class="aprod-form-group">
                                <label class="aprod-form-label">Category</label>
                                <select class="aprod-form-control @error('category_id') is-invalid @enderror"
                                    wire:model.defer="category_id">
                                    <option value="">— None —</option>
                                    @foreach($categoryOptions as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id') <div class="aprod-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Name --}}
                    <div class="aprod-form-group">
                        <label class="aprod-form-label">Product Name <span class="req">*</span></label>
                        <input type="text"
                            class="aprod-form-control @error('name') is-invalid @enderror"
                            wire:model.defer="name"
                            placeholder="e.g. Special Combo Deal">
                        @error('name') <div class="aprod-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Price + Compare Price --}}
                    <div class="aprod-row">
                        <div class="aprod-col">
                            <div class="aprod-form-group">
                                <label class="aprod-form-label">Price (BDT) <span class="req">*</span></label>
                                <div class="aprod-input-prefix">
                                    <span class="aprod-input-prefix-text">৳</span>
                                    <input type="number"
                                        class="aprod-form-control @error('price') is-invalid @enderror"
                                        wire:model.defer="price"
                                        min="1" placeholder="0">
                                </div>
                                @error('price') <div class="aprod-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="aprod-col">
                            <div class="aprod-form-group">
                                <label class="aprod-form-label">Compare Price</label>
                                <div class="aprod-input-prefix">
                                    <span class="aprod-input-prefix-text">৳</span>
                                    <input type="number"
                                        class="aprod-form-control @error('compare_price') is-invalid @enderror"
                                        wire:model.defer="compare_price"
                                        min="1" placeholder="Optional">
                                </div>
                                <div class="aprod-form-hint">Original price before discount (optional).</div>
                                @error('compare_price') <div class="aprod-invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Sort Order --}}
                    <div class="aprod-form-group">
                        <label class="aprod-form-label">Sort Order</label>
                        <input type="number"
                            class="aprod-form-control @error('sort_order') is-invalid @enderror"
                            wire:model.defer="sort_order"
                            min="0" placeholder="0">
                        <div class="aprod-form-hint">Lower number appears first.</div>
                        @error('sort_order') <div class="aprod-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Description --}}
                    <div class="aprod-form-group">
                        <label class="aprod-form-label">Description</label>
                        <textarea class="aprod-form-control @error('description') is-invalid @enderror"
                            wire:model.defer="description"
                            rows="3"
                            placeholder="Brief description of the product...">{{ $description }}</textarea>
                        <div class="char-count">{{ strlen($description) }} / 500</div>
                        @error('description') <div class="aprod-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Image --}}
                    <div class="aprod-form-group">
                        <label class="aprod-form-label">Image</label>

                        {{-- Existing image preview --}}
                        @if($existingImage && !$image)
                            <div class="aprod-img-preview">
                                <img src="{{ $existingImage }}" alt="Current">
                                <div class="aprod-img-preview-info">
                                    <span>Current image</span>
                                    <button type="button" class="aprod-img-remove"
                                        wire:click="$set('existingImage', null)">
                                        <span class="material-icons-round">delete</span> Remove
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- New image preview --}}
                        @if($image)
                            <div class="aprod-img-preview">
                                <img src="{{ $image->temporaryUrl() }}" alt="Preview">
                                <div class="aprod-img-preview-info">
                                    <span>{{ $image->getClientOriginalName() }}</span>
                                    <button type="button" class="aprod-img-remove"
                                        wire:click="$set('image', null)">
                                        <span class="material-icons-round">close</span> Cancel
                                    </button>
                                </div>
                            </div>
                        @endif

                        <input type="file"
                            class="aprod-form-control @error('image') is-invalid @enderror"
                            wire:model="image" accept="image/*">
                        <div class="aprod-form-hint">JPG, PNG, WEBP — max 2 MB. Optional.</div>

                        <div wire:loading wire:target="image" class="aprod-upload-progress">
                            <div class="aprod-upload-bar"></div>
                            <small>Uploading...</small>
                        </div>

                        @error('image') <div class="aprod-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Active toggle --}}
                    <label class="aprod-switch-wrap">
                        <span class="aprod-switch-label">Product is currently active</span>
                        <label class="aprod-toggle">
                            <input type="checkbox" wire:model.defer="is_active" id="aprodActive">
                            <span class="aprod-toggle-slider"></span>
                        </label>
                    </label>

                </div>

                <div class="aprod-modal-footer">
                    <button class="btn-aprod-secondary"
                        wire:click="$set('showModal', false)">Cancel</button>
                    <button class="btn-aprod-primary"
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
        <div class="aprod-modal-backdrop">
            <div class="aprod-delete-modal">
                <div class="aprod-delete-icon">⚠️</div>
                <h6>Delete Product?</h6>
                <p>The image and all data will be permanently removed.<br>This action cannot be undone.</p>
                <div class="aprod-delete-actions">
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
        .aprod-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 16px 12px;
            background: var(--bg);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .aprod-topbar-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.18rem;
            font-weight: 700;
            color: var(--dark);
        }
        .aprod-topbar-title .title-emoji { font-size: 1.2rem; }

        /* ── Add Product Button ── */
        .btn-new-aprod {
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
        .btn-new-aprod:hover {
            background: #e02d7a;
            box-shadow: 0 6px 24px rgba(255,61,139,.45);
            transform: translateY(-1px);
        }
        .btn-new-aprod .plus-icon { font-size: 1.1rem; font-weight: 400; line-height: 1; }

        /* ── Filter Bar ── */
        .aprod-filters {
            display: flex;
            gap: 8px;
            padding: 8px 16px 12px;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .aprod-filters::-webkit-scrollbar { display: none; }
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
        .aprod-search { padding: 0 16px 12px; }
        .aprod-search-inner { position: relative; }
        .aprod-search-inner .search-icon {
            position: absolute;
            left: 13px; top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: .95rem;
            pointer-events: none;
        }
        .aprod-search-inner input {
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
        .aprod-search-inner input:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 3px rgba(255,61,139,.1);
        }

        /* ── Product Card ── */
        .aprod-card {
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
        .aprod-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
            background: var(--pink);
            border-radius: 4px 0 0 4px;
            opacity: 0;
            transition: var(--transition);
        }
        .aprod-card:hover {
            box-shadow: var(--shadow-hover);
            border-color: rgba(255,61,139,.2);
            transform: translateY(-2px);
        }
        .aprod-card:hover::before { opacity: 1; }

        /* card top row */
        .aprod-card-top {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
        }
        .aprod-card-thumb {
            flex-shrink: 0;
            width: 52px; height: 52px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            background: var(--bg);
        }
        .aprod-card-thumb img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .aprod-icon-fallback { font-size: 1.5rem; line-height: 1; color: var(--muted); }

        .aprod-card-info { flex: 1; min-width: 0; }
        .aprod-card-title {
            font-size: .98rem;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .aprod-card-desc {
            font-size: .78rem;
            color: var(--muted);
            margin-top: 2px;
            line-height: 1.4;
        }

        .aprod-status-badge {
            flex-shrink: 0;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: .68rem;
            font-weight: 600;
            font-family: var(--font);
        }
        .aprod-status-badge.available   { background: #E8FAF0; color: #1A9453; }
        .aprod-status-badge.unavailable { background: #FFF0F0; color: #E53935; }

        /* meta row */
        .aprod-card-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }
        .aprod-sec-pill {
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
        .aprod-cat-pill {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: .7rem;
            font-weight: 600;
            background: #EEF2FF;
            color: #4F46E5;
        }
        .aprod-cat-pill .material-icons-round { font-size: .8rem; }
        .aprod-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: .74rem;
            color: var(--muted);
        }
        .aprod-meta-item .material-icons-round { font-size: .85rem; }

        /* card bottom row */
        .aprod-card-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
            gap: 8px;
        }
        .aprod-price-badge {
            font-size: .9rem;
            font-weight: 700;
            color: var(--pink);
        }
        .aprod-compare-price {
            font-size: .78rem;
            color: var(--muted);
            text-decoration: line-through;
        }

        /* toggle */
        .aprod-toggle {
            position: relative;
            width: 40px; height: 22px;
        }
        .aprod-toggle input { opacity: 0; width: 0; height: 0; }
        .aprod-toggle-slider {
            position: absolute;
            inset: 0;
            background: #ddd;
            border-radius: 50px;
            cursor: pointer;
            transition: var(--transition);
        }
        .aprod-toggle-slider::before {
            content: '';
            position: absolute;
            width: 16px; height: 16px;
            left: 3px; top: 3px;
            background: #fff;
            border-radius: 50%;
            transition: var(--transition);
            box-shadow: 0 1px 4px rgba(0,0,0,.2);
        }
        .aprod-toggle input:checked + .aprod-toggle-slider { background: var(--pink); }
        .aprod-toggle input:checked + .aprod-toggle-slider::before { transform: translateX(18px); }

        /* action buttons */
        .aprod-card-actions { display: flex; align-items: center; gap: 6px; }
        .aprod-btn-edit {
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
        .aprod-btn-edit:hover { background: var(--pink-light); color: var(--pink); }
        .aprod-btn-edit .material-icons-round { font-size: .95rem; }

        .aprod-btn-delete {
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
        .aprod-btn-delete:hover { background: #FFD6D6; }
        .aprod-btn-delete .material-icons-round { font-size: .95rem; }

        /* ── Empty State ── */
        .aprod-empty { text-align: center; padding: 60px 20px; }
        .aprod-empty-icon {
            font-size: 3rem; opacity: .25;
            display: block; margin-bottom: 12px;
        }
        .aprod-empty p { color: var(--muted); font-size: .88rem; margin: 0 0 16px; }

        /* ── Pagination ── */
        .aprod-pagination {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .aprod-pagination small { font-size: .74rem; color: var(--muted); }

        /* ── Alerts ── */
        .aprod-alert {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 12px 16px;
            padding: 12px 14px;
            border-radius: var(--radius-md);
            font-size: .82rem;
        }
        .aprod-alert span { flex: 1; }
        .aprod-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
        .aprod-alert-error   { background: #FFF0F0; color: #E53935; border: 1px solid #FFBCBC; }
        .aprod-alert-close {
            background: none; border: none; cursor: pointer;
            font-size: 1.1rem; color: inherit; padding: 0; line-height: 1;
        }

        /* ── Modal ── */
        .aprod-modal-backdrop {
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

        .aprod-modal {
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
            .aprod-modal-backdrop { align-items: center; padding: 20px; }
            .aprod-modal { border-radius: var(--radius-lg); max-height: 88vh; }
        }

        .aprod-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 18px 0;
            flex-shrink: 0;
        }
        .aprod-modal-title { font-size: 1rem; font-weight: 700; color: var(--dark); }
        .aprod-modal-close {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--bg);
            border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--soft-dark);
            font-size: 1rem;
            transition: var(--transition);
        }
        .aprod-modal-close:hover { background: var(--pink-light); color: var(--pink); }

        .aprod-modal-drag {
            width: 40px; height: 4px;
            background: var(--border);
            border-radius: 4px;
            margin: 10px auto 0;
            flex-shrink: 0;
        }

        .aprod-modal-body {
            overflow-y: auto;
            padding: 18px;
            flex: 1;
        }
        .aprod-modal-body::-webkit-scrollbar { width: 4px; }
        .aprod-modal-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

        .aprod-modal-footer {
            display: flex;
            gap: 10px;
            padding: 14px 18px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }

        /* Form */
        .aprod-form-group { margin-bottom: 14px; }
        .aprod-form-label {
            display: block;
            font-size: .8rem;
            font-weight: 600;
            color: var(--soft-dark);
            margin-bottom: 5px;
        }
        .aprod-form-label .req { color: var(--pink); margin-left: 2px; }
        .aprod-form-control {
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
        select.aprod-form-control {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='%23888' d='M4 6l4 4 4-4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 14px;
            padding-right: 32px;
        }
        .aprod-form-control:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 3px rgba(255,61,139,.1);
            background-color: #fff;
        }
        .aprod-form-control.is-invalid { border-color: #E53935; }
        .aprod-invalid-feedback { color: #E53935; font-size: .74rem; margin-top: 4px; }
        .aprod-form-hint { font-size: .72rem; color: var(--muted); margin-top: 4px; }

        .aprod-input-prefix { display: flex; align-items: stretch; }
        .aprod-input-prefix-text {
            padding: 10px 11px;
            background: var(--border);
            border: 1.5px solid var(--border);
            border-right: none;
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
            font-size: .84rem;
            color: var(--soft-dark);
            font-weight: 600;
        }
        .aprod-input-prefix .aprod-form-control {
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
        }

        .aprod-row { display: flex; gap: 12px; }
        .aprod-col { flex: 1; min-width: 0; }

        /* Image preview */
        .aprod-img-preview {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            background: var(--bg);
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--border);
            margin-bottom: 10px;
        }
        .aprod-img-preview img {
            width: 56px; height: 56px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border);
            flex-shrink: 0;
        }
        .aprod-img-preview-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .aprod-img-preview-info span { font-size: .75rem; color: var(--muted); }
        .aprod-img-remove {
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
        .aprod-img-remove:hover { background: #FFD6D6; }
        .aprod-img-remove .material-icons-round { font-size: .8rem; }

        /* Upload progress */
        .aprod-upload-progress { margin-top: 8px; }
        .aprod-upload-bar {
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
        .aprod-upload-progress small { font-size: .72rem; color: var(--muted); }

        .aprod-switch-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: var(--bg);
            border-radius: var(--radius-sm);
            cursor: pointer;
        }
        .aprod-switch-label {
            font-size: .84rem;
            color: var(--soft-dark);
            font-weight: 500;
            flex: 1;
        }

        /* Buttons */
        .btn-aprod-primary {
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
        .btn-aprod-primary:hover { background: #e02d7a; }
        .btn-aprod-primary:disabled { opacity: .6; cursor: not-allowed; }

        .btn-aprod-secondary {
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
        .btn-aprod-secondary:hover { background: var(--border); }

        /* ── Delete Modal ── */
        .aprod-delete-modal {
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
        .aprod-delete-icon {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: #FFF0F0;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
            font-size: 1.6rem;
        }
        .aprod-delete-modal h6 {
            font-size: .98rem; font-weight: 700;
            color: var(--dark); margin: 0 0 6px;
        }
        .aprod-delete-modal p {
            font-size: .8rem; color: var(--muted);
            margin: 0 0 20px; line-height: 1.5;
        }
        .aprod-delete-actions { display: flex; gap: 10px; justify-content: center; }

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
            .aprod-row { flex-direction: column; }
            .aprod-topbar-title { font-size: 1rem; }
        }

    </style>
@endpush