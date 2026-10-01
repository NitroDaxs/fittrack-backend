<?php

namespace App\Http\Controllers\Api;

use App\Enums\ArticleCategory;
use App\Enums\Difficulty;
use App\Enums\ExerciseCategory;
use App\Enums\RoutineGoal;
use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\MuscleGroup;
use Illuminate\Http\JsonResponse;

class TaxonomyController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'muscles' => MuscleGroup::select('id', 'name', 'slug')->get(),
            'equipment' => Equipment::select('id', 'name', 'slug')->get(),
            'difficulties' => Difficulty::toArray(),
            'categories' => ExerciseCategory::toArray(),
            'articleCategories' => ArticleCategory::toArray(),
            'goals' => RoutineGoal::toArray(),
        ]);
    }
}
