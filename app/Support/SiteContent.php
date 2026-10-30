<?php

namespace App\Support;

use App\Enums\AcademicLevel;
use App\Enums\Offering;
use App\Models\Article;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\SiteSection;
use App\Models\Teacher;
use App\Models\Testimonial;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Site content. In Phases 0–1 it lives in resources/content as PHP arrays
 * and Markdown files, so the pages export as static HTML. With FEATURE_CMS
 * on (M4) the same shapes come from the database, where the admin edits
 * them; ContentSeeder copies the files in.
 */
final class SiteContent
{
    public static function fromDatabase(): bool
    {
        return (bool) config('features.cms');
    }

    /**
     * @return array<string, mixed>
     */
    public static function section(string $name): array
    {
        $content = self::fromDatabase()
            ? (SiteSection::query()->where('key', $name)->value('content') ?? self::sectionFile($name))
            : self::sectionFile($name);

        if (is_string($content)) {
            $content = json_decode($content, true);
        }

        /** @var array<string, mixed> $content */
        if (self::fromDatabase()) {
            $content = self::withNewKeys($content, self::sectionFile($name));
        }

        if (self::fromDatabase() && in_array($name, ['tuition', 'academic'], true)) {
            $content[$name === 'tuition' ? 'teachers' : 'experts'] = self::people($name);
            $content['testimonials'] = self::testimonials($name);
        }

        // M6: with online orders on, the academic price list comes from the
        // services and add-ons the admin manages (ACA-1, ACA-2).
        if ($name === 'academic' && config('features.academic_orders') && Service::query()->exists()) {
            $content['services'] = self::priceList();
            $content['addons'] = array_values(ServiceAddon::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn (ServiceAddon $addon) => ['name' => $addon->name, 'price' => $addon->price, 'unit' => $addon->unit->label()])->all());
        }

        return $content;
    }

    /**
     * Adds text the admin hasn't seen yet (a new heading or button added to
     * the files after the page was copied into the database), without
     * touching anything the admin edited.
     *
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $file
     * @return array<string, mixed>
     */
    public static function withNewKeys(array $content, array $file): array
    {
        if (is_array($file['text'] ?? null)) {
            $content['text'] = (is_array($content['text'] ?? null) ? $content['text'] : []) + $file['text'];
        }

        return $content + $file;
    }

    /**
     * @return list<array{name: string, body: string, prices: list<int|null>, turnaround: string, revisions: int|null}>
     */
    private static function priceList(): array
    {
        return array_values(Service::query()->active()->get()->map(fn (Service $service) => [
            'name' => $service->title,
            'body' => (string) $service->description,
            'prices' => array_map(fn (AcademicLevel $level) => $service->priceFor($level), AcademicLevel::cases()),
            'turnaround' => (string) $service->turnaround,
            'revisions' => $service->free_revisions,
        ])->all());
    }

    /**
     * @return array<string, mixed>
     */
    public static function sectionFile(string $name): array
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
        if (self::fromDatabase()) {
            return array_values(Article::query()->published()->latest('published_on')->latest('id')->get()
                ->map(fn (Article $article) => [
                    'slug' => $article->slug,
                    'title' => $article->title,
                    'description' => $article->description,
                    'offering' => $article->offering,
                    'published' => $article->published_on->toDateString(),
                ])->all());
        }

        return self::articleFiles();
    }

    /**
     * @return list<array{slug: string, title: string, description: string, offering: string, published: string}>
     */
    public static function articleFiles(): array
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
                $markdown = self::fromDatabase()
                    ? (string) Article::query()->where('slug', $slug)->value('body')
                    : self::articleMarkdown($slug);

                return $article + ['html' => Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false])];
            }
        }

        throw new NotFoundHttpException;
    }

    public static function articleMarkdown(string $slug): string
    {
        return (string) file_get_contents(resource_path("content/resources/{$slug}.md"));
    }

    /**
     * Teachers (tuition) or experts (academic) the admin chose to show.
     *
     * @return list<array{name: string, subjects: string, bio: string}>
     */
    private static function people(string $offering): array
    {
        return array_values(Teacher::query()
            ->with('user')
            ->where('show_on_site', true)
            ->whereIn('offering', [$offering, Offering::Both->value])
            ->where(fn ($query) => $query->whereNull('active_to')->orWhereDate('active_to', '>=', now()))
            ->whereHas('user', fn ($query) => $query->where('status', 'active'))
            ->get()
            ->map(fn (Teacher $teacher) => [
                'name' => $teacher->user->name,
                'subjects' => implode(', ', $teacher->subjects ?? []),
                'bio' => (string) $teacher->bio,
            ])->all());
    }

    /**
     * @return list<array{quote: string, name: string, detail: string}>
     */
    private static function testimonials(string $offering): array
    {
        return array_values(Testimonial::query()
            ->where('offering', $offering)
            ->where('is_published', true)
            ->orderBy('sort')
            ->get()
            ->map(fn (Testimonial $testimonial) => [
                'quote' => $testimonial->quote,
                'name' => $testimonial->name,
                'detail' => (string) $testimonial->detail,
            ])->all());
    }
}
