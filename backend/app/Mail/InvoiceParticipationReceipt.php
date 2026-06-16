<?php

namespace App\Mail;

use App\Models\RegisteredInvoice;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceParticipationReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly RegisteredInvoice $invoice,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu comprobante de participación – Super Carnes 2026',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-participation-receipt',
            with: [
                'invoice' => $this->invoice,
                'participant' => $this->invoice->user,
                'qrImageBytes' => $this->buildQrCodePng(),
            ],
        );
    }

    private function buildQrCodePng(): string
    {
        $qrCode = new QrCode(
            data: $this->invoice->cufe,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 320,
            margin: 12,
            foregroundColor: new Color(16, 19, 26),
            backgroundColor: new Color(255, 255, 255),
        );

        return (new PngWriter())->write($qrCode)->getString();
    }
}
