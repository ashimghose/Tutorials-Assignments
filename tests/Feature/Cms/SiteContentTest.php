<?php

use App\Models\Article;
use App\Models\SiteSection;
use App\Models\Teacher;
use App\Models\Testimonial;
use App\Support\SiteContent;
use Database\Seeders\ContentSeeder;

beforeEach(function () {
    $this->seed(ContentSeeder::class);
});

function setSection(string $key, callable $change): void
{
    $section = SiteSection::where('key', $key)->sole();
    $content = $section->content;
    $change($content);
    $section->update(['content' => $content]);
}

test('M4 the seeder copies the Phase 0 content files into the database', function () {
    expect(SiteSection::pluck('key')->sort()->values()->all())->toBe(['academic', 'home', 'tuition'])
        ->and(Article::count())->toBe(count(SiteContent::articleFiles()));

    $tuition = SiteSection::where('key', 'tuition')->sole()->content;
    expect($tuition['plans'])->toBe(SiteContent::sectionFile('tuition')['plans'])
        ->and($tuition)->not->toHaveKey('teachers');

    $article = Article::where('slug', 'how-to-structure-a-synopsis')->sole();
    expect($article->body)->toBe(SiteContent::articleMarkdown('how-to-structure-a-synopsis'));
});

test('running the seeder again keeps the admin\'s edits', function () {
    setSection('tuition', fn (array &$content) => $content['hero']['title'] = 'Edited by the admin');

    $this->seed(ContentSeeder::class);

    expect(SiteSection::where('key', 'tuition')->sole()->content['hero']['title'])->toBe('Edited by the admin');
});

test('CMS-1 with FEATURE_CMS on, the pages show what the admin edited', function () {
    config(['features.cms' => true]);
    setSection('tuition', fn (array &$content) => $content['hero']['title'] = 'Small batches, big results');
    setSection('home', function (array &$content) {
        $content['hero']['title'] = 'A new home headline';
        $content['banner'] = ['text' => 'Admissions open for April', 'link' => ''];
    });

    $this->get(route('tuition'))->assertOk()->assertSee('Small batches, big results');
    $this->get(route('home'))->assertOk()->assertSee('A new home headline')->assertSee('Admissions open for April');
});

test('with FEATURE_CMS off, the pages still come from the files', function () {
    setSection('tuition', fn (array &$content) => $content['hero']['title'] = 'Only in the database');

    $this->get(route('tuition'))->assertOk()->assertDontSee('Only in the database')
        ->assertSee(SiteContent::sectionFile('tuition')['hero']['title']);
});

test('CMS-1 published testimonials and teachers chosen for the site appear on their section', function () {
    config(['features.cms' => true]);
    Testimonial::create(['offering' => 'tuition', 'quote' => 'My daughter loves maths now.', 'name' => 'Priya S.', 'is_published' => true]);
    Testimonial::create(['offering' => 'tuition', 'quote' => 'Not agreed yet.', 'name' => 'Ravi K.', 'is_published' => false]);
    $teacher = Teacher::factory()->create(['bio' => 'Twelve years teaching CBSE maths.', 'show_on_site' => true]);
    Teacher::factory()->create(['bio' => 'Hidden teacher bio.', 'show_on_site' => false]);

    $this->get(route('tuition'))
        ->assertSee('My daughter loves maths now.')
        ->assertDontSee('Not agreed yet.')
        ->assertSee($teacher->user->name)
        ->assertSee('Twelve years teaching CBSE maths.')
        ->assertDontSee('Hidden teacher bio.');

    $this->get(route('academic'))->assertDontSee('My daughter loves maths now.');
});

test('MKT-6 guides come from the database, and unpublished or future ones stay hidden', function () {
    config(['features.cms' => true]);
    Article::factory()->create(['slug' => 'live-guide', 'title' => 'A live guide']);
    Article::factory()->create(['slug' => 'draft-guide', 'title' => 'A draft guide', 'is_published' => false]);
    Article::factory()->create(['slug' => 'future-guide', 'title' => 'A future guide', 'published_on' => now()->addWeek()]);

    $this->get(route('resources.index'))->assertSee('A live guide')->assertDontSee('A draft guide')->assertDontSee('A future guide');
    $this->get(route('resources.show', 'live-guide'))->assertOk();
    $this->get(route('resources.show', 'draft-guide'))->assertNotFound();
    $this->get(route('sitemap'))->assertSee(route('resources.show', 'live-guide'))->assertDontSee('draft-guide');
});
