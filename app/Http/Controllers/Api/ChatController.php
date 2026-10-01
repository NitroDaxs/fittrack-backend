<?php

namespace App\Http\Controllers\Api;

use App\Enums\Difficulty;
use App\Enums\RoutineGoal;
use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleSummaryResource;
use App\Models\Article;
use App\Models\Equipment;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Routine;
use App\Models\SetLog;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * The AI never touches real data. Its only job is to read the user's question
 * and classify it -- which topic, plus a few slot values drawn from vocabularies
 * we hand it. Every number, name and record in the response is then looked up
 * from the database in code below, so a small local model can't hallucinate an
 * exercise that doesn't exist or a bodyweight the user never logged.
 */
class ChatController extends Controller
{
    /** Answerable without an account. */
    private const PUBLIC_TOPICS = ['exercises', 'routines', 'articles'];

    /** Reads the signed-in user's own data -- requires a Sanctum token. */
    private const MEMBER_TOPICS = ['measurements', 'workouts', 'strength', 'saved', 'stats'];

    /**
     * Everyday words people use that aren't slugs in our taxonomy. Checked
     * before the fuzzy slug match so "abs" lands on `core` rather than
     * accidentally matching `abductors`.
     */
    private const MUSCLE_ALIASES = [
        'abs' => 'core',
        'stomach' => 'core',
        'pecs' => 'chest',
        'pec' => 'chest',
        'delts' => 'shoulders',
        'deltoids' => 'shoulders',
        'arms' => 'biceps',
        'arm' => 'biceps',
        'legs' => 'quads',
        'leg' => 'quads',
        'thighs' => 'quads',
        'quadriceps' => 'quads',
        'hamstring' => 'hamstrings',
        'glute' => 'glutes',
        'butt' => 'glutes',
        'lat' => 'lats',
        'trap' => 'traps',
        'tricep' => 'triceps',
        'bicep' => 'biceps',
        'calf' => 'calves',
        'abdominals' => 'core',
    ];

    public function ask(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $user = $request->user('sanctum');
        $parsed = $this->classify($validated['message']);

        if (!$parsed) {
            return response()->json($this->payload(
                "The assistant isn't available right now - is Ollama running?"
            ));
        }

        return response()->json($this->answer($parsed, $user));
    }

    /*
    |--------------------------------------------------------------------------
    | Classification (the only place the model is involved)
    |--------------------------------------------------------------------------
    */

    private function classify(string $message): ?array
    {
        $vocab = $this->vocabulary();
        $topics = array_merge(self::PUBLIC_TOPICS, self::MEMBER_TOPICS);

        $system = "You are FitTrack's assistant. Classify the user's question. Reply with JSON only.\n\n"
            . "\"topic\": one of [" . implode(', ', $topics) . "]\n"
            . "  exercises    - which exercises to do for a muscle group or piece of equipment\n"
            . "  routines     - training programmes, splits, plans\n"
            . "  articles     - guides, reading, written advice\n"
            . "  measurements - the user's own weight, body fat, waist, chest or arm measurements\n"
            . "  workouts     - the user's own logged workout sessions and history\n"
            . "  strength     - the user's personal best or progress on one specific lift\n"
            . "  saved        - what the user has saved or favourited\n"
            . "  stats        - the user's streak, weekly workout count, overall summary\n\n"
            . "\"reply\": one short friendly sentence\n"
            . "\"muscleGroup\": one of [" . implode(', ', $vocab['muscles']) . "] or null\n"
            . "\"equipment\": one of [" . implode(', ', $vocab['equipment']) . "] or null\n"
            . "\"goal\": one of [strength, hypertrophy, fat_loss, endurance, general_fitness] or null\n"
            . "\"level\": one of [beginner, intermediate, advanced] or null\n"
            . "\"daysPerWeek\": a whole number 1-7 or null\n"
            . "\"metric\": one of [weight, bodyFat, waist, chest, arms] or null\n"
            . "\"date\": a date or time phrase copied exactly from the message, or null\n"
            . "\"exerciseName\": the exercise the user named (e.g. \"bench press\"), or null\n"
            . "\"search\": keywords for finding an article, or null\n\n"
            . "Set a field to null unless the user's message actually implies it. "
            . "Never invent exercise names or numbers -- the app supplies real data separately.";

        try {
            $response = Http::timeout(45)
                ->post(config('services.ollama.url') . '/api/chat', [
                    'model' => config('services.ollama.model'),
                    'stream' => false,
                    'format' => 'json',
                    // Classification is not creative writing: we want the single
                    // most likely answer every time, not a sample from the
                    // distribution. Ollama defaults to 0.8, which makes the same
                    // question land on different topics between runs.
                    'options' => ['temperature' => 0],
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $message],
                    ],
                ]);

