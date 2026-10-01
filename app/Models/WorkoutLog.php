<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'workout_id', 'name', 'date', 'duration_minutes', 'notes'];

    protected $casts = [
        'date' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setLogs(): HasMany
    {
        return $this->hasMany(SetLog::class);
    }
}
