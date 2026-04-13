<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScoringMethod extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class, 'scoring_method_id');
    }
}
