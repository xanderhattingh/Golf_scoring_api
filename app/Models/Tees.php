<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tees extends Model
{
    protected $fillable = [
        'name',
        'description',
        'colour_code',
    ];

    public function courseTees(): HasMany
    {
        return $this->hasMany(CourseTees::class, 'tee_id');
    }
}
