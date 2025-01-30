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
        Schema::create('analytics_dashboards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->boolean('is_public')->default(false);
            $table->boolean('is_default')->default(false);
            $table->json('layout_config'); // Grid layout configuration
            $table->json('filters')->nullable(); // Default dashboard filters
            $table->json('time_range')->nullable(); // Default time range
            $table->json('permissions')->nullable(); // Access permissions
            $table->json('custom_styles')->nullable();
            $table->integer('refresh_interval')->nullable(); // Auto-refresh interval in seconds
            $table->timestamp('last_refreshed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_public');
            $table->index('is_default');
        });

        Schema::create('analytics_dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('analytics_dashboards')->onDelete('cascade');
            $table->foreignId('report_id')->nullable()->constrained('analytics_reports')->nullOnDelete();
            $table->string('widget_type'); // chart, metric, table, custom
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('config'); // Widget-specific configuration
            $table->json('data_source'); // Data source configuration
            $table->json('visualization'); // Visualization settings
            $table->json('filters')->nullable(); // Widget-specific filters
            $table->json('position'); // Grid position and size
            $table->boolean('is_realtime')->default(false);
            $table->integer('refresh_interval')->nullable();
            $table->json('custom_styles')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('widget_type');
            $table->index('sort_order');
        });

        Schema::create('analytics_dashboard_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('analytics_dashboards')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('permission_level'); // view, edit, manage
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['dashboard_id', 'user_id'], 'dashboard_share_unique');
            $table->index('permission_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_dashboard_shares');
        Schema::dropIfExists('analytics_dashboard_widgets');
        Schema::dropIfExists('analytics_dashboards');
    }
}; 