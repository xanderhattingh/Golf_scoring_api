<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoundUser extends Model
{
    protected $fillable = [
        'round_id',
        'user_id',
        'round_handicap',
    ];

    protected $casts = [
        'round_handicap' => 'integer',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class, 'round_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function holeScores(): HasMany
    {
        return $this->hasMany(HoleScore::class, 'round_user_id');
    }

    public function animalEvents(): HasMany
    {
        return $this->hasMany(AnimalEvent::class, 'round_user_id');
    }

    public function roundTeamUsers(): HasMany
    {
        return $this->hasMany(RoundTeamUser::class, 'round_user_id');
    }
}
