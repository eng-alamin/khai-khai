{{-- resources/views/livewire/admin/slider-component.blade.php --}}
<div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="aslide-alert aslide-alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="aslide-alert-close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="aslide-alert aslide-alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="aslide-alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="aslide-topbar">
            <div class="aslide-topbar-title">
                <span class="title-emoji">🖼️</span>
                Sliders
            </div>
            <button class="btn-new-aslide" wire:click="openCreate">
                <span class="plus-icon">＋</span>
                Add Slider
            </button>
        </div>

        {{-- ── Filter Chips ── --}}
        <div class="aslide-filters">
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

        {{-- ── Slider Cards ── --}}
        @forelse($sliders as $slider)
            @php
                $statusClass = $slider->is_active ? 'available' : 'unavailable';
                $statusLabel = $slider->is_active ? 'Active' : 'Inactive';
            @endphp

            <div class="aslide-card" wire:key="slider-{{ $slider->id }}">
                {{-- Top Row --}}
                <div class="aslide-card-top">
                    <div class="aslide-card-thumb">
                        @if($slider->image)
                            <img src="{{ $slider->image }}" alt="Slider #{{ $slider->id }}">
                        @else
                            <span class="material-icons-round aslide-icon-fallback">image</span>
                        @endif
                    </div>
                    <div class="aslide-card-info">
                        <div class="aslide-card-title">Slider #{{ $slider->id }}</div>
                        @if($slider->url)
                            <div class="aslide-card-desc">{{ Str::limit($slider->url, 45) }}</div>
                        @else
                            <div class="aslide-card-desc aslide-card-desc-muted">No link URL set</div>
                        @endif
                    </div>
                    <span class="aslide-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                {{-- Bottom Row --}}
                <div class="aslide-card-bottom">
                    <label class="aslide-toggle">
                        <input type="checkbox"
                            @checked($slider->is_active)
                            wire:click="toggleActive({{ $slider->id }})">
                        <span class="aslide-toggle-slider"></span>
                    </label>
                    <div class="aslide-card-actions">
                        <button class="aslide-btn-edit"
                            wire:click="openEdit({{ $slider->id }})">
                            <span class="material-icons-round">drive_file_rename_outline</span>
                            Edit
                        </button>
                        <button class="aslide-btn-delete"
                            wire:click="confirmDeleteRecord({{ $slider->id }})">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </div>
                </div>
            </div>

        @empty
            <div class="aslide-empty">
                <i class="bi bi-images aslide-empty-icon"></i>
                <p>No sliders found.</p>
                <button class="btn-new-aslide" wire:click="openCreate">+ Add New Slider</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($sliders->hasPages())
            <div class="aslide-pagination">
                <small>Showing {{ $sliders->firstItem() ?? 0 }}–{{ $sliders->lastItem() ?? 0 }} of {{ $sliders->total() }} total</small>
                {{ $sliders->links('pagination::custom') }}
            </div>
        @endif

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Create / Edit Modal
         ══════════════════════════════════════ --}}
    @if($showModal)
        <div class="aslide-modal-backdrop" wire:ignore.self wire:click.self="$set('showModal', false)">
            <div class="aslide-modal">

                <div class="aslide-modal-drag"></div>

                <div class="aslide-modal-header">
                    <div class="aslide-modal-title">
                        {{ $editId ? '✏️ Edit Slider' : '🖼️ Add New Slider' }}
                    </div>
                    <button class="aslide-modal-close" wire:click="$set('showModal', false)">✕</button>
                </div>

                <div class="aslide-modal-body">

                    {{-- Image --}}
                    <div class="aslide-form-group">
                        <label class="aslide-form-label">Slider Image
                            @if(! $editId)<span class="req">*</span>@endif
                        </label>

                        {{-- Existing image preview --}}
                        @if($existingImage && !$image)
                            <div class="aslide-img-preview">
                                <img src="{{ $existingImage }}" alt="Current">
                                <div class="aslide-img-preview-info">
                                    <span>Current image</span>
                                    <button type="button" class="aslide-img-remove"
                                        wire:click="$set('existingImage', null)">
                                        <span class="material-icons-round">delete</span> Remove
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- New image preview --}}
                        @if($image)
                            <div class="aslide-img-preview">
                                <img src="{{ $image->temporaryUrl() }}" alt="Preview">
                                <div class="aslide-img-preview-info">
                                    <span>{{ $image->getClientOriginalName() }}</span>
                                    <button type="button" class="aslide-img-remove"
                                        wire:click="$set('image', null)">
                                        <span class="material-icons-round">close</span> Cancel
                                    </button>
                                </div>
                            </div>
                        @endif

                        <input type="file"
                            class="aslide-form-control @error('image') is-invalid @enderror"
                            wire:model="image" accept="image/*">
                        <div class="aslide-form-hint">JPG, PNG, WEBP — max 2 MB. Recommended: wide banner ratio.</div>

                        <div wire:loading wire:target="image" class="aslide-upload-progress">
                            <div class="aslide-upload-bar"></div>
                            <small>Uploading...</small>
                        </div>

                        @error('image') <div class="aslide-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Link URL --}}
                    <div class="aslide-form-group">
                        <label class="aslide-form-label">Link URL</label>
                        <input type="text"
                            class="aslide-form-control @error('url') is-invalid @enderror"
                            wire:model.defer="url"
                            placeholder="https://example.com/promo (optional)">
                        <div class="aslide-form-hint">Where the customer goes when they tap this slide. Leave blank if none.</div>
                        @error('url') <div class="aslide-invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Active toggle --}}
                    <label class="aslide-switch-wrap">
                        <span class="aslide-switch-label">Slider is currently active</span>
                        <label class="aslide-toggle">
                            <input type="checkbox" wire:model.defer="is_active" id="aslideActive">
                            <span class="aslide-toggle-slider"></span>
                        </label>
                    </label>

                </div>

                <div class="aslide-modal-footer">
                    <button class="btn-aslide-secondary"
                        wire:click="$set('showModal', false)">Cancel</button>
                    <button class="btn-aslide-primary"
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
        <div class="aslide-modal-backdrop">
            <div class="aslide-delete-modal">
                <div class="aslide-delete-icon">⚠️</div>
                <h6>Delete Slider?</h6>
                <p>The image and all data will be permanently removed.<br>This action cannot be undone.</p>
                <div class="aslide-delete-actions">
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
        .aslide-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 16px 12px;
            background: var(--bg);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .aslide-topbar-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.18rem;
            font-weight: 700;
            color: var(--dark);
        }
        .aslide-topbar-title .title-emoji { font-size: 1.2rem; }

        /* ── Add Slider Button ── */
        .btn-new-aslide {
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
        .btn-new-aslide:hover {
            background: #e02d7a;
            box-shadow: 0 6px 24px rgba(255,61,139,.45);
            transform: translateY(-1px);
        }
        .btn-new-aslide .plus-icon { font-size: 1.1rem; font-weight: 400; line-height: 1; }

        /* ── Filter Bar ── */
        .aslide-filters {
            display: flex;
            gap: 8px;
            padding: 8px 16px 12px;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .aslide-filters::-webkit-scrollbar { display: none; }
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

        /* ── Slider Card ── */
        .aslide-card {
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
        .aslide-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
            background: var(--pink);
            border-radius: 4px 0 0 4px;
            opacity: 0;
            transition: var(--transition);
        }
        .aslide-card:hover {
            box-shadow: var(--shadow-hover);
            border-color: rgba(255,61,139,.2);
            transform: translateY(-2px);
        }
        .aslide-card:hover::before { opacity: 1; }

        /* card top row */
        .aslide-card-top {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
        }
        .aslide-card-thumb {
            flex-shrink: 0;
            width: 72px; height: 52px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            background: var(--bg);
        }
        .aslide-card-thumb img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .aslide-icon-fallback { font-size: 1.5rem; line-height: 1; color: var(--muted); }

        .aslide-card-info { flex: 1; min-width: 0; }
        .aslide-card-title {
            font-size: .98rem;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .aslide-card-desc {
            font-size: .78rem;
            color: var(--muted);
            margin-top: 2px;
            line-height: 1.4;
            word-break: break-all;
        }
        .aslide-card-desc-muted { font-style: italic; opacity: .7; }

        .aslide-status-badge {
            flex-shrink: 0;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: .68rem;
            font-weight: 600;
            font-family: var(--font);
        }
        .aslide-status-badge.available   { background: #E8FAF0; color: #1A9453; }
        .aslide-status-badge.unavailable { background: #FFF0F0; color: #E53935; }

        /* card bottom row */
        .aslide-card-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
            gap: 8px;
        }

        /* toggle */
        .aslide-toggle {
            position: relative;
            width: 40px; height: 22px;
        }
        .aslide-toggle input { opacity: 0; width: 0; height: 0; }
        .aslide-toggle-slider {
            position: absolute;
            inset: 0;
            background: #ddd;
            border-radius: 50px;
            cursor: pointer;
            transition: var(--transition);
        }
        .aslide-toggle-slider::before {
            content: '';
            position: absolute;
            width: 16px; height: 16px;
            left: 3px; top: 3px;
            background: #fff;
            border-radius: 50%;
            transition: var(--transition);
            box-shadow: 0 1px 4px rgba(0,0,0,.2);
        }
        .aslide-toggle input:checked + .aslide-toggle-slider { background: var(--pink); }
        .aslide-toggle input:checked + .aslide-toggle-slider::before { transform: translateX(18px); }

        /* action buttons */
        .aslide-card-actions { display: flex; align-items: center; gap: 6px; }
        .aslide-btn-edit {
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
        .aslide-btn-edit:hover { background: var(--pink-light); color: var(--pink); }
        .aslide-btn-edit .material-icons-round { font-size: .95rem; }

        .aslide-btn-delete {
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
        .aslide-btn-delete:hover { background: #FFD6D6; }
        .aslide-btn-delete .material-icons-round { font-size: .95rem; }

        /* ── Empty State ── */
        .aslide-empty { text-align: center; padding: 60px 20px; }
        .aslide-empty-icon {
            font-size: 3rem; opacity: .25;
            display: block; margin-bottom: 12px;
        }
        .aslide-empty p { color: var(--muted); font-size: .88rem; margin: 0 0 16px; }

        /* ── Pagination ── */
        .aslide-pagination {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .aslide-pagination small { font-size: .74rem; color: var(--muted); }

        /* ── Alerts ── */
        .aslide-alert {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 12px 16px;
            padding: 12px 14px;
            border-radius: var(--radius-md);
            font-size: .82rem;
        }
        .aslide-alert span { flex: 1; }
        .aslide-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
        .aslide-alert-error   { background: #FFF0F0; color: #E53935; border: 1px solid #FFBCBC; }
        .aslide-alert-close {
            background: none; border: none; cursor: pointer;
            font-size: 1.1rem; color: inherit; padding: 0; line-height: 1;
        }

        /* ── Modal ── */
        .aslide-modal-backdrop {
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

        .aslide-modal {
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
            .aslide-modal-backdrop { align-items: center; padding: 20px; }
            .aslide-modal { border-radius: var(--radius-lg); max-height: 88vh; }
        }

        .aslide-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 18px 0;
            flex-shrink: 0;
        }
        .aslide-modal-title { font-size: 1rem; font-weight: 700; color: var(--dark); }
        .aslide-modal-close {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--bg);
            border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--soft-dark);
            font-size: 1rem;
            transition: var(--transition);
        }
        .aslide-modal-close:hover { background: var(--pink-light); color: var(--pink); }

        .aslide-modal-drag {
            width: 40px; height: 4px;
            background: var(--border);
            border-radius: 4px;
            margin: 10px auto 0;
            flex-shrink: 0;
        }

        .aslide-modal-body {
            overflow-y: auto;
            padding: 18px;
            flex: 1;
        }
        .aslide-modal-body::-webkit-scrollbar { width: 4px; }
        .aslide-modal-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

        .aslide-modal-footer {
            display: flex;
            gap: 10px;
            padding: 14px 18px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }

        /* Form */
        .aslide-form-group { margin-bottom: 14px; }
        .aslide-form-label {
            display: block;
            font-size: .8rem;
            font-weight: 600;
            color: var(--soft-dark);
            margin-bottom: 5px;
        }
        .aslide-form-label .req { color: var(--pink); margin-left: 2px; }
        .aslide-form-control {
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
        .aslide-form-control:focus {
            border-color: var(--pink);
            box-shadow: 0 0 0 3px rgba(255,61,139,.1);
            background: #fff;
        }
        .aslide-form-control.is-invalid { border-color: #E53935; }
        .aslide-invalid-feedback { color: #E53935; font-size: .74rem; margin-top: 4px; }
        .aslide-form-hint { font-size: .72rem; color: var(--muted); margin-top: 4px; }

        /* Image preview */
        .aslide-img-preview {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            background: var(--bg);
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--border);
            margin-bottom: 10px;
        }
        .aslide-img-preview img {
            width: 76px; height: 52px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border);
            flex-shrink: 0;
        }
        .aslide-img-preview-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .aslide-img-preview-info span { font-size: .75rem; color: var(--muted); }
        .aslide-img-remove {
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
        .aslide-img-remove:hover { background: #FFD6D6; }
        .aslide-img-remove .material-icons-round { font-size: .8rem; }

        /* Upload progress */
        .aslide-upload-progress { margin-top: 8px; }
        .aslide-upload-bar {
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
        .aslide-upload-progress small { font-size: .72rem; color: var(--muted); }

        .aslide-switch-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: var(--bg);
            border-radius: var(--radius-sm);
            cursor: pointer;
        }
        .aslide-switch-label {
            font-size: .84rem;
            color: var(--soft-dark);
            font-weight: 500;
            flex: 1;
        }

        /* Buttons */
        .btn-aslide-primary {
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
        .btn-aslide-primary:hover { background: #e02d7a; }
        .btn-aslide-primary:disabled { opacity: .6; cursor: not-allowed; }

        .btn-aslide-secondary {
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
        .btn-aslide-secondary:hover { background: var(--border); }

        /* ── Delete Modal ── */
        .aslide-delete-modal {
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
        .aslide-delete-icon {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: #FFF0F0;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
            font-size: 1.6rem;
        }
        .aslide-delete-modal h6 {
            font-size: .98rem; font-weight: 700;
            color: var(--dark); margin: 0 0 6px;
        }
        .aslide-delete-modal p {
            font-size: .8rem; color: var(--muted);
            margin: 0 0 20px; line-height: 1.5;
        }
        .aslide-delete-actions { display: flex; gap: 10px; justify-content: center; }

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

    </style>
@endpush