<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    public $timestamps = false;
    protected $table = 'equipment';
    protected $fillable = ['name', 'slug'];

    public function exercises()
    {
        return $this->belongsToMany(Exercise::class);
    }
    public function equipment()
    {
        // Explicitly declare 'exercise_equipment' as the pivot table
        return $this->belongsToMany(Equipment::class, 'exercise_equipment');
    }
}
