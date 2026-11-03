<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MKT-1: the academic headline typed in the admin on 2026-11-03 listed every
 * service in one long sentence and pushed the buttons off the screen. The
 * owner chose a short headline with the services as highlighted badges
 * (content.keywords, added from resources/content/academic.php), worded so it
 * never offers to write the student's work (ACA). The headline and search
 * title are replaced only while they still hold the old text, so later admin
 * edits are never lost.
 */
return new class extends Migration
{
    public const OLD_TITLE_START = 'Content of assignments';

    public const NEW_TITLE = 'Thesis, dissertation and research paper support';

    public const OLD_SEO_TITLE = 'Thesis, dissertation and research paper editing, formatting and printing';

    public const NEW_SEO_TITLE = 'Thesis, dissertation and research paper editing, Turnitin plagiarism and AI check, formatting and printing';

    public function up(): void
    {
        $row = DB::table('site_sections')->where('key', 'academic')->first();
        if ($row === null) {
            return;
        }

        $content = json_decode((string) $row->content, true);
        if (! is_array($content)) {
            return;
        }

        if (str_starts_with((string) ($content['hero']['title'] ?? ''), self::OLD_TITLE_START)) {
            $content['hero']['title'] = self::NEW_TITLE;
        }

        if (($content['text']['seo_title'] ?? null) === self::OLD_SEO_TITLE) {
            $content['text']['seo_title'] = self::NEW_SEO_TITLE;
        }

        DB::table('site_sections')->where('key', 'academic')->update([
            'content' => json_encode($content),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // The headline can be changed in /admin; nothing to restore.
    }
};
