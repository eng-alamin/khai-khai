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
 * The customer's side of the support conversation.
 */
class SupportThreadTest extends TestCase
{
    use RefreshDatabase;
    use BuildsMarketplaceData;

    private function makeTicket(User $customer, string $status = 'open'): SupportTicket
    {
        $n = $this->next();

        return SupportTicket::forceCreate([
            'ticket_number' => 'TKTHREAD' . str_pad((string) $n, 3, '0', STR_PAD_LEFT),
            'customer_id'   => $customer->id,
            'category'      => 'order',
            'subject'       => 'Order is late',
            'message'       => 'My order has not arrived for an hour.',
            'status'        => $status,
        ]);
    }

    private function service(): SupportTicketService
    {
        return app(SupportTicketService::class);
    }

    public function test_a_customer_can_write_a_follow_up_on_their_own_ticket(): void
    {
        $customer = $this->makeUser('customer');
        $ticket   = $this->makeTicket($customer);

        $this->assertTrue($this->service()->customerReply($ticket->id, $customer, 'Still nothing, please help.'));

        $message = $ticket->fresh()->messages->sole();
        $this->assertSame('customer', $message->sender_role);
        $this->assertSame($customer->id, (int) $message->sender_id);
        $this->assertSame('Still nothing, please help.', $message->body);
    }

    public function test_a_customer_cannot_write_on_someone_elses_ticket(): void
    {
        $owner    = $this->makeUser('customer');
        $stranger = $this->makeUser('customer');
        $ticket   = $this->makeTicket($owner);

        $this->assertFalse($this->service()->customerReply($ticket->id, $stranger, 'Hello there.'));
        $this->assertCount(0, $ticket->fresh()->messages);
    }

    public function test_a_customer_cannot_write_on_a_closed_ticket(): void
    {
        $customer = $this->makeUser('customer');
        $ticket   = $this->makeTicket($customer, 'closed');

        $this->assertFalse($this->service()->customerReply($ticket->id, $customer, 'Please reopen this.'));
        $this->assertCount(0, $ticket->fresh()->messages);
        $this->assertSame('closed', $ticket->fresh()->status);
    }

    public function test_a_customer_message_reopens_a_resolved_or_in_progress_ticket(): void
    {
        $customer = $this->makeUser('customer');

        foreach (['resolved', 'in_progress'] as $status) {
            $ticket = $this->makeTicket($customer, $status);

            $this->assertTrue($this->service()->customerReply($ticket->id, $customer, 'It is not solved yet.'));
            $this->assertSame('open', $ticket->fresh()->status, $status);
        }
    }

    public function test_active_admins_are_notified_of_a_customer_message(): void
    {
        $customer = $this->makeUser('customer');
        $admin    = $this->makeUser('admin');
        $inactive = $this->makeUser('admin', ['is_active' => false]);
        $ticket   = $this->makeTicket($customer);

        $this->service()->customerReply($ticket->id, $customer, 'Any news about my order?');

        $this->assertSame(1, Notification::where('user_id', $admin->id)->where('type', 'support_ticket_customer_reply')->count());
        $this->assertSame(0, Notification::where('user_id', $inactive->id)->where('type', 'support_ticket_customer_reply')->count());
    }

    public function test_the_thread_keeps_both_sides_in_order(): void
    {
        $customer = $this->makeUser('customer');
        $admin    = $this->makeUser('admin');
        $ticket   = $this->makeTicket($customer);

        $this->service()->reply($ticket->id, $admin, 'We are checking.', 'in_progress');
        $this->service()->customerReply($ticket->id, $customer, 'Thanks, waiting.');
        $this->service()->reply($ticket->id, $admin, 'Sorted now.', 'resolved');

        $thread = $ticket->fresh()->messages;

        $this->assertSame(['admin', 'customer', 'admin'], $thread->pluck('sender_role')->all());
        $this->assertSame(['We are checking.', 'Thanks, waiting.', 'Sorted now.'], $thread->pluck('body')->all());
        $this->assertSame('resolved', $ticket->fresh()->status);
    }

    public function test_the_customer_follow_up_does_not_change_the_latest_admin_reply_column(): void
    {
        $customer = $this->makeUser('customer');
        $ticket   = $this->makeTicket($customer);

        $this->service()->reply($ticket->id, $this->makeUser('admin'), 'Our answer.', 'resolved');
        $this->service()->customerReply($ticket->id, $customer, 'One more question.');

        $this->assertSame('Our answer.', $ticket->fresh()->reply);
    }
}
