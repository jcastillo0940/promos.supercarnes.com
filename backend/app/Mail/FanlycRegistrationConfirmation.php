<?php

namespace App\Mail;

use App\Models\FanlycCoupon;
use App\Models\FanlycInvoice;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FanlycRegistrationConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly FanlycInvoice $invoice,
        public readonly ?FanlycCoupon $coupon = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->coupon
                ? 'Tu cupon Fanlyc esta listo - Super Carnes'
                : 'Recibimos tu factura para Fanlyc - Super Carnes',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.fanlyc-registration',
            with: [
                'invoice' => $this->invoice,
                'coupon' => $this->coupon,
                'qrImageBytes' => $this->coupon ? $this->buildQrCodePng() : null,
            ],
        );
    }

    private function buildQrCodePng(): string
    {
        $qrCode = new QrCode(
            data: (string) $this->coupon->code,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 320,
            margin: 12,
            foregroundColor: new Color(93, 49, 12),
            backgroundColor: new Color(255, 255, 255),
        );

        return (new PngWriter())->write($qrCode)->getString();
    }
}
