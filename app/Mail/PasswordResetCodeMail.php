<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * 6-digit code for the mobile app's "Forgot password". Sent right away (not
 * queued): the app is waiting for this mail and a queue worker may not run.
 */
class PasswordResetCodeMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $name,
        public string $code,
        public int $minutes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your password reset code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-code',
            with: [
                'name'    => $this->name,
                'code'    => $this->code,
                'minutes' => $this->minutes,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
