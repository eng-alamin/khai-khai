{{-- resources/views/livewire/vendor/menu/menu-item-component.blade.php --}}
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
                <span class="title-emoji">🍽️</span>
                Menu Items
            </div>
            <button class="btn-new-adm" wire:click="openCreate">
                <span class="plus-icon">＋</span>
                Add Item
            </button>
        </div>

        {{-- ── Search (left) + Status/Category Select (right) ── --}}
        <div class="filterbar">
            <div class="search-inner filterbar-search">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search items...">
            </div>

            <div class="filterbar-selects">
                <select class="select" wire:model.live="filterStatus">
                    <option value="">All Status</option>
                    <option value="available">✅ Available</option>
                    <option value="unavailable">🔴 Unavailable</option>
                </select>

                <select class="select" wire:model.live="filterCategory">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->emoji }} {{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ── Item Cards ── --}}
        @forelse($items as $i => $item)
            @php
                $statusClass = $item->is_available ? 'available' : 'unavailable';
                $statusLabel = $item->is_available ? 'Available' : 'Unavailable';
            @endphp

            <div class="card" wire:key="menu-item-{{ $item->id }}">
                {{-- Top Row --}}
                <div class="card-top">
                    <div class="card-thumb">
                        @if($item->image_url)
                            <img src="{{ $item->image_url }}" alt="{{ $item->name }}">
                        @else
                            <span class="icon-fallback">{{ $item->emoji ?? '🍽️' }}</span>
                        @endif
                    </div>
                    <div class="card-info">
                        <div class="card-title">{{ $item->name }}</div>
                        @if($item->description)
                            <div class="card-desc">{{ Str::limit($item->description, 55) }}</div>
                        @endif
                    </div>
                    <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                {{-- Meta --}}
                <div class="card-meta">
                    @if($item->category)
                        <span class="cat-pill">
                            {{ $item->category->emoji }} {{ $item->category->name }}
                        </span>
                    @endif
                    <span class="meta-item">
                        <span class="material-icons-round">sort</span>
                        Order: {{ $item->sort_order }}
                    </span>
                </div>

                {{-- Bottom Row --}}
                <div class="card-bottom">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="price-badge">৳{{ number_format($item->price, 0) }}</span>
                        <label class="toggle">
                            <input type="checkbox"
                                @checked($item->is_available)
                                wire:click="toggleAvailability({{ $item->id }})">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="card-actions">
                        <button class="btn-edit"
                            wire:click="openEdit({{ $item->id }})">
                            <span class="material-icons-round">drive_file_rename_outline</span>
                            Edit
                        </button>
                        <button class="btn-delete"
                            wire:click="confirmDeleteRecord({{ $item->id }})">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </div>
                </div>
            </div>

        @empty
            <div class="empty">
                <i class="bi bi-inbox empty-icon"></i>
                <p>No items found.</p>
                <button class="btn-new-adm" wire:click="openCreate">+ Add New Item</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($items->hasPages())
            <div class="pagination">
                <small>Showing {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} of {{ $items->total() }} total</small>
                {{ $items->links('pagination::custom') }}
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
                        {{ $editId ? '✏️ Edit Menu Item' : '🍽️ Add New Menu Item' }}
                    </div>
                    <button class="jara-modal-close" wire:click="$set('showModal', false)">✕</button>
                </div>

                <div class="jara-modal-body">

                    {{-- Category + Name --}}
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Category <span class="req">*</span></label>
                                <select class="form-control @error('category_id') is-invalid @enderror"
                                    wire:model.defer="category_id">
                                    <option value="0">— Select —</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->emoji }} {{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Item Name <span class="req">*</span></label>
                                <input type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    wire:model.defer="name"
                                    placeholder="e.g. Chicken Burger, Rice + Fish">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Price + Emoji + Sort Order --}}
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
                                <label class="form-label">Emoji</label>
                                <input type="text"
                                    class="form-control @error('emoji') is-invalid @enderror"
                                    wire:model.defer="emoji"
                                    placeholder="e.g. 🍚 🍔 🍕"
                                    maxlength="10">
                                @error('emoji') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Sort Order</label>
                                <input type="number"
                                    class="form-control @error('sort_order') is-invalid @enderror"
                                    wire:model.defer="sort_order"
                                    min="0" placeholder="0">
                                <div class="form-hint">Lower number appears first.</div>
                                @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                            wire:model.defer="description"
                            rows="3"
                            placeholder="Brief description of the item...">{{ $description }}</textarea>
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

                    {{-- Available toggle --}}
                    <label class="switch-wrap">
                        <span class="switch-label">Item is currently available</span>
                        <label class="toggle">
                            <input type="checkbox" wire:model.defer="is_available" id="itemAvailable">
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
                <h6>Delete Item?</h6>
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
