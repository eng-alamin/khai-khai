{{-- resources/views/livewire/vendor/review-component.blade.php --}}
<div>

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="rv-topbar">
            <div class="rv-topbar-title">
                <span class="title-emoji">⭐</span>
                Customer Reviews
            </div>
            <span class="rv-total-badge">{{ $stats['total'] }} total</span>
        </div>

        {{-- ── Stat Cards ── --}}
        <div class="rv-stats">

            {{-- Food rating --}}
            <div class="rv-stat-card rv-stat-card--accent">
                <p class="rv-stat-label">Food Rating</p>
                <p class="rv-stat-big">{{ number_format($stats['avgFood'], 1) }}</p>
                <div class="rv-stars rv-stars--lg">
                    @for ($s = 1; $s <= 5; $s++)
                        <svg viewBox="0 0 20 20" fill="{{ $s <= round($stats['avgFood']) ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                </div>
            </div>

            {{-- Delivery rating --}}
            <div class="rv-stat-card">
                <p class="rv-stat-label">Delivery Rating</p>
                <p class="rv-stat-big">{{ $stats['avgDelivery'] > 0 ? number_format($stats['avgDelivery'], 1) : '—' }}</p>
                <div class="rv-stars rv-stars--lg">
                    @for ($s = 1; $s <= 5; $s++)
                        <svg viewBox="0 0 20 20" fill="{{ $s <= round($stats['avgDelivery']) ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                </div>
            </div>

            {{-- 5-star count --}}
            <div class="rv-stat-card">
                <p class="rv-stat-label">5-Star Reviews</p>
                <p class="rv-stat-big">{{ $stats['fiveStar'] }}</p>
                <p class="rv-stat-sub">
                    {{ $stats['total'] > 0 ? round(($stats['fiveStar'] / $stats['total']) * 100) : 0 }}% of all reviews
                </p>
            </div>

            {{-- Rating distribution --}}
            <div class="rv-stat-card rv-stat-card--dist">
                <p class="rv-stat-label">Rating Breakdown</p>
                <div class="rv-dist">
                    @foreach ($stats['ratingDist'] as $star => $data)
                        <div class="rv-dist-row">
                            <span class="rv-dist-label">{{ $star }}★</span>
                            <div class="rv-dist-track">
                                <div class="rv-dist-fill" style="width: {{ $data['percent'] }}%"></div>
                            </div>
                            <span class="rv-dist-count">{{ $data['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- ── Search ── --}}
        <div class="rv-search">
            <div class="rv-search-inner">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.350ms="search"
                    placeholder="Search comments...">
            </div>
        </div>

        {{-- ── Rating Chips ── --}}
        <div class="rv-filters">
            @foreach (['all' => 'All Stars', '5' => '★ 5', '4' => '★ 4', '3' => '★ 3', '2' => '★ 2', '1' => '★ 1'] as $val => $label)
                <button type="button"
                    wire:click="$set('filterRating', '{{ $val }}')"
                    class="filter-chip {{ $filterRating === $val ? 'active' : '' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- ── Type + Sort ── --}}
        <div class="rv-selects">
            <select wire:model.live="filterType" class="rv-select">
                <option value="all">All Reviews</option>
                <option value="food">Food Only</option>
                <option value="delivery">With Delivery</option>
            </select>
            <select wire:model.live="sortBy" class="rv-select">
                <option value="latest">Latest First</option>
                <option value="highest">Highest Rated</option>
                <option value="lowest">Lowest Rated</option>
            </select>
        </div>

        {{-- ── Review List ── --}}
        <div class="rv-list" wire:loading.class="rv-list--loading">

            @forelse ($reviews as $review)
                <div class="rv-item" wire:key="review-{{ $review->id }}">

                    {{-- Head row --}}
                    <div class="rv-item-head">
                        <div class="rv-avatar">
                            {{ strtoupper(substr($review->customer->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="rv-item-meta">
                            <div class="rv-item-name">{{ $review->customer->name ?? 'Guest' }}</div>
                            <div class="rv-item-date">{{ $review->created_at->diffForHumans() }}</div>
                        </div>
                        <div class="rv-item-badges">
                            <span class="rv-badge rv-badge--food">
                                <svg viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M7.612 1.323c.152-.43.624-.43.776 0l1.064 3.18a.5.5 0 00.475.346h3.25c.459 0 .65.594.28.87l-2.631 1.96a.5.5 0 00-.18.558l1.064 3.18c.153.43-.35.79-.716.558L8.28 9.935a.5.5 0 00-.56 0l-2.714 1.04c-.366.232-.869-.128-.716-.558l1.064-3.18a.5.5 0 00-.18-.558L2.543 5.72c-.37-.276-.179-.87.28-.87h3.25a.5.5 0 00.475-.346l1.064-3.18z"/>
                                </svg>
                                Food {{ $review->food_rating }}/5
                            </span>
                            @if ($review->delivery_rating)
                                <span class="rv-badge rv-badge--delivery">
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M1 8h10M8 5l3 3-3 3" stroke-linecap="round" stroke-linejoin="round"/>
                                        <rect x="11" y="5" width="4" height="6" rx="1"/>
                                    </svg>
                                    Delivery {{ $review->delivery_rating }}/5
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Stars row --}}
                    <div class="rv-stars rv-stars--sm">
                        @for ($s = 1; $s <= 5; $s++)
                            <svg viewBox="0 0 20 20" fill="{{ $s <= $review->food_rating ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>

                    {{-- Comment --}}
                    @if ($review->comment)
                        <p class="rv-item-comment">{{ $review->comment }}</p>
                    @else
                        <p class="rv-item-no-comment">No written comment.</p>
                    @endif

                    {{-- Order reference --}}
                    @if ($review->order)
                        <p class="rv-item-order">Order #{{ $review->order->id }}</p>
                    @endif

                </div>
            @empty
                <div class="rv-empty">
                    <i class="bi bi-star rv-empty-icon"></i>
                    <p>No reviews match your filters.</p>
                    <button type="button"
                        wire:click="$set('filterRating','all'); $set('filterType','all'); $set('search','')"
                        class="rv-btn-new-offer">Clear filters</button>
                </div>
            @endforelse

        </div>

        {{-- ── Pagination ── --}}
        @if ($reviews->hasPages())
            <div class="rv-pagination">
                {{ $reviews->links() }}
            </div>
        @endif

    </div>{{-- /main-content --}}

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

    /* ── Top Header ── */
    .rv-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 16px 12px;
        background: var(--bg);
        position: sticky;
        top: 0;
        z-index: 50;
    }
    .rv-topbar-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 1.18rem;
        font-weight: 700;
        color: var(--dark);
    }
    .rv-topbar-title .title-emoji {
        font-size: 1.2rem;
    }
    .rv-total-badge {
        font-size: .76rem;
        font-weight: 600;
        padding: 6px 14px;
        background: var(--pink-light);
        color: var(--pink);
        border-radius: 50px;
        white-space: nowrap;
    }

    /* ── Stat Cards ── */
    .rv-stats {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 12px;
        padding: 4px 16px 16px;
    }
    .rv-stat-card {
        background: var(--card-bg);
        border: 1.5px solid var(--border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-card);
        padding: 16px;
        transition: var(--transition);
    }
    .rv-stat-card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-2px);
    }
    .rv-stat-card--accent {
        background: var(--pink);
        border-color: var(--pink);
        color: #fff;
    }
    .rv-stat-card--accent .rv-stat-label,
    .rv-stat-card--accent .rv-stat-sub { color: rgba(255,255,255,.78); }
    .rv-stat-card--accent .rv-stars svg { color: #fff; }
    .rv-stat-card--dist { grid-column: span 2; }
    .rv-stat-label {
        font-size: .7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--muted);
        margin: 0 0 6px;
    }
    .rv-stat-big {
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: -.03em;
        line-height: 1;
        color: var(--dark);
        margin: 0 0 8px;
    }
    .rv-stat-card--accent .rv-stat-big { color: #fff; }
    .rv-stat-sub { font-size: .76rem; color: var(--muted); margin: 6px 0 0; }

    /* ── Stars ── */
    .rv-stars { display: flex; gap: 2px; }
    .rv-stars svg { color: var(--pink); }
    .rv-stars--lg svg { width: 17px; height: 17px; }
    .rv-stars--sm svg { width: 15px; height: 15px; }

    /* ── Distribution bars ── */
    .rv-dist { display: flex; flex-direction: column; gap: 7px; margin-top: 6px; }
    .rv-dist-row { display: flex; align-items: center; gap: 9px; }
    .rv-dist-label { font-size: .74rem; font-weight: 600; color: var(--muted); min-width: 22px; }
    .rv-dist-track {
        flex: 1;
        height: 6px;
        background: var(--border);
        border-radius: 50px;
        overflow: hidden;
    }
    .rv-dist-fill {
        height: 100%;
        background: var(--pink);
        border-radius: 50px;
        transition: width .4s ease;
    }
    .rv-dist-count { font-size: .72rem; color: var(--muted); min-width: 18px; text-align: right; }

    /* ── Search ── */
    .rv-search { padding: 0 16px 12px; }
    .rv-search-inner { position: relative; }
    .rv-search-inner .search-icon {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--muted);
        font-size: .95rem;
        pointer-events: none;
    }
    .rv-search-inner input {
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
    .rv-search-inner input:focus {
        border-color: var(--pink);
        box-shadow: 0 0 0 3px rgba(255,61,139,.1);
    }

    /* ── Filter Chips ── */
    .rv-filters {
        display: flex;
        gap: 8px;
        padding: 0 16px 12px;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .rv-filters::-webkit-scrollbar { display: none; }
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

    /* ── Selects ── */
    .rv-selects {
        display: flex;
        gap: 8px;
        padding: 0 16px 16px;
    }
    .rv-select {
        flex: 1;
        padding: 9px 12px;
        border: 1.5px solid var(--border);
        border-radius: var(--radius-sm);
        font-family: var(--font);
        font-size: .8rem;
        color: var(--dark);
        background: var(--card-bg);
        cursor: pointer;
        outline: none;
        transition: var(--transition);
    }
    .rv-select:focus { border-color: var(--pink); }

    /* ── Review List ── */
    .rv-list { display: flex; flex-direction: column; gap: 0; transition: opacity .2s; }
    .rv-list--loading { opacity: .5; pointer-events: none; }

    .rv-item {
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
    .rv-item::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 4px;
        background: var(--pink);
        border-radius: 4px 0 0 4px;
        opacity: 0;
        transition: var(--transition);
    }
    .rv-item:hover {
        box-shadow: var(--shadow-hover);
        border-color: rgba(255,61,139,.2);
        transform: translateY(-2px);
    }
    .rv-item:hover::before { opacity: 1; }

    .rv-item-head {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    .rv-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: var(--pink-light);
        color: var(--pink);
        font-weight: 700;
        font-size: .9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .rv-item-meta { flex: 1; min-width: 0; }
    .rv-item-name { font-size: .9rem; font-weight: 700; color: var(--dark); }
    .rv-item-date { font-size: .73rem; color: var(--muted); margin-top: 2px; }
    .rv-item-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-left: auto; }

    .rv-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 50px;
        font-size: .7rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .rv-badge svg { width: 11px; height: 11px; }
    .rv-badge--food     { background: var(--pink-light); color: var(--pink); }
    .rv-badge--delivery { background: #EFF8FF; color: #1565C0; }

    .rv-item-comment    { font-size: .85rem; line-height: 1.6; color: var(--soft-dark); margin: 10px 0 6px; }
    .rv-item-no-comment { font-size: .8rem; color: var(--muted); font-style: italic; margin: 10px 0 6px; }
    .rv-item-order      { font-size: .72rem; color: var(--muted); margin: 0; }

    /* ── Empty State ── */
    .rv-empty {
        text-align: center;
        padding: 60px 20px;
    }
    .rv-empty-icon {
        font-size: 3rem;
        opacity: .25;
        display: block;
        margin-bottom: 12px;
        color: var(--muted);
    }
    .rv-btn-new-offer{
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
        box-shadow: 0 4px 18px rgba(255, 61, 139, .35);
        transition: var(--transition);
        letter-spacing: .02em;
    }
    .rv-empty p { color: var(--muted); font-size: .88rem; margin: 0 0 16px; }

    /* ── Pagination ── */
    .rv-pagination {
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ── Responsive ── */
    @media (max-width: 400px) {
        .rv-stat-card--dist { grid-column: span 2; }
        .rv-stats { grid-template-columns: 1fr 1fr; }
        .rv-topbar-title { font-size: 1rem; }
        .rv-item-badges { margin-left: 0; }
    }
</style>
@endpush