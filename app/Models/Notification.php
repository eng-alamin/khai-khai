<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'data',
        'action_url',
        'priority',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data'    => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, $userId)
    {
        // No user (guest) must match NOTHING. A plain where('user_id', null)
        // would become "user_id IS NULL" and expose the shared admin rows.
        if ($userId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('user_id', $userId);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /** Material icon name for the bell list, chosen by notification type. */
    public function iconName(): string
    {
        return match ($this->type) {
            'new_order', 'new_product_order'                   => 'shopping_bag',
            'order_available', 'rider_assigned', 'rider_unassigned', 'order_picked_up' => 'two_wheeler',
            'order_cancelled'                                  => 'cancel',
            'order_delivered'                                  => 'check_circle',
            'delivery_issue', 'delivery_issue_customer',
            'delivery_retry', 'delivery_failed'                => 'warning',
            default                                            => 'notifications',
        };
    }

    /** FontAwesome class for the customer layout (it does not load Material Icons). */
    public function faIconClass(): string
    {
        return match ($this->iconName()) {
            'shopping_bag' => 'fa-shopping-bag',
            'two_wheeler'  => 'fa-motorcycle',
            'cancel'       => 'fa-circle-xmark',
            'check_circle' => 'fa-circle-check',
            'warning'      => 'fa-triangle-exclamation',
            default        => 'fa-bell',
        };
    }

    /**
     * action_url only if it is a path on this site ("/..." but not "//host").
     * Anything else is ignored, so a notification can never send a user to
     * another website.
     */
    public function safeActionUrl(): ?string
    {
        $url = (string) $this->action_url;

        if ($url === '' || ! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return null;
        }

        return $url;
    }
}