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

        {{-- ── Payout Account ── --}}
        <div class="history-card" style="margin-bottom:16px;">
            <div class="history-title">Payout Account</div>
            <p style="margin:0 0 12px;font-size:13px;opacity:.75;">
                Tell us where to send your money. We use this when your payout is paid.
            </p>

            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label class="form-label">Method</label>
                        <select class="form-control" wire:model.live="accMethod">
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="bank">Bank</option>
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label class="form-label">{{ $accMethod === 'bank' ? 'Account number' : 'Mobile number' }}</label>
                        <input type="text" class="form-control @error('accNumber') is-invalid @enderror"
                            wire:model="accNumber" placeholder="{{ $accMethod === 'bank' ? 'Account number' : '01XXXXXXXXX' }}">
                        @error('accNumber') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            @if($accMethod === 'bank')
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label class="form-label">Bank name</label>
                            <input type="text" class="form-control @error('accBank') is-invalid @enderror" wire:model="accBank">
                            @error('accBank') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label class="form-label">Account holder name <small>(optional)</small></label>
                            <input type="text" class="form-control @error('accName') is-invalid @enderror" wire:model="accName">
                            @error('accName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            @endif

            <button class="btn-primary" type="button" wire:click="saveAccount" wire:loading.attr="disabled">
                <span wire:loading wire:target="saveAccount" class="spinner-sm"></span>
                Save Account
            </button>

            @if($accountSaved)
                <span style="margin-left:10px;color:#16a34a;font-size:13px;">✔ Saved</span>
            @endif
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
