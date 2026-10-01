<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminExerciseController extends Controller
{
    /** Columns the admin table is allowed to sort by. */
    private const SORTABLE = ['name', 'difficulty', 'exercise_type', 'created_at'];

    public function index(Request $request): JsonResponse
    {
        // Was eager-loading `primaryMuscles`/`secondaryMuscles`, which are not
        // relations on Exercise -- the model has a single `muscleGroups()`
        // belongsToMany carrying a `role` pivot. That 500'd every request.
        $query = Exercise::with(['muscleGroups', 'equipment', 'media']);

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->input('search');
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        });

        $query->when($request->filled('category'), function ($q) use ($request) {
            $q->where('exercise_type', Str::snake(strtolower($request->input('category'))));
        });

        $sort = in_array($request->input('sort'), self::SORTABLE, true)
            ? $request->input('sort')
            : 'created_at';
        $dir = $request->input('dir') === 'asc' ? 'asc' : 'desc';

        $perPage = $request->input('perPage', $request->input('per_page', 20));

        return response()->json($query->orderBy($sort, $dir)->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request, required: true);

        $exercise = Exercise::create([
            ...collect($validated)->except(['muscle_groups', 'primary_muscles', 'equipment'])->toArray(),
            'slug' => Str::slug($validated['name']),
            'created_by' => $request->user()->id,
            'is_published' => $validated['is_published'] ?? true,
        ]);

        $this->syncRelations($exercise, $validated);

        return response()->json($exercise->load(['muscleGroups', 'equipment', 'media']), 201);
    }

    public function update(Request $request, Exercise $exercise): JsonResponse
    {
        $validated = $this->validatePayload($request, required: false);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $exercise->update(
            collect($validated)->except(['muscle_groups', 'primary_muscles', 'equipment'])->toArray()
        );

        $this->syncRelations($exercise, $validated);

        return response()->json($exercise->load(['muscleGroups', 'equipment', 'media']));
    }

    public function destroy(Exercise $exercise): JsonResponse
    {
        $exercise->delete();

        return response()->json(['status' => 'deleted']);
    }

    private function validatePayload(Request $request, bool $required): array
    {
        $presence = $required ? 'required' : 'sometimes';

        return $request->validate([
            'name' => "{$presence}|string|max:255",
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            // Enum values come from the App\Enums classes -- the previous
            // ruleset allowed "mobility"/"balance", which the exercise_type
            // column rejects, and disallowed real values like "stretching".
            'difficulty' => "{$presence}|in:beginner,intermediate,advanced",
            'exercise_type' => "{$presence}|in:strength,cardio,stretching,plyometrics,powerlifting,olympic_weightlifting,strongman",
            'video_url' => 'nullable|url',
            'thumbnail_url' => 'nullable|url',
            'is_published' => 'sometimes|boolean',
            // Taxonomy is accepted as slugs or names rather than raw ids, so the
            // admin UI can send what it already has from /taxonomies.
            'muscle_groups' => 'sometimes|array',
            'muscle_groups.*' => 'string',
            'primary_muscles' => 'sometimes|array',
            'primary_muscles.*' => 'string',
            'equipment' => 'sometimes|array',
            'equipment.*' => 'string',
        ]);
    }

    /**
     * Attaches muscle groups with the correct pivot role and equipment, both
     * resolved by slug or name so callers never need internal ids.
     */
    private function syncRelations(Exercise $exercise, array $validated): void
    {
        if (array_key_exists('muscle_groups', $validated)) {
            $primaryNames = collect($validated['primary_muscles'] ?? [])
                ->map(fn($value) => Str::slug($value));

            $groups = MuscleGroup::query()
                ->whereIn('slug', collect($validated['muscle_groups'])->map(fn($v) => Str::slug($v)))
                ->get();

            $exercise->muscleGroups()->sync(
                $groups->mapWithKeys(fn($group) => [
                    $group->id => ['role' => $primaryNames->contains($group->slug) ? 'primary' : 'secondary'],
                ])->all()
            );
        }

        if (array_key_exists('equipment', $validated)) {
            $ids = Equipment::query()
                ->whereIn('slug', collect($validated['equipment'])->map(fn($v) => Str::slug($v)))
                ->pluck('id');

            $exercise->equipment()->sync($ids);
        }
    }
}
