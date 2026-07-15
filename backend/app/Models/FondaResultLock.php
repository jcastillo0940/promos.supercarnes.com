<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FondaResultLock extends Model
{
    protected $fillable = [
        'campaign_id',
        'frozen_at',
        'published_at',
        'frozen_by',
        'published_by',
        'freeze_reason',
        'publish_reason',
    ];

    protected function casts(): array
    {
        return [
            'frozen_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
