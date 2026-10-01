<?php

namespace App\Enums;

enum RoutineGoal: string
{
    case STRENGTH = 'strength';
    case HYPERTROPHY = 'hypertrophy';
    case FAT_LOSS = 'fat_loss';
    case ENDURANCE = 'endurance';
    case GENERAL_FITNESS = 'general_fitness';

    public function label(): string
    {
        return match ($this) {
            self::STRENGTH => 'Strength',
            self::HYPERTROPHY => 'Hypertrophy',
            self::FAT_LOSS => 'Fat Loss',
            self::ENDURANCE => 'Endurance',
            self::GENERAL_FITNESS => 'General Fitness',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'id' => $case->value,
            'name' => $case->label(),
            'slug' => $case->value,
        ], self::cases());
    }
}
