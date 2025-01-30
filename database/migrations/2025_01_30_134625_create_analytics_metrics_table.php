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
        Schema::create('analytics_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->text('description')->nullable();
            $table->string('category'); // sales, inventory, customers, marketing, etc.
            $table->string('data_type'); // number, currency, percentage, duration
            $table->string('aggregation_type'); // sum, avg, count, min, max, custom
            $table->json('calculation_logic'); // SQL or calculation rules
            $table->json('dimensions')->nullable(); // Available dimensions for this metric
            $table->json('filters')->nullable(); // Default filters
            $table->json('formatting')->nullable(); // Display formatting options
            $table->json('thresholds')->nullable(); // Alert thresholds
            $table->boolean('is_realtime')->default(false);
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('permissions')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category');
            $table->index('is_active');
        });

        Schema::create('analytics_metric_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metric_id')->constrained('analytics_metrics')->onDelete('cascade');
            $table->string('dimension_key', 100)->nullable();
            $table->string('dimension_value', 100)->nullable();
            $table->decimal('value', 15, 4);
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->string('time_granularity', 20); // hour, day, week, month
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Using a shorter name for the composite index
            $table->index(['metric_id', 'dimension_key', 'dimension_value', 'period_start'], 'metric_values_composite_idx');
            $table->index('period_start');
            $table->index('period_end');
        });

        Schema::create('analytics_metric_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metric_id')->constrained('analytics_metrics')->onDelete('cascade');
            $table->string('alert_type'); // threshold, anomaly, trend
            $table->json('conditions');
            $table->string('severity'); // info, warning, critical
            $table->json('notification_channels'); // email, slack, webhook
            $table->json('recipients');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->index('alert_type');
            $table->index('severity');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_metric_alerts');
        Schema::dropIfExists('analytics_metric_values');
        Schema::dropIfExists('analytics_metrics');
    }
}; 