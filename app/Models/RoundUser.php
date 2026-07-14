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

    /**
     * User ids that are currently in an incomplete (active) round.
     * Such players can't be added to a new round until theirs is completed.
     */
    public static function activeUserIds(): \Illuminate\Support\Collection
    {
        return static::whereHas('round', fn ($q) => $q->where('completed', false))
            ->pluck('user_id')
            ->unique()
            ->values();
    }

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
