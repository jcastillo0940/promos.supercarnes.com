<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignParticipantConsent extends Model
{
    protected $fillable = ['campaign_id', 'user_id', 'terms_version', 'accepted_at', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }
}
