<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude'   => 'float',
        'longitude'  => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}