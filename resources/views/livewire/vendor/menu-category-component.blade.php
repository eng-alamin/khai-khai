{{-- resources/views/livewire/vendor/menu/menu-category-component.blade.php --}}
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
            <h5 id="cardHeaderTitleAllsections">Menu Categories</h5>
            <p id="cardHeaderSubtitle">Create, edit, and manage menu categories for your restaurant.</p>
        </div>

        {{-- ── Toolbar ── --}}
        <div class="card-header border-0">
            <div class="card-toolbar">
                {{-- Search --}}
                <div class="card-toolbar-title">
                    <div style="position:relative;display:inline-flex;align-items:center">
                        <span class="material-icons-round" style="position:absolute;left:10px;font-size:17px;color:var(--muted);pointer-events:none">search</span>
                        <input type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search categories..."
                            style="border:1px solid rgba(0,0,0,.1);border-radius:8px;padding:7px 12px 7px 32px;font-size:.78rem;font-family:inherit;color:var(--dark);outline:none;background:#f8f9fa;width:220px"/>
                    </div>
                </div>

                {{-- Per page --}}
                @if($categories->total() > 10)
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" wire:model.live="perPage">
                            <option value="10">10 / page</option>
                            <option value="25">25 / page</option>
                            <option value="50">50 / page</option>
                        </select>
                    </div>
                @endif

                {{-- Add button --}}
                <button class="btn-outline bg-dark text-white rounded" wire:click="openCreate">
                    <span class="material-icons-round">add</span>
                    <span>Add Category</span>
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
                            <th wire:click="sortBy('name')" style="cursor:pointer">
                                Name
                                @if($sortField === 'name')
                                    {!! $sortDirection === 'asc' ? '↑' : '↓' !!}
                                @endif
                            </th>
                            <th>Emoji</th>
                            <th wire:click="sortBy('sort_order')" style="cursor:pointer">
                                Order
                                @if($sortField === 'sort_order')
                                    {!! $sortDirection === 'asc' ? '↑' : '↓' !!}
                                @endif
                            </th>
                            <th>Items</th>
                            <th wire:click="sortBy('is_active')" style="cursor:pointer">
                                Status
                                @if($sortField === 'is_active')
                                    {!! $sortDirection === 'asc' ? '↑' : '↓' !!}
                                @endif
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $i => $category)
                            <tr>
                                <td class="text-muted">{{ $categories->firstItem() + $i }}</td>
                                <td class="fw-500">{{ $category->name }}</td>
                                <td style="font-size:1.3rem">{{ $category->emoji ?? '—' }}</td>
                                <td>{{ $category->sort_order }}</td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill">
                                        {{ $category->menu_items_count }} items
                                    </span>
                                </td>
                                <td>
                                    @if($category->is_active)
                                        <span class="badge bg-success-subtle text-success rounded-pill">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger rounded-pill">
                                            <i class="bi bi-x-circle me-1"></i>Inactive
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="act-btn edit" title="Edit"
                                            wire:click="openEdit({{ $category->id }})">
                                            <span class="material-icons-round">drive_file_rename_outline</span>
                                        </button>
                                        <button class="act-btn delete" title="Delete"
                                            wire:click="confirmDeleteRecord({{ $category->id }})">
                                            <span class="material-icons-round">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox display-5 d-block mb-2 opacity-25"></i>
                                    No categories found.
                                    <a href="#" wire:click.prevent="openCreate">Create one now</a>.
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
                Showing {{ $categories->firstItem() ?? 0 }}–{{ $categories->lastItem() ?? 0 }}
                of {{ $categories->total() }}
            </small>
            {{ $categories->links('pagination::custom') }}
        </div>

    </div>{{-- card --}}


    {{-- ══════════════════════════════════════
         CREATE / EDIT MODAL
         ══════════════════════════════════════ --}}
    @if($showModal)
        <div class="modal fade show d-block" tabindex="-1"
            style="background:rgba(0,0,0,.5);" wire:ignore.self>
            <div class="modal-dialog modal-md modal-dialog-scrollable">
                <div class="modal-content">

                    <div class="modal-header border-0">
                        <h5 class="modal-title">
                            {{ $editId ? 'Edit Category' : 'Create Category' }}
                        </h5>
                        <button type="button" class="btn-close"
                            wire:click="$set('showModal', false)"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">

                            {{-- Name --}}
                            <div class="col-12">
                                <label class="form-label">
                                    Category Name <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    wire:model.defer="name"
                                    placeholder="e.g. Rice, Burger, Dessert">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Emoji --}}
                            <div class="col-6">
                                <label class="form-label">Emoji</label>
                                <input type="text"
                                    class="form-control @error('emoji') is-invalid @enderror"
                                    wire:model.defer="emoji"
                                    placeholder="e.g. 🍚 🍔 🍰"
                                    maxlength="10">
                                @error('emoji')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Optional — shown next to the category name</div>
                            </div>

                            {{-- Sort order --}}
                            <div class="col-6">
                                <label class="form-label">
                                    Sort Order <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                    class="form-control @error('sort_order') is-invalid @enderror"
                                    wire:model.defer="sort_order"
                                    min="0" placeholder="0">
                                @error('sort_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Lower number appears first</div>
                            </div>

                            {{-- Active toggle --}}
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                        wire:model.defer="is_active" id="catActive">
                                    <label class="form-check-label" for="catActive">
                                        Keep category active
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
                        <h6 class="fw-bold">Delete Category?</h6>
                        <p class="text-muted small">This action cannot be undone.</p>
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