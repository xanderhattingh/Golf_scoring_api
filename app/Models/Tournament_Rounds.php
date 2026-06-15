<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament_Rounds extends Model
{
    protected $table = 'tournament_rounds';

    protected $fillable = [
        'tournament_id',
        'round_id',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournaments::class, 'tournament_id');
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class, 'round_id');
    }

    public function roundUsers(): HasMany
    {
        return $this->hasMany(RoundUser::class, 'round_id', 'round_id');
    }
}
