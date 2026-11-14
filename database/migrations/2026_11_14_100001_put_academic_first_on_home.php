<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MKT-1, VRT-1: the owner wants Google to show Academic first, then Tuition,
 * then Music, for the home page. The live home page title, description,
 * headline, intro and footer line are updated, each only while it still
 * holds the text we shipped (saved escaped or not), so admin edits are kept.
 */
return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string}> path => [old, new] */
    public const CHANGES = [
        'hero.title' => [
            'Learning support for school and beyond',
            'Guidance for your own assignments and research, school to PhD',
        ],
        'hero.lead' => [
            'Live online classes for school children, and expert help for college students to make their own research work clearer, correctly formatted and ready to submit.',
            'We help you make your own assignments, synopsis, thesis or dissertation clearer, correctly formatted and ready to submit. We also run live online tuition for classes 1 to 12, and guitar classes.',
        ],
        'text.seo_title' => [
            'Online tuition for classes 1 to 12, and thesis and dissertation help',
            'Assignment and thesis guidance, plus tuition and music',
        ],
        'text.seo_description' => [
            'Live online tuition for classes 1 to 12 in small batches, and guidance, editing, formatting and printing for Bachelors, Masters and PhD students\' research work.',
            'Guidance, editing and formatting for your own assignments, theses and dissertations, school to PhD. Plus live tuition for classes 1 to 12 and guitar lessons.',
        ],
        'text.footer' => [
            'Live online tuition for classes 1 to 12, and academic guidance, editing and printing for college students.',
            'Academic guidance, editing and printing for your own work, school to PhD, plus live online tuition for classes 1 to 12 and music lessons.',
        ],
    ];

    public function up(): void
    {
        $row = DB::table('site_sections')->where('key', 'home')->first();
        $content = $row === null ? null : json_decode((string) $row->content, true);
        if (! is_array($content)) {
            return;
        }

        foreach (self::CHANGES as $path => [$old, $new]) {
            $current = data_get($content, $path);
            if (is_string($current) && html_entity_decode($current, ENT_QUOTES | ENT_HTML5, 'UTF-8') === $old) {
                data_set($content, $path, $new);
            }
        }

        DB::table('site_sections')->where('key', 'home')->update([
            'content' => json_encode($content),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // The text can be changed in /admin; nothing to restore.
    }
};
