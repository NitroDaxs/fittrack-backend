<?php

namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\ExerciseCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    use HasFactory;
    protected $casts = [
        'difficulty' => Difficulty::class,
        'exercise_type' => ExerciseCategory::class,
        'is_published' => 'boolean',
    ];
    protected $fillable = [
        'name',
        'slug',
        'description',
        'instructions',
        'difficulty',
        'exercise_type',
        'video_url',
        'thumbnail_url',
        'created_by',
        'is_published',
    ];

    public function muscleGroups()
    {
        return $this->belongsToMany(
            MuscleGroup::class,
            'exercise_muscle_group',
            'exercise_id',
            'muscle_group_id'
        )->withPivot('role');
    }

    public function equipment()
    {
        // Fixed: Target Equipment::class with explicit foreign keys
        return $this->belongsToMany(
            Equipment::class,
            'exercise_equipment',
            'exercise_id',
            'equipment_id'
        );
    }

    public function media()
    {
        return $this->hasMany(ExerciseMedia::class)->orderBy('sort_order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
