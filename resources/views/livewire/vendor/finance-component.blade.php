{{-- resources/views/livewire/vendor/finance-component.blade.php --}}
<div>

    <div class="main-content">

        {{-- ── Summary Cards ── --}}
        <div class="fin-summary-grid">

            <div class="fin-summary-card">
                <div class="fin-summary-icon fin-icon-green">
                    <span class="material-icons-round">payments</span>
                </div>
                <div class="fin-summary-text">
                    <div class="fin-summary-value fin-value-green">৳{{ number_format($monthlyRevenue) }}</div>
                    <div class="fin-summary-label">This Month's Revenue</div>
                </div>
                <div class="fin-summary-blob"></div>
            </div>

            <div class="fin-summary-card">
                <div class="fin-summary-icon fin-icon-pink">
                    <span class="material-icons-round">percent</span>
                </div>
                <div class="fin-summary-text">
                    <div class="fin-summary-value fin-value-pink">৳{{ number_format($monthlyCommission) }}</div>
                    <div class="fin-summary-label">KhaiKhai Commission ({{ rtrim(rtrim(number_format($commissionRate, 1), '0'), '.') }}%)</div>
                </div>
                <div class="fin-summary-blob"></div>
            </div>

            <div class="fin-summary-card">
                <div class="fin-summary-icon fin-icon-blue">
                    <span class="material-icons-round">account_balance_wallet</span>
                </div>
                <div class="fin-summary-text">
                    <div class="fin-summary-value fin-value-blue">৳{{ number_format($monthlyNet) }}</div>
                    <div class="fin-summary-label">Net Revenue</div>
                </div>
                <div class="fin-summary-blob"></div>
            </div>

            <div class="fin-summary-card">
                <div class="fin-summary-icon fin-icon-orange">
                    <span class="material-icons-round">event</span>
                </div>
                <div class="fin-summary-text">
                    <div class="fin-summary-value fin-value-orange">{{ $nextPaymentDate }}</div>
                    <div class="fin-summary-label">Next Payment</div>
                </div>
                <div class="fin-summary-blob"></div>
            </div>

        </div>

        {{-- ── Payment History ── --}}
        <div class="fin-history-card">
            <div class="fin-history-title">Payment History</div>

            @if(count($paymentHistory))
                <div class="fin-table-wrap">
                    <table class="fin-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Sales</th>
                                <th>Commission</th>
                                <th>Net</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paymentHistory as $row)
                                <tr>
                                    <td class="fin-cell-date">{{ $row['date'] }}</td>
                                    <td class="fin-cell-sales">৳{{ number_format($row['sales']) }}</td>
                                    <td class="fin-cell-commission">৳{{ number_format($row['commission']) }}</td>
                                    <td class="fin-cell-net">৳{{ number_format($row['net']) }}</td>
                                    <td>
                                        @if($row['status'] === 'paid')
                                            <span class="fin-status-badge fin-status-paid">Paid</span>
                                        @elseif($row['status'] === 'processing')
                                            <span class="fin-status-badge fin-status-processing">Processing</span>
                                        @elseif($row['status'] === 'pending')
                                            <span class="fin-status-badge fin-status-pending">Pending</span>
                                        @else
                                            <span class="fin-status-badge fin-status-failed">Failed</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="fin-empty">
                    <span class="material-icons-round fin-empty-icon">receipt_long</span>
                    <p>No payment history found.</p>
                </div>
            @endif
        </div>

    </div>{{-- /main-content --}}

</div>

@push('styles')
    <style>

        /* ── Page Wrapper ── */
        .main-content {
            background: var(--bg);
            min-height: 100vh;
            padding: 20px 16px 80px;
            font-family: var(--font);
        }

        /* ── Summary Grid ── */
        .fin-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .fin-summary-card {
            background: var(--card-bg);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 18px;
            box-shadow: var(--shadow-card);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }
        .fin-summary-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        .fin-summary-blob {
            position: absolute;
            right: -18px;
            top: -18px;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: var(--bg);
            opacity: .6;
            z-index: 0;
        }

        .fin-summary-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            position: relative;
            z-index: 1;
        }
        .fin-summary-icon .material-icons-round { font-size: 1.2rem; }

        .fin-icon-green  { background: #E8FAF0; color: #1A9453; }
        .fin-icon-pink   { background: var(--pink-light); color: var(--pink); }
        .fin-icon-blue   { background: #E9F1FF; color: #2F6FED; }
        .fin-icon-orange { background: #FFF4E0; color: #F5A623; }

        .fin-summary-text { position: relative; z-index: 1; }

        .fin-summary-value {
            font-size: 1.3rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 4px;
            white-space: nowrap;
        }
        .fin-value-green  { color: #1A9453; }
        .fin-value-pink   { color: var(--pink); }
        .fin-value-blue   { color: #2F6FED; }
        .fin-value-orange { color: #F5A623; }

        .fin-summary-label {
            font-size: .78rem;
            color: var(--muted);
            font-weight: 500;
        }

        /* ── History Card ── */
        .fin-history-card {
            background: var(--card-bg);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            padding: 22px;
        }

        .fin-history-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 18px;
        }

        .fin-table-wrap {
            overflow-x: auto;
        }

        .fin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .fin-table thead th {
            text-align: left;
            font-size: .76rem;
            font-weight: 600;
            color: var(--muted);
            background: var(--bg);
            padding: 12px 14px;
            white-space: nowrap;
        }
        .fin-table thead th:first-child { border-radius: var(--radius-sm) 0 0 var(--radius-sm); }
        .fin-table thead th:last-child  { border-radius: 0 var(--radius-sm) var(--radius-sm) 0; }

        .fin-table tbody td {
            padding: 16px 14px;
            font-size: .86rem;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        .fin-table tbody tr:last-child td { border-bottom: none; }

        .fin-cell-date       { color: var(--dark); font-weight: 500; }
        .fin-cell-sales      { color: var(--dark); }
        .fin-cell-commission { color: #E53935; font-weight: 600; }
        .fin-cell-net        { color: #1A9453; font-weight: 700; }

        .fin-status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 50px;
            font-size: .74rem;
            font-weight: 600;
        }
        .fin-status-paid       { background: #E8FAF0; color: #1A9453; }
        .fin-status-processing { background: #E9F1FF; color: #2F6FED; }
        .fin-status-pending    { background: #FFF4E0; color: #F5A623; }
        .fin-status-failed     { background: #FFF0F0; color: #E53935; }

        /* ── Empty State ── */
        .fin-empty {
            text-align: center;
            padding: 50px 20px;
        }
        .fin-empty-icon {
            font-size: 2.6rem;
            opacity: .25;
            display: block;
            margin-bottom: 10px;
        }
        .fin-empty p { color: var(--muted); font-size: .86rem; margin: 0; }

        /* ── Responsive ── */
        @media (max-width: 1024px) {
            .fin-summary-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) {
            .fin-summary-grid { grid-template-columns: 1fr; gap: 12px; }
            .fin-history-card { padding: 16px; }
            .fin-summary-card { padding: 14px; }
        }

    </style>
@endpush