# Exercise seed data

One file per body part, each returning a flat array of exercise definitions.
`ExerciseSeeder` loads them in the order listed in its `FILES` constant.

## Entry shape

```php
[
    'name' => 'Face Pull',
    'video' => 'rep-qVOkqgk',        // YouTube id -> video_url, thumbnail_url, exercise_media
    'difficulty' => 'beginner',      // beginner | intermediate | advanced
    'type' => 'strength',            // see enum note below
    'equipment' => ['Cable Machine'],
    'primary' => ['Rear Delts'],
    'secondary' => ['Traps', 'Back'],
    'description' => 'One sentence, shown on the library card.',
    'instructions' => ['Step one.', 'Step two.', 'Step three.', 'Step four.'],
]
```

`equipment`, `primary` and `secondary` names must exist in
`ExerciseSeeder::resolveMuscleGroups()` / `resolveEquipment()`, which create the
taxonomy rows. Adding a new name means adding it there first.

The seeder keys on the slug and uses `updateOrCreate`, so re-running it updates
in place rather than duplicating.

## Video sources

Every `video` id comes from one of ScottHermanFitness' three "HOW TO"
demonstration playlists, scraped from the live playlist pages (never
hand-written). Scott gave the go-ahead to link his videos.

- Barbell & Dumbbell: `PLacPhVACI3MNgGaNdQfNfcMyupRfSiMmQ`
- Machine & Cable: `PLacPhVACI3MPUu-vCblBkHiGwYYYEQZnI`
- Bodyweight: `PLacPhVACI3MM8-fmxD_0cTAEQKCUJmnPH`

Thumbnails are derived as `https://i.ytimg.com/vi/<id>/hqdefault.jpg`, so they
always match the linked video.

## core-lifts.php ordering is load-bearing

`MemberDataSeeder` hard-codes exercise ids 1-5 (bench, squat, deadlift, pull-up,
shoulder press) when it fabricates training history. Do not reorder
`core-lifts.php` or move it out of first position in `ExerciseSeeder::FILES`.
Add new exercises to the body-part files instead.

## Known enum drift

`type` can only be `strength`, `cardio`, `mobility` or `balance` -- that is what
the `exercises.exercise_type` column enum allows (see
`2026_07_24_133814_create_exercises_table.php`). `App\Enums\ExerciseCategory`
has since grown `stretching`, `plyometrics`, `powerlifting`,
`olympic_weightlifting` and `strongman`, and `mobility`/`balance` were dropped
from it -- so the enum and the column no longer agree in either direction.

This data only uses `strength` and `cardio`, the two values valid in both.
Several entries in `conditioning.php` are really plyometrics and would be
categorised better once the column and the enum are reconciled.
