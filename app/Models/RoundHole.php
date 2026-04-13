<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoundHole extends Model
{
    protected $fillable = [
        'round_id',
        'hole_number',
        'par',
        'stroke_index',
    ];

    protected $casts = [
        'hole_number' => 'integer',
        'par' => 'integer',
        'stroke_index' => 'integer',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class, 'round_id');
    }
}
