<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Card shape for listings and chat results.
 *
 * The articles table stores `body` as a JSON array of blocks and has no
 * excerpt, read time, or flat author name -- ArticleCard needs all three, so
 * they're derived here rather than in each caller. Notably `author` must be a
 * string: passing the raw relation (an object) into the React tree throws.
 */
class ArticleSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $blocks = static::blocks($this->body);
        $plain = static::plainText($blocks);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'category' => $this->category,
            // The admin table shows a draft/published pill; the public listing
            // only ever sees published rows, so this is harmless there.
            'status' => $this->status,
            'excerpt' => Str::limit($plain, 160),
            'author' => $this->author?->name ?? 'FitTrack',
            // Drafts have no published_at, so fall back to created_at rather
            // than rendering "Invalid Date" in the admin table.
            'date' => ($this->published_at ?? $this->created_at)?->toDateString(),
            'readMinutes' => static::readMinutes($plain),
            'image' => '',
        ];
    }

    /** Body is stored as a JSON block array; tolerate plain text from older rows. */
    public static function blocks($body): array
    {
        if (is_array($body)) {
            return $body;
        }

        $decoded = json_decode((string) $body, true);

        return is_array($decoded)
            ? $decoded
            : [['type' => 'p', 'text' => (string) $body]];
    }

    public static function plainText(array $blocks): string
    {
        return collect($blocks)
            ->map(fn($block) => is_array($block) ? ($block['text'] ?? '') : '')
            ->filter()
            ->implode(' ');
    }

    /** 200 wpm is the usual reading-speed assumption for this kind of estimate. */
    public static function readMinutes(string $plain): int
    {
        return max(1, (int) ceil(str_word_count($plain) / 200));
    }
}
