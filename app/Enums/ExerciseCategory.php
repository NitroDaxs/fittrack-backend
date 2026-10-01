<?php

namespace App\Enums;

enum ExerciseCategory: string
{
    case STRENGTH = 'strength';
    case CARDIO = 'cardio';
    case STRETCHING = 'stretching';
    case PLYOMETRICS = 'plyometrics';
    case POWERLIFTING = 'powerlifting';
    case OLYMPIC_WEIGHTLIFTING = 'olympic_weightlifting';
    case STRONGMAN = 'strongman';

    public function label(): string
    {
        return match ($this) {
            self::STRENGTH => 'Strength',
            self::CARDIO => 'Cardio',
            self::STRETCHING => 'Stretching',
            self::PLYOMETRICS => 'Plyometrics',
            self::POWERLIFTING => 'Powerlifting',
            self::OLYMPIC_WEIGHTLIFTING => 'Olympic Weightlifting',
            self::STRONGMAN => 'Strongman',
        };
    }

    public static function toArray(): array
    {
        return array_map(fn ($case) => [
            'id' => $case->value,
            'name' => $case->label(),
            'slug' => $case->value,
        ], self::cases());
    }
}
