<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reconciles `exercises.exercise_type` with App\Enums\ExerciseCategory.
 *
 * The column was created as enum('strength','cardio','mobility','balance') while
 * the PHP enum has since grown stretching, plyometrics, powerlifting,
 * olympic_weightlifting and strongman, and dropped mobility/balance. The two
 * disagreed in both directions -- documented in
 * database/seeders/data/exercises/README.md as "Known enum drift".
 *
 * That drift was reachable from the admin console: the Category select is fed by
 * ExerciseCategory, so picking "Plyometrics" passed validation and then failed at
 * the column with "Data truncated for column 'exercise_type'".
 *
 * Raw ALTER rather than Blueprint->change(): Laravel's schema builder cannot
 * modify a MySQL enum's allowed values in place.
 */
return new class extends Migration {
    /** Every value the PHP enum accepts. */
    private const VALUES = [
        'strength',
        'cardio',
        'stretching',
        'plyometrics',
        'powerlifting',
        'olympic_weightlifting',
        'strongman',
    ];

    /** What the column allowed before -- mobility/balance included, for rollback. */
    private const PREVIOUS = ['strength', 'cardio', 'mobility', 'balance'];

    public function up(): void
    {
        // Nothing currently uses mobility/balance (the seed data only uses
        // strength and cardio), but remap defensively so dropping them from the
        // allowed set can't truncate an existing row.
        DB::table('exercises')->whereIn('exercise_type', ['mobility', 'balance'])
            ->update(['exercise_type' => 'stretching']);

        $this->setEnum(self::VALUES, 'strength');
    }

    public function down(): void
    {
        // Fold the values that won't exist after rollback back into strength.
        DB::table('exercises')
            ->whereNotIn('exercise_type', self::PREVIOUS)
            ->update(['exercise_type' => 'strength']);

        $this->setEnum(self::PREVIOUS, 'strength');
    }

    private function setEnum(array $values, string $default): void
    {
        $list = implode(',', array_map(fn($value) => "'{$value}'", $values));

        DB::statement(
            "ALTER TABLE `exercises` MODIFY `exercise_type` ENUM({$list}) NOT NULL DEFAULT '{$default}'"
        );
    }
};
