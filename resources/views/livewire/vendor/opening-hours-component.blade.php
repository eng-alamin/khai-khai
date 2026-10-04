{{-- resources/views/livewire/vendor/opening-hours-component.blade.php --}}
<div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="topbar">
            <div class="topbar-title">
                <span class="title-emoji">🕒</span>
                Opening Hours
            </div>
            <button class="btn-new-adm" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading wire:target="save" class="spinner-sm"></span>
                Save
            </button>
        </div>

        {{-- ── Automatic open/close switch ── --}}
        <div class="card">
            <label class="switch-wrap">
                <span class="switch-label">
                    Open / close my restaurant automatically
                    <small style="display:block;opacity:.7;">
                        Now: {{ $isOpenNow ? '🟢 Open' : '🔴 Closed' }}.
                        You can still open or close manually; the schedule takes over again at the next opening or closing time.
                    </small>
                </span>
                <label class="toggle">
                    <input type="checkbox" wire:model="useOpeningHours">
                    <span class="toggle-slider"></span>
                </label>
            </label>
        </div>

        {{-- ── Weekly table ── --}}
        @foreach($displayOrder as $day)
            <div class="card" wire:key="hours-day-{{ $day }}">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <div class="card-title" style="min-width:110px;">{{ $dayNames[$day] }}</div>

                    <label class="switch-wrap" style="margin:0;">
                        <span class="switch-label">Closed all day</span>
                        <label class="toggle">
                            <input type="checkbox" wire:model.live="days.{{ $day }}.closed">
                            <span class="toggle-slider"></span>
                        </label>
                    </label>
                </div>

                @unless($days[$day]['closed'])
                    <div class="row" style="margin-top:10px;">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Opens</label>
                                <input type="time"
                                    class="form-control @error('days.'.$day.'.opens') is-invalid @enderror"
                                    wire:model="days.{{ $day }}.opens">
                                @error('days.'.$day.'.opens') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Closes</label>
                                <input type="time"
                                    class="form-control @error('days.'.$day.'.closes') is-invalid @enderror"
                                    wire:model="days.{{ $day }}.closes">
                                <div class="form-hint">A time earlier than opening means it closes after midnight.</div>
                                @error('days.'.$day.'.closes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                @endunless
            </div>
        @endforeach

        <div style="margin:16px 0;">
            <button class="btn-primary" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading wire:target="save" class="spinner-sm"></span>
                Save Opening Hours
            </button>
        </div>

    </div>{{-- /main-content --}}

</div>
