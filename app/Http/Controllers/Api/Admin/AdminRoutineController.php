<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Routine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminRoutineController extends Controller
{
    /** Columns the admin table is allowed to sort by. */
    private const SORTABLE = ['title', 'goal', 'level', 'days_per_week', 'created_at'];

    public function index(Request $request): JsonResponse
    {
        $query = Routine::with(['creator:id,name', 'workouts.workoutExercises.exercise']);

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->input('search');
            $q->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        });

        $query->when($request->filled('goal'), fn($q) => $q->where('goal', Str::snake(strtolower($request->input('goal')))));
        $query->when($request->filled('level'), fn($q) => $q->where('level', strtolower($request->input('level'))));

        $sort = in_array($request->input('sort'), self::SORTABLE, true)
            ? $request->input('sort')
            : 'created_at';
        $dir = $request->input('dir') === 'asc' ? 'asc' : 'desc';

        $perPage = $request->input('perPage', $request->input('per_page', 20));

        return response()->json($query->orderBy($sort, $dir)->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'goal' => 'required|in:strength,hypertrophy,fat_loss,endurance,general_fitness',
            'level' => 'required|in:beginner,intermediate,advanced',
            'days_per_week' => 'required|integer|min:1|max:7',
            'is_public' => 'boolean',
            ...$this->dayRules(),
        ]);

        $routine = Routine::create([
            ...collect($validated)->except('days')->toArray(),
            'slug' => Str::slug($validated['title']),
            'created_by' => $request->user()->id,
        ]);

        if (array_key_exists('days', $validated)) {
            $this->syncDays($routine, $validated['days'], $request->user()->id);
        }

        return response()->json($this->withDays($routine), 201);
    }

    /** Loads a routine with its days and their exercises, for the admin builder. */
    public function show(Routine $routine): JsonResponse
    {
        return response()->json($this->withDays($routine));
    }

    public function update(Request $request, Routine $routine): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'goal' => 'sometimes|in:strength,hypertrophy,fat_loss,endurance,general_fitness',
            'level' => 'sometimes|in:beginner,intermediate,advanced',
            'days_per_week' => 'sometimes|integer|min:1|max:7',
            'is_public' => 'boolean',
            ...$this->dayRules(),
        ]);

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $routine->update(collect($validated)->except('days')->toArray());

        // Absent `days` means "leave the programme alone" -- editing a title from
        // the table must not wipe the schedule.
        if (array_key_exists('days', $validated)) {
            $this->syncDays($routine, $validated['days'], $request->user()->id);
        }

        return response()->json($this->withDays($routine->fresh()));
    }

    public function destroy(Routine $routine): JsonResponse
    {
        $routine->delete();

        return response()->json(['status' => 'deleted']);
    }

    /**
     * Validation for the nested programme. Exercises are referenced by id
     * because the builder picks them from the library rather than typing names.
     */
    private function dayRules(): array
    {
        return [
            'days' => 'sometimes|array',
            'days.*.name' => 'required|string|max:255',
            'days.*.exercises' => 'sometimes|array',
            'days.*.exercises.*.exercise_id' => 'required|exists:exercises,id',
            'days.*.exercises.*.target_sets' => 'nullable|integer|min:1|max:20',
            'days.*.exercises.*.target_reps' => 'nullable|string|max:20',
            'days.*.exercises.*.target_weight' => 'nullable|numeric|min:0',
            'days.*.exercises.*.rest_seconds' => 'nullable|integer|min:0|max:900',
            'days.*.exercises.*.notes' => 'nullable|string',
        ];
    }

    /**
     * Replaces the whole programme in one transaction: the builder always sends
     * the complete set of days, so a partial write can't leave a routine with
     * half a schedule. `day_order` and `order` come from array position, which
     * is what the drag-to-reorder UI manipulates.
     */
    private function syncDays(Routine $routine, array $days, int $userId): void
    {
        DB::transaction(function () use ($routine, $days, $userId) {
            // workout_exercises is cascadeOnDelete from workouts, so dropping the
            // days takes their exercises with them.
            $routine->workouts()->delete();

            foreach (array_values($days) as $dayIndex => $day) {
                $workout = $routine->workouts()->create([
                    'name' => $day['name'],
                    'day_order' => $dayIndex + 1,
                    'created_by' => $userId,
                    'is_template' => true,
                ]);

                foreach (array_values($day['exercises'] ?? []) as $exerciseIndex => $entry) {
                    $workout->workoutExercises()->create([
                        'exercise_id' => $entry['exercise_id'],
                        'order' => $exerciseIndex + 1,
                        'target_sets' => $entry['target_sets'] ?? null,
                        'target_reps' => $entry['target_reps'] ?? null,
                        'target_weight' => $entry['target_weight'] ?? null,
                        'rest_seconds' => $entry['rest_seconds'] ?? null,
                        'notes' => $entry['notes'] ?? null,
                    ]);
                }
            }
        });
    }

    private function withDays(Routine $routine): Routine
    {
        return $routine->load([
            'creator:id,name',
            'workouts.workoutExercises.exercise:id,name,slug,thumbnail_url',
        ]);
    }
}
