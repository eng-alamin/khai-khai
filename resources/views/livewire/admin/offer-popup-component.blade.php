{{-- resources/views/livewire/admin/offer-popup-component.blade.php --}}
{{-- Styles: resources/css/blade.css (shared classes, Bootstrap 5 required) --}}
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
                <span class="title-emoji">🎉</span>
                Offer Popups
            </div>
            <button class="btn-new-adm" wire:click="openCreate">
                <span class="plus-icon">＋</span>
                Add Popup
            </button>
        </div>

        {{-- ── Info ── --}}
        <div class="form-hint" style="margin:0 0 12px;">
            Customer-er home page-e ekti popup-i dekhano hoy: sobcheye notun <strong>Live</strong> popup.
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

        {{-- ── Popup Cards ── --}}
        @forelse($popups as $popup)
            @php
                $label       = $popup->status_label;
                $statusClass = $label === 'Live' ? 'available' : 'unavailable';
            @endphp

            <div class="card" wire:key="offer-popup-{{ $popup->id }}">
                {{-- Top Row --}}
                <div class="card-top">
                    <div class="card-thumb" style="display:flex;align-items:center;justify-content:center;font-size:22px;">
                        🎉
                    </div>
                    <div class="card-info">
                        <div class="card-title">{{ $popup->highlight }} {{ $popup->highlight_sub }}</div>
                        <div class="card-desc">{{ Str::limit($popup->title, 45) }}</div>
                        <div class="card-desc card-desc-muted">
                            @if($popup->starts_at || $popup->ends_at)
                                {{ $popup->starts_at?->format('d M, h:i A') ?? 'Now' }}
                                →
                                {{ $popup->ends_at?->format('d M, h:i A') ?? 'No end' }}
                            @else
                                No schedule (always on while active)
                            @endif
                            · {{ $popup->repeat_hours > 0 ? 'Repeat after ' . $popup->repeat_hours . 'h' : 'Show every visit' }}
                        </div>
                    </div>
                    <span class="status-badge {{ $statusClass }}">{{ $label }}</span>
                </div>

                {{-- Bottom Row --}}
                <div class="card-bottom">
                    <label class="toggle">
                        <input type="checkbox"
                            @checked($popup->is_active)
                            wire:click="toggleActive({{ $popup->id }})">
                        <span class="toggle-slider"></span>
                    </label>
                    <div class="card-actions">
                        <button class="btn-edit"
                            wire:click="openEdit({{ $popup->id }})">
                            <span class="material-icons-round">drive_file_rename_outline</span>
                            Edit
                        </button>
                        <button class="btn-delete"
                            wire:click="confirmDeleteRecord({{ $popup->id }})">
                            <span class="material-icons-round">delete</span>
                        </button>
                    </div>
                </div>
            </div>

        @empty
            <div class="empty">
                <i class="bi bi-megaphone empty-icon"></i>
                <p>No offer popups found.</p>
                <button class="btn-new-adm" wire:click="openCreate">+ Add New Popup</button>
            </div>
        @endforelse

        {{-- ── Pagination ── --}}
        @if($popups->hasPages())
            <div class="pagination">
                <small>Showing {{ $popups->firstItem() ?? 0 }}–{{ $popups->lastItem() ?? 0 }} of {{ $popups->total() }} total</small>
                {{ $popups->links('pagination::custom') }}
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
                        {{ $editId ? '✏️ Edit Offer Popup' : '🎉 Add Offer Popup' }}
                    </div>
                    <button class="jara-modal-close" wire:click="$set('showModal', false)">✕</button>
                </div>

                <div class="jara-modal-body">

                    {{-- Badge --}}
                    <div class="form-group">
                        <label class="form-label">Badge (ছোট ট্যাগ)</label>
                        <input type="text" class="form-control @error('badge') is-invalid @enderror"
                            wire:model="badge" placeholder="🎉 বিশেষ অফার">
                        @error('badge') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Highlight --}}
                    <div class="form-group">
                        <label class="form-label">Highlight <span class="req">*</span></label>
                        <input type="text" class="form-control @error('highlight') is-invalid @enderror"
                            wire:model="highlight" placeholder="৪০%–৫০%">
                        <div class="form-hint">Popup-er upore boro kore dekhabe.</div>
                        @error('highlight') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Highlight sub --}}
                    <div class="form-group">
                        <label class="form-label">Highlight-er niche lekha</label>
                        <input type="text" class="form-control @error('highlight_sub') is-invalid @enderror"
                            wire:model="highlight_sub" placeholder="ডিসকাউন্ট">
                        @error('highlight_sub') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Title --}}
                    <div class="form-group">
                        <label class="form-label">Title <span class="req">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                            wire:model="title" placeholder="সাথে ফ্রি ডেলিভারি! 🛵">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Message --}}
                    <div class="form-group">
                        <label class="form-label">Message</label>
                        <textarea rows="3" class="form-control @error('message') is-invalid @enderror"
                            wire:model="message"
                            placeholder="পছন্দের রেস্টুরেন্ট আর বাজার থেকে অর্ডার করুন..."></textarea>
                        @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Chips --}}
                    <div class="form-group">
                        <label class="form-label">Chips</label>
                        <input type="text" class="form-control @error('chips') is-invalid @enderror"
                            wire:model="chips" placeholder="🍔 রেস্টুরেন্ট, 🛒 বাজার, 🚚 ফ্রি ডেলিভারি">
                        <div class="form-hint">Comma (,) diye alada korun. Maximum 6 ti dekhabe.</div>
                        @error('chips') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Button text --}}
                    <div class="form-group">
                        <label class="form-label">Button Text <span class="req">*</span></label>
                        <input type="text" class="form-control @error('button_text') is-invalid @enderror"
                            wire:model="button_text" placeholder="এখনই অর্ডার করুন">
                        @error('button_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Button URL --}}
                    <div class="form-group">
                        <label class="form-label">Button Link</label>
                        <input type="text" class="form-control @error('button_url') is-invalid @enderror"
                            wire:model="button_url" placeholder="/restaurants  ba  https://example.com">
                        <div class="form-hint">Khali rakhle Restaurants page-e jabe.</div>
                        @error('button_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Repeat hours --}}
                    <div class="form-group">
                        <label class="form-label">Abar dekhabe (ghonta pore)</label>
                        <input type="number" min="0" class="form-control @error('repeat_hours') is-invalid @enderror"
                            wire:model="repeat_hours">
                        <div class="form-hint">24 = ekbar dekhale 24 ghonta ar dekhabe na. 0 = protibar home page-e ashle dekhabe.</div>
                        @error('repeat_hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Schedule --}}
                    <div class="form-group">
                        <label class="form-label">Start (optional)</label>
                        <input type="datetime-local" class="form-control @error('starts_at') is-invalid @enderror"
                            wire:model="starts_at">
                        @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">End (optional)</label>
                        <input type="datetime-local" class="form-control @error('ends_at') is-invalid @enderror"
                            wire:model="ends_at">
                        <div class="form-hint">Khali rakhle manually off na kora porjonto cholbe.</div>
                        @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Active toggle --}}
                    <label class="switch-wrap">
                        <span class="switch-label">Popup is currently active</span>
                        <label class="toggle">
                            <input type="checkbox" wire:model="is_active" id="admOfferPopupActive">
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
                <h6>Delete Offer Popup?</h6>
                <p>This popup will be permanently removed.<br>This action cannot be undone.</p>
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
