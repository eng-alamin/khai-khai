{{-- resources/views/livewire/vendor/finance-component.blade.php --}}
<div>

    <div class="main-content">

        {{-- ── Summary Cards ── --}}
        <div class="summary-grid">

            <div class="summary-card">
                <div class="summary-icon icon-green">
                    <span class="material-icons-round">payments</span>
                </div>
                <div class="summary-text">
                    <div class="summary-value value-green">৳{{ number_format($monthlyRevenue) }}</div>
                    <div class="summary-label">This Month's Revenue</div>
                </div>
                <div class="summary-blob"></div>
            </div>

            <div class="summary-card">
                <div class="summary-icon icon-pink">
                    <span class="material-icons-round">percent</span>
                </div>
                <div class="summary-text">
                    <div class="summary-value value-pink">৳{{ number_format($monthlyCommission) }}</div>
                    <div class="summary-label">KhaiKhai Commission ({{ rtrim(rtrim(number_format($commissionRate, 1), '0'), '.') }}%)</div>
                </div>
                <div class="summary-blob"></div>
            </div>

            <div class="summary-card">
                <div class="summary-icon icon-blue">
                    <span class="material-icons-round">account_balance_wallet</span>
                </div>
                <div class="summary-text">
                    <div class="summary-value value-blue">৳{{ number_format($monthlyNet) }}</div>
                    <div class="summary-label">Net Revenue</div>
                </div>
                <div class="summary-blob"></div>
            </div>

            <div class="summary-card">
                <div class="summary-icon icon-orange">
                    <span class="material-icons-round">event</span>
                </div>
                <div class="summary-text">
                    <div class="summary-value value-orange">{{ $nextPaymentDate }}</div>
                    <div class="summary-label">Next Payment</div>
                </div>
                <div class="summary-blob"></div>
            </div>

        </div>

        {{-- ── Payment History ── --}}
        <div class="history-card">
            <div class="history-title">Payment History</div>

            @if(count($paymentHistory))
                <div class="table-wrap">
                    <table class="table">
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
                                    <td class="cell-date">{{ $row['date'] }}</td>
                                    <td class="cell-sales">৳{{ number_format($row['sales']) }}</td>
                                    <td class="cell-commission">৳{{ number_format($row['commission']) }}</td>
                                    <td class="cell-net">৳{{ number_format($row['net']) }}</td>
                                    <td>
                                        @if($row['status'] === 'paid')
                                            <span class="status-badge paid">Paid</span>
                                        @elseif($row['status'] === 'processing')
                                            <span class="status-badge processing">Processing</span>
                                        @elseif($row['status'] === 'pending')
                                            <span class="status-badge pending">Pending</span>
                                        @else
                                            <span class="status-badge failed">Failed</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty">
                    <span class="material-icons-round empty-icon">receipt_long</span>
                    <p>No payment history found.</p>
                </div>
            @endif
        </div>

    </div>{{-- /main-content --}}

</div>
