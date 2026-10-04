<?php

namespace App\Livewire\Rider;

use App\Models\Order;
use App\Models\RiderEarning;
use App\Models\RiderPayout;
use App\Models\User;
use App\Services\PaymentAccountService;
use App\Services\RiderBalanceService;
use App\Services\RiderCashService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class FinanceComponent extends Component
{
    use WithPagination;

    public int $perPage = 10;

    // ── Payout request form ───────────────────────────────
    public $payoutAmount  = '';
    public string $payoutMethod  = 'bkash';
    public string $payoutAccount = '';

    /** Admin setting "rider_min_payout" (Taka). Used when the setting is missing. */
    private const DEFAULT_MIN_PAYOUT = 500;

    /** Pre-fill the form with the account the rider saved earlier. */
    public function mount(PaymentAccountService $accounts): void
    {
        $this->fillSavedAccount($accounts);
    }

    public function updatedPayoutMethod(): void
    {
        $this->resetErrorBag('payoutAccount');
        $this->fillSavedAccount(app(PaymentAccountService::class));
    }

    private function fillSavedAccount(PaymentAccountService $accounts): void
    {
        $this->payoutAccount = $accounts->find((int) Auth::id(), $this->payoutMethod)?->account_number ?? '';
    }

    private const MONTH_LABELS = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar',  4 => 'Apr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul',  8 => 'Aug',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    /* ── This month's total earnings (in Taka) ── */
    public function getMonthlyEarningsProperty(): float
    {
        $total = RiderEarning::where('rider_id', Auth::id())
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return (float) $total;
    }

    /* ── This month's total delivery count ── */
    public function getMonthlyDeliveriesProperty(): int
    {
        return Order::where('rider_id', Auth::id())
            ->where('status', 'delivered')
            ->whereMonth('delivered_at', now()->month)
            ->whereYear('delivered_at', now()->year)
            ->count();
    }

    /* ── Total sent to bank (all-time sum of paid payouts) ── */
    public function getTotalPayoutProperty(): float
    {
        $total = RiderPayout::where('rider_id', Auth::id())
            ->where('status', 'paid')
            ->sum('amount');

        return (float) $total;
    }

    /* ── Money the rider can still withdraw ── */
    public function getAvailableBalanceProperty(): float
    {
        return app(RiderBalanceService::class)->availableFor((int) Auth::id());
    }

    /** Cash from COD orders the rider still has to hand over (never below 0 here). */
    public function getCashInHandProperty(): float
    {
        return max(0.0, app(RiderCashService::class)->cashInHand((int) Auth::id()));
    }

    public function getMinPayoutProperty(): float
    {
        return (float) \App\Models\AdminSetting::get('rider_min_payout', self::DEFAULT_MIN_PAYOUT);
    }

    public function getHasOpenRequestProperty(): bool
    {
        return app(RiderBalanceService::class)->hasOpenRequest((int) Auth::id());
    }

    /* ── Rider asks the admin to pay out part of his balance ── */
    public function requestPayout(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->canDeliver()) {
            $this->dispatch('show-toast', message: 'আপনার অ্যাকাউন্ট এখনো অনুমোদিত হয়নি।', type: 'error');
            return;
        }

        $accounts = app(PaymentAccountService::class);

        $this->validate([
            'payoutAmount'  => ['required', 'numeric', 'min:' . $this->minPayout],
            'payoutMethod'  => ['required', Rule::in(RiderPayout::METHODS)],
            'payoutAccount' => $accounts->numberRules($this->payoutMethod),
        ], [
            'payoutAmount.required'  => 'Please enter the amount.',
            'payoutAmount.numeric'   => 'Amount must be a number.',
            'payoutAmount.min'       => 'Minimum payout is Tk ' . number_format($this->minPayout) . '.',
            'payoutAccount.required' => 'Please enter your account number.',
            'payoutAccount.regex'    => $accounts->numberMessage($this->payoutMethod),
        ]);

        $amount = round((float) $this->payoutAmount, 2);

        $result = DB::transaction(function () use ($user, $amount) {
            // Lock this rider's row so two requests sent at the same moment
            // are handled one after the other, never both against the same balance.
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $balance = app(RiderBalanceService::class);

            if ($balance->hasOpenRequest($user->id)) {
                return 'open';
            }

            if ($amount > $balance->availableFor($user->id)) {
                return 'balance';
            }

            $payout = RiderPayout::create([
                'rider_id'                 => $user->id,
                'amount'                   => $amount,
                'method'                   => $this->payoutMethod,
                'account_number'           => $this->payoutAccount,
                // Frozen copy: the rider may change his number later, the payout must not.
                'payment_account_snapshot' => [
                    'method'         => $this->payoutMethod,
                    'account_number' => $this->payoutAccount,
                    'rider_name'     => $user->name,
                    'rider_phone'    => $user->phone,
                ],
            ]);

            activity()
                ->causedBy($user)
                ->performedOn($payout)
                ->withProperties(['amount' => $amount, 'method' => $this->payoutMethod])
                ->log('Rider requested payout');

            return 'ok';
        });

        if ($result === 'open') {
            $this->dispatch('show-toast', message: 'আপনার একটি পেআউট রিকুয়েস্ট ইতিমধ্যে চলছে। সেটি শেষ হলে আবার চেষ্টা করুন।', type: 'warning');
            return;
        }

        if ($result === 'balance') {
            $this->dispatch('show-toast', message: 'আপনার ব্যালেন্সের চেয়ে বেশি টাকা চাওয়া যাবে না।', type: 'error');
            return;
        }

        // Remember the account, so the next request is pre-filled.
        $accounts->save($user, $this->payoutMethod, $this->payoutAccount);

        $this->reset('payoutAmount');
        $this->resetPage();
        $this->dispatch('show-toast', message: '✅ পেআউট রিকুয়েস্ট পাঠানো হয়েছে। অ্যাডমিন যাচাই করে টাকা পাঠাবেন।', type: 'success');
    }

    /* ── Paginated payment history ── */
    public function getPayoutHistoryProperty()
    {
        return RiderPayout::where('rider_id', Auth::id())
            ->orderByRaw('COALESCE(resolved_at, created_at) DESC')
            ->paginate($this->perPage);
    }

    private function formatDate(Carbon $date): string
    {
        return $date->day . ' ' .
               self::MONTH_LABELS[$date->month] . ' ' .
               $date->year;
    }

    public function render()
    {
        $rows = $this->payoutHistory->through(function (RiderPayout $payout) {
            return [
                'id'         => $payout->id,
                'date_label' => $payout->resolved_at
                    ? $this->formatDate($payout->resolved_at)
                    : $this->formatDate($payout->created_at),
                'method'     => $payout->method ?? 'bKash',   // e.g. bKash, Nagad, Bank
                'amount'     => 'Tk ' . $payout->amount,
                'status'     => $payout->status,              // pending|approved|processing|paid|rejected|failed
            ];
        });

        return view('livewire.rider.finance-component', [
            'rows'              => $rows,
            'monthlyEarnings'   => 'Tk ' . number_format($this->monthlyEarnings),
            'monthlyDeliveries' => $this->monthlyDeliveries,
            'totalPayout'       => 'Tk ' . number_format($this->totalPayout),
            'availableBalance'  => 'Tk ' . number_format($this->availableBalance, 2),
            'minPayout'         => 'Tk ' . number_format($this->minPayout),
            'hasOpenRequest'    => $this->hasOpenRequest,
            'cashInHand'        => 'Tk ' . number_format($this->cashInHand, 2),
            'hasCashInHand'     => $this->cashInHand > 0,
            'cashNetting'       => app(RiderBalanceService::class)->nettingEnabled(),
        ])->layout('layouts.rider', [
            'title'           => 'Finance | KhaiKhai',
            'breadcrumbTitle' => 'Finance',
        ]);
    }
}