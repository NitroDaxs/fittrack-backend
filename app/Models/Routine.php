<?php

namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\RoutineGoal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Routine extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'goal',
        'level',
        'days_per_week',
        'created_by',
        'is_public',
        'is_published',
    ];

    protected $casts = [
        'goal' => RoutineGoal::class,
        'level' => Difficulty::class,
        'is_public' => 'boolean',
        'is_published' => 'boolean',
        'days_per_week' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class)->orderBy('day_order');
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'routine_user')->withPivot('saved_at');
    }
}
