{{-- resources/views/livewire/admin/product-component.blade.php --}}
{{-- Styles: resources/css/blade.css (shared "" classes, Bootstrap 5 required) --}}
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
                <span class="title-emoji">🛍️</span>
                Products
            </div>
            <button class="btn-new-adm" wire:click="openCreate">
                <span class="plus-icon">＋</span>
                Add Product
            </button>
        </div>

        {{-- ── Search (left) + Status/Section Select (right) ── --}}
        <div class="filterbar">
            <div class="search-inner filterbar-search">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search products...">
            </div>

            <div class="filterbar-selects">
                <select class="select" wire:model.live="filterStatus">
                    <option value="">All Status</option>
                    <option value="active">✅ Active</option>
                    <option value="inactive">🔴 Inactive</option>
                </select>

                <select class="select" wire:model.live="filterSection">
                    <option value="">All Sections</option>
                    @foreach($sections as $sec)
                        <option value="{{ $sec }}">{{ ucwords(str_replace('_', ' ', $sec)) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ── Product Cards ── --}}
        @forelse($products as $product)
            @php
                $statusClass = $product->is_active ? 'available' : 'unavailable';
                $statusLabel = $product->is_active ? 'Active' : 'Inactive';
            @endphp

            <div class="card" wire:key="product-{{ $product->id }}">
                {{-- Top Row --}}
                <div class="card-top">
                    <div class="card-thumb">
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                        @else
                            <span class="material-icons-round icon-fallback">image</span>
                        @endif
                    </div>
                    <div class="card-info">
                        <div class="card-title">{{ $product->name }}</div>
                        @if($product->description)
                            <div class="card-desc">{{ Str::limit($product->description, 55) }}</div>
                        @endif
                    </div>
                    <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                {{-- Meta --}}
                <div class="card-meta">
                    <span class="sec-pill">
                        {{ ucwords(str_replace('_', ' ', $product->section)) }}
                    </span>
                    @if($product->category)
                        <span class="cat-pill">
                            <span class="material-icons-round">category</span>
                            {{ $product->category->name }}
                        </span>
                    @endif
                    <span class="meta-item">
                        <span class="material-icons-round">sort</span>
                        Order: {{ $product->sort_order }}
                    </span>
                </div>

                {{-- Bottom Row --}}
                <div class="card-bottom">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="price-badge">৳{{ number_format($product->price, 0) }}</span>
                        @if($product->compare_price)
                            <span class="compare-price">৳{{ number_format($product->compare_price, 0) }}</span>
                        @endif
                        <label class="toggle">
                            <input type="checkbox"
                                @checked($product->is_active)
                                wire:click="toggleActive({{ $product->id }})">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="card-actions">
                        <button class="btn-edit"
                            wire:click="openEdit({{ $product->id }})">
                            <span class="material-icons-round">drive_file_rename_outline</span>
                            Edit
                        </button>
                        <button class="btn-delete"
                            wire:click="confirmDeleteRecord({{ $product->id }})">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </div>
                </div>
            </div>

        @empty
            <div class="empty">
                <i class="bi bi-inbox empty-icon"></i>
                <p>No products found.</p>
                <button class="btn-new-adm" wire:click="openCreate">+ Add New Product</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($products->hasPages())
            <div class="pagination">
                <small>Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }} total</small>
                {{ $products->links('pagination::custom') }}
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
                        {{ $editId ? '✏️ Edit Product' : '🛍️ Add New Product' }}
                    </div>
                    <button class="jara-modal-close" wire:click="$set('showModal', false)">✕</button>
                </div>

                <div class="jara-modal-body">

                    {{-- Section + Category --}}
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Homepage Section <span class="req">*</span></label>
                                <select class="form-control @error('section') is-invalid @enderror"
                                    wire:model.defer="section">
                                    <option value="">— Select —</option>
                                    @foreach($sections as $sec)
                                        <option value="{{ $sec }}">{{ ucwords(str_replace('_', ' ', $sec)) }}</option>
                                    @endforeach
                                </select>
                                @error('section') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select class="form-control @error('category_id') is-invalid @enderror"
                                    wire:model.defer="category_id">
                                    <option value="">— None —</option>
                                    @foreach($categoryOptions as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Name --}}
                    <div class="form-group">
                        <label class="form-label">Product Name <span class="req">*</span></label>
                        <input type="text"
                            class="form-control @error('name') is-invalid @enderror"
                            wire:model.defer="name"
                            placeholder="e.g. Special Combo Deal">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Price + Compare Price --}}
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Price (BDT) <span class="req">*</span></label>
                                <div class="input-prefix">
                                    <span class="input-prefix-text">৳</span>
                                    <input type="number"
                                        class="form-control @error('price') is-invalid @enderror"
                                        wire:model.defer="price"
                                        min="1" placeholder="0">
                                </div>
                                @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Compare Price</label>
                                <div class="input-prefix">
                                    <span class="input-prefix-text">৳</span>
                                    <input type="number"
                                        class="form-control @error('compare_price') is-invalid @enderror"
                                        wire:model.defer="compare_price"
                                        min="1" placeholder="Optional">
                                </div>
                                <div class="form-hint">Original price before discount (optional).</div>
                                @error('compare_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Sort Order --}}
                    <div class="form-group">
                        <label class="form-label">Sort Order</label>
                        <input type="number"
                            class="form-control @error('sort_order') is-invalid @enderror"
                            wire:model.defer="sort_order"
                            min="0" placeholder="0">
                        <div class="form-hint">Lower number appears first.</div>
                        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Description --}}
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                            wire:model.defer="description"
                            rows="3"
                            placeholder="Brief description of the product...">{{ $description }}</textarea>
                        <div class="char-count">{{ strlen($description) }} / 500</div>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Image --}}
                    <div class="form-group">
                        <label class="form-label">Image</label>

                        {{-- Existing image preview --}}
                        @if($existingImage && !$image)
                            <div class="img-preview">
                                <img src="{{ $existingImage }}" alt="Current">
                                <div class="img-preview-info">
                                    <span>Current image</span>
                                    <button type="button" class="img-remove"
                                        wire:click="$set('existingImage', null)">
                                        <span class="material-icons-round">delete</span> Remove
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- New image preview --}}
                        @if($image)
                            <div class="img-preview">
                                <img src="{{ $image->temporaryUrl() }}" alt="Preview">
                                <div class="img-preview-info">
                                    <span>{{ $image->getClientOriginalName() }}</span>
                                    <button type="button" class="img-remove"
                                        wire:click="$set('image', null)">
                                        <span class="material-icons-round">close</span> Cancel
                                    </button>
                                </div>
                            </div>
                        @endif

                        <input type="file"
                            class="form-control @error('image') is-invalid @enderror"
                            wire:model="image" accept="image/*">
                        <div class="form-hint">JPG, PNG, WEBP — max 2 MB. Optional.</div>

                        <div wire:loading wire:target="image" class="upload-progress">
                            <div class="upload-bar"></div>
                            <small>Uploading...</small>
                        </div>

                        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Active toggle --}}
                    <label class="switch-wrap">
                        <span class="switch-label">Product is currently active</span>
                        <label class="toggle">
                            <input type="checkbox" wire:model.defer="is_active" id="admProductActive">
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
                <h6>Delete Product?</h6>
                <p>The image and all data will be permanently removed.<br>This action cannot be undone.</p>
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

