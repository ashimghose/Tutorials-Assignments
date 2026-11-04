<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MKT-1: the owner asked for assignments (school to PhD) to be a main topic
 * on the academic page again, after the 2026-11-03 short headline dropped
 * the word. The new Assignments box comes from resources/content/academic.php
 * on its own (SiteContent::withNewKeys). Here the live headline, badges,
 * intro, search text and home page card are updated, each only while it
 * still holds the text we shipped, so later admin edits are never lost.
 */
return new class extends Migration
{
    public const OLD_TITLE = 'Thesis, dissertation and research paper support';

    public const NEW_TITLE = 'Assignments, thesis, dissertation and research paper support';

    public const KEYWORD = 'Assignments: school to PhD';

    public const OLD_LEAD = 'For Bachelors, Masters and PhD students in any stream. We help you structure, polish, format and check your own work, then print, bind and deliver it.';

    public const NEW_LEAD = 'For Bachelors, Masters and PhD students in any stream, from term assignments to the final thesis. We help you structure, polish, format and check your own work, then print, bind and deliver it.';

    public const OLD_SEO_TITLE = 'Thesis, dissertation and research paper editing, Turnitin plagiarism and AI check, formatting and printing';

    public const NEW_SEO_TITLE = 'Assignment, thesis, dissertation and research paper editing, Turnitin plagiarism and AI check, formatting and printing';

    public const OLD_SEO_DESCRIPTION = 'Guidance, editing, proofreading, formatting, similarity checks, printing and delivery for Bachelors, Masters and PhD students\' synopses, research papers, theses and dissertations.';

    public const NEW_SEO_DESCRIPTION = 'Guidance, editing, proofreading, formatting, similarity checks, printing and delivery for Bachelors, Masters and PhD students\' assignments, synopses, research papers, theses and dissertations.';

    public const OLD_CARD_BODY = 'Guidance, editing, formatting and similarity checks for your synopsis, research paper, thesis or dissertation, plus printing, binding and delivery.';

    public const NEW_CARD_BODY = 'Guidance, editing, formatting and similarity checks for your assignments, synopsis, research paper, thesis or dissertation, plus printing, binding and delivery.';

    public const OLD_CARD_POINT = 'Bachelors, Masters and PhD, any stream';

    public const NEW_CARD_POINT = 'Assignments from school to PhD, any stream';

    public function up(): void
    {
        $this->change('academic', function (array $content): array {
            if (($content['hero']['title'] ?? null) === self::OLD_TITLE) {
                $content['hero']['title'] = self::NEW_TITLE;
            }

            if (($content['hero']['lead'] ?? null) === self::OLD_LEAD) {
                $content['hero']['lead'] = self::NEW_LEAD;
            }

            // Add the badge first, unless the admin already has one about assignments.
            if (is_array($content['keywords'] ?? null)) {
                $mentioned = collect($content['keywords'])->contains(fn (mixed $keyword) => is_string($keyword) && str_contains(strtolower($keyword), 'assignment'));
                if (! $mentioned) {
                    $content['keywords'] = [self::KEYWORD, ...array_values($content['keywords'])];
                }
            }

            if (($content['text']['seo_title'] ?? null) === self::OLD_SEO_TITLE) {
                $content['text']['seo_title'] = self::NEW_SEO_TITLE;
            }

            if (($content['text']['seo_description'] ?? null) === self::OLD_SEO_DESCRIPTION) {
                $content['text']['seo_description'] = self::NEW_SEO_DESCRIPTION;
            }

            return $content;
        });

        $this->change('home', function (array $content): array {
            if (($content['offerings']['academic']['body'] ?? null) === self::OLD_CARD_BODY) {
                $content['offerings']['academic']['body'] = self::NEW_CARD_BODY;
            }

            $points = $content['offerings']['academic']['points'] ?? null;
            if (is_array($points) && ($index = array_search(self::OLD_CARD_POINT, $points, true)) !== false) {
                $content['offerings']['academic']['points'][$index] = self::NEW_CARD_POINT;
            }

            return $content;
        });
    }

    public function down(): void
    {
        // The text can be changed in /admin; nothing to restore.
    }

    /**
     * @param  Closure(array<string, mixed>): array<string, mixed>  $change
     */
    private function change(string $key, Closure $change): void
    {
        $row = DB::table('site_sections')->where('key', $key)->first();
        $content = $row === null ? null : json_decode((string) $row->content, true);
        if (! is_array($content)) {
            return;
        }

        DB::table('site_sections')->where('key', $key)->update([
            'content' => json_encode($change($content)),
            'updated_at' => now(),
        ]);
    }
};
