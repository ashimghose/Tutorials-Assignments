<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * HIR-1 to HIR-4: job openings on the Careers pages, edited at /admin,
     * and the applications people send for them.
     */
    public function up(): void
    {
        Schema::create('job_openings', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('offering', 16);
            $table->string('title');
            $table->string('summary', 300);
            $table->text('body');
            $table->string('location', 160);
            $table->string('region', 80)->default('India');
            $table->string('languages', 160);
            $table->string('pay', 300);
            $table->unsignedSmallInteger('openings')->default(1);
            $table->boolean('is_open')->default(true);
            $table->date('posted_on');
            $table->date('closes_on')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_opening_id')->nullable()->constrained()->nullOnDelete();
            $table->string('job_title');
            $table->string('offering', 16);
            $table->string('name', 120);
            $table->string('mobile', 20);
            $table->string('email');
            $table->string('city', 120);
            $table->json('languages');
            $table->string('subjects', 300);
            $table->string('qualifications', 500);
            $table->text('experience');
            $table->string('availability', 200);
            $table->string('link', 500)->nullable();
            $table->text('message')->nullable();
            $table->timestamp('declared_at');
            $table->string('utm_source', 100)->nullable()->index();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('status', 16)->default('new')->index();
            $table->timestamps();
            $table->index(['offering', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_openings');
    }
};
