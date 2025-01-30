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
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_name');
            $table->string('event_category');
            $table->string('event_source'); // web, mobile, api, system
            $table->morphs('subject'); // Polymorphic relation to the subject of the event
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->json('properties')->nullable(); // Additional event properties
            $table->json('metadata')->nullable(); // Technical metadata
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_type')->nullable();
            $table->string('platform')->nullable();
            $table->string('browser')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->decimal('value', 10, 2)->nullable(); // Monetary or numeric value associated with the event
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index('event_name');
            $table->index('event_category');
            $table->index('event_source');
            $table->index('session_id');
            $table->index('occurred_at');
        });

        // Create event aggregations table for pre-calculated metrics
        Schema::create('analytics_event_aggregations', function (Blueprint $table) {
            $table->id();
            $table->string('event_name', 100);
            $table->string('event_category', 50);
            $table->string('aggregation_type', 20); // count, sum, avg, etc.
            $table->string('time_period', 20); // hour, day, week, month
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->decimal('value', 15, 2);
            $table->json('dimensions')->nullable(); // Additional grouping dimensions
            $table->timestamps();

            // Using shorter column lengths and a shorter index name
            $table->unique([
                'event_name',
                'event_category',
                'aggregation_type',
                'time_period',
                'period_start'
            ], 'event_agg_unique');
            
            $table->index('period_start');
            $table->index('period_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_event_aggregations');
        Schema::dropIfExists('analytics_events');
    }
}; 