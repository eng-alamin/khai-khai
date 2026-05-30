{{-- resources/views/livewire/vendor/menu/menu-item-component.blade.php --}}
<div>

    {{-- ── Flash messages ── --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">

        {{-- ── Floating header ── --}}
        <div class="kk-card-header header-pink-gradient">
            <h5 id="cardHeaderTitleAllsections">Menu Items</h5>
            <p id="cardHeaderSubtitle">Create, update, set prices, and manage all food items for your restaurant.</p>
        </div>

        {{-- ── Toolbar ── --}}
        <div class="card-header border-0">
            <div class="card-toolbar">

                {{-- Search --}}
                <div class="card-toolbar-title">
                    <div style="position:relative;display:inline-flex;align-items:center">
                        <span class="material-icons-round"
                            style="position:absolute;left:10px;font-size:17px;color:var(--muted);pointer-events:none">search</span>
                        <input type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search items..."
                            style="border:1px solid rgba(0,0,0,.1);border-radius:8px;padding:7px 12px 7px 32px;
                                font-size:.78rem;font-family:inherit;color:var(--dark);outline:none;
                                background:#f8f9fa;width:200px"/>
                    </div>
                </div>

                {{-- Category filter --}}
                <select class="form-select form-select-sm" wire:model.live="filterCategory"
                    style="width:160px;">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">
                            {{ $cat->emoji }} {{ $cat->name }}
                        </option>
                    @endforeach
                </select>

                {{-- Per page --}}
                @if($items->total() > 10)
                    <select class="form-select form-select-sm" wire:model.live="perPage"
                        style="width:120px;">
                        <option value="10">10 / page</option>
                        <option value="25">25 / page</option>
                        <option value="50">50 / page</option>
                    </select>
                @endif

                {{-- Add button --}}
                <button class="btn-outline bg-dark text-white rounded" wire:click="openCreate">
                    <span class="material-icons-round">add</span>
                    <span>Add Item</span>
                </button>

            </div>
        </div>

        {{-- ── Table ── --}}
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Image</th>
                            <th wire:click="sortBy('name')" style="cursor:pointer">
                                Name
                                @if($sortField === 'name') {!! $sortDirection === 'asc' ? '↑' : '↓' !!} @endif
                            </th>
                            <th>Category</th>
                            <th wire:click="sortBy('price')" style="cursor:pointer">
                                Price
                                @if($sortField === 'price') {!! $sortDirection === 'asc' ? '↑' : '↓' !!} @endif
                            </th>
                            <th wire:click="sortBy('sort_order')" style="cursor:pointer">
                                Order
                                @if($sortField === 'sort_order') {!! $sortDirection === 'asc' ? '↑' : '↓' !!} @endif
                            </th>
                            <th>Available</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $i => $item)
                            <tr>
                                <td class="text-muted">{{ $items->firstItem() + $i }}</td>

                                {{-- Image / Emoji thumbnail --}}
                                <td>
                                    @if($item->image_url)
                                        <img src="{{ $item->image_url }}" alt="{{ $item->name }}"
                                            style="width:40px;height:40px;object-fit:cover;
                                                border-radius:8px;border:1px solid #eee;">
                                    @else
                                        <span style="font-size:1.6rem;line-height:1;">
                                            {{ $item->emoji ?? '🍽️' }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="fw-500">{{ $item->name }}</div>
                                    @if($item->description)
                                        <small class="text-muted" style="font-size:.72rem;">
                                            {{ Str::limit($item->description, 40) }}
                                        </small>
                                    @endif
                                </td>

                                <td>
                                    @if($item->category)
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill">
                                            {{ $item->category->emoji }}
                                            {{ $item->category->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="fw-600">
                                    ৳{{ number_format($item->price / 100) }}
                                </td>

                                <td>{{ $item->sort_order }}</td>

                                {{-- Availability toggle --}}
                                <td>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox"
                                            @checked($item->is_available)
                                            wire:click="toggleAvailability({{ $item->id }})"
                                            style="cursor:pointer;">
                                    </div>
                                </td>

                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="act-btn edit" title="Edit"
                                            wire:click="openEdit({{ $item->id }})">
                                            <span class="material-icons-round">drive_file_rename_outline</span>
                                        </button>
                                        <button class="act-btn delete" title="Delete"
                                            wire:click="confirmDeleteRecord({{ $item->id }})">
                                            <span class="material-icons-round">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox display-5 d-block mb-2 opacity-25"></i>
                                    No items found.
                                    <a href="#" wire:click.prevent="openCreate">Add one now</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Pagination ── --}}
        <div class="card-footer border-0 bg-white d-flex align-items-center justify-content-between flex-wrap gap-2 py-2 px-3">
            <small class="text-muted">
                Showing {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }}
                of {{ $items->total() }}
            </small>
            {{ $items->links('pagination::custom') }}
        </div>

    </div>{{-- card --}}


    {{-- ══════════════════════════════════════
         CREATE / EDIT MODAL
         ══════════════════════════════════════ --}}
    @if($showModal)
        <div class="modal fade show d-block" tabindex="-1"
            style="background:rgba(0,0,0,.5);" wire:ignore.self>
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">

                    <div class="modal-header border-0">
                        <h5 class="modal-title">
                            {{ $editId ? 'Edit Menu Item' : 'Add New Menu Item' }}
                        </h5>
                        <button type="button" class="btn-close"
                            wire:click="$set('showModal', false)"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">

                            {{-- Category --}}
                            <div class="col-md-6">
                                <label class="form-label">
                                    Category <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('category_id') is-invalid @enderror"
                                    wire:model.defer="category_id">
                                    <option value="0">Select a category</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">
                                            {{ $cat->emoji }} {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Name --}}
                            <div class="col-md-6">
                                <label class="form-label">
                                    Item Name <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    wire:model.defer="name"
                                    placeholder="e.g. Chicken Burger, Rice + Fish">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Price --}}
                            <div class="col-md-4">
                                <label class="form-label">
                                    Price (BDT) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">৳</span>
                                    <input type="number"
                                        class="form-control border-start-0 @error('price') is-invalid @enderror"
                                        wire:model.defer="price"
                                        min="1" placeholder="0">
                                </div>
                                @error('price')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Emoji --}}
                            <div class="col-md-4">
                                <label class="form-label">Emoji</label>
                                <input type="text"
                                    class="form-control @error('emoji') is-invalid @enderror"
                                    wire:model.defer="emoji"
                                    placeholder="e.g. 🍚 🍔 🍕"
                                    maxlength="10">
                                @error('emoji')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Sort order --}}
                            <div class="col-md-4">
                                <label class="form-label">Sort Order</label>
                                <input type="number"
                                    class="form-control @error('sort_order') is-invalid @enderror"
                                    wire:model.defer="sort_order"
                                    min="0" placeholder="0">
                                @error('sort_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Description --}}
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                    wire:model.defer="description"
                                    rows="2"
                                    placeholder="Brief description of the item...">{{ $description }}</textarea>
                                <div class="d-flex justify-content-end">
                                    <small class="text-muted">{{ strlen($description) }} / 500</small>
                                </div>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Image upload --}}
                            <div class="col-12">
                                <label class="form-label">Image</label>

                                {{-- Existing image preview --}}
                                @if($existingImage && !$image)
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <img src="{{ $existingImage }}" alt="Current"
                                            style="width:64px;height:64px;object-fit:cover;
                                                border-radius:10px;border:1px solid #dee2e6;">
                                        <div>
                                            <div style="font-size:.78rem;color:#6c757d;">Current image</div>
                                            <button type="button" class="btn btn-sm btn-outline-danger mt-1"
                                                wire:click="$set('existingImage', null)">
                                                <i class="bi bi-trash me-1"></i>Remove
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                {{-- New image preview --}}
                                @if($image)
                                    <div class="d-flex align-items-center gap-3 mb-2">
                                        <img src="{{ $image->temporaryUrl() }}" alt="Preview"
                                            style="width:64px;height:64px;object-fit:cover;
                                                border-radius:10px;border:1px solid #dee2e6;">
                                        <div>
                                            <div style="font-size:.78rem;color:#6c757d;">
                                                {{ $image->getClientOriginalName() }}
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary mt-1"
                                                wire:click="$set('image', null)">
                                                <i class="bi bi-x me-1"></i>Cancel
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                <input type="file" class="form-control @error('image') is-invalid @enderror"
                                    wire:model="image" accept="image/*">
                                <div class="form-text">JPG, PNG, WEBP — max 2 MB. Optional.</div>

                                {{-- Upload progress --}}
                                <div wire:loading wire:target="image" class="mt-2">
                                    <div class="progress" style="height:4px;border-radius:99px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
                                    </div>
                                    <small class="text-muted">Uploading...</small>
                                </div>

                                @error('image')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Available toggle --}}
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                        wire:model.defer="is_available" id="itemAvailable">
                                    <label class="form-check-label" for="itemAvailable">
                                        Item is currently available
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light"
                            wire:click="$set('showModal', false)">Cancel</button>
                        <button type="button" class="btn bg-dark text-white"
                            wire:click="save"
                            wire:loading.attr="disabled">
                            <span wire:loading wire:target="save"
                                class="spinner-border spinner-border-sm me-1"></span>
                            {{ $editId ? 'Update' : 'Create' }}
                        </button>
                    </div>

                </div>
            </div>
        </div>
    @endif


    {{-- ══════════════════════════════════════
         DELETE CONFIRM MODAL
         ══════════════════════════════════════ --}}
    @if($confirmDelete)
        <div class="modal fade show d-block" tabindex="-1"
            style="background:rgba(0,0,0,.5);">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-body text-center py-4">
                        <div style="width:56px;height:56px;border-radius:50%;background:#fee2e2;
                            display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                            <i class="bi bi-exclamation-triangle text-danger" style="font-size:1.5rem;"></i>
                        </div>
                        <h6 class="fw-bold">Delete Item?</h6>
                        <p class="text-muted small">The image and all data will be permanently removed. This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer justify-content-center border-0 pt-0">
                        <button class="btn btn-light btn-sm"
                            wire:click="$set('confirmDelete', false)">Cancel</button>
                        <button class="btn btn-danger btn-sm"
                            wire:click="deleteRecord">
                            <span wire:loading wire:target="deleteRecord"
                                class="spinner-border spinner-border-sm me-1"></span>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>