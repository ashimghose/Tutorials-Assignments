<?php

use App\Support\SiteContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MUS: the music page was first stored with the example content (vocal,
 * keyboard, tabla, ₹1,500 and ₹3,500). The content seeder never overwrites a
 * page, so the guitar content (₹1,000 group classes) never reached the live
 * site. This swaps in the guitar content once, and only while the page still
 * has the untouched example hero title, so admin edits are never lost.
 */
return new class extends Migration
{
    public const PLACEHOLDER_TITLE = 'Live online music lessons: singing and instruments';

    public function up(): void
    {
        $row = DB::table('site_sections')->where('key', 'music')->first();
        if ($row === null) {
            return;
        }

        $content = json_decode((string) $row->content, true);
        if (! is_array($content) || ($content['hero']['title'] ?? null) !== self::PLACEHOLDER_TITLE) {
            return;
        }

        $fresh = SiteContent::sectionFile('music');
        unset($fresh['teachers'], $fresh['experts'], $fresh['testimonials']);

        DB::table('site_sections')->where('key', 'music')->update([
            'content' => json_encode($fresh),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // The example content is not worth restoring.
    }
};
