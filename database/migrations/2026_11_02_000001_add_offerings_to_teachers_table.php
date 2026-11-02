<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * VRT-1: a teacher can work in any mix of sections (tuition, academic,
 * music), so the single "offering" (tuition, academic or both) becomes a
 * list. Existing teachers keep what they had: "both" becomes tuition and
 * academic.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->json('offerings')->nullable()->after('user_id');
        });

        DB::table('teachers')->orderBy('id')->each(function (object $teacher) {
            $offerings = $teacher->offering === 'both' ? ['tuition', 'academic'] : [$teacher->offering];
            DB::table('teachers')->where('id', $teacher->id)->update(['offerings' => json_encode($offerings)]);
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('offering');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('offering', 16)->default('tuition')->after('user_id');
        });

        DB::table('teachers')->orderBy('id')->each(function (object $teacher) {
            $offerings = json_decode((string) $teacher->offerings, true) ?: ['tuition'];
            $offering = count(array_intersect($offerings, ['tuition', 'academic'])) === 2 ? 'both' : $offerings[0];
            DB::table('teachers')->where('id', $teacher->id)->update(['offering' => $offering === 'music' ? 'tuition' : $offering]);
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('offerings');
        });
    }
};
