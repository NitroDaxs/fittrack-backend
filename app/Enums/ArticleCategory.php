<?php

namespace App\Enums;

/**
 * Mirrors the `category` enum on the articles table. The articles listing
 * filters on these -- previously it was handed ExerciseCategory values
 * (strength/cardio/...), which never matched an article.
 */
enum ArticleCategory: string
{
    case NUTRITION = 'nutrition';
    case TRAINING_THEORY = 'training_theory';
    case RECOVERY = 'recovery';
    case MINDSET = 'mindset';

    public function label(): string
    {
        return match ($this) {
            self::NUTRITION => 'Nutrition',
            self::TRAINING_THEORY => 'Training Theory',
            self::RECOVERY => 'Recovery',
            self::MINDSET => 'Mindset',
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
