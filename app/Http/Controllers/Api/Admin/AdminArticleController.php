<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleSummaryResource;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminArticleController extends Controller
{
    private const SORTABLE = ['title', 'category', 'status', 'published_at', 'created_at'];

    public function index(Request $request): JsonResponse
    {
        $query = Article::with('author:id,name,email');

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->input('search');
            $q->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        });

        $query->when($request->filled('status'), function ($q) use ($request) {
            $status = strtolower($request->input('status'));
            if (in_array($status, ['draft', 'published'], true)) {
                $q->where('status', $status);
            }
        });

        $sort = in_array($request->input('sort'), self::SORTABLE, true)
            ? $request->input('sort')
            : 'created_at';
        $dir = $request->input('dir') === 'asc' ? 'asc' : 'desc';

        $perPage = $request->input('perPage', $request->input('per_page', 20));

        $articles = $query->orderBy($sort, $dir)->paginate($perPage);

        // Same card shape as the public listing, so the admin table can show a
        // real excerpt and read time instead of a raw JSON body blob.
        return response()->json(
            ArticleSummaryResource::collection($articles)->response()->getData(true)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request, required: true);
        $status = $validated['status'] ?? 'draft';

        $article = Article::create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'body' => json_encode($this->toBlocks($validated)),
            'category' => $validated['category'],
            'author_id' => $request->user()->id,
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]);

        return response()->json(
            new ArticleSummaryResource($article->load('author:id,name')),
            201
        );
    }

    public function update(Request $request, Article $article): JsonResponse
    {
        $validated = $this->validatePayload($request, required: false);

        $changes = collect($validated)->only(['title', 'category', 'status'])->all();

        if (isset($validated['title'])) {
            $changes['slug'] = Str::slug($validated['title']);
        }

        // Only rewrite the body when the request actually carries content --
        // otherwise editing a title from the admin table would blank the
        // article's blocks.
        if (array_key_exists('body', $validated) || array_key_exists('excerpt', $validated)) {
            $changes['body'] = json_encode($this->toBlocks($validated));
        }

        // Stamp the publish date the first time it goes live, and clear it if
        // it's pulled back to draft.
        if (($validated['status'] ?? null) === 'published' && $article->status !== 'published') {
            $changes['published_at'] = now();
        } elseif (($validated['status'] ?? null) === 'draft') {
            $changes['published_at'] = null;
        }

        $article->update($changes);

        return response()->json(new ArticleSummaryResource($article->load('author:id,name')));
    }

    public function destroy(Article $article): JsonResponse
    {
        $article->delete();

        return response()->json(['status' => 'deleted']);
    }

    private function validatePayload(Request $request, bool $required): array
    {
        $presence = $required ? 'required' : 'sometimes';

        return $request->validate([
            'title' => "{$presence}|string|max:255",
            'category' => "{$presence}|in:nutrition,training_theory,recovery,mindset",
            'status' => 'sometimes|in:draft,published',
            // `body` may arrive as structured blocks (what the seeder and public
            // detail page use) or as plain prose typed into the admin textarea.
            'body' => 'sometimes|nullable',
            'excerpt' => 'sometimes|nullable|string',
        ]);
    }

    /**
     * Normalises whatever the caller sent into the block array the articles
     * table stores and ArticleResource decodes.
     */
    private function toBlocks(array $validated): array
    {
        $body = $validated['body'] ?? null;

        if (is_array($body) && $body !== []) {
            return $body;
        }

        $text = is_string($body) && trim($body) !== ''
            ? $body
            : (string) ($validated['excerpt'] ?? '');

        // Blank lines separate paragraphs; a line ending in a colon and short
        // enough to be a heading becomes one.
        return collect(preg_split('/\n\s*\n/', trim($text)))
            ->filter(fn($chunk) => trim($chunk) !== '')
            ->map(function ($chunk) {
                $chunk = trim($chunk);

                return Str::endsWith($chunk, ':') && Str::length($chunk) < 80
                    ? ['type' => 'h2', 'text' => rtrim($chunk, ':')]
                    : ['type' => 'p', 'text' => $chunk];
            })
            ->values()
            ->all() ?: [['type' => 'p', 'text' => '']];
    }
}
