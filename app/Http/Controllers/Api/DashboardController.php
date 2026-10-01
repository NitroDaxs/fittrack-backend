<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\SetLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'stats' => [
                'current_streak' => $this->currentStreak($user),
                'workouts_this_week' => $this->workoutsThisWeek($user),
                'latest_body_weight' => $this->latestBodyWeight($user),
            ],
            'consistency' => $this->consistencyHeatmap($user),
            'measurement_trend' => $this->measurementTrend($user),
            'default_progression' => $this->defaultExerciseProgression($user),
        ]);
    }

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

    private function workoutsThisWeek($user): int
    {
        return $user->workoutLogs()
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
    }

    private function latestBodyWeight($user): ?float
    {
        return $user->bodyMeasurements()
            ->latest('date')
            ->value('weight');
    }

    private function consistencyHeatmap($user, int $days = 90): array
    {
        return $user->workoutLogs()
            ->selectRaw('DATE(date) as date, COUNT(*) as count')
            ->where('date', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'count' => (int) $row->count,
            ])
            ->toArray();
    }

    private function measurementTrend($user, int $limit = 30): array
    {
        return $user->bodyMeasurements()
            ->orderByDesc('date')
            ->limit($limit)
            ->get(['date', 'weight', 'body_fat_pct'])
            ->reverse()
            ->values()
            ->toArray();
    }

    private function defaultExerciseProgression($user): ?array
    {
        $exerciseId = $user->setLogs()
            ->select('exercise_id')
            ->groupBy('exercise_id')
            ->orderByRaw('COUNT(*) DESC')
            ->value('exercise_id');

        if (!$exerciseId) {
            return null;
        }

        return [
            'exercise' => Exercise::find($exerciseId)?->only(['id', 'name']),
            'data' => $this->progressionData($user, $exerciseId),
        ];
    }

    public function exerciseProgression(Request $request, Exercise $exercise): JsonResponse
    {
        return response()->json([
            'exercise' => $exercise->only(['id', 'name']),
            'data' => $this->progressionData($request->user(), $exercise->id),
        ]);
    }

    private function progressionData($user, int $exerciseId): array
    {
        // Query SetLog directly (rather than via the HasManyThrough relation,
        // which auto-adds a non-aggregated `laravel_through_key` column that
        // breaks only_full_group_by) with an explicit join for the user scope.
        return SetLog::query()
            ->join('workout_logs', 'workout_logs.id', '=', 'set_logs.workout_log_id')
            ->where('workout_logs.user_id', $user->id)
            ->where('set_logs.exercise_id', $exerciseId)
            ->selectRaw('DATE(workout_logs.date) as date, MAX(set_logs.weight) as max_weight, MAX(ROUND(set_logs.weight * (1 + set_logs.reps / 30), 2)) as estimated_1rm')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}
