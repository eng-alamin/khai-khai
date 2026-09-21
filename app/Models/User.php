<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // FIX (Critical Bug #1): $fillable previously only allowed
    // name/phone/email/password/avatar. Registration flows (Customer,
    // Vendor, Rider) all pass 'uuid', 'role', 'is_verified', 'is_active',
    // and 'points' to User::create(). Those fields were silently stripped
    // by mass-assignment protection, and since `uuid` and `role` are
    // NOT NULL columns with no DB default, every registration failed at
    // the database level. Per project standard, $guarded = [] is used
    // instead of an allow-list so this class of bug cannot recur when a
    // new column is added later.
    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at'      => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /**
     * Safety net: `uuid` is a NOT NULL, unique column with no database
     * default. Registration flows already set it explicitly, but if any
     * future code path (a seeder, a console command, tinker, etc.) creates
     * a User without passing `uuid`, this guarantees one is always
     * generated instead of the insert crashing.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    public function restaurant()
    {
        return $this->hasOne(Restaurant::class, 'owner_id');
    }

    public function isVendor(): bool
    {
        return $this->role === 'vendor';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasRestaurant(): bool
    {
        return $this->restaurant !== null;
    }

    public function isRestaurantApproved(): bool
    {
        return $this->hasRestaurant() && $this->restaurant->is_approved;
    }

    public function customerProfile()
    {
        return $this->hasOne(\App\Models\CustomerProfile::class, 'customer_id');
    }

    public function addresses()
    {
        return $this->hasMany(\App\Models\CustomerAddress::class, 'customer_id');
    }

    public function defaultAddress()
    {
        return $this->hasOne(\App\Models\CustomerAddress::class, 'customer_id')
                    ->where('is_default', true);
    }

    public function riderProfile()
    {
        return $this->hasOne(\App\Models\RiderProfile::class, 'user_id');
    }

    public function riderEarnings()
    {
        return $this->hasMany(\App\Models\RiderEarning::class, 'rider_id');
    }

    public function orders()
    {
        return $this->hasMany(\App\Models\Order::class, 'customer_id');
    }

    protected function isOnline(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->last_seen_at?->gt(now()->subMinutes(5)) ?? false,
        );
    }
}