            if (!$response->successful()) {
                return null;
            }

            return json_decode($response->json('message.content'), true) ?: null;
        } catch (ConnectionException $e) {
            // Ollama isn't running, or the host/port is wrong.
            return null;
        }
    }

    /** Taxonomy slugs, read from the DB so the prompt can never drift from the data. */
    private function vocabulary(): array
    {
        return Cache::remember('chat.vocabulary', now()->addHour(), fn() => [
            'muscles' => MuscleGroup::orderBy('slug')->pluck('slug')->all(),
            'equipment' => Equipment::orderBy('slug')->pluck('slug')->all(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Dispatch
    |--------------------------------------------------------------------------
    */

    private function answer(array $parsed, $user): array
    {
        $topic = $parsed['topic'] ?? 'exercises';

        // One gate for every personal topic, rather than a check per handler.
        if (in_array($topic, self::MEMBER_TOPICS, true) && !$user) {
            return $this->payload(
                'Log in to ask about your own training data.',
                ['authRequired' => true]
            );
        }

        return match ($topic) {
            'routines' => $this->answerRoutines($parsed),
            'articles' => $this->answerArticles($parsed),
            'measurements' => $this->answerMeasurements($parsed, $user),
            'workouts' => $this->answerWorkouts($parsed, $user),
            'strength' => $this->answerStrength($parsed, $user),
            'saved' => $this->answerSaved($user),
            'stats' => $this->answerStats($user),
            default => $this->answerExercises($parsed),
        };
    }

    /** Every response has the same shape, so the frontend never branches on topic. */
    private function payload(string $reply, array $extra = []): array
    {
        return array_merge([
            'reply' => $reply,
            'exercises' => [],
            'routines' => [],
            'articles' => [],
            'measurements' => [],
            'workouts' => [],
            'authRequired' => false,
        ], $extra);
    }

    /*
    |--------------------------------------------------------------------------
    | Public topics
    |--------------------------------------------------------------------------
    */

    private function answerExercises(array $parsed): array
    {
        $vocab = $this->vocabulary();
        $muscle = $this->matchSlug($parsed['muscleGroup'] ?? null, $vocab['muscles'], self::MUSCLE_ALIASES);
        $equipment = $this->matchSlug($parsed['equipment'] ?? null, $vocab['equipment']);

        if (!$muscle && !$equipment) {
            return $this->payload('Tell me a muscle group and I\'ll pull some exercises -- try "chest" or "legs".');
        }

        $query = Exercise::with(['muscleGroups', 'equipment', 'media'])
            ->where('is_published', true);

        if ($muscle) {
            $query->whereHas('muscleGroups', fn($q) => $q->where('slug', $muscle));
        }
        if ($equipment) {
            $query->whereHas('equipment', fn($q) => $q->where('slug', $equipment));
        }

        $exercises = $query->take(3)->get();
        $what = $muscle ? str_replace('-', ' ', $muscle) : 'these';
        $with = $equipment ? ' using ' . str_replace('-', ' ', $equipment) : '';

        $reply = $exercises->isNotEmpty()
            ? "Here are a few {$what} exercises{$with}:"
            : "I couldn't find any {$what} exercises{$with}.";

        return $this->payload($reply, ['exercises' => $exercises]);
    }

    private function answerRoutines(array $parsed): array
    {
        $goal = $this->matchEnum($parsed['goal'] ?? null, RoutineGoal::cases());
        $level = $this->matchEnum($parsed['level'] ?? null, Difficulty::cases());
        $days = filter_var($parsed['daysPerWeek'] ?? null, FILTER_VALIDATE_INT);
        $days = ($days >= 1 && $days <= 7) ? $days : null;

        $query = Routine::with('creator:id,name')
            ->where('is_public', true)
            ->where('is_published', true);

        if ($goal) {
            $query->where('goal', $goal);
        }
        if ($level) {
            $query->where('level', $level);
        }
        if ($days) {
            $query->where('days_per_week', $days);
        }

        $routines = $query->take(3)->get();

        $filters = array_filter([
            $level,
            $goal ? str_replace('_', ' ', $goal) : null,
            $days ? "{$days}-day" : null,
        ]);
        $described = $filters ? implode(' ', $filters) . ' ' : '';

        $reply = $routines->isNotEmpty()
            ? "Here are some {$described}routines:"
            : "I couldn't find any {$described}routines -- try loosening the filters.";

        return $this->payload($reply, ['routines' => $routines]);
    }

    private function answerArticles(array $parsed): array
    {
        $search = trim((string) ($parsed['search'] ?? ''));

        $query = Article::with('author:id,name')->where('status', 'published');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $articles = ArticleSummaryResource::collection(
            $query->latest('published_at')->take(3)->get()
        )->resolve();

        $articles = collect($articles);

        // Distinguish "your search matched nothing" from "there's nothing here
        // at all" -- otherwise an empty library looks like a failed search.
        $libraryEmpty = $articles->isEmpty()
            && Article::where('status', 'published')->doesntExist();

        $reply = match (true) {
            $articles->isNotEmpty() && $search !== '' => "Here's what I found on \"{$search}\":",
            $articles->isNotEmpty() => 'Here are some recent articles:',
            $libraryEmpty => 'There are no published articles yet.',
            $search !== '' => "I couldn't find any articles about \"{$search}\".",
            default => "I couldn't find any articles.",
        };

        return $this->payload($reply, ['articles' => $articles]);
    }

    /*
    |--------------------------------------------------------------------------
    | Member topics (the user's own data)
    |--------------------------------------------------------------------------
    */

    private function answerMeasurements(array $parsed, $user): array
    {
        $records = $user->bodyMeasurements()->orderBy('date')->get();

        if ($records->isEmpty()) {
            return $this->payload("You haven't logged any measurements yet -- add one on the Measurements page.");
        }

        $metric = $this->matchMetric($parsed['metric'] ?? null);
        $targetDate = $this->parseDate($parsed['date'] ?? null);

        if ($targetDate) {
            // Closest logged date, not an exact match -- people don't log daily.
            $closest = $records->sortBy(fn($r) => abs($r->date->diffInDays($targetDate, false)))->first();

            return $this->payload(
                $this->describeEntry($closest, $metric),
                ['measurements' => collect([$closest])]
            );
        }

        [$reply, $measurements] = $this->describeHistory($records, $metric);

        return $this->payload($reply, ['measurements' => $measurements]);
    }

    private function answerWorkouts(array $parsed, $user): array
    {
        $total = $user->workoutLogs()->count();

        if ($total === 0) {
            return $this->payload("You haven't logged any workouts yet -- start one from the Builder.");
        }

        // "did I work out yesterday?" -- the classifier extracts the date, so
        // answer the question actually asked rather than dumping recent history.
        $targetDate = $this->parseDate($parsed['date'] ?? null);

        if ($targetDate) {
            $onDate = $user->workoutLogs()
                ->whereDate('date', $targetDate->toDateString())
                ->orderBy('date')
                ->get();

            $label = $targetDate->toFormattedDateString();

            if ($onDate->isEmpty()) {
                return $this->payload("No workouts logged on {$label}.");
            }

            $names = $onDate->pluck('name')->implode(', ');

            return $this->payload(
                "Yes -- on {$label} you logged {$names}.",
                ['workouts' => $this->presentWorkouts($onDate)]
            );
        }

        $logs = $user->workoutLogs()->orderByDesc('date')->take(5)->get();
        $latest = $logs->first();
        $duration = $latest->duration_minutes ? " ({$latest->duration_minutes} min)" : '';

        $reply = "You've logged {$total} workouts. Most recent: \"{$latest->name}\" on "
            . $latest->date->toFormattedDateString() . "{$duration}.";

        return $this->payload($reply, ['workouts' => $this->presentWorkouts($logs)]);
    }

    private function presentWorkouts($logs): array
    {
        return $logs->map(fn($log) => [
            'id' => $log->id,
            'name' => $log->name,
            'date' => $log->date->toDateString(),
            'durationMinutes' => $log->duration_minutes,
        ])->all();
    }

    private function answerStrength(array $parsed, $user): array
    {
        $name = trim((string) ($parsed['exerciseName'] ?? ''));

        if ($name === '') {
            return $this->payload('Which lift? Try "what\'s my best bench press?"');
        }

        $exercise = Exercise::with(['muscleGroups', 'equipment', 'media'])
            ->where('name', 'like', "%{$name}%")
            ->first();

        if (!$exercise) {
            return $this->payload("I couldn't find an exercise called \"{$name}\" in the library.");
        }

        // Heaviest single set the user has logged for this exercise. Joined
        // explicitly rather than via the HasManyThrough so we can select
        // columns from both tables.
        $best = SetLog::query()
            ->join('workout_logs', 'workout_logs.id', '=', 'set_logs.workout_log_id')
            ->where('workout_logs.user_id', $user->id)
            ->where('set_logs.exercise_id', $exercise->id)
            ->orderByDesc('set_logs.weight')
            ->select('set_logs.weight', 'set_logs.reps', 'workout_logs.date')
            ->first();

        if (!$best) {
            return $this->payload(
                "You haven't logged any sets for {$exercise->name} yet.",
                ['exercises' => collect([$exercise])]
            );
        }

        $date = Carbon::parse($best->date)->toFormattedDateString();
        $reply = "Your heaviest logged set on {$exercise->name} is {$best->weight} lbs x {$best->reps} reps, on {$date}.";

        // A record question wants a number, not a demo video. The card only
        // earns its place above, where there's no number to give and the
        // exercise itself is the useful answer.
        return $this->payload($reply);
    }

    private function answerSaved($user): array
    {
        $routines = $user->savedRoutines()->get();
        $exercises = $user->savedExercises()
            ->with(['muscleGroups', 'equipment', 'media'])
            ->get();

        if ($routines->isEmpty() && $exercises->isEmpty()) {
            return $this->payload("You haven't saved anything yet -- tap the bookmark on any exercise or routine.");
        }

        $parts = [];
        if ($exercises->isNotEmpty()) {
            $parts[] = $exercises->count() . ' ' . Str::plural('exercise', $exercises->count());
        }
        if ($routines->isNotEmpty()) {
            $parts[] = $routines->count() . ' ' . Str::plural('routine', $routines->count());
        }

        return $this->payload(
            'You have ' . implode(' and ', $parts) . ' saved:',
            [
                'exercises' => $exercises->take(3),
                'routines' => $routines->take(3),
            ]
        );
    }

    private function answerStats($user): array
    {
        $total = $user->workoutLogs()->count();

        if ($total === 0) {
            return $this->payload("You haven't logged any workouts yet, so there are no stats to show.");
        }

        $thisWeek = $user->workoutLogs()
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $streak = $this->currentStreak($user);
        $latestWeight = $user->bodyMeasurements()->latest('date')->value('weight');

        $reply = "{$total} workouts logged, {$thisWeek} this week"
            . ($streak > 0 ? ", current streak {$streak} " . Str::plural('day', $streak) : '')
            . ($latestWeight !== null ? ". Latest weight: {$latestWeight} lbs." : '.');

        return $this->payload($reply);
    }

    /** Consecutive days with at least one logged workout, counting back from today. */
    private function currentStreak($user): int
    {
        $dates = $user->workoutLogs()
            ->selectRaw('DATE(date) as log_date')
            ->distinct()
            ->orderByDesc('log_date')
            ->pluck('log_date');

        $streak = 0;
        $cursor = now()->startOfDay();

        foreach ($dates as $date) {
            $date = Carbon::parse($date);
            if ($date->equalTo($cursor) || $date->equalTo($cursor->copy()->subDay())) {
                $streak++;
                $cursor = $date->copy()->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    /*
    |--------------------------------------------------------------------------
    | Slot normalisation -- small models rarely echo our vocabulary exactly
    |--------------------------------------------------------------------------
    */

    /** Exact slug, then alias, then a containment match, else null. */
    private function matchSlug(?string $value, array $allowed, array $aliases = []): ?string
    {
        if (!$value) {
            return null;
        }

        $needle = Str::slug((string) $value);

        if (in_array($needle, $allowed, true)) {
            return $needle;
        }
        if (isset($aliases[$needle]) && in_array($aliases[$needle], $allowed, true)) {
            return $aliases[$needle];
        }
        foreach ($allowed as $slug) {
            if (str_contains($needle, $slug) || str_contains($slug, $needle)) {
                return $slug;
            }
        }

        return null;
    }

    /** @param  array<\BackedEnum>  $cases */
    private function matchEnum(?string $value, array $cases): ?string
    {
        if (!$value) {
            return null;
        }

        $needle = Str::of($value)->lower()->replace([' ', '-'], '_')->toString();

        foreach ($cases as $case) {
            if ($case->value === $needle || str_contains($needle, $case->value)) {
                return $case->value;
            }
        }

        return null;
    }

    private function matchMetric(?string $value): ?string
    {
        $raw = strtolower((string) $value);

        return match (true) {
            str_contains($raw, 'fat') => 'bodyFat',
            str_contains($raw, 'waist') => 'waist',
            str_contains($raw, 'chest') => 'chest',
            str_contains($raw, 'arm') => 'arms',
            str_contains($raw, 'weight') => 'weight',
            default => null,
        };
    }

    /**
     * The model only copies a date phrase out of the message -- it never has to
     * understand it. Carbon does the parsing, and junk fails safely to null.
     */
    private function parseDate(?string $text): ?Carbon
    {
        if (!$text) {
            return null;
        }

        try {
            return Carbon::parse($text);
        } catch (\Exception $e) {
            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Measurement phrasing
    |--------------------------------------------------------------------------
    */

    /**
     * The one place that knows how to read each metric: weight and body fat are
     * real columns, while waist/chest/arms live inside the custom_measurements
     * JSON column. Add new metrics here only.
     *
     * @return array{0: callable, 1: string, 2: string} [getter, label, unit]
     */
    private function metricAccessor(?string $metric): array
    {
        return match ($metric) {
            'bodyFat' => [fn($r) => $r->body_fat_pct, 'body fat', '%'],
            'waist' => [fn($r) => $r->custom_measurements['waist'] ?? null, 'waist', ' in'],
            'chest' => [fn($r) => $r->custom_measurements['chest'] ?? null, 'chest', ' in'],
            'arms' => [fn($r) => $r->custom_measurements['arms'] ?? null, 'arms', ' in'],
            default => [fn($r) => $r->weight, 'weight', ' lbs'],
        };
    }

    private function describeEntry($record, ?string $metric): string
    {
        $date = $record->date->toFormattedDateString();

        if ($metric) {
            [$get, $label, $unit] = $this->metricAccessor($metric);
            $value = $get($record);

            return $value !== null
                ? "On {$date} your {$label} was {$value}{$unit}."
                : "You didn't log {$label} on {$date}.";
        }

        $parts = [];
        if ($record->weight !== null) {
            $parts[] = "weight was {$record->weight} lbs";
        }
        if ($record->body_fat_pct !== null) {
            $parts[] = "body fat was {$record->body_fat_pct}%";
        }

        return $parts
            ? "On {$date}, your " . implode(' and ', $parts) . '.'
            : "You logged a measurement on {$date}, but it didn't include weight or body fat.";
    }

    private function describeHistory($records, ?string $metric): array
    {
        [$get, $label, $unit] = $this->metricAccessor($metric);

        $withValue = $records->filter(fn($r) => $get($r) !== null);

        if ($withValue->isEmpty()) {
            return ["You haven't logged {$label} in any of your measurements yet.", collect()];
        }

        $latestValue = $get($withValue->last());
        $change = round($latestValue - $get($withValue->first()), 1);
        $sign = $change > 0 ? '+' : '';

        $reply = "You've logged {$records->count()} measurements. "
            . "Latest {$label}: {$latestValue}{$unit} "
            . "({$sign}{$change}{$unit} since your first entry).";

        return [$reply, $records->sortByDesc('date')->take(5)->values()];
    }
}
