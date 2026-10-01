<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Full article for the detail page: everything on the card, plus the decoded
 * body blocks and a few related reads.
 */
class ArticleResource extends ArticleSummaryResource
{
    public function toArray(Request $request): array
    {
        $blocks = static::blocks($this->body);

        return array_merge(parent::toArray($request), [
            'body' => $blocks,
            'related' => ArticleSummaryResource::collection(
                $this->related ?? collect()
            )->resolve(),
        ]);
    }
}
