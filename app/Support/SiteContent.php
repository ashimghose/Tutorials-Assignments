<?php

namespace App\Support;

use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Phase 0 site content lives in resources/content as PHP arrays and Markdown
 * files, so M4 can seed the CMS tables from them instead of retyping.
 */
final class SiteContent
{
    /**
     * @return array<string, mixed>
     */
    public static function section(string $name): array
    {
        /** @var array<string, mixed> $content */
        $content = require resource_path("content/{$name}.php");

        return $content;
    }

    /**
     * @return list<array{slug: string, title: string, description: string, offering: string, published: string}>
     */
    public static function articles(): array
    {
        /** @var list<array{slug: string, title: string, description: string, offering: string, published: string}> $articles */
        $articles = require resource_path('content/resources.php');

        return $articles;
    }

    /**
     * @return array{slug: string, title: string, description: string, offering: string, published: string, html: string}
     */
    public static function article(string $slug): array
    {
        foreach (self::articles() as $article) {
            if ($article['slug'] === $slug) {
                $markdown = (string) file_get_contents(resource_path("content/resources/{$slug}.md"));

                return $article + ['html' => Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false])];
            }
        }

        throw new NotFoundHttpException;
    }
}
