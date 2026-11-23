<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MUS-1, MKT-1: the guitar group fee is for one class a week, and a second
 * fee card offers one-to-one classes by quote. On the live music page the
 * Fees heading, intro and group plan wording change only while they still
 * hold the text we shipped, so admin edits are kept; the one-to-one card is
 * added once.
 */
return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string}> path => [old, new] */
    public const TEXT = [
        'fees_title' => ['One simple monthly fee', 'Group or one-to-one classes'],
        'fees_lead' => [
            'One fee per learner, per month. No admission fee, and the first class is a free trial.',
            'Group classes have one simple monthly fee. For one-to-one classes, tell us how many classes a week you want and we send you a quote. No admission fee.',
        ],
    ];

    public const GROUP_PLAN = 'Guitar group classes';

    public const GROUP_PERIOD = ['per learner, per month', 'per learner, per month, for one class a week'];

    public const GROUP_FEATURES = [
        ['Live online group classes', 'Beginners welcome', 'Practice tips after every class'],
        ['One live online group class every week', 'Beginners welcome', 'Practice tips after every class'],
    ];

    public const QUOTE_PLAN = [
        'name' => 'Dedicated (one-to-one) class',
        'price' => null,
        'period' => 'depends on how many classes a week you choose',
        'subjects' => ['Acoustic guitar'],
        'features' => ['Live online class with the teacher, just for you', 'One or more classes a week', 'Lesson times that suit you'],
    ];

    public function up(): void
    {
        $row = DB::table('site_sections')->where('key', 'music')->first();
        $content = $row === null ? null : json_decode((string) $row->content, true);
        if (! is_array($content)) {
            return;
        }

        foreach (self::TEXT as $key => [$old, $new]) {
            if (self::same($content['text'][$key] ?? null, $old)) {
                $content['text'][$key] = $new;
            }
        }

        $plans = is_array($content['plans'] ?? null) ? array_values($content['plans']) : [];
        foreach ($plans as $i => $plan) {
            if (! is_array($plan) || ! self::same($plan['name'] ?? null, self::GROUP_PLAN)) {
                continue;
            }
            if (self::same($plan['period'] ?? null, self::GROUP_PERIOD[0])) {
                $plans[$i]['period'] = self::GROUP_PERIOD[1];
            }
            if (($plan['features'] ?? null) === self::GROUP_FEATURES[0]) {
                $plans[$i]['features'] = self::GROUP_FEATURES[1];
            }
        }

        $names = array_map(fn (mixed $plan) => is_array($plan) ? ($plan['name'] ?? null) : null, $plans);
        if (! in_array(self::QUOTE_PLAN['name'], $names, true)) {
            $plans[] = self::QUOTE_PLAN;
        }
        $content['plans'] = $plans;

        DB::table('site_sections')->where('key', 'music')->update([
            'content' => json_encode($content),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // The fees can be changed in /admin; nothing to restore.
    }

    private static function same(mixed $current, string $text): bool
    {
        return is_string($current) && html_entity_decode($current, ENT_QUOTES | ENT_HTML5, 'UTF-8') === $text;
    }
};
