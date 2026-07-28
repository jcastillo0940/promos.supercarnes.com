<?php

namespace App\Support\Fanlyc;

use App\Models\FanlycCoupon;
use App\Models\FanlycInvoice;
use Illuminate\Support\Str;

class FanlycCouponIssuer
{
    public function issueFor(FanlycInvoice $invoice): FanlycCoupon
    {
        return FanlycCoupon::query()->create([
            'fanlyc_invoice_id' => $invoice->id,
            'user_id' => $invoice->user_id,
            'fanlyc_zone_id' => $invoice->fanlyc_zone_id,
            'code' => $this->generateCode(),
            'status' => 'issued',
        ]);
    }

    private function generateCode(): string
    {
        do {
            $code = 'FLY-'.strtoupper(Str::random(5));
        } while (FanlycCoupon::query()->where('code', $code)->exists());

        return $code;
    }
}
