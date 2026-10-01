<?php

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Enums\RoutineGoal;
use App\Models\Routine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoutineFactory extends Factory
{
    protected $model = Routine::class;

    public function definition(): array
    {
        $title = $this->faker->words(3, true);

        return [
            'title' => ucwords($title),
            'slug' => Str::slug($title),
            'description' => $this->faker->paragraph(),
            'goal' => $this->faker->randomElement(RoutineGoal::cases()),
            'level' => $this->faker->randomElement(Difficulty::cases()),
            'days_per_week' => $this->faker->numberBetween(3, 6),
            'created_by' => User::first()?->id ?? User::factory(),
            'is_public' => true,
            'is_published' => true,
        ];
    }
}
