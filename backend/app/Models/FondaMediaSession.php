<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FondaMediaSession extends Model
{
    protected $fillable = [
        'campaign_id',
        'registration_id',
        'user_id',
        'status',
        'started_at',
        'submitted_at',
    ];
}
