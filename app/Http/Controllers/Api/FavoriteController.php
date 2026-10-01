<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\Routine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function saveRoutine(Request $request, Routine $routine): JsonResponse
    {
        $request->user()->savedRoutines()->syncWithoutDetaching([$routine->id]);
        return response()->json(['message' => 'Routine saved successfully']);
    }

    public function unsaveRoutine(Request $request, Routine $routine): JsonResponse
    {
        $request->user()->savedRoutines()->detach($routine->id);
        return response()->json(['message' => 'Routine unsaved successfully']);
    }

    public function saveExercise(Request $request, Exercise $exercise): JsonResponse
    {
        $request->user()->savedExercises()->syncWithoutDetaching([$exercise->id]);
        return response()->json(['message' => 'Exercise saved successfully']);
    }

    public function unsaveExercise(Request $request, Exercise $exercise): JsonResponse
    {
        $request->user()->savedExercises()->detach($exercise->id);
        return response()->json(['message' => 'Exercise unsaved successfully']);
    }
}
