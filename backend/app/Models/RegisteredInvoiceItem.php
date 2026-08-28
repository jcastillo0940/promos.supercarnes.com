<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegisteredInvoiceItem extends Model
{
    protected $fillable = [
        'registered_invoice_id', 'barcode', 'description', 'quantity',
        'unit_price', 'is_eligible', 'source_payload',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'is_eligible' => 'boolean', 'source_payload' => 'array'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(RegisteredInvoice::class, 'registered_invoice_id');
    }
}
