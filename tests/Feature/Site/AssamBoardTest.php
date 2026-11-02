<?php

use App\Models\SiteSection;
use App\Support\SiteContent;

function assamBoardMigration(): object
{
    return require database_path('migrations/2026_11_02_100001_add_assam_board_to_tuition_content.php');
}

test('MKT-1 the tuition page offers the Assam board and names it for Google', function () {
    $this->get('/tuition')
        ->assertOk()
        ->assertSee('Boards: CBSE, ICSE, Assam Board (ASSEB)')
        ->assertSee('formerly SEBA and AHSEC')
        ->assertSee('<title>Online tuition for classes 1 to 12: CBSE, ICSE, Assam Board | '.config('app.name').'</title>', false)
        ->assertSee('CBSE, ICSE and Assam Board (SEBA/AHSEC) syllabus', false);
});

test('MKT-1 the Assam board reaches a live page that still has the original text', function () {
    config(['features.cms' => true]);
    $migration = assamBoardMigration();

    $content = SiteContent::sectionFile('tuition');
    $content['boards'] = $migration::OLD_BOARDS;
    $content['text']['seo_title'] = $migration::OLD_TEXT['seo_title'];
    $content['text']['seo_description'] = $migration::OLD_TEXT['seo_description'];
    foreach ($content['faqs'] as $i => $faq) {
        if ($faq['q'] === $migration::BOARDS_QUESTION) {
            $content['faqs'][$i]['a'] = $migration::OLD_BOARDS_ANSWER;
        }
    }
    unset($content['teachers'], $content['testimonials']);
    SiteSection::create(['key' => 'tuition', 'content' => $content]);

    $migration->up();

    $this->get('/tuition')
        ->assertSee('Boards: CBSE, ICSE, Assam Board (ASSEB)')
        ->assertSee('formerly SEBA and AHSEC')
        ->assertDontSee($migration::OLD_BOARDS_ANSWER)
        ->assertSee('<title>Online tuition for classes 1 to 12: CBSE, ICSE, Assam Board', false);
});

test('MKT-1 boards and text the admin has already changed are left alone', function () {
    $migration = assamBoardMigration();

    $section = SiteSection::create(['key' => 'tuition', 'content' => [
        'boards' => ['CBSE', 'State board'],
        'faqs' => [['q' => $migration::BOARDS_QUESTION, 'a' => 'Our own answer']],
        'text' => ['seo_title' => 'Our own title', 'seo_description' => 'Our own description'],
    ]]);

    $migration->up();

    $content = $section->fresh()->content;
    expect($content['boards'])->toBe(['CBSE', 'State board'])
        ->and($content['faqs'][0]['a'])->toBe('Our own answer')
        ->and($content['text']['seo_title'])->toBe('Our own title')
        ->and($content['text']['seo_description'])->toBe('Our own description');
});
