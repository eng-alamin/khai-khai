<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The conversation on a customer's support ticket.
 *
 * Admin:
 *   open / in_progress / resolved -> reply (added to the thread, new status)
 *   open / in_progress / resolved -> status change only
 * Customer:
 *   open / in_progress / resolved -> write a follow-up message; the ticket
 *                                    goes back to "open" so admins see it
 *   closed                        -> final, nobody can write or change it
 *
 * Every method returns false when the ticket is closed or gone, so a double
 * click or a second admin can never change a closed ticket. The ticket row is
 * locked, so two admins answering at the same moment are handled one by one.
 */
class SupportTicketService
{
    /** Statuses an admin may set. "open" is only the starting state. */
    public const SETTABLE = ['in_progress', 'resolved', 'closed'];

    public function reply(int $ticketId, User $admin, string $reply, string $status): bool
    {
        if (! in_array($status, self::SETTABLE, true)) {
            return false;
        }

        $done = null;

        $ok = DB::transaction(function () use ($ticketId, $admin, $reply, $status, &$done) {
            $ticket = SupportTicket::query()->whereKey($ticketId)->lockForUpdate()->first();

            if ($ticket === null || $ticket->status === 'closed') {
                return false;
            }

            $from = $ticket->status;

            // The thread keeps every answer. The old single `reply` column
            // always mirrors the latest admin answer.
            $this->addMessage($ticket, SupportTicketMessage::ROLE_ADMIN, $admin->id, $reply);

            // status / reply / replied_by / replied_at are not mass-assignable.
            $ticket->forceFill([
                'reply'      => $reply,
                'status'     => $status,
                'replied_by' => $admin->id,
                'replied_at' => now(),
            ])->save();

            activity()
                ->causedBy($admin)
                ->performedOn($ticket)
                ->withProperties(['from' => $from, 'to' => $status])
                ->log('Admin replied to support ticket');

            $done = $ticket;

            return true;
        });

        // After the commit, so the customer is never told about a rolled-back reply.
        if ($ok && $done !== null) {
            $this->notifyCustomer($done);
        }

        return $ok;
    }

    public function setStatus(int $ticketId, User $admin, string $status): bool
    {
        if (! in_array($status, self::SETTABLE, true)) {
            return false;
        }

        return DB::transaction(function () use ($ticketId, $admin, $status) {
            $ticket = SupportTicket::query()->whereKey($ticketId)->lockForUpdate()->first();

            if ($ticket === null || $ticket->status === 'closed' || $ticket->status === $status) {
                return false;
            }

            $from = $ticket->status;

            $ticket->forceFill(['status' => $status])->save();

            activity()
                ->causedBy($admin)
                ->performedOn($ticket)
                ->withProperties(['from' => $from, 'to' => $status])
                ->log('Admin changed support ticket status');

            return true;
        });
    }

    /**
     * The customer writes a follow-up on THEIR OWN ticket.
     *
     * @return bool false = not this customer's ticket, or it is closed.
     */
    public function customerReply(int $ticketId, User $customer, string $body): bool
    {
        $done = null;

        $ok = DB::transaction(function () use ($ticketId, $customer, $body, &$done) {
            $ticket = SupportTicket::query()
                ->whereKey($ticketId)
                ->where('customer_id', $customer->id)
                ->lockForUpdate()
                ->first();

            if ($ticket === null || $ticket->status === 'closed') {
                return false;
            }

            $from = $ticket->status;

            $this->addMessage($ticket, SupportTicketMessage::ROLE_CUSTOMER, $customer->id, $body);

            // Back to "open": the admin inbox shows it again.
            $ticket->forceFill(['status' => 'open'])->save();

            activity()
                ->causedBy($customer)
                ->performedOn($ticket)
                ->withProperties(['from' => $from, 'to' => 'open'])
                ->log('Customer replied to support ticket');

            $done = $ticket;

            return true;
        });

        if ($ok && $done !== null) {
            $this->notifyAdmins($done);
        }

        return $ok;
    }

    private function addMessage(SupportTicket $ticket, string $role, int $senderId, string $body): void
    {
        $message = new SupportTicketMessage();
        $message->forceFill([
            'support_ticket_id' => $ticket->id,
            'sender_id'         => $senderId,
            'sender_role'       => $role,
            'body'              => $body,
        ])->save();
    }

    private function notifyAdmins(SupportTicket $ticket): void
    {
        try {
            $adminIds = User::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->limit(20)
                ->pluck('id');

            foreach ($adminIds as $adminId) {
                Notification::create([
                    'user_id'    => $adminId,
                    'type'       => 'support_ticket_customer_reply',
                    'title'      => 'টিকেটে কাস্টমারের নতুন বার্তা',
                    'body'       => "#{$ticket->ticket_number}: {$ticket->subject}",
                    'data'       => ['ticket_id' => $ticket->id],
                    'action_url' => route('admin.support', [], false),
                    'priority'   => 'normal',
                ]);
            }
        } catch (Throwable $e) {
            // A notification problem must never undo or break the message.
            Log::warning('Support customer-reply notification failed', [
                'ticket_id' => $ticket->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    private function notifyCustomer(SupportTicket $ticket): void
    {
        try {
            Notification::create([
                'user_id'    => $ticket->customer_id,
                'type'       => 'support_ticket_reply',
                'title'      => 'আপনার টিকেটের উত্তর এসেছে',
                'body'       => "#{$ticket->ticket_number}: {$ticket->subject}",
                'data'       => ['ticket_id' => $ticket->id],
                'action_url' => route('customer.support', [], false),
                'priority'   => 'normal',
            ]);
        } catch (Throwable $e) {
            // A notification problem must never undo or break the reply.
            Log::warning('Support reply notification failed', [
                'ticket_id' => $ticket->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
