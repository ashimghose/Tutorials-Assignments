<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * M4 (CMS-1, MKT-6): site content the admin edits without code. Seeded
     * from resources/content by ContentSeeder.
     */
    public function up(): void
    {
        // One row per page section (home, tuition, academic); the content has
        // the same shape as the resources/content/{key}.php array.
        Schema::create('site_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->json('content');
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title');
            $table->string('description', 300);
            $table->string('offering', 16);
            $table->text('body');
            $table->date('published_on');
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('offering', 16);
            $table->text('quote');
            $table->string('name', 80);
            $table->string('detail', 120)->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->boolean('show_on_site')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('show_on_site');
        });
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('site_sections');
    }
};
