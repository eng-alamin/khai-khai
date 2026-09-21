<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'otp_code',
        'purpose',
        'expires_at',
        'used_at',
    ];
}
