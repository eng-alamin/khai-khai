<?php

namespace App\Services;

use App\Models\PaymentAccount;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Saved payout accounts (bKash / Nagad / bank) of vendors and riders.
 * The number-format rules live here only, so every form validates the same way.
 */
class PaymentAccountService
{
    public const METHODS = PaymentAccount::METHODS;

    /** Validation rules for the account number of the given method. */
    public function numberRules(string $method): array
    {
        return $method === 'bank'
            ? ['required', 'regex:/^[0-9\-]{6,30}$/']
            : ['required', 'regex:/^01[3-9]\d{8}$/'];
    }

    public function numberMessage(string $method): string
    {
        return match ($method) {
            'bank'  => 'Enter a valid bank account number (digits only).',
            'nagad' => 'Enter a valid Nagad number (e.g. 01XXXXXXXXX).',
            default => 'Enter a valid bKash number (e.g. 01XXXXXXXXX).',
        };
    }

    public function find(int $userId, string $method): ?PaymentAccount
    {
        return PaymentAccount::query()
            ->where('user_id', $userId)
            ->where('method', $method)
            ->first();
    }

    public function save(User $user, string $method, string $number, ?string $name = null, ?string $bank = null): PaymentAccount
    {
        return PaymentAccount::updateOrCreate(
            ['user_id' => $user->id, 'method' => $method],
            [
                'account_number' => $number,
                'account_name'   => $name !== null && trim($name) !== '' ? trim($name) : null,
                'bank_name'      => $method === 'bank' && $bank !== null && trim($bank) !== '' ? trim($bank) : null,
            ]
        );
    }

    /**
     * All saved accounts of many users at once, grouped by user_id.
     *
     * @param  array<int, int|null> $userIds
     * @return Collection<int, Collection<int, PaymentAccount>>
     */
    public function allFor(array $userIds): Collection
    {
        $ids = array_values(array_unique(array_filter($userIds)));

        if ($ids === []) {
            return collect();
        }

        return PaymentAccount::query()
            ->whereIn('user_id', $ids)
            ->orderBy('method')
            ->get()
            ->groupBy('user_id');
    }
}
