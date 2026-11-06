<?php

namespace App\Http\Controllers;

use App\Models\JobOpening;
use App\Support\SiteContent;
use App\Support\Verticals;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * Phase 0 marketing pages. They read no session or database so that
 * `php artisan export` can save them as static HTML.
 */
class SiteController extends Controller
{
    public function home(Request $request): View
    {
        // VRT-3: on a section's own domain, its landing page is the home page.
        $section = Verticals::forHost($request->getHost());
        if ($section !== null) {
            return match ($section) {
                'tuition' => $this->tuition(),
                'academic' => $this->academic(),
                default => $this->music(),
            };
        }

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

    public function music(): View
    {
        return view('site.music.index', [
            'content' => SiteContent::section('music'),
            'articles' => $this->articlesFor('music'),
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
    public function sitemap(Request $request): Response
    {
        // VRT-3: a section's own domain lists only its landing page; shared
        // pages are listed once, on the main domain. Sections on their own
        // domain are left out here, as Google wants one domain per sitemap.
        $section = Verticals::forHost($request->getHost());

        $urls = $section !== null
            ? collect([['loc' => url('/'), 'lastmod' => null]])
            : collect(['home'])
                ->merge(array_filter(Verticals::enabledKeys(), fn (string $key) => Verticals::domain($key) === null))
                ->merge(['resources.index', 'about', 'contact', 'privacy', 'terms', 'refunds'])
                ->map(fn (string $name) => ['loc' => route($name), 'lastmod' => null])
                ->merge(collect(SiteContent::articles())->map(fn (array $article) => [
                    'loc' => route('resources.show', $article['slug']),
                    'lastmod' => $article['published'],
                ]))
                ->merge($this->careersUrls());

        return response()
            ->view('site.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    /**
     * HIR-6: the Careers pages and each open job (FEATURE_CMS).
     *
     * @return Collection<int, array{loc: string, lastmod: string|null}>
     */
    private function careersUrls(): Collection
    {
        if (! config('features.cms')) {
            return collect();
        }

        return collect([['loc' => route('careers.index'), 'lastmod' => null]])
            ->merge(JobOpening::query()->acceptingApplications()->orderBy('sort_order')->get()
                ->map(fn (JobOpening $job) => ['loc' => route('careers.show', $job), 'lastmod' => $job->updated_at?->toDateString()]));
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
