<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
        'is_active',
    ];

    protected $casts = [
        'balance'   => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Wallet e taka jog kora. balance column update + audit row — duitai
     * ekhane hoy jate kono jaygay ekta bad diye onnota na kore.
     */
    public function credit(float $amount, string $source, ?string $description = null, $reference = null, ?int $createdBy = null): WalletTransaction
    {
        return $this->applyTransaction('credit', $amount, $source, $description, $reference, $createdBy);
    }

    /**
     * Wallet theke taka katar aage balance check kore, na hole exception.
     */
    public function debit(float $amount, string $source, ?string $description = null, $reference = null, ?int $createdBy = null): WalletTransaction
    {
        return $this->applyTransaction('debit', $amount, $source, $description, $reference, $createdBy);
    }

    /**
     * FIX: credit() ar debit() ager version e transaction chhilo na.
     * Duita request ek shathe ashle duitai purono balance dekhe pass hoye
     * jeto (double spend). Ekhon:
     *  1. DB transaction er moddhe kaj hoy — balance ar ledger row ek shathe
     *     save hoy, na hole duitai rollback.
     *  2. Wallet row lock kora hoy (lockForUpdate) — ekta request shesh na
     *     hoa porjonto onnota opekkha kore, tai balance check always fresh.
     */
    private function applyTransaction(
        string $type,
        float $amount,
        string $source,
        ?string $description,
        $reference,
        ?int $createdBy
    ): WalletTransaction {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Wallet amount must be greater than zero.');
        }

        return DB::transaction(function () use ($type, $amount, $source, $description, $reference, $createdBy) {
            $locked = static::query()->lockForUpdate()->findOrFail($this->getKey());

            $current = (float) $locked->balance;

            if ($type === 'debit' && $current < $amount) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }

            $newBalance = $type === 'credit'
                ? round($current + $amount, 2)
                : round($current - $amount, 2);

            $locked->balance = $newBalance;
            $locked->save();

            // Ei object er balance o update rakhi, jate caller $wallet->balance e notun maan pay.
            $this->setAttribute('balance', $newBalance);
            $this->syncOriginalAttribute('balance');

            return $this->transactions()->create([
                'type'           => $type,
                'amount'         => $amount,
                'balance_after'  => $newBalance,
                'source'         => $source,
                'description'    => $description,
                'reference_type' => $reference instanceof Model ? $reference->getMorphClass() : null,
                'reference_id'   => $reference instanceof Model ? $reference->getKey() : null,
                'created_by'     => $createdBy,
            ]);
        });
    }

    public function getBalanceInTakaAttribute(): string
    {
        return '৳' . number_format((float) $this->balance);
    }
}
