<?php

namespace App\Mail;

use App\Models\FondaRegistration;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FondaChallengeRegistrationConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly FondaRegistration $registration,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu inscripción al Fonda Challenge 2026 – Super Carnes',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.fonda-challenge-registration',
            with: [
                'registration' => $this->registration,
                'qrImageBytes' => $this->buildQrCodePng(),
            ],
        );
    }

    private function buildQrCodePng(): string
    {
        $qrCode = new QrCode(
            data: route('fonda-challenge.show', ['code' => $this->registration->code]),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 320,
            margin: 12,
            foregroundColor: new Color(93, 49, 12),
            backgroundColor: new Color(255, 255, 255),
        );

        return (new PngWriter())->write($qrCode)->getString();
    }
}
