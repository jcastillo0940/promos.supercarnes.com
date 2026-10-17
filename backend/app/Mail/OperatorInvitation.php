<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OperatorInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $operator,
        public readonly string $resetUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitación al backoffice – Super Carnes',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.operator-invitation',
            with: [
                'operator' => $this->operator,
                'resetUrl' => $this->resetUrl,
            ],
        );
    }
}
