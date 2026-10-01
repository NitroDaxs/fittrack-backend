<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MuscleGroup extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'slug'];

    public function exercises()
    {
        return $this->belongsToMany(Exercise::class)->withPivot('role');
    }
}
