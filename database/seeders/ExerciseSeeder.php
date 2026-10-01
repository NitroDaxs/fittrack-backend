<?php

namespace Database\Seeders;

use App\Enums\Difficulty;
use App\Enums\ExerciseCategory;
use App\Models\Equipment;
use App\Models\Exercise;
use App\Models\ExerciseMedia;
use App\Models\MuscleGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the exercise library.
 *
 * Exercise definitions live in database/seeders/data/exercises/*.php, one file
 * per body part. Each entry carries a YouTube video id from ScottHermanFitness'
 * "HOW TO" demonstration playlists -- Scott gave us the go-ahead to link his
 * videos. Ids were scraped from the live playlists, never hand-written, so a
 * missing video means the video was pulled, not that the id was mistyped.
 *
 * data/exercises/core-lifts.php MUST stay first and keep its current order:
 * MemberDataSeeder references exercise ids 1-5 directly.
 */
class ExerciseSeeder extends Seeder
{
    /** Body-part files, loaded in this order. */
    private const FILES = [
        'core-lifts',
        'chest',
        'back',
        'shoulders',
        'biceps',
        'triceps',
        'forearms',
        'quads',
        'hamstrings-glutes',
        'calves',
        'abs',
        'conditioning',
    ];

    public function run(): void
    {
        $admin = User::first() ?? User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
        ]);

        $muscles = $this->resolveMuscleGroups();
        $equipment = $this->resolveEquipment();

        foreach (self::FILES as $file) {
            $definitions = require database_path("seeders/data/exercises/{$file}.php");

            foreach ($definitions as $item) {
                $this->createExercise($item, $admin->id, $muscles, $equipment);
            }
        }
    }

    private function createExercise(array $item, int $adminId, array $muscles, array $equipment): void
    {
        $videoId = $item['video'];
        $slug = Str::slug($item['name']);

        $exercise = Exercise::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $item['name'],
                'description' => $item['description'],
                'instructions' => $this->formatInstructions($item['instructions']),
                'difficulty' => Difficulty::from($item['difficulty']),
                'exercise_type' => ExerciseCategory::from($item['type']),
                'video_url' => "https://www.youtube.com/watch?v={$videoId}",
                'thumbnail_url' => "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg",
                'created_by' => $adminId,
                'is_published' => true,
            ]
        );

        $exercise->muscleGroups()->detach();
        foreach ($item['primary'] as $name) {
            $exercise->muscleGroups()->attach($muscles[$name]->id, ['role' => 'primary']);
        }
        foreach ($item['secondary'] as $name) {
            $exercise->muscleGroups()->attach($muscles[$name]->id, ['role' => 'secondary']);
        }

        $exercise->equipment()->sync(
            collect($item['equipment'])->map(fn ($name) => $equipment[$name]->id)->all()
        );

        ExerciseMedia::updateOrCreate(
            ['exercise_id' => $exercise->id, 'type' => 'video'],
            ['url' => "https://www.youtube.com/embed/{$videoId}", 'sort_order' => 0]
        );
    }

    /**
     * Numbered steps -- resources.js on the frontend strips the "1. " prefix
     * when it turns instructions into the step list.
     */
    private function formatInstructions(array $steps): string
    {
        return collect($steps)
            ->map(fn ($step, $index) => ($index + 1).'. '.$step)
            ->implode("\n");
    }

    /** @return array<string, MuscleGroup> */
    private function resolveMuscleGroups(): array
    {
        $names = [
            'Chest', 'Back', 'Lats', 'Traps', 'Lower Back', 'Quads', 'Hamstrings',
            'Glutes', 'Shoulders', 'Rear Delts', 'Biceps', 'Triceps', 'Forearms',
            'Core', 'Obliques', 'Calves', 'Abductors', 'Adductors', 'Full Body',
        ];

        return collect($names)
            ->mapWithKeys(fn ($name) => [
                $name => MuscleGroup::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]),
            ])
            ->all();
    }

    /** @return array<string, Equipment> */
    private function resolveEquipment(): array
    {
        $names = [
            'Barbell', 'Dumbbell', 'Bodyweight', 'Machine', 'Cable Machine',
            'Smith Machine', 'Kettlebell', 'EZ-Bar', 'Pull-up Bar', 'Dip Bar',
            'Bench', 'Mat', 'Weight Plate', 'Medicine Ball', 'Exercise Ball',
            'Bosu Ball', 'Suspension Straps', 'Box', 'Landmine',
        ];

        return collect($names)
            ->mapWithKeys(fn ($name) => [
                $name => Equipment::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]),
            ])
            ->all();
    }
}
