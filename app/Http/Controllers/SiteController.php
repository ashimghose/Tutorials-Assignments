<?php

namespace App\Http\Controllers;

use App\Support\SiteContent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * Phase 0 marketing pages. They read no session or database so that
 * `php artisan export` can save them as static HTML.
 */
class SiteController extends Controller
{
    public function home(): View
    {
        return view('site.home', [
            'content' => SiteContent::section('home'),
            'articles' => array_slice(SiteContent::articles(), 0, 3),
        ]);
    }

    public function tuition(): View
    {
        return view('site.tuition.index', [
            'content' => SiteContent::section('tuition'),
            'articles' => $this->articlesFor('tuition'),
        ]);
    }

    public function academic(): View
    {
        return view('site.academic.index', [
            'content' => SiteContent::section('academic'),
            'articles' => $this->articlesFor('academic'),
        ]);
    }

    public function resources(): View
    {
        return view('site.resources.index', [
            'articles' => SiteContent::articles(),
        ]);
    }

    public function article(string $slug): View
    {
        return view('site.resources.show', [
            'article' => SiteContent::article($slug),
        ]);
    }

    /**
     * CMS-4: sitemap of every public page.
     */
    public function sitemap(): Response
    {
        $urls = collect(['home', 'tuition', 'academic', 'resources.index', 'about', 'contact', 'privacy', 'terms', 'refunds'])
            ->map(fn (string $name) => route($name))
            ->merge(collect(SiteContent::articles())->map(fn (array $article) => route('resources.show', $article['slug'])));

        return response()
            ->view('site.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    /**
     * @return list<array{slug: string, title: string, description: string, offering: string, published: string}>
     */
    private function articlesFor(string $offering): array
    {
        return array_values(array_filter(
            SiteContent::articles(),
            fn (array $article) => $article['offering'] === $offering,
        ));
    }
}
