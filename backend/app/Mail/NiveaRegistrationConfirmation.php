<?php

namespace App\Mail;

use App\Models\NiveaEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NiveaRegistrationConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly NiveaEntry $entry)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Empollerando a Mamá – Tu participación fue recibida',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nivea-registration',
        );
    }
}
