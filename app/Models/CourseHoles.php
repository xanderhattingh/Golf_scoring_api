<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseHoles extends Model
{
    protected $fillable = [
        'course_tee_id',
        'hole_number',
        'par',
        'stroke_index',
        'yards',
        'meters',
    ];

    public function courseTee(): BelongsTo
    {
        return $this->belongsTo(CourseTees::class, 'course_tee_id');
    }
}
