{{-- resources/views/livewire/vendor/review-component.blade.php --}}
<div>

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="topbar">
            <div class="topbar-title">
                <span class="title-emoji">⭐</span>
                Customer Reviews
            </div>
            <span class="review-total-badge">{{ $stats['total'] }} total</span>
        </div>

        {{-- ── Stat Cards ── --}}
        <div class="rating-stats">

            {{-- Food rating --}}
            <div class="rating-stat-card rating-stat-card--accent">
                <p class="rating-stat-label">Food Rating</p>
                <p class="rating-stat-big">{{ number_format($stats['avgFood'], 1) }}</p>
                <div class="rating-stars rating-stars--lg">
                    @for ($s = 1; $s <= 5; $s++)
                        <svg viewBox="0 0 20 20" fill="{{ $s <= round($stats['avgFood']) ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                </div>
            </div>

            {{-- Delivery rating --}}
            <div class="rating-stat-card">
                <p class="rating-stat-label">Delivery Rating</p>
                <p class="rating-stat-big">{{ $stats['avgDelivery'] > 0 ? number_format($stats['avgDelivery'], 1) : '—' }}</p>
                <div class="rating-stars rating-stars--lg">
                    @for ($s = 1; $s <= 5; $s++)
                        <svg viewBox="0 0 20 20" fill="{{ $s <= round($stats['avgDelivery']) ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                </div>
            </div>

            {{-- 5-star count --}}
            <div class="rating-stat-card">
                <p class="rating-stat-label">5-Star Reviews</p>
                <p class="rating-stat-big">{{ $stats['fiveStar'] }}</p>
                <p class="rating-stat-sub">
                    {{ $stats['total'] > 0 ? round(($stats['fiveStar'] / $stats['total']) * 100) : 0 }}% of all reviews
                </p>
            </div>

            {{-- Rating distribution --}}
            <div class="rating-stat-card rating-stat-card--dist">
                <p class="rating-stat-label">Rating Breakdown</p>
                <div class="rating-dist">
                    @foreach ($stats['ratingDist'] as $star => $data)
                        <div class="rating-dist-row">
                            <span class="rating-dist-label">{{ $star }}★</span>
                            <div class="rating-dist-track">
                                <div class="rating-dist-fill" style="width: {{ $data['percent'] }}%"></div>
                            </div>
                            <span class="rating-dist-count">{{ $data['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- ── Search (left) + Rating/Type/Sort Select (right) ── --}}
        <div class="filterbar">
            <div class="search-inner filterbar-search">
                <span class="material-icons-round search-icon">search</span>
                <input type="text"
                    wire:model.live.debounce.350ms="search"
                    placeholder="Search comments...">
            </div>

            <div class="filterbar-selects">
                <select class="select" wire:model.live="filterRating">
                    @foreach (['all' => 'All Stars', '5' => '★ 5', '4' => '★ 4', '3' => '★ 3', '2' => '★ 2', '1' => '★ 1'] as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select class="select" wire:model.live="filterType">
                    <option value="all">All Reviews</option>
                    <option value="food">Food Only</option>
                    <option value="delivery">With Delivery</option>
                </select>

                <select class="select" wire:model.live="sortBy">
                    <option value="latest">Latest First</option>
                    <option value="highest">Highest Rated</option>
                    <option value="lowest">Lowest Rated</option>
                </select>
            </div>
        </div>

        {{-- ── Review List ── --}}
        <div class="review-list" wire:loading.class="review-list--loading">

            @forelse ($reviews as $review)
                <div class="card" wire:key="review-{{ $review->id }}">

                    {{-- Head row --}}
                    <div class="review-item-head">
                        <div class="review-avatar">
                            {{ strtoupper(substr($review->customer->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="review-item-meta">
                            <div class="review-item-name">{{ $review->customer->name ?? 'Guest' }}</div>
                            <div class="review-item-date">{{ $review->created_at->diffForHumans() }}</div>
                        </div>
                        <div class="review-item-badges">
                            <span class="review-badge review-badge--food">
                                <svg viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M7.612 1.323c.152-.43.624-.43.776 0l1.064 3.18a.5.5 0 00.475.346h3.25c.459 0 .65.594.28.87l-2.631 1.96a.5.5 0 00-.18.558l1.064 3.18c.153.43-.35.79-.716.558L8.28 9.935a.5.5 0 00-.56 0l-2.714 1.04c-.366.232-.869-.128-.716-.558l1.064-3.18a.5.5 0 00-.18-.558L2.543 5.72c-.37-.276-.179-.87.28-.87h3.25a.5.5 0 00.475-.346l1.064-3.18z"/>
                                </svg>
                                Food {{ $review->food_rating }}/5
                            </span>
                            @if ($review->delivery_rating)
                                <span class="review-badge review-badge--delivery">
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
                    <div class="rating-stars rating-stars--sm">
                        @for ($s = 1; $s <= 5; $s++)
                            <svg viewBox="0 0 20 20" fill="{{ $s <= $review->food_rating ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>

                    {{-- Comment --}}
                    @if ($review->comment)
                        <p class="review-comment">{{ $review->comment }}</p>
                    @else
                        <p class="review-no-comment">No written comment.</p>
                    @endif

                    {{-- Order reference --}}
                    @if ($review->order)
                        <p class="review-order-ref">Order #{{ $review->order->id }}</p>
                    @endif

                </div>
            @empty
                <div class="empty">
                    <i class="bi bi-star review-empty-icon"></i>
                    <p>No reviews match your filters.</p>
                    <button type="button"
                        wire:click="$set('filterRating','all'); $set('filterType','all'); $set('search','')"
                        class="btn-new-adm">Clear filters</button>
                </div>
            @endforelse

        </div>

        {{-- ── Pagination ── --}}
        @if ($reviews->hasPages())
            <div class="review-pagination">
                {{ $reviews->links() }}
            </div>
        @endif

    </div>{{-- /main-content --}}

</div>
