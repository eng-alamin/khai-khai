<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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
     * User → Restaurant (1 to 1)
     * একজন vendor-এর একটাই restaurant থাকে।
     * restaurants.owner_id = users.id
     */
    public function restaurant()
    {
        return $this->hasOne(Restaurant::class, 'owner_id');
    }

    public function isVendor(): bool
    {
        return $this->role === 'vendor'; // Adjust based on your role field
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

    // User → CustomerProfile (1 to 1)
    public function customerProfile()
    {
        return $this->hasOne(\App\Models\CustomerProfile::class, 'customer_id');
    }
    
    // User → CustomerAddresses (1 to many)  [already exists, keeping for reference]
    public function addresses()
    {
        return $this->hasMany(\App\Models\CustomerAddress::class, 'customer_id');
    }
    
    // Default address shortcut
    public function defaultAddress()
    {
        return $this->hasOne(\App\Models\CustomerAddress::class, 'customer_id')
                    ->where('is_default', true);
    }

    // Rider 
    public function riderProfile()
    {
        return $this->hasOne(\App\Models\RiderProfile::class, 'user_id');
    }
    public function riderEarnings()
    {
        return $this->hasOne(\App\Models\RiderEarning::class, 'rider_id');
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
