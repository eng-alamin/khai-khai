<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Field names match lib/models/support_models.dart (SupportTicket.fromJson).
 * `messages` is the conversation AFTER the customer's first message
 * (`message`), oldest first; the controller always loads it.
 *
 * @mixin \App\Models\SupportTicket
 */
class SupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $t = $this->resource;

        return [
            'id'            => $t->id,
            'ticket_number' => $t->ticket_number,
            'category'      => $t->category,
            'subject'       => $t->subject,
            'message'       => $t->message,
            'status'        => $t->status,
            // Latest admin answer (mirror of the last admin message).
            'reply'         => $t->reply,
            // A closed ticket is final: nobody can write to it.
            'can_reply'     => $t->status !== 'closed',
            'order'         => $t->relationLoaded('order') && $t->order
                ? ['order_number' => $t->order->order_number]
                : null,
            'messages'      => $t->relationLoaded('messages')
                ? $t->messages->map(fn ($m) => [
                    'role'       => $m->sender_role,
                    'body'       => $m->body,
                    'created_at' => $m->created_at?->toIso8601String(),
                ])->values()
                : [],
            'created_at'    => $t->created_at?->toIso8601String(),
            'updated_at'    => $t->updated_at?->toIso8601String(),
        ];
    }
}
