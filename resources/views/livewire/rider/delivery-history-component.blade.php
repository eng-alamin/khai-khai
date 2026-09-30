{{-- resources/views/livewire/rider/delivery-history-component.blade.php --}}
{{-- Styles: resources/css/blade.css (shared classes, Bootstrap 5 required) --}}
<div class="card dh-card">

    {{-- HEADER --}}
    <div class="dh-header">
        <div class="dh-title">Delivery History</div>
        <span class="dh-badge-total">Total {{ $totalDelivered }}</span>
    </div>

    {{-- TABLE --}}
    <div class="dh-table-wrap">
        <table class="dh-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Restaurant</th>
                    <th>Date</th>
                    <th>Earning</th>
                    <th>Rating</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr wire:key="history-{{ $row['id'] }}">
                        <td class="dh-order">#{{ $row['order_number'] }}</td>
                        <td class="dh-customer">{{ $row['customer'] }}</td>
                        <td class="dh-restaurant">{{ $row['restaurant'] }}</td>
                        <td class="dh-date">{{ $row['date_label'] }}</td>
                        <td class="dh-earning">{{ $row['earning'] }}</td>
                        <td class="dh-rating">
                            @if($row['rating'])
                                {{ $row['rating'] }}★
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="dh-empty">
                                <div style="font-size:40px;" class="mb-2">📦</div>
                                <div style="font-weight:700;color:var(--muted);">No deliveries completed yet</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    @if($rows->hasPages())
        <div class="dh-pagination">
            <button
                class="btn-kk btn-ghost-kk"
                wire:click="previousPage"
                @if($rows->onFirstPage()) disabled @endif
            >
                « Previous
            </button>

            <span class="dh-page-info">
                Page {{ $rows->currentPage() }} / {{ $rows->lastPage() }}
            </span>

            <button
                class="btn-kk btn-ghost-kk"
                wire:click="nextPage"
                @if(! $rows->hasMorePages()) disabled @endif
            >
                Next »
            </button>
        </div>
    @endif

</div>
