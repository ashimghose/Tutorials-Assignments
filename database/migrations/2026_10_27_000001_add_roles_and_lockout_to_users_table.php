<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AUTH-3 to AUTH-6: roles, usernames for school students (who may have
     * no email), invited teachers without a password yet, and lockout.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->string('username', 30)->nullable()->unique()->after('email');
            $table->string('role', 32)->nullable()->index()->after('name');
            $table->string('status', 16)->default('active')->after('role');
            $table->unsignedTinyInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropIndex(['role']);
            $table->dropColumn(['username', 'role', 'status', 'failed_login_attempts', 'locked_until', 'last_login_at']);
        });
    }
};
