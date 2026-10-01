<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Routine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoutineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Routine::with(['creator:id,name'])
            ->where('is_public', true)
            ->where('is_published', true);

        // 1. Search Query
        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->input('search');
            $q->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        });

        // 2. Goal / Goals (Handles arrays 'goals[]', 'goal[]', 'goals', or 'goal')
        $goals = $request->input('goals') ?? $request->input('goal');
        if (!empty($goals)) {
            $goalsArray = is_array($goals) ? $goals : explode(',', $goals);
            $query->whereIn('goal', $goalsArray);
        }

        // 3. Level / Levels (Handles arrays 'levels[]', 'level[]', 'levels', or 'level')
        $levels = $request->input('levels') ?? $request->input('level');
        if (!empty($levels)) {
            $levelsArray = is_array($levels) ? $levels : explode(',', $levels);
            $query->whereIn('level', $levelsArray);
        }

        // 4. Days per week (Handles 'daysPerWeek', 'days_per_week', 'days', and 5+ logic)
        $daysParam = $request->input('daysPerWeek') ?? $request->input('days_per_week') ?? $request->input('days');
        if (!empty($daysParam)) {
            // Handle array if frontend sends array of days (e.g. days[]=5)
            $daysVal = is_array($daysParam) ? reset($daysParam) : $daysParam;

            if ($daysVal == 5 || $daysVal == '5+') {
                $query->where('days_per_week', '>=', 5);
            } else {
                $query->where('days_per_week', $daysVal);
            }
        }

        $perPage = $request->input('perPage', $request->input('per_page', 8));

        return response()->json($query->paginate($perPage));
    }

    public function show(string $key): JsonResponse
    {
        $routine = Routine::with([
            'creator:id,name',
            'workouts.workoutExercises.exercise.muscleGroups',
            'workouts.workoutExercises.exercise.equipment',
        ])
            ->where('is_public', true)
            ->where('is_published', true)
            ->where(function ($q) use ($key) {
                $q->where('id', $key)->orWhere('slug', $key);
            })->firstOrFail();

        return response()->json($routine);
    }
}
