<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        $this->increment('balance', $amount);

        return $this->transactions()->create([
            'type'          => 'credit',
            'amount'        => $amount,
            'balance_after' => $this->balance,
            'source'        => $source,
            'description'   => $description,
            'reference_type'=> $reference ? get_class($reference) : null,
            'reference_id'  => $reference?->id,
            'created_by'    => $createdBy,
        ]);
    }

    /**
     * Wallet theke taka katar aage balance check kore, na hole exception.
     */
    public function debit(float $amount, string $source, ?string $description = null, $reference = null, ?int $createdBy = null): WalletTransaction
    {
        if ((float) $this->balance < $amount) {
            throw new \RuntimeException('Insufficient wallet balance.');
        }

        $this->decrement('balance', $amount);

        return $this->transactions()->create([
            'type'          => 'debit',
            'amount'        => $amount,
            'balance_after' => $this->balance,
            'source'        => $source,
            'description'   => $description,
            'reference_type'=> $reference ? get_class($reference) : null,
            'reference_id'  => $reference?->id,
            'created_by'    => $createdBy,
        ]);
    }

    public function getBalanceInTakaAttribute(): string
    {
        return '৳' . number_format((float) $this->balance);
    }
}
