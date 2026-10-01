<?php

namespace App\Enums;

enum Difficulty: string
{
    case BEGINNER = 'beginner';
    case INTERMEDIATE = 'intermediate';
    case ADVANCED = 'advanced';

    public function label(): string
    {
        return match ($this) {
            self::BEGINNER => 'Beginner',
            self::INTERMEDIATE => 'Intermediate',
            self::ADVANCED => 'Advanced',
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
