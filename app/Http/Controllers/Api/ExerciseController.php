<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    public function index(Request $request)
    {
        $query = Exercise::with(['muscleGroups', 'equipment', 'media'])->where('is_published', true);

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->filled('exercise_type')) {
            $query->where('exercise_type', $request->exercise_type);
        }

        if ($request->filled('muscleGroup')) {
            $query->when($request->filled('muscleGroup'), function ($q) use ($request) {
                $muscleGroup = $request->input('muscleGroup');

                $q->whereHas('muscleGroups', function ($sub) use ($muscleGroup) {
                    $sub->where('id', $muscleGroup)
                        ->orWhere('slug', strtolower($muscleGroup))
                        ->orWhere('name', $muscleGroup);
                });
            });
        }

        if ($request->filled('equipment')) {
            $query->when($request->filled('equipment'), function ($q) use ($request) {
                $equipment = $request->input('equipment');

                $q->whereHas('equipment', function ($sub) use ($equipment) {
                    $sub->where('id', $equipment)
                        ->orWhere('slug', strtolower($equipment))
                        ->orWhere('name', $equipment);
                });
            });
        }

        $perPage = $request->input('perPage', $request->input('per_page', 8));

        return response()->json($query->paginate($perPage));
    }

    public function show($key)
    {
        $exercise = Exercise::with(['muscleGroups', 'equipment', 'media'])
            ->where('is_published', true)
            ->where(function ($q) use ($key) {
                $q->where('id', $key)->orWhere('slug', $key);
            })->firstOrFail();

        return response()->json($exercise);
    }
}
