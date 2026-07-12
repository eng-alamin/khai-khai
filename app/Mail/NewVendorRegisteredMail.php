<?php

namespace App\Mail;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewVendorRegisteredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Restaurant $restaurant;
    public User $vendorUser;

    public function __construct(Restaurant $restaurant, User $vendorUser)
    {
        $this->restaurant = $restaurant;
        $this->vendorUser = $vendorUser;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Vendor Registration — ' . $this->restaurant->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vendor-registered',
            with: [
                'restaurant' => $this->restaurant,
                'vendorUser' => $this->vendorUser,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}