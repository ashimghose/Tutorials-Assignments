<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * M8 (ADM-1 to ADM-4): payout rates, money received, costs and teacher
     * payouts. Money is whole paise.
     */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            // ADM-1: per class taken (or per hour), and per academic order (fixed or % of the order).
            $table->unsignedInteger('class_rate')->nullable();
            $table->string('class_rate_per', 8)->default('class');
            $table->string('order_rate_type', 8)->nullable();
            $table->unsignedInteger('order_rate')->nullable();
        });

        Schema::table('order_statuses', function (Blueprint $table) {
            // A final status that means "cancelled": no payout and not counted as completed.
            $table->boolean('is_cancelled')->default(false);
        });
        DB::table('order_statuses')->where('name', 'Cancelled')->update(['is_cancelled' => true]);

        // Money received: recorded by the admin for now, by the verified gateway later (PAY-2).
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('offering', 16);
            $table->foreignId('enrolment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('gateway_fee')->default(0);
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->date('received_on')->index();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Printing, courier and other costs.
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('offering', 16);
            $table->string('category', 20);
            $table->foreignId('academic_order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount');
            $table->date('spent_on')->index();
            $table->string('note', 255)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ADM-2: a month's payout to one teacher or expert, as calculated when it was paid.
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->date('month');
            $table->unsignedInteger('classes')->default(0);
            $table->unsignedInteger('class_amount')->default(0);
            $table->unsignedInteger('orders')->default(0);
            $table->unsignedInteger('order_amount')->default(0);
            $table->unsignedInteger('total');
            $table->date('paid_on')->index();
            $table->string('reference', 100)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['teacher_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payments');
        Schema::table('order_statuses', function (Blueprint $table) {
            $table->dropColumn('is_cancelled');
        });
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['class_rate', 'class_rate_per', 'order_rate_type', 'order_rate']);
        });
    }
};
