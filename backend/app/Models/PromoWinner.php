<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoWinner extends Model
{
    protected $table = 'promo_winners';

    protected $fillable = [
        'phase_id',
        'user_id',
        'leaderboard_position',
        'total_points',
        'exact_hits',
        'invoice_count',
        'invoice_total_amount',
        'goal_prediction_delta',
        'ranking_timestamp',
        'selection_reason',
        'status',
        'replacement_for_winner_id',
        'notes',
        'selected_at',
        'last_contact_at',
        'responded_at',
        'disqualified_at',
        'created_by',
        'delivery_status',
        'delivery_qr_scanned_at',
        'id_card_photo_path',
        'delivery_photo_path',
        'delivery_notes',
        'delivered_by',
        'prize_delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'total_points' => 'decimal:2',
            'invoice_total_amount' => 'decimal:2',
            'ranking_timestamp' => 'datetime',
            'selected_at' => 'datetime',
            'last_contact_at' => 'datetime',
            'responded_at' => 'datetime',
            'disqualified_at' => 'datetime',
            'delivery_qr_scanned_at' => 'datetime',
            'prize_delivered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
