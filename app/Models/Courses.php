<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Courses extends Model
{
    protected $fillable = [
        'name',
        'location',
        'latitude',
        'longitude',
        'phone',
        'num_holes',
        'created_by',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function courseTees(): HasMany
    {
        return $this->hasMany(CourseTees::class, 'course_id');
    }
}
