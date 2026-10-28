<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * M6 (ACA-1 to ACA-9): the academic price list, add-ons, admin-managed
     * order statuses, orders with their add-ons, status history and files.
     * Money is whole paise.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('type', 60);
            $table->text('description')->nullable();
            $table->json('includes')->nullable();
            // One price per level; empty means "quoted".
            $table->unsignedInteger('price_bachelors')->nullable();
            $table->unsignedInteger('price_masters')->nullable();
            $table->unsignedInteger('price_phd')->nullable();
            $table->string('turnaround', 60)->nullable();
            $table->unsignedTinyInteger('free_revisions')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('service_addons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('kind', 16);
            $table->string('unit', 16);
            $table->unsignedInteger('price');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('service_service_addon', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_addon_id')->constrained()->cascadeOnDelete();
            $table->primary(['service_id', 'service_addon_id']);
        });

        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('color', 16)->default('gray');
            $table->boolean('notify_student')->default(false);
            $table->boolean('is_final')->default(false);
            $table->boolean('expert_can_set')->default(false);
            $table->boolean('is_revision')->default(false);
            $table->boolean('needs_tracking')->default(false);
            $table->timestamps();
        });

        Schema::create('academic_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service_title', 120);
            $table->string('level', 16);
            $table->string('university', 160);
            $table->string('course', 120);
            $table->string('stream', 120)->nullable();
            $table->string('topic', 255);
            $table->date('deadline');
            $table->unsignedInteger('pages')->nullable();
            $table->unsignedInteger('words')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_quote')->default(false);
            $table->unsignedInteger('quote_amount')->nullable();
            $table->date('quote_expires_at')->nullable();
            $table->string('quote_note', 255)->nullable();
            // The server's price when ordered (Services\Pricing); empty until quoted.
            $table->unsignedInteger('subtotal')->nullable();
            $table->unsignedInteger('gst')->nullable();
            $table->unsignedInteger('total')->nullable();
            $table->unsignedTinyInteger('gst_percent')->default(0);
            $table->foreignId('order_status_id')->constrained()->restrictOnDelete();
            $table->foreignId('expert_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->unsignedTinyInteger('revisions_allowed')->default(0);
            $table->unsignedTinyInteger('revisions_used')->default(0);
            $table->string('delivery_name', 120)->nullable();
            $table->string('delivery_phone', 15)->nullable();
            $table->string('delivery_address', 255)->nullable();
            $table->string('delivery_city', 80)->nullable();
            $table->string('delivery_state', 80)->nullable();
            $table->string('delivery_pincode', 6)->nullable();
            $table->string('courier_name', 80)->nullable();
            $table->string('tracking_number', 80)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->timestamps();

            $table->index(['order_status_id', 'expert_id']);
        });

        Schema::create('academic_order_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_addon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 80);
            $table->string('kind', 16);
            $table->unsignedInteger('unit_price');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('line_total');
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_status_id')->nullable()->constrained('order_statuses')->nullOnDelete();
            $table->foreignId('to_status_id')->constrained('order_statuses')->restrictOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at');
        });

        Schema::create('order_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 16);
            $table->string('disk', 20);
            $table->string('path');
            $table->string('original_name', 255);
            $table->string('mime', 120);
            $table->unsignedInteger('size');
            $table->timestamp('delete_after')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_files');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('academic_order_addons');
        Schema::dropIfExists('academic_orders');
        Schema::dropIfExists('order_statuses');
        Schema::dropIfExists('service_service_addon');
        Schema::dropIfExists('service_addons');
        Schema::dropIfExists('services');
    }
};
