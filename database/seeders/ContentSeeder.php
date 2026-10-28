<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\SiteSection;
use App\Support\SiteContent;
use Illuminate\Database\Seeder;

/**
 * M4: copies the Phase 0 content files (resources/content) into the
 * database, so nothing is retyped. Existing rows are left alone, so it never
 * overwrites what the admin has edited.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SiteSection::KEYS as $key) {
            $content = SiteContent::sectionFile($key);
            // People and testimonials come from their own tables.
            unset($content['teachers'], $content['experts'], $content['testimonials']);

            SiteSection::query()->firstOrCreate(['key' => $key], ['content' => $content]);
        }

        foreach (SiteContent::articleFiles() as $article) {
            Article::query()->firstOrCreate(['slug' => $article['slug']], [
                'title' => $article['title'],
                'description' => $article['description'],
                'offering' => $article['offering'],
                'body' => SiteContent::articleMarkdown($article['slug']),
                'published_on' => $article['published'],
                'is_published' => true,
            ]);
        }
    }
}
