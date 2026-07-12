<?php

namespace App\Mail;

use App\Models\RiderProfile;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewRiderRegisteredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public RiderProfile $riderProfile;
    public User $riderUser;

    public function __construct(RiderProfile $riderProfile, User $riderUser)
    {
        $this->riderProfile = $riderProfile;
        $this->riderUser    = $riderUser;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Rider Registration — ' . $this->riderUser->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rider-registered',
            with: [
                'riderProfile' => $this->riderProfile,
                'riderUser'    => $this->riderUser,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}