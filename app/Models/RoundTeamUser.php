<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoundTeamUser extends Model
{
    protected $fillable = [
        'round_team_id',
        'round_user_id',
    ];

    public function roundTeam(): BelongsTo
    {
        return $this->belongsTo(RoundTeam::class, 'round_team_id');
    }

    public function roundUser(): BelongsTo
    {
        return $this->belongsTo(RoundUser::class, 'round_user_id');
    }
}
