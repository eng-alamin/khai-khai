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

    // Allow-list of mass-assignable columns. Every registration flow sets
    // uuid, role, is_verified, is_active and points explicitly, so they must
    // be listed here. Columns that are never set from a form (remember_token,
    // last_seen_at, last_login_at, last_login_ip) are written with forceFill()
    // and are intentionally NOT listed. When you add a new users column,
    // add it here only if a form or flow needs to mass-assign it.
    protected $fillable = [
        'uuid',
        'name',
        'phone',
        'email',
        'email_verified_at',
        'password',
        'role',
        'avatar',
        'is_verified',
        'is_active',
        'points',
    ];

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

    public function isRider(): bool
    {
        return $this->role === 'rider';
    }

    /**
     * A rider may see orders and take deliveries only while the account is
     * active AND the admin has approved the rider profile. Single source of
     * truth: the route middleware and the rider actions both call this.
     */
    public function canDeliver(): bool
    {
        return $this->isRider()
            && (bool) $this->is_active
            && (bool) $this->riderProfile?->is_approved;
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

    public function wallet()
    {
        return $this->hasOne(\App\Models\Wallet::class);
    }

    public function deviceTokens()
    {
        return $this->hasMany(\App\Models\DeviceToken::class);
    }

    public function favorites()
    {
        return $this->hasMany(\App\Models\Favorite::class);
    }

    /**
     * Wallet na thakle create kore return kore — jekhanei wallet lagbe
     * shekhaneo $user->getOrCreateWallet() call korle chalbe, "user er
     * wallet nai" error handle korte hobe na.
     */
    public function getOrCreateWallet(): \App\Models\Wallet
    {
        return $this->wallet ?? $this->wallet()->create(['balance' => 0]);
    }

    protected function isOnline(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->last_seen_at?->gt(now()->subMinutes(5)) ?? false,
        );
    }
}