<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Round extends Model
{
    protected $fillable = [
        'course_id',
        'scoring_method_id',
        'created_by',
        'date',
        'format',
        'completed',
        'current_hole',
        'starting_hole',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'current_hole' => 'integer',
        'starting_hole' => 'integer',
        'date' => 'date',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Courses::class, 'course_id');
    }

    public function scoringMethod(): BelongsTo
    {
        return $this->belongsTo(ScoringMethod::class, 'scoring_method_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function roundUsers(): HasMany
    {
        return $this->hasMany(RoundUser::class, 'round_id');
    }

    public function roundTeams(): HasMany
    {
        return $this->hasMany(RoundTeam::class, 'round_id');
    }

    public function roundHoles(): HasMany
    {
        return $this->hasMany(RoundHole::class, 'round_id');
    }

    public function holeScores(): HasMany
    {
        return $this->hasMany(HoleScore::class, 'round_id');
    }

    public function animalEvents(): HasMany
    {
        return $this->hasMany(AnimalEvent::class, 'round_id');
    }
}
