<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FondaJuryAssignment extends Model
{
    protected $fillable = [
        'campaign_id',
        'registration_id',
        'user_id',
        'status',
        'assigned_at',
        'conflicted_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'conflicted_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(FondaRegistration::class, 'registration_id');
    }
}
