<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'exercise_id',
        'order',
        'target_sets',
        'target_reps',
        'target_weight',
        'rest_seconds',
        'notes',
    ];

    protected $casts = [
        'order' => 'integer',
        'target_sets' => 'integer',
        'target_weight' => 'decimal:2',
        'rest_seconds' => 'integer',
    ];

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
