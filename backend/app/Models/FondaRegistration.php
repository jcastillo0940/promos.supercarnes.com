<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FondaRegistration extends Model
{
    protected $fillable = [
        'campaign_id',
        'code',
        'status',
        'full_name',
        'cedula',
        'email',
        'phone',
        'fonda_name',
        'fonda_location',
        'dish_name',
        'description',
        'consent_terms',
        'meta',
        'checked_in_at',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'checked_in_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
