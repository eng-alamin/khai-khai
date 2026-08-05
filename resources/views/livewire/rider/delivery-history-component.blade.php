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
                                <div style="font-weight:700;color:var(--text-2);">No deliveries completed yet</div>
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

@push('styles')
<style>
    :root {
        --success: #16a34a;
        --warning: #f59e0b;
        --danger: #ef4444;
        --pink: #ec4899;
        --text-1: #1f2937;
        --text-2: #4b5563;
        --text-3: #9ca3af;
        --border: #e5e7eb;
        --radius: 14px;
        --shadow: 0 2px 10px rgba(0,0,0,.06);
    }

    .card {
        background: #fff;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        padding: 22px 24px;
        width: 100%;
    }

    /* header */
    .dh-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
    }
    .dh-title {
        font-size: 19px;
        font-weight: 800;
        color: var(--pink);
    }
    .dh-badge-total {
        background: #fce7f3;
        color: var(--pink);
        font-size: 12px;
        font-weight: 700;
        padding: 5px 14px;
        border-radius: 999px;
        white-space: nowrap;
    }

    /* table */
    .dh-table-wrap { overflow-x: auto; }
    .dh-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }
    .dh-table thead th {
        background: #f9fafb;
        color: var(--text-3);
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .4px;
        text-align: left;
        padding: 12px 14px;
        white-space: nowrap;
    }
    .dh-table tbody td {
        padding: 14px;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
    }
    .dh-table tbody tr:last-child td { border-bottom: none; }
    .dh-table tbody tr:hover { background: #fafafa; }

    .dh-order      { font-weight: 800; color: var(--text-1); }
    .dh-customer   { color: var(--text-1); }
    .dh-restaurant { color: #2563eb; font-weight: 600; }
    .dh-date       { color: var(--text-3); font-size: 13px; }
    .dh-earning    { color: var(--success); font-weight: 800; }
    .dh-rating     { color: #f59e0b; font-weight: 700; }

    .dh-empty {
        text-align: center;
        padding: 30px 0;
        color: var(--text-3);
    }

    /* pagination */
    .dh-pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        margin-top: 18px;
    }
    .dh-page-info {
        font-size: 13px;
        color: var(--text-3);
        font-weight: 700;
    }

    .btn-kk {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 6px; padding: 8px 16px; border-radius: 10px;
        font-size: 13px; font-weight: 700; border: none;
        cursor: pointer; white-space: nowrap;
        transition: opacity .15s, transform .1s; text-decoration: none;
    }
    .btn-ghost-kk {
        background: #f3f4f6;
        color: var(--text-2);
    }
    .btn-kk:hover   { opacity: .88; }
    .btn-kk:active  { transform: scale(.97); }
    .btn-kk:disabled { opacity: .4; cursor: not-allowed; }

    .mb-2 { margin-bottom: 8px; }
</style>
@endpush