{{-- resources/views/livewire/admin/slider-component.blade.php --}}
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
                <span class="title-emoji">🖼️</span>
                Sliders
            </div>
            <button class="btn-new-adm" wire:click="openCreate">
                <span class="plus-icon">＋</span>
                Add Slider
            </button>
        </div>

        {{-- ── Status Select (right) ── --}}
        <div class="filterbar filterbar-end">
            <div class="filterbar-selects">
                <select class="select" wire:model.live="filterStatus">
                    <option value="">All Status</option>
                    <option value="active">✅ Active</option>
                    <option value="inactive">🔴 Inactive</option>
                </select>
            </div>
        </div>

        {{-- ── Slider Cards ── --}}
        @forelse($sliders as $slider)
            @php
                $statusClass = $slider->is_active ? 'available' : 'unavailable';
                $statusLabel = $slider->is_active ? 'Active' : 'Inactive';
            @endphp

            <div class="card" wire:key="slider-{{ $slider->id }}">
                {{-- Top Row --}}
                <div class="card-top">
                    <div class="card-thumb">
                        @if($slider->image)
                            <img src="{{ $slider->image }}" alt="Slider #{{ $slider->id }}">
                        @else
                            <span class="material-icons-round icon-fallback">image</span>
                        @endif
                    </div>
                    <div class="card-info">
                        <div class="card-title">Slider #{{ $slider->id }}</div>
                        @if($slider->url)
                            <div class="card-desc">{{ Str::limit($slider->url, 45) }}</div>
                        @else
                            <div class="card-desc card-desc-muted">No link URL set</div>
                        @endif
                    </div>
                    <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                {{-- Bottom Row --}}
                <div class="card-bottom">
                    <label class="toggle">
                        <input type="checkbox"
                            @checked($slider->is_active)
                            wire:click="toggleActive({{ $slider->id }})">
                        <span class="toggle-slider"></span>
                    </label>
                    <div class="card-actions">
                        <button class="btn-edit"
                            wire:click="openEdit({{ $slider->id }})">
                            <span class="material-icons-round">drive_file_rename_outline</span>
                            Edit
                        </button>
                        <button class="btn-delete"
                            wire:click="confirmDeleteRecord({{ $slider->id }})">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </div>
                </div>
            </div>

        @empty
            <div class="empty">
                <i class="bi bi-images empty-icon"></i>
                <p>No sliders found.</p>
                <button class="btn-new-adm" wire:click="openCreate">+ Add New Slider</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($sliders->hasPages())
            <div class="pagination">
                <small>Showing {{ $sliders->firstItem() ?? 0 }}–{{ $sliders->lastItem() ?? 0 }} of {{ $sliders->total() }} total</small>
                {{ $sliders->links('pagination::custom') }}
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
                        {{ $editId ? '✏️ Edit Slider' : '🖼️ Add New Slider' }}
                    </div>
                    <button class="jara-modal-close" wire:click="$set('showModal', false)">✕</button>
                </div>

                <div class="jara-modal-body">

                    {{-- Image --}}
                    <div class="form-group">
                        <label class="form-label">Slider Image
                            @if(! $editId)<span class="req">*</span>@endif
                        </label>

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
                        <div class="form-hint">JPG, PNG, WEBP — max 2 MB. Recommended: wide banner ratio.</div>

                        <div wire:loading wire:target="image" class="upload-progress">
                            <div class="upload-bar"></div>
                            <small>Uploading...</small>
                        </div>

                        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Link URL --}}
                    <div class="form-group">
                        <label class="form-label">Link URL</label>
                        <input type="text"
                            class="form-control @error('url') is-invalid @enderror"
                            wire:model.defer="url"
                            placeholder="https://example.com/promo (optional)">
                        <div class="form-hint">Where the customer goes when they tap this slide. Leave blank if none.</div>
                        @error('url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Active toggle --}}
                    <label class="switch-wrap">
                        <span class="switch-label">Slider is currently active</span>
                        <label class="toggle">
                            <input type="checkbox" wire:model.defer="is_active" id="admSliderActive">
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
                <h6>Delete Slider?</h6>
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

