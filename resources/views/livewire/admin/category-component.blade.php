{{-- resources/views/livewire/admin/category-component.blade.php --}}
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
                <span class="title-emoji">🗂️</span>
                Categories
            </div>
            <button class="btn-new-adm" wire:click="openCreate">
                <span class="plus-icon">＋</span>
                Add Category
            </button>
        </div>

        {{-- ── Search (left) + Status/Type Select (right) ── --}}
        <div class="filterbar">
            <div class="search-inner filterbar-search">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search categories...">
            </div>

            <div class="filterbar-selects">
                <select class="select" wire:model.live="filterStatus">
                    <option value="">All Status</option>
                    <option value="active">✅ Active</option>
                    <option value="inactive">🔴 Inactive</option>
                </select>

                <select class="select" wire:model.live="filterType">
                    <option value="">All Types</option>
                    @foreach($categoryTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select class="select" wire:model.live="filterLevel">
                    <option value="">All Levels</option>
                    <option value="category">Category</option>
                    <option value="sub">Sub Category</option>
                    <option value="child">Child Category</option>
                </select>
            </div>
        </div>

        {{-- ── Category Cards ── --}}
        @forelse($categories as $category)
            @php
                $statusClass = $category->is_active ? 'available' : 'unavailable';
                $statusLabel = $category->is_active ? 'Active' : 'Inactive';
            @endphp

            <div class="card" wire:key="category-{{ $category->id }}">
                {{-- Top Row --}}
                <div class="card-top">
                    <div class="card-thumb">
                        @if($category->image_url)
                            <img src="{{ $category->image_url }}" alt="{{ $category->name }}">
                        @elseif($category->emoji)
                            <span style="font-size:24px;">{{ $category->emoji }}</span>
                        @else
                            <span class="material-icons-round icon-fallback">image</span>
                        @endif
                    </div>
                    <div class="card-info">
                        <div class="card-title">{{ $category->name }}</div>
                        @if($category->parent_id)
                            <div class="card-desc card-desc-muted">{{ $category->path }}</div>
                        @endif
                        @php
                            $isFoodType = $category->type === \App\Models\Category::TYPE_FOOD;
                            $itemCount  = $isFoodType ? $category->foods_count : $category->products_count;
                        @endphp
                        <div class="card-desc">
                            {{ $itemCount }} {{ $isFoodType ? 'food(s)' : 'product(s)' }}
                            @if($category->children_count > 0)
                                · {{ $category->children_count }} sub
                            @endif
                        </div>
                    </div>
                    <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                {{-- Meta --}}
                <div class="card-meta">
                    <span class="type-badge">{{ $categoryTypes[$category->type] ?? ucfirst($category->type) }}</span>
                    <span class="type-badge">{{ $category->level_label }}</span>
                    <span class="meta-item">
                        <span class="material-icons-round">sort</span>
                        Order: {{ $category->sort_order }}
                    </span>
                </div>

                {{-- Bottom Row --}}
                <div class="card-bottom">
                    <label class="toggle">
                        <input type="checkbox"
                            @checked($category->is_active)
                            wire:click="toggleActive({{ $category->id }})">
                        <span class="toggle-slider"></span>
                    </label>
                    <div class="card-actions">
                        <button class="btn-edit"
                            wire:click="openEdit({{ $category->id }})">
                            <span class="material-icons-round">drive_file_rename_outline</span>
                            Edit
                        </button>
                        <button class="btn-delete"
                            wire:click="confirmDeleteRecord({{ $category->id }})">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </div>
                </div>
            </div>

        @empty
            <div class="empty">
                <i class="bi bi-inbox empty-icon"></i>
                <p>No categories found.</p>
                <button class="btn-new-adm" wire:click="openCreate">+ Add New Category</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($categories->hasPages())
            <div class="pagination">
                <small>Showing {{ $categories->firstItem() ?? 0 }}–{{ $categories->lastItem() ?? 0 }} of {{ $categories->total() }} total</small>
                {{ $categories->links('pagination::custom') }}
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
                        {{ $editId ? '✏️ Edit Category' : '🗂️ Add New Category' }}
                    </div>
                    <button class="jara-modal-close" wire:click="$set('showModal', false)">✕</button>
                </div>

                <div class="jara-modal-body">

                    {{-- Type --}}
                    <div class="form-group">
                        <label class="form-label">Category Type <span class="req">*</span></label>
                        <select class="form-control @error('type') is-invalid @enderror"
                            wire:model.live="type" @disabled($parent_id)>
                            @foreach($categoryTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @if($parent_id)
                            <div class="form-hint">Parent-er type-i use hobe.</div>
                        @endif
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Parent (Category / Sub Category / Child Category) --}}
                    <div class="form-group">
                        <label class="form-label">Parent Category</label>
                        <select class="form-control @error('parent_id') is-invalid @enderror"
                            wire:model.live="parent_id">
                            <option value="">— None (top-level Category) —</option>
                            @foreach($parentOptions as $id => $path)
                                <option value="{{ $id }}">{{ $path }}</option>
                            @endforeach
                        </select>
                        <div class="form-hint">
                            None = Category · Category select korle = Sub Category · Sub Category select korle = Child Category (max 3 level).
                        </div>
                        @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Name + Sort Order --}}
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Category Name <span class="req">*</span></label>
                                <input type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    wire:model.defer="name"
                                    placeholder="e.g. Offers, Fruits, Vegetables">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

                    {{-- Emoji --}}
                    <div class="form-group">
                        <label class="form-label">Emoji</label>
                        <input type="text"
                            class="form-control @error('emoji') is-invalid @enderror"
                            wire:model="emoji" maxlength="10" placeholder="🛒">
                        <div class="form-hint">Image na thakle emoji dekhabe.</div>
                        @error('emoji') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Image --}}
                    <div class="form-group">
                        <label class="form-label">Tile Image</label>

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
                        <div class="form-hint">JPG, PNG, WEBP — max 2 MB. Shown as the homepage tile image.</div>

                        <div wire:loading wire:target="image" class="upload-progress">
                            <div class="upload-bar"></div>
                            <small>Uploading...</small>
                        </div>

                        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Active toggle --}}
                    <label class="switch-wrap">
                        <span class="switch-label">Category is currently active</span>
                        <label class="toggle">
                            <input type="checkbox" wire:model.defer="is_active" id="admCategoryActive">
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
                <h6>Delete Category?</h6>
                <p>The image and all data will be permanently removed.<br>Sub categories must be deleted first. This action cannot be undone.</p>
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

