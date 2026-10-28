<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * M5 (CLS-1, CLS-2, PAY-4): tuition batches, their scheduled classes and
     * each child's enrolment. Classes are "class_sessions" because Laravel's
     * login sessions already use "sessions".
     */
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 80);
            $table->unsignedTinyInteger('class_level');
            $table->string('board', 40);
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            // Weekly slots in India time: [{day: 1-7 (ISO, Monday = 1), start: "17:00", minutes: 60}].
            $table->json('schedule');
            $table->unsignedTinyInteger('max_seats')->default(15);
            $table->unsignedInteger('monthly_fee');
            $table->boolean('record_by_default')->default(false);
            $table->string('meet_link')->nullable();
            $table->date('starts_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            // The weekly slot this class came from; stays the same when the class is moved,
            // so the schedule doesn't add the slot again.
            $table->dateTime('scheduled_for')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 16)->default('scheduled');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->boolean('recording_on')->default(false);
            $table->string('meet_link')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'scheduled_for']);
            $table->index('starts_at');
        });

        Schema::create('enrolments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16)->default('pending');
            $table->date('paid_through')->nullable();
            $table->string('removed_reason')->nullable();
            $table->dateTime('removed_at')->nullable();
            $table->date('last_reminder_on')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'batch_id']);
            $table->index(['status', 'paid_through']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrolments');
        Schema::dropIfExists('class_sessions');
        Schema::dropIfExists('batches');
    }
};
