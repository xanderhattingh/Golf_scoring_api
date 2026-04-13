<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalEvent extends Model
{
    protected $fillable = [
        'round_id',
        'round_user_id',
        'hole_number',
        'animal_type',
        'event_at',
    ];

    protected $casts = [
        'hole_number' => 'integer',
        'event_at' => 'datetime',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class, 'round_id');
    }

    public function roundUser(): BelongsTo
    {
        return $this->belongsTo(RoundUser::class, 'round_user_id');
    }
}
