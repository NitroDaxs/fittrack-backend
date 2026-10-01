<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExerciseMedia extends Model
{
    protected $fillable = ['exercise_id', 'type', 'url', 'sort_order'];

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }
}
