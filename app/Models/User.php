<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

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
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    // ];

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
            'password' => 'hashed',
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
}
