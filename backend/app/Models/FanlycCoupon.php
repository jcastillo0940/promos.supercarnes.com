<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FanlycCoupon extends Model
{
    protected $fillable = [
        'fanlyc_invoice_id',
        'user_id',
        'fanlyc_zone_id',
        'code',
        'status',
        'redeemed_at',
        'redeemed_by_user_id',
        'redemption_notes',
        'void_reason',
        'voided_by_user_id',
        'voided_at',
    ];

    protected function casts(): array
    {
        return [
            'redeemed_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function fanlycInvoice(): BelongsTo
    {
        return $this->belongsTo(FanlycInvoice::class, 'fanlyc_invoice_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fanlycZone(): BelongsTo
    {
        return $this->belongsTo(FanlycZone::class, 'fanlyc_zone_id');
    }

    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by_user_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by_user_id');
    }
}
