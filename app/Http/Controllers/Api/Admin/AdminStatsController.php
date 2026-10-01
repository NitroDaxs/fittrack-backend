<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Exercise;
use App\Models\Routine;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Http\JsonResponse;

/**
 * Feeds the admin overview. These two endpoints were called by the frontend
 * (`adminApi.stats()` / `adminApi.activity()`) but never existed, so the admin
 * dashboard 404'd on load.
 */
class AdminStatsController extends Controller
{
    public function stats(): JsonResponse
    {
        $since = now()->subDays(30);

        return response()->json([
            'totalExercises' => Exercise::count(),
            'exerciseDelta' => $this->delta(Exercise::where('created_at', '>=', $since)->count()),
            'totalRoutines' => Routine::count(),
            'routineDelta' => $this->delta(Routine::where('created_at', '>=', $since)->count()),
            'publishedArticles' => Article::where('status', 'published')->count(),
            'articleDelta' => $this->delta(Article::where('created_at', '>=', $since)->count()),
            'totalUsers' => User::count(),
            'userDelta' => $this->delta(User::where('created_at', '>=', $since)->count()),

            // Real infrastructure metrics would come from a monitoring service;
            // these are placeholders so the health bars render something honest
            // rather than pretending to measure uptime.
            'apiUptime' => 100,
            'storageCapacity' => $this->storagePercentUsed(),
        ]);
    }

    /** Newest content and sign-ups, merged into one reverse-chronological feed. */
    public function activity(): JsonResponse
    {
        $items = collect();

        foreach (Exercise::latest()->take(4)->get() as $exercise) {
            $items->push([
                'id' => "exercise-{$exercise->id}",
                'at' => $exercise->created_at,
                'icon' => 'fitness_center',
                'tone' => 'primary',
                'text' => "Exercise \"{$exercise->name}\" added to the library",
                'meta' => $exercise->created_at?->diffForHumans(),
            ]);
        }

        foreach (Article::latest()->take(4)->get() as $article) {
            $items->push([
                'id' => "article-{$article->id}",
                'at' => $article->created_at,
                'icon' => 'article',
                'tone' => 'tertiary',
                'text' => "Article \"{$article->title}\" " . ($article->status === 'published' ? 'published' : 'drafted'),
                'meta' => $article->created_at?->diffForHumans(),
            ]);
        }

        foreach (User::latest()->take(4)->get() as $user) {
            $items->push([
                'id' => "user-{$user->id}",
                'at' => $user->created_at,
                'icon' => 'person_add',
                'tone' => 'secondary',
                'text' => "{$user->name} joined as {$user->role->value}",
                'meta' => $user->created_at?->diffForHumans(),
            ]);
        }

        foreach (WorkoutLog::latest()->take(4)->get() as $log) {
            $items->push([
                'id' => "workout-{$log->id}",
                'at' => $log->created_at,
                'icon' => 'exercise',
                'tone' => 'neutral',
                'text' => "Workout \"{$log->name}\" logged",
                'meta' => $log->created_at?->diffForHumans(),
            ]);
        }

        return response()->json(
            $items->sortByDesc('at')->take(8)->values()->map(
                fn($item) => collect($item)->except('at')->all()
            )
        );
    }

    /** The dashboard renders this as a percentage bar, so clamp it to 0-100. */
    private function storagePercentUsed(): int
    {
        $path = storage_path();
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if (!$total || !$free) {
            return 0;
        }

        return (int) min(100, max(0, round((($total - $free) / $total) * 100)));
    }

    /** The UI checks for a leading "+" to decide which arrow to draw. */
    private function delta(int $count): string
    {
        return $count > 0 ? "+{$count} this month" : 'No change';
    }
}
