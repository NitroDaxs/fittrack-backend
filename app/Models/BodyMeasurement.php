<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodyMeasurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'weight',
        'body_fat_pct',
        'custom_measurements',
        'photo_url',
    ];

    protected $casts = [
        'date' => 'date',
        'weight' => 'decimal:2',
        'body_fat_pct' => 'decimal:1',
        'custom_measurements' => 'array', // Automatically serializes/deserializes JSON
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
