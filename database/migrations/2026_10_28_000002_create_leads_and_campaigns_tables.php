<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * M4 (CMS-3, MKT-2 to MKT-5): campaign landing pages and the leads from
     * site forms, each with the campaign it came from.
     */
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('offering', 16);
            $table->string('title');
            $table->string('headline');
            $table->string('offer')->nullable();
            $table->text('body')->nullable();
            $table->string('interest', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 16);
            $table->string('offering', 16);
            $table->string('name', 120);
            $table->string('mobile', 15);
            $table->string('email')->nullable();
            $table->string('interest', 160)->nullable();
            $table->string('class_or_service', 160)->nullable();
            $table->text('message')->nullable();
            $table->string('price_range', 60)->nullable();
            $table->string('utm_source', 100)->nullable()->index();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('page', 120)->nullable();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('marketing_consent')->default(false);
            $table->string('status', 16)->default('new')->index();
            $table->timestamp('became_customer_at')->nullable();
            $table->timestamps();
            $table->index(['offering', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
        Schema::dropIfExists('campaigns');
    }
};
