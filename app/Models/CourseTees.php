<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseTees extends Model
{
    protected $fillable = [
        'course_id',
        'tee_id',
        'course_rating',
        'slope_rating',
        'total_yards',
        'total_meters',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Courses::class, 'course_id');
    }

    public function tee(): BelongsTo
    {
        return $this->belongsTo(Tees::class, 'tee_id');
    }

    public function holes(): HasMany
    {
        return $this->hasMany(CourseHoles::class, 'course_tee_id');
    }
}
