<?php

namespace Database\Seeders;

use App\Models\BodyMeasurement;
use App\Models\SetLog;
use App\Models\User;
use App\Models\WorkoutLog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Populates member@test.com with realistic training history so the dashboard,
 * workout history, measurements, and profile screens have data to render.
 * Idempotent: clears this user's logs/measurements/favorites first, so it can
 * be re-run safely.
 */
class MemberDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'member@test.com')->first();

        if (!$user) {
            $this->command->warn('member@test.com not found -- run DatabaseSeeder first.');
            return;
        }

        // --- reset (set_logs cascade on workout_logs delete) ------------------
        $user->workoutLogs()->delete();
        $user->bodyMeasurements()->delete();
        $user->savedRoutines()->detach();
        $user->savedExercises()->detach();

        $this->seedWorkoutLogs($user);
        $this->seedMeasurements($user);
        $this->seedFavorites($user);

        $this->command->info('Seeded training data for member@test.com.');
    }

    private function seedWorkoutLogs(User $user): void
    {
        // Exercises that appear in sessions, with a "current" top working weight
        // (lbs) and a per-week ramp so older sessions are lighter -> the strength
        // progression chart trends upward. Bodyweight moves use weight 0.
        $plan = [
            ['exercise_id' => 1, 'top' => 190, 'stepPerWeek' => 4, 'reps' => 8],   // Bench Press
            ['exercise_id' => 2, 'top' => 285, 'stepPerWeek' => 6, 'reps' => 6],   // Back Squat
            ['exercise_id' => 3, 'top' => 335, 'stepPerWeek' => 7, 'reps' => 5],   // Deadlift
            ['exercise_id' => 4, 'top' => 0,   'stepPerWeek' => 0, 'reps' => 10],  // Pull-Up (bodyweight)
            ['exercise_id' => 5, 'top' => 100, 'stepPerWeek' => 2, 'reps' => 10],  // Shoulder Press
        ];

        // 4-day recent streak + scattered older sessions (~3/week feel).
        $offsets = [0, 1, 2, 3];
        for ($d = 6; $d <= 63; $d += 2) {
            $offsets[] = $d;
        }

        $names = ['Push Day', 'Pull Day', 'Leg Day', 'Upper Body', 'Full Body'];

        foreach ($offsets as $i => $offset) {
            $date = Carbon::now()->subDays($offset)->setTime(18, 0);
            $weeksAgo = $offset / 7;

            $log = WorkoutLog::create([
                'user_id' => $user->id,
                'workout_id' => null,
                'name' => $names[$i % count($names)],
                'date' => $date,
                'duration_minutes' => 45 + ($i % 4) * 10, // 45-75
                'notes' => null,
            ]);

            // Bench is in every session (so it's the "default" progression lift);
            // rotate two accessories in alongside it.
            $chosen = [$plan[0], $plan[1 + ($i % 4)]];

            foreach ($chosen as $entry) {
                $weight = 0;
                if ($entry['top'] > 0) {
                    $raw = $entry['top'] - $weeksAgo * $entry['stepPerWeek'];
                    $weight = max(45, round($raw / 5) * 5); // never below an empty bar
                }

                for ($set = 1; $set <= 3; $set++) {
                    SetLog::create([
                        'workout_log_id' => $log->id,
                        'exercise_id' => $entry['exercise_id'],
                        'set_number' => $set,
                        'weight' => $weight,
                        'reps' => $entry['reps'] + ($set === 3 ? -1 : 0), // last set a rep short
                        'is_completed' => true,
                    ]);
                }
            }
        }
    }

    private function seedMeasurements(User $user): void
    {
        // Eight entries over ~9 weeks, weight and body fat trending down.
        for ($i = 0; $i < 8; $i++) {
            $offset = $i * 9; // most recent (i=0) is today
            BodyMeasurement::create([
                'user_id' => $user->id,
                'date' => Carbon::now()->subDays($offset)->toDateString(),
                'weight' => 86 + $i * 0.9,          // ~86kg now, heavier further back
                'body_fat_pct' => 12 + $i * 0.5,    // ~12% now
                'custom_measurements' => [
                    'chest' => 42 - $i * 0.2,
                    'waist' => 32 + $i * 0.3,
                    'arms' => 15.5 - $i * 0.1,
                ],
                'photo_url' => null,
            ]);
        }
    }

    private function seedFavorites(User $user): void
    {
        // Routine/exercise ids come from RoutineSeeder/ExerciseSeeder.
        $user->savedRoutines()->syncWithoutDetaching([
            1 => ['saved_at' => now()],
            2 => ['saved_at' => now()],
        ]);
        $user->savedExercises()->syncWithoutDetaching([
            1 => ['saved_at' => now()],
            3 => ['saved_at' => now()],
            4 => ['saved_at' => now()],
        ]);
    }
}
