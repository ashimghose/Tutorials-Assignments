<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\SiteSection;
use App\Support\SiteContent;
use Illuminate\Database\Seeder;

/**
 * M4: copies the Phase 0 content files (resources/content) into the
 * database, so nothing is retyped. It never overwrites what the admin has
 * edited; it only adds pages and text that are new in the files.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SiteSection::KEYS as $key) {
            $content = SiteContent::sectionFile($key);
            // People and testimonials come from their own tables.
            unset($content['teachers'], $content['experts'], $content['testimonials']);

            $section = SiteSection::query()->firstOrCreate(['key' => $key], ['content' => $content]);
            $merged = SiteContent::withNewKeys($section->content, $content);
            if ($merged !== $section->content) {
                $section->update(['content' => $merged]);
            }
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
