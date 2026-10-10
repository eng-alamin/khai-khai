<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SupportReplyRequest;
use App\Http\Requests\Api\SupportTicketRequest;
use App\Http\Resources\SupportTicketResource;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Customer support tickets for the mobile app (port of
 * Livewire\Customer\SupportComponent). Every query starts from
 * customer_id = the signed-in user, so a foreign ticket answers 404.
 */
class SupportController extends Controller
{
    /** GET /support/tickets — the customer's latest 20 tickets. */
    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::query()
            ->where('customer_id', $request->user()->id)
            ->with(['messages', 'order:id,order_number'])
            ->latest()
            ->latest('id')
            ->take(20)
            ->get();

        return response()->json(['data' => SupportTicketResource::collection($tickets)->resolve($request)]);
    }

    /** POST /support/tickets {category, order_number?, subject, message} */
    public function store(SupportTicketRequest $request): JsonResponse
    {
        $user = $request->user();

        // Stops ticket spam: 5 tickets per hour per customer.
        if ($wait = $this->tooMany('support-ticket:' . $user->id, 5, 3600)) {
            return $this->throttled($wait);
        }

        // Optional order reference: linked only if it genuinely belongs to this
        // customer. Exact, case-insensitive match with any leading '#' removed.
        $order = null;
        $clean = ltrim(trim((string) $request->validated('order_number')), '#');
        if ($clean !== '') {
            $order = Order::query()
                ->where('customer_id', $user->id)
                ->whereRaw('UPPER(order_number) = ?', [strtoupper($clean)])
                ->first();
        }

        do {
            $number = 'TK' . now()->format('ymd') . strtoupper(Str::random(4));
        } while (SupportTicket::where('ticket_number', $number)->exists());

        // ticket_number and status are not mass-assignable.
        $ticket = new SupportTicket([
            'customer_id' => $user->id,
            'order_id'    => $order?->id,
            'category'    => $request->validated('category'),
            'subject'     => trim($request->validated('subject')),
            'message'     => trim($request->validated('message')),
        ]);
        $ticket->ticket_number = $number;
        $ticket->save();

        activity()
            ->causedBy($user)
            ->performedOn($ticket)
            ->withProperties(['ticket_number' => $ticket->ticket_number, 'category' => $ticket->category])
            ->log('Support ticket created');

        return response()->json(['data' => $this->detail($request, $ticket->id)], 201);
    }

    /** POST /support/tickets/{ticket}/reply {message} — follow-up on an own ticket. */
    public function reply(SupportReplyRequest $request, SupportTicketService $service, int $ticket): JsonResponse
    {
        $user = $request->user();

        $owned = SupportTicket::query()->where('customer_id', $user->id)->findOrFail($ticket);

        if ($wait = $this->tooMany('support-reply:' . $user->id, 20, 3600)) {
            return $this->throttled($wait);
        }

        if (! $service->customerReply($owned->id, $user, trim($request->validated('message')))) {
            return response()->json([
                'message' => 'এই টিকেটে আর বার্তা পাঠানো যাবে না।',
                'code'    => 'not_replyable',
            ], 422);
        }

        return response()->json(['data' => $this->detail($request, $owned->id)]);
    }

    private function detail(Request $request, int $ticketId): array
    {
        $ticket = SupportTicket::query()
            ->where('customer_id', $request->user()->id)
            ->with(['messages', 'order:id,order_number'])
            ->findOrFail($ticketId);

        return (new SupportTicketResource($ticket))->resolve($request);
    }

    /** @return int seconds to wait, or 0 when allowed (and counts this attempt). */
    private function tooMany(string $key, int $max, int $decay): int
    {
        if (RateLimiter::tooManyAttempts($key, $max)) {
            return max(1, RateLimiter::availableIn($key));
        }

        RateLimiter::hit($key, $decay);

        return 0;
    }

    private function throttled(int $seconds): JsonResponse
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return response()->json([
            'message' => 'অনেকবার চেষ্টা করা হয়েছে। ' . $minutes . ' মিনিট পর আবার চেষ্টা করুন।',
            'code'    => 'too_many_attempts',
        ], 429);
    }
}
