<?php

namespace App\Mail;

use App\Models\SabaEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SabaRegistrationConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly SabaEntry $entry)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu participación en Emprende tu Sueño con Saba - Super Carnes',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.saba-registration',
            with: [
                'entry' => $this->entry->loadMissing('branch'),
            ],
        );
    }
}
