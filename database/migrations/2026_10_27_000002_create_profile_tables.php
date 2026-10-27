<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One profile table per kind of user (spec "Core data model").
     */
    public function up(): void
    {
        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('mobile', 15);
            $table->timestamps();
        });

        // Minimum child data only (DPDP): first name, class, board, optional school.
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->string('first_name', 60);
            $table->unsignedTinyInteger('class_level');
            $table->string('board', 40);
            $table->string('school', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('academic_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('mobile', 15);
            $table->string('university', 160)->nullable();
            $table->string('course', 120)->nullable();
            $table->string('level', 20)->nullable();
            $table->string('stream', 120)->nullable();
            $table->timestamp('adult_declared_at');
            $table->timestamps();
        });

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('offering', 16);
            $table->json('subjects')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->date('active_from')->nullable();
            $table->date('active_to')->nullable();
            $table->timestamps();
        });

        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('purpose', 32);
            $table->string('policy_version', 40);
            $table->timestamp('given_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
        });

        // ADM-6: who did what in the admin panel.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 60);
            $table->nullableMorphs('subject');
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('consents');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('academic_students');
        Schema::dropIfExists('students');
        Schema::dropIfExists('parents');
    }
};
