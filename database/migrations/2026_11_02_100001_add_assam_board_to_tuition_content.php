<?php

use App\Support\SiteContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MKT-1: the tuition page now also offers the Assam state board (ASSEB,
 * formerly SEBA and AHSEC). The live page reads its text from the database,
 * so this copies the new boards list, board FAQ answer and search title and
 * description from resources/content/tuition.php. Each one is replaced only
 * while it still holds the original text, so admin edits are never lost.
 */
return new class extends Migration
{
    public const OLD_BOARDS = ['CBSE', 'ICSE'];

    public const BOARDS_QUESTION = 'Which boards do you teach?';

    public const OLD_BOARDS_ANSWER = 'We currently teach CBSE and ICSE syllabi. Ask us on WhatsApp if your child follows a state board.';

    public const OLD_TEXT = [
        'seo_title' => 'Live online tuition for classes 1 to 12',
        'seo_description' => 'Live online tuition for classes 1 to 12 in small batches of up to 15. CBSE and ICSE syllabus, monthly fees per subject, free demo class.',
    ];

    public function up(): void
    {
        $row = DB::table('site_sections')->where('key', 'tuition')->first();
        if ($row === null) {
            return;
        }

        $content = json_decode((string) $row->content, true);
        if (! is_array($content)) {
            return;
        }

        $file = SiteContent::sectionFile('tuition');

        if (($content['boards'] ?? null) === self::OLD_BOARDS) {
            $content['boards'] = $file['boards'];
        }

        $newAnswer = null;
        foreach ($file['faqs'] as $faq) {
            if ($faq['q'] === self::BOARDS_QUESTION) {
                $newAnswer = $faq['a'];
            }
        }

        foreach ($content['faqs'] ?? [] as $i => $faq) {
            if (($faq['q'] ?? null) === self::BOARDS_QUESTION && ($faq['a'] ?? null) === self::OLD_BOARDS_ANSWER && $newAnswer !== null) {
                $content['faqs'][$i]['a'] = $newAnswer;
            }
        }

        foreach (self::OLD_TEXT as $key => $old) {
            if (($content['text'][$key] ?? null) === $old) {
                $content['text'][$key] = $file['text'][$key];
            }
        }

        DB::table('site_sections')->where('key', 'tuition')->update([
            'content' => json_encode($content),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Admins can change the boards in /admin; nothing to restore.
    }
};
