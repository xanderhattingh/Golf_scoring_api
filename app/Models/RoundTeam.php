<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoundTeam extends Model
{
    protected $fillable = [
        'round_id',
        'name',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class, 'round_id');
    }

    public function roundTeamUsers(): HasMany
    {
        return $this->hasMany(RoundTeamUser::class, 'round_team_id');
    }

    public function holeScores(): HasMany
    {
        return $this->hasMany(HoleScore::class, 'round_team_id');
    }
}
