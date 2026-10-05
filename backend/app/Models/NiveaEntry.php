<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NiveaEntry extends Model
{
    public const STATUSES = [
        'approved' => 'Aprobada',
        'pending_review' => 'En revisión',
        'rejected' => 'Rechazada',
    ];

    protected $fillable = [
        'campaign_id',
        'cedula',
        'active_cedula',
        'full_name',
        'phone',
        'email',
        'birthdate',
        'nominee_name',
        'story',
        'declared_eligibility_at',
        'terms_version',
        'accepted_terms_at',
        'cufe',
        'qr_raw_text',
        'invoice_number',
        'issuer_ruc',
        'issuer_name',
        'branch_id',
        'issued_at',
        'purchase_amount',
        'eligible_units',
        'product_check_status',
        'matched_products',
        'dgi_response_payload',
        'status',
        'validation_notes',
        'source',
        'reviewed_by_user_id',
        'reviewed_at',
        'winner_position',
        'winner_selected_at',
        'winner_selected_by_user_id',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'declared_eligibility_at' => 'datetime',
            'accepted_terms_at' => 'datetime',
            'issued_at' => 'datetime',
            'purchase_amount' => 'decimal:2',
            'eligible_units' => 'integer',
            'matched_products' => 'array',
            'dgi_response_payload' => 'array',
            'reviewed_at' => 'datetime',
            'winner_position' => 'integer',
            'winner_selected_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function firstName(): string
    {
        return preg_split('/\s+/', trim($this->full_name))[0] ?? 'Participante';
    }
}
