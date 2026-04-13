<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HoleScore extends Model
{
    protected $fillable = [
        'round_id',
        'round_team_id',
        'round_user_id',
        'hole_number',
        'strokes',
        'points',
        'has_pink_ball',
    ];

    protected $casts = [
        'hole_number' => 'integer',
        'strokes' => 'integer',
        'points' => 'integer',
        'has_pink_ball' => 'boolean',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class, 'round_id');
    }

    public function roundTeam(): BelongsTo
    {
        return $this->belongsTo(RoundTeam::class, 'round_team_id');
    }

    public function roundUser(): BelongsTo
    {
        return $this->belongsTo(RoundUser::class, 'round_user_id');
    }
}
