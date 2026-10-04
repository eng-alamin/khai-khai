<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAccount extends Model
{
    public const METHODS = ['bkash', 'nagad', 'bank'];

    // user_id is always set by the server (the logged-in user), never by a form.
    protected $fillable = [
        'user_id',
        'method',
        'account_number',
        'account_name',
        'bank_name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** "bKash 01XXXXXXXXX" / "Bank: City Bank, A/C 1234 (Name)" */
    public function getLabelAttribute(): string
    {
        return match ($this->method) {
            'bkash' => 'bKash ' . $this->account_number,
            'nagad' => 'Nagad ' . $this->account_number,
            default => 'Bank' . ($this->bank_name ? ': ' . $this->bank_name : '')
                . ', A/C ' . $this->account_number
                . ($this->account_name ? ' (' . $this->account_name . ')' : ''),
        };
    }
}
