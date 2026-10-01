<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SetLog extends Model
{
    use HasFactory;

    protected $fillable = ['workout_log_id', 'exercise_id', 'set_number', 'weight', 'reps', 'is_completed'];

    protected $casts = [
        'weight' => 'decimal:2',
        'reps' => 'integer',
        'is_completed' => 'boolean',
    ];

    public function workoutLog(): BelongsTo
    {
        return $this->belongsTo(WorkoutLog::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
