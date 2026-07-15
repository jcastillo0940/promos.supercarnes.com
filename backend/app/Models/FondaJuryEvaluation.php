<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FondaJuryEvaluation extends Model
{
    protected $fillable = [
        'campaign_id',
        'registration_id',
        'assignment_id',
        'user_id',
        'sabor',
        'tecnica',
        'presentacion',
        'originalidad',
        'uso_producto',
        'final_score',
        'commentary',
        'status',
        'started_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'sabor' => 'decimal:1',
            'tecnica' => 'decimal:1',
            'presentacion' => 'decimal:1',
            'originalidad' => 'decimal:1',
            'uso_producto' => 'decimal:1',
            'final_score' => 'decimal:2',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(FondaRegistration::class, 'registration_id');
    }
}
