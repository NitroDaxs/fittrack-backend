<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('email', 'admin@test.com')->first()
            ?? User::orderBy('id')->first();

        if (!$author) {
            $this->command->warn('No users found -- run DatabaseSeeder first.');

            return;
        }

        $definitions = require database_path('seeders/data/articles.php');

        // Stagger publish dates a week apart, newest first, so the listing's
        // "latest" ordering and the featured slot are stable and meaningful.
        foreach (array_values($definitions) as $index => $item) {
            Article::updateOrCreate(
                ['slug' => Str::slug($item['title'])],
                [
                    'title' => $item['title'],
                    'body' => json_encode($item['body']),
                    'category' => $item['category'],
                    'author_id' => $author->id,
                    'status' => 'published',
                    'published_at' => now()->subWeeks($index),
                ]
            );
        }

        $this->command->info('Seeded ' . count($definitions) . ' articles.');
    }
}
