<?php

namespace App\Livewire\Customer;

use App\Models\Order;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class SupportComponent extends Component
{
    public string $category    = '';
    public string $orderNumber = '';
    public string $subject     = '';
    public string $message     = '';

    public array $categoryOptions = [
        'order'        => 'অর্ডার সমস্যা',
        'payment'      => 'পেমেন্ট সমস্যা',
        'delivery'     => 'ডেলিভারি সমস্যা',
        'food_quality' => 'খাবারের মান',
        'account'      => 'অ্যাকাউন্ট সমস্যা',
        'refund'       => 'রিফান্ড অনুরোধ',
        'other'        => 'অন্যান্য',
    ];

    protected function rules(): array
    {
        return [
            'category'    => 'required|in:order,payment,delivery,food_quality,account,refund,other',
            'orderNumber' => 'nullable|string|max:20',
            'subject'     => 'required|string|min:3|max:150',
            'message'     => 'required|string|min:10|max:1000',
        ];
    }

    protected function messages(): array
    {
        return [
            'category.required' => 'সমস্যার ধরন বেছে নিন!',
            'subject.required'  => 'বিষয় লিখুন!',
            'message.min'       => 'বিস্তারিত অন্তত ১০ অক্ষর লিখুন!',
        ];
    }

    public function submitTicket(): void
    {
        $this->validate();

        $user = Auth::user();

        // Optional order reference — only linked if it genuinely belongs to this customer.
        // BUG FIX: previously used LIKE '%term%' (wildcard on both sides), which
        // could match the wrong order whenever one order number was a substring
        // of another (e.g. searching "2602" could match "KK260212" instead of
        // "KK2602"). Order numbers are exact, system-generated identifiers, so
        // this is now an exact match (case-insensitive, '#' prefix stripped).
        $order = null;
        $cleanNumber = ltrim(trim($this->orderNumber), '#');
        if ($cleanNumber !== '') {
            $order = Order::where('customer_id', $user->id)
                ->whereRaw('UPPER(order_number) = ?', [strtoupper($cleanNumber)])
                ->first();
        }

        do {
            $ticketNumber = 'TK' . now()->format('ymd') . strtoupper(Str::random(4));
        } while (SupportTicket::where('ticket_number', $ticketNumber)->exists());

        $ticket = SupportTicket::create([
            'ticket_number' => $ticketNumber,
            'customer_id'   => $user->id,
            'order_id'      => $order?->id,
            'category'      => $this->category,
            'subject'       => $this->subject,
            'message'       => $this->message,
            'status'        => 'open',
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($ticket)
            ->withProperties(['ticket_number' => $ticket->ticket_number, 'category' => $ticket->category])
            ->log('Support ticket created');

        $this->reset(['category', 'orderNumber', 'subject', 'message']);

        $this->dispatch('show-toast', message: '✅ টিকেট পাঠানো হয়েছে! শীঘ্রই যোগাযোগ করা হবে।', type: 'success');
    }

    public function render()
    {
        $tickets = SupportTicket::where('customer_id', Auth::id())
            ->latest()
            ->take(20)
            ->get();

        return view('livewire.customer.support-component', [
            'tickets' => $tickets,
        ])->layout('layouts.customer', [
            'title' => 'Support | KhaiKhai', 'breadcrumbTitle' => 'Support'
        ]);
    }
}