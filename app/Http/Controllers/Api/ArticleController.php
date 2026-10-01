<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\ArticleSummaryResource;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Article::with('author:id,name')->where('status', 'published');

        // Both were previously ignored, so the listing's search box and category
        // chips had no effect on the results.
        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->input('search');
            $q->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        });

        $query->when($request->filled('category'), function ($q) use ($request) {
            $q->where('category', $request->input('category'));
        });

        $perPage = $request->input('perPage', $request->input('per_page', 12));

        $articles = $query->latest('published_at')->paginate($perPage);

        return response()->json(
            ArticleSummaryResource::collection($articles)->response()->getData(true)
        );
    }

    public function show(string $key): JsonResponse
    {
        $article = Article::with('author:id,name')
            ->where('status', 'published')
            ->where(fn($q) => $q->where('id', $key)->orWhere('slug', $key))
            ->firstOrFail();

        // Same category first, then anything else recent, so a thin category
        // still fills the "Related" row.
        $related = Article::with('author:id,name')
            ->where('status', 'published')
            ->whereKeyNot($article->id)
            ->orderByRaw('category = ? DESC', [$article->category])
            ->latest('published_at')
            ->take(3)
            ->get();

        $article->setAttribute('related', $related);

        return response()->json(new ArticleResource($article));
    }
}
