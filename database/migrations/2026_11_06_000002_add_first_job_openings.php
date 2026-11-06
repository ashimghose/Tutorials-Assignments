<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * HIR-1: the owner's first hiring round (6 Nov 2026) from
 * resources/content/careers.php. After this the admin edits or closes them
 * at Admin → Hiring → Job openings. Skipped when there are job openings
 * already.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('job_openings')->exists()) {
            return;
        }

        $now = now();

        /** @var list<array<string, string|int>> $jobs */
        $jobs = require resource_path('content/careers.php');

        foreach ($jobs as $index => $job) {
            DB::table('job_openings')->insert([
                ...$job,
                'is_open' => true,
                'posted_on' => $now->toDateString(),
                'sort_order' => $index * 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Job openings are edited in /admin; nothing to undo.
    }
};
