<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'units',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
    ];

    // --- DASHBOARD & LOGGING RELATIONSHIPS ---

    public function workoutLogs(): HasMany // 👈 ADDED HERE
    {
        return $this->hasMany(WorkoutLog::class);
    }

    public function setLogs(): HasManyThrough
    {
        return $this->hasManyThrough(SetLog::class, WorkoutLog::class);
    }

    public function bodyMeasurements(): HasMany
    {
        return $this->hasMany(BodyMeasurement::class);
    }

    // --- ROUTINE & WORKOUT RELATIONSHIPS ---

    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class, 'created_by');
    }

    public function createdRoutines(): HasMany
    {
        return $this->hasMany(Routine::class, 'created_by');
    }

    // --- HELPER METHODS ---

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isMember(): bool
    {
        return $this->role === UserRole::MEMBER;
    }

    public function savedRoutines(): BelongsToMany
    {
        return $this->belongsToMany(Routine::class, 'routine_user')->withPivot('saved_at');
    }

    public function savedExercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'exercise_user')->withPivot('saved_at');
    }
}
