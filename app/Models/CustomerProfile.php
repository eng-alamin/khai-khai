<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
     // Order/spend stats are maintained by the system, not user input.
     protected $fillable = [
         'customer_id',
         'default_address_id',
     ];

     public function defaultAddress()
     {
          return $this->belongsTo(\App\Models\CustomerAddress::class, 'default_address_id');
     }
}
