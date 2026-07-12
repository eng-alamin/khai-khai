<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
     protected $guarded = [];

     public function defaultAddress()
     {
          return $this->belongsTo(\App\Models\CustomerAddress::class, 'default_address_id');
     }
}
