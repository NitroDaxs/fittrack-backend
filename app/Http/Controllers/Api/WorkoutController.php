<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SetLog;
use App\Models\WorkoutLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = $request->user()->workoutLogs()
            ->with('setLogs.exercise')
            ->orderByDesc('date')
            ->get();

        return response()->json($logs);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'workout_id' => 'nullable|exists:workouts,id',
            'duration_minutes' => 'nullable|integer|min:0',
            'date' => 'nullable|date',
            'notes' => 'nullable|string',
            'exercises' => 'required|array|min:1',
            'exercises.*.exercise_id' => 'required|exists:exercises,id',
            'exercises.*.sets' => 'required|array|min:1',
            'exercises.*.sets.*.weight' => 'nullable|numeric|min:0',
            'exercises.*.sets.*.reps' => 'nullable|integer|min:0',
        ]);

        $log = $request->user()->workoutLogs()->create([
            'workout_id' => $validated['workout_id'] ?? null,
            'name' => $validated['name'],
            'date' => $validated['date'] ?? now(),
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['exercises'] as $exercise) {
            foreach ($exercise['sets'] as $index => $set) {
                $log->setLogs()->create([
                    'exercise_id' => $exercise['exercise_id'],
                    'set_number' => $index + 1,
                    'weight' => $set['weight'] ?? 0,
                    'reps' => $set['reps'] ?? 0,
                    'is_completed' => true,
                ]);
            }
        }

        return response()->json(
            $log->load('setLogs.exercise'),
            201
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $log = $request->user()->workoutLogs()
            ->with('setLogs.exercise')
            ->findOrFail($id);

        return response()->json($log);
    }
}
