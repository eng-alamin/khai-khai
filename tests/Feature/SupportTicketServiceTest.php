<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\SupportTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMarketplaceData;
use Tests\TestCase;

/**
 * Admin answers to customer support tickets.
 */
class SupportTicketServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMarketplaceData;

    private function makeTicket(User $customer, string $status = 'open'): SupportTicket
    {
        $n = $this->next();

        return SupportTicket::forceCreate([
            'ticket_number' => 'TKTEST' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'customer_id'   => $customer->id,
            'category'      => 'order',
            'subject'       => 'Order is late',
            'message'       => 'My order has not arrived for an hour.',
            'status'        => $status,
        ]);
    }

    public function test_a_reply_is_saved_with_the_admin_and_the_new_status(): void
    {
        $admin    = $this->makeUser('admin');
        $customer = $this->makeUser('customer');
        $ticket   = $this->makeTicket($customer);

        $this->assertTrue(app(SupportTicketService::class)->reply($ticket->id, $admin, 'We are checking with the rider.', 'in_progress'));

        $fresh = $ticket->fresh();
        $this->assertSame('We are checking with the rider.', $fresh->reply);
        $this->assertSame('in_progress', $fresh->status);
        $this->assertSame($admin->id, (int) $fresh->replied_by);
        $this->assertNotNull($fresh->replied_at);
    }

    public function test_the_customer_is_notified_of_a_reply(): void
    {
        $customer = $this->makeUser('customer');
        $ticket   = $this->makeTicket($customer);

        app(SupportTicketService::class)->reply($ticket->id, $this->makeUser('admin'), 'Sorted, sorry for the delay.', 'resolved');

        $this->assertSame(1, Notification::where('user_id', $customer->id)->where('type', 'support_ticket_reply')->count());
    }

    public function test_every_admin_reply_is_added_to_the_thread_until_the_ticket_is_closed(): void
    {
        $admin   = $this->makeUser('admin');
        $service = app(SupportTicketService::class);
        $ticket  = $this->makeTicket($this->makeUser('customer'));

        $this->assertTrue($service->reply($ticket->id, $admin, 'First answer.', 'in_progress'));
        $this->assertTrue($service->reply($ticket->id, $admin, 'Second answer.', 'resolved'));

        // Nothing is overwritten any more: both answers stay, oldest first.
        $this->assertSame(['First answer.', 'Second answer.'], $ticket->fresh()->messages->pluck('body')->all());
        // The old column mirrors the latest answer.
        $this->assertSame('Second answer.', $ticket->fresh()->reply);

        $this->assertTrue($service->reply($ticket->id, $admin, 'Final answer.', 'closed'));
        $this->assertFalse($service->reply($ticket->id, $admin, 'Too late.', 'resolved'));
        $this->assertCount(3, $ticket->fresh()->messages);
        $this->assertSame('closed', $ticket->fresh()->status);
    }

    public function test_a_closed_ticket_cannot_change_status_or_be_reopened(): void
    {
        $admin  = $this->makeUser('admin');
        $ticket = $this->makeTicket($this->makeUser('customer'), 'closed');

        $this->assertFalse(app(SupportTicketService::class)->setStatus($ticket->id, $admin, 'in_progress'));
        $this->assertSame('closed', $ticket->fresh()->status);
    }

    public function test_only_the_allowed_statuses_can_be_set(): void
    {
        $admin   = $this->makeUser('admin');
        $service = app(SupportTicketService::class);
        $ticket  = $this->makeTicket($this->makeUser('customer'));

        $this->assertFalse($service->setStatus($ticket->id, $admin, 'open'));
        $this->assertFalse($service->setStatus($ticket->id, $admin, 'banana'));
        $this->assertFalse($service->reply($ticket->id, $admin, 'Hello there.', 'banana'));
        $this->assertSame('open', $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->reply);
    }

    public function test_setting_the_same_status_again_does_nothing(): void
    {
        $admin   = $this->makeUser('admin');
        $service = app(SupportTicketService::class);
        $ticket  = $this->makeTicket($this->makeUser('customer'));

        $this->assertTrue($service->setStatus($ticket->id, $admin, 'in_progress'));
        $this->assertFalse($service->setStatus($ticket->id, $admin, 'in_progress'));
    }

    public function test_a_missing_ticket_returns_false(): void
    {
        $admin = $this->makeUser('admin');

        $this->assertFalse(app(SupportTicketService::class)->reply(99999, $admin, 'Hello there.', 'resolved'));
        $this->assertFalse(app(SupportTicketService::class)->setStatus(99999, $admin, 'resolved'));
    }
}
