<?php

use App\Models\SiteSection;
use App\Support\SiteContent;

function assignmentsMigration(): object
{
    return require database_path('migrations/2026_11_04_100001_bring_back_academic_assignments.php');
}

test('MKT-1 the academic page has an Assignments box from school to PhD', function () {
    config(['site.whatsapp.numbers.academic' => '919958067504']);
    $assignments = SiteContent::sectionFile('academic')['assignments'];

    $this->get('/academic')->assertOk()
        ->assertSee('id="assignments"', false)
        ->assertSee('>Assignments: school to PhD</li>', false)
        ->assertSee($assignments['college_title'])
        ->assertSee($assignments['school_title'])
        ->assertSee('wa.me/919958067504?text='.rawurlencode($assignments['school_message']), false)
        ->assertSeeInOrder(['id="assignments"', 'id="integrity"', 'id="prices"'], false);
});

test('ACA-3 school assignments go through a parent and a teacher guides the child', function () {
    $school = strtolower(SiteContent::sectionFile('academic')['assignments']['school_body']);

    expect($school)->toContain('parents')->toContain('guide')->toContain('your child does the work');
});

test('MKT-1 the live academic page gets assignments back, with the new box', function () {
    config(['features.cms' => true]);
    $migration = assignmentsMigration();

    $content = SiteContent::sectionFile('academic');
    $content['hero']['title'] = $migration::OLD_TITLE;
    $content['hero']['lead'] = $migration::OLD_LEAD;
    $content['keywords'] = ['Editing and proofreading', 'Printing and binding'];
    $content['text']['seo_title'] = $migration::OLD_SEO_TITLE;
    $content['text']['seo_description'] = $migration::OLD_SEO_DESCRIPTION;
    unset($content['assignments'], $content['experts'], $content['testimonials']);
    $academic = SiteSection::create(['key' => 'academic', 'content' => $content]);

    $home = SiteContent::sectionFile('home');
    $home['offerings']['academic']['body'] = $migration::OLD_CARD_BODY;
    $home['offerings']['academic']['points'][0] = $migration::OLD_CARD_POINT;
    $homeRow = SiteSection::create(['key' => 'home', 'content' => $home]);

    $migration->up();

    expect($academic->fresh()->content['keywords'])->toBe([$migration::KEYWORD, 'Editing and proofreading', 'Printing and binding'])
        ->and($homeRow->fresh()->content['offerings']['academic']['points'][0])->toBe($migration::NEW_CARD_POINT);

    $this->get('/academic')
        ->assertSee('>'.$migration::NEW_TITLE.'</h1>', false)
        ->assertSee($migration::NEW_LEAD)
        ->assertSee('<title>'.$migration::NEW_SEO_TITLE, false)
        ->assertSee('id="assignments"', false);

    $this->get('/')->assertSee($migration::NEW_CARD_BODY);
});

test('MKT-1 text the admin has since changed is left alone', function () {
    $migration = assignmentsMigration();

    $section = SiteSection::create(['key' => 'academic', 'content' => [
        'hero' => ['title' => 'Our own headline', 'lead' => 'Our own lead'],
        'keywords' => ['Assignment guidance', 'Editing'],
        'text' => ['seo_title' => 'Our own title'],
    ]]);

    $migration->up();

    expect($section->fresh()->content['hero'])->toBe(['title' => 'Our own headline', 'lead' => 'Our own lead'])
        ->and($section->fresh()->content['keywords'])->toBe(['Assignment guidance', 'Editing'])
        ->and($section->fresh()->content['text']['seo_title'])->toBe('Our own title');
});

test('VRT-1 sections are listed Academic services, then Tuition, then Music', function () {
    config(['verticals.music.enabled' => true]);

    $this->get('/')->assertOk()
        ->assertSeeInOrder(['>Academic services</a>', '>Tuition</a>', '>Music lessons</a>'], false)
        ->assertSeeInOrder(['For college students, 18+', 'For parents', 'For children and adults']);
});
