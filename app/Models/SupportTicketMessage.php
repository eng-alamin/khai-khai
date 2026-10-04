<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One message in a support ticket conversation (after the first one). */
class SupportTicketMessage extends Model
{
    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_ADMIN    = 'admin';

    // Written only by SupportTicketService, never from request input.
    protected $fillable = [];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
