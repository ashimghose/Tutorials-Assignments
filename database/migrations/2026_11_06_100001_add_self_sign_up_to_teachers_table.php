<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HIR-8: teachers and experts can sign up themselves. Their account waits
 * for the admin's approval (approved_at); everyone already here was invited
 * by the admin, so they count as approved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('mobile', 20)->nullable();
            $table->timestamp('approved_at')->nullable();
        });

        DB::table('teachers')->update(['approved_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['mobile', 'approved_at']);
        });
    }
};
