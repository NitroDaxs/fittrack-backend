<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\Routine;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use Illuminate\Database\Seeder;

class RoutineSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $exercises = Exercise::all();

        if ($exercises->isEmpty()) {
            return;
        }

        // Create 3 sample routines
        $routines = [
            [
                'title' => 'Push Pull Legs Hypertrophy',
                'description' => 'Classic 6-day split designed for maximum muscle growth and recovery.',
                'days_per_week' => 6,
                'goal' => 'hypertrophy',
                'level' => 'intermediate',
                'workouts' => ['Push Day A', 'Pull Day A', 'Legs Day A'],
            ],
            [
                'title' => 'Starting 5x5 Strength',
                'description' => 'A linear progression strength building routine ideal for beginners.',
                'days_per_week' => 3,
                'goal' => 'strength',
                'level' => 'beginner',
                'workouts' => ['Workout A (Squat/Bench/Row)', 'Workout B (Squat/OHP/Deadlift)'],
            ],
            [
                'title' => 'Upper Lower Power',
                'description' => '4-day split blending heavy powerlifting movements with accessory work.',
                'days_per_week' => 4,
                'goal' => 'strength',
                'level' => 'advanced',
                'workouts' => ['Upper Power', 'Lower Power', 'Upper Hypertrophy', 'Lower Hypertrophy'],
            ],
            [
                'title' => 'Upper Lower Power',
                'description' => '4-day split blending heavy powerlifting movements with accessory work.',
                'days_per_week' => 4,
                'goal' => 'strength',
                'level' => 'advanced',
                'workouts' => ['Upper Power', 'Lower Power', 'Upper Hypertrophy', 'Lower Hypertrophy'],
            ],
            [
                'title' => 'Upper Lower Power',
                'description' => '4-day split blending heavy powerlifting movements with accessory work.',
                'days_per_week' => 4,
                'goal' => 'strength',
                'level' => 'advanced',
                'workouts' => ['Upper Power', 'Lower Power', 'Upper Hypertrophy', 'Lower Hypertrophy'],
            ],
            [
                'title' => 'Upper Lower Power',
                'description' => '4-day split blending heavy powerlifting movements with accessory work.',
                'days_per_week' => 4,
                'goal' => 'strength',
                'level' => 'advanced',
                'workouts' => ['Upper Power', 'Lower Power', 'Upper Hypertrophy', 'Lower Hypertrophy'],
            ],
            [
                'title' => 'Upper Lower Power',
                'description' => '4-day split blending heavy powerlifting movements with accessory work.',
                'days_per_week' => 4,
                'goal' => 'strength',
                'level' => 'advanced',
                'workouts' => ['Upper Power', 'Lower Power', 'Upper Hypertrophy', 'Lower Hypertrophy'],
            ],
            [
                'title' => 'Upper Lower Power',
                'description' => '4-day split blending heavy powerlifting movements with accessory work.',
                'days_per_week' => 4,
                'goal' => 'strength',
                'level' => 'advanced',
                'workouts' => ['Upper Power', 'Lower Power', 'Upper Hypertrophy', 'Lower Hypertrophy'],
            ],
            [
                'title' => 'Upper Lower Power',
                'description' => '4-day split blending heavy powerlifting movements with accessory work.',
                'days_per_week' => 4,
                'goal' => 'strength',
                'level' => 'advanced',
                'workouts' => ['Upper Power', 'Lower Power', 'Upper Hypertrophy', 'Lower Hypertrophy'],
            ],
        ];

        foreach ($routines as $routineData) {
            $workouts = $routineData['workouts'];
            unset($routineData['workouts']);

            $routine = Routine::create(array_merge($routineData, [
                'slug' => \Illuminate\Support\Str::slug($routineData['title']),
                'created_by' => $admin->id,
                'is_public' => true,
                'is_published' => true,
            ]));

            foreach ($workouts as $index => $workoutName) {
                $workout = Workout::create([
                    'routine_id' => $routine->id,
                    'name' => $workoutName,
                    'day_order' => $index + 1,
                    'created_by' => $admin->id,
                    'is_template' => true,
                ]);

                // Attach 5 random exercises to each workout day
                $randomExercises = $exercises->random(min(5, $exercises->count()));
                foreach ($randomExercises as $order => $exercise) {
                    WorkoutExercise::create([
                        'workout_id' => $workout->id,
                        'exercise_id' => $exercise->id,
                        'order' => $order + 1,
                        'target_sets' => 4,
                        'target_reps' => '8-12',
                        'target_weight' => 60.00,
                        'rest_seconds' => 90,
                        'notes' => 'Keep form strict',
                    ]);
                }
            }
        }
    }
}
