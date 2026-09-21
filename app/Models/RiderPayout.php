<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderPayout extends Model
{
    // status/processed_by/admin_note are set only by the admin approval flow.
    protected $fillable = [
        'rider_id',
        'amount',
        'method',
        'account_number',
        'payment_account_snapshot',
    ];
}
