<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tournaments extends Model
{
    protected $fillable = [
        'name',
        'description',
        'creator_id',
        'course_id',
        'tee_id',
        'scoring_method_id',
        'status',
        'invite_code',
    ];

    public function rounds(): HasMany
    {
        return $this->hasMany(Tournament_Rounds::class, 'tournament_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Courses::class, 'course_id', 'id');
    }

    public function scoring_method(): BelongsTo
    {
        return $this->belongsTo(ScoringMethod::class, 'scoring_method_id', 'id');
    }
    

}
