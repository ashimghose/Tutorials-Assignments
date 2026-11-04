<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MUS-2, MUS-3: music learners sign up themselves (no parent account) and
     * join music batches. A learner is a `students` row without a parent; the
     * batch and enrolment tables are shared with tuition, tagged by offering.
     * Under-18 learners need a parent's approval by email before using it.
     */
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->string('offering', 16)->default('tuition')->after('id')->index();
            $table->unsignedTinyInteger('class_level')->nullable()->change();
            $table->string('board', 40)->nullable()->change();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->change();
            $table->unsignedTinyInteger('class_level')->nullable()->change();
            $table->string('board', 40)->nullable()->change();
            $table->string('mobile', 15)->nullable()->after('school');
            $table->timestamp('adult_declared_at')->nullable()->after('mobile');
            $table->string('guardian_email')->nullable()->after('adult_declared_at');
            $table->timestamp('guardian_consented_at')->nullable()->after('guardian_email');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['mobile', 'adult_declared_at', 'guardian_email', 'guardian_consented_at']);
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->dropIndex(['offering']);
            $table->dropColumn('offering');
        });
    }
};
