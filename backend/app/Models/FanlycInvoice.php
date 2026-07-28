<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FanlycInvoice extends Model
{
    protected $fillable = [
        'user_id',
        'campaign_id',
        'branch_id',
        'fanlyc_zone_id',
        'cufe',
        'qr_raw_text',
        'invoice_number',
        'issuer_ruc',
        'issuer_name',
        'issued_at',
        'purchase_amount',
        'sku_check_status',
        'sku_check_payload',
        'status',
        'validation_notes',
        'dgi_response_payload',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'purchase_amount' => 'decimal:2',
            'sku_check_payload' => 'array',
            'dgi_response_payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function fanlycZone(): BelongsTo
    {
        return $this->belongsTo(FanlycZone::class, 'fanlyc_zone_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function coupon(): HasOne
    {
        return $this->hasOne(FanlycCoupon::class, 'fanlyc_invoice_id');
    }
}
