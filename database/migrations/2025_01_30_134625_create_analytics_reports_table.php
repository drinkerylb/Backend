<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->string('report_type'); // standard, custom, scheduled
            $table->json('data_source'); // Data source configuration
            $table->json('metrics'); // Array of metrics to include
            $table->json('dimensions')->nullable(); // Grouping dimensions
            $table->json('filters')->nullable(); // Report filters
            $table->json('sorting')->nullable(); // Sorting configuration
            $table->json('visualization')->nullable(); // Default visualization settings
            $table->json('custom_calculations')->nullable(); // Custom metric calculations
            $table->boolean('is_public')->default(false);
            $table->boolean('cache_enabled')->default(true);
            $table->integer('cache_duration')->nullable(); // Cache duration in minutes
            $table->json('schedule')->nullable(); // Schedule configuration for automated reports
            $table->json('export_settings')->nullable(); // Export configuration
            $table->json('permissions')->nullable(); // Access permissions
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('report_type');
            $table->index('is_public');
        });

        Schema::create('analytics_report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('analytics_reports')->onDelete('cascade');
            $table->string('frequency'); // daily, weekly, monthly, custom
            $table->json('schedule_config'); // Detailed schedule configuration
            $table->json('recipients'); // Email recipients
            $table->string('format'); // pdf, excel, csv
            $table->json('delivery_settings'); // Delivery configuration
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_scheduled_at')->nullable();
            $table->timestamps();

            $table->index('frequency');
            $table->index('is_active');
            $table->index('next_scheduled_at');
        });

        Schema::create('analytics_report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('analytics_reports')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->string('format'); // pdf, excel, csv
            $table->string('status'); // pending, processing, completed, failed
            $table->string('file_path')->nullable();
            $table->json('export_settings');
            $table->json('filters_applied')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_report_exports');
        Schema::dropIfExists('analytics_report_schedules');
        Schema::dropIfExists('analytics_reports');
    }
};
