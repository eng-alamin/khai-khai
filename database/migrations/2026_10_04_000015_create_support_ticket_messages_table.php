<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Support ticket conversation.
 *
 * support_tickets.message stays the customer's first message. Every later
 * message (admin answers and customer follow-ups) is a row here.
 *
 * The old single support_tickets.reply column is kept: it always holds the
 * latest admin answer, so anything still reading it keeps working. Existing
 * answers are copied into the new table below.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('sender_role', ['customer', 'admin']);
            $table->text('body');
            $table->timestamps();

            $table->index(['support_ticket_id', 'id']);
        });

        // Copy answers that were already given into the thread.
        $now = now();

        DB::table('support_tickets')
            ->whereNotNull('reply')
            ->where('reply', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($tickets) use ($now) {
                $rows = [];

                foreach ($tickets as $ticket) {
                    $at = $ticket->replied_at ?? $now;

                    $rows[] = [
                        'support_ticket_id' => $ticket->id,
                        'sender_id'         => $ticket->replied_by,
                        'sender_role'       => 'admin',
                        'body'              => $ticket->reply,
                        'created_at'        => $at,
                        'updated_at'        => $at,
                    ];
                }

                DB::table('support_ticket_messages')->insert($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
    }
};